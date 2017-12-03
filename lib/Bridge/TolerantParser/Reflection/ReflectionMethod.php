<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection;

use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\TokenKind;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Docblock;
use Phpactor\WorseReflection\Core\DocblockResolver;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Inference\FrameBuilder;
use Phpactor\WorseReflection\Core\NodeText;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod as CoreReflectionMethod;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Visibility;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionParameterCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionParameterCollection as CoreReflectionParameterCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Types;

class ReflectionMethod extends AbstractReflectionClassMember implements CoreReflectionMethod
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var MethodDeclaration
     */
    private $node;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var DocblockResolver
     */
    private $docblockResolver;

    /**
     * @var FrameBuilder
     */
    private $frameBuilder;

    /**
     * @var AbstractReflectionClass
     */
    private $class;

    public function __construct(
        ServiceLocator $serviceLocator,
        AbstractReflectionClass $class,
        MethodDeclaration $node
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->node = $node;
        $this->class = $class;
    }

    public function name(): string
    {
        return $this->node->getName();
    }

    public function frame(): Frame
    {
        return $this->serviceLocator->frameBuilder()->buildFromNode($this->node);
    }

    public function declaringClass(): ReflectionClassLike
    {
        $class = $this->node->getFirstAncestor(ClassLike::class)->getNamespacedName();

        if (null === $class) {
            throw new \InvalidArgumentException(sprintf(
                'Could not locate class-like ancestor node for method "%s"',
                $this->name()
            ));
        }

        return $this->serviceLocator->reflector()->reflectClassLike(ClassName::fromString($class));
    }

    public function isAbstract(): bool
    {
        foreach ($this->node->modifiers as $token) {
            if ($token->kind === TokenKind::AbstractKeyword) {
                return true;
            }
        }

        return false;
    }

    public function isStatic(): bool
    {
        return $this->node->isStatic();
    }

    public function parameters(): CoreReflectionParameterCollection
    {
        return ReflectionParameterCollection::fromMethodDeclaration($this->serviceLocator, $this->node);
    }

    public function docblock(): Docblock
    {
        return Docblock::fromNode($this->node);
    }

    public function visibility(): Visibility
    {
        foreach ($this->node->modifiers as $token) {
            if ($token->kind === TokenKind::PrivateKeyword) {
                return Visibility::private();
            }

            if ($token->kind === TokenKind::ProtectedKeyword) {
                return Visibility::protected();
            }
        }

        return Visibility::public();
    }

    /**
     * If type not explicitly set, try and infer it from the docblock.
     *
     * @deprecated Use inferredReturnTypes()
     */
    public function inferredReturnType(): Type
    {
        foreach ($this->inferredReturnTypes() as $type) {
            return $type;
        }

        return Type::unknown();
    }

    public function inferredReturnTypes(): Types
    {
        if ($this->returnType()->isDefined()) {
            return Types::fromInferredTypes([ $this->returnType() ]);
        }

        $classMethodOverrides = $this->class()->docblock()->methodTypes();
        if (isset($classMethodOverrides[$this->name()])) {
            $types = [ $classMethodOverrides[$this->name()] ];
        } else {
            $types = $this->docblock()->returnTypes();
        }

        $types = array_map(function (Type $type) {
            return $this->scope()->resolveFullyQualifiedName($type, $this->class());
        }, $types);

        $types = Types::fromInferredTypes($types);

        if ($this->docblock()->inherits()) {
            if (($this->class()->isClass() || $this->class()->isInterface()) && $this->class()->parent() && $this->class()->parent()->methods()->has($this->name())) {
                $parentMethod = $this->class()->parent()->methods()->get($this->name());
                $types = $types->merge($parentMethod->inferredReturnTypes());
            } else {
                $this->serviceLocator()->logger()->warning(sprintf(
                    'inheritdoc used on class "%s", but class has no parent',
                    $this->class()->name()->full()
                ));
            }
        }

        return $types;
    }

    public function returnType(): Type
    {
        if (null === $this->node->returnType) {
            return Type::undefined();
        }

        if ($this->node->returnType instanceof Token) {
            return Type::fromString($this->node->returnType->getText($this->node->getFileContents()));
        }

        return Type::fromString($this->node->returnType->getResolvedName());
    }

    public function body(): NodeText
    {
        $statements = $this->node->compoundStatementOrSemicolon->statements;
        return NodeText::fromString(implode(PHP_EOL, array_reduce($statements, function ($acc, $statement) {
            $acc[] = (string) $statement->getText();
            return $acc;
        }, [])));
    }

    public function class(): ReflectionClassLike
    {
        return $this->class;
    }

    protected function node(): Node
    {
        return $this->node;
    }

    protected function serviceLocator(): ServiceLocator
    {
        return $this->serviceLocator;
    }
}
