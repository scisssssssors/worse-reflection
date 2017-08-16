Worse Reflection
==================

[![Build Status](https://travis-ci.org/phpactor/worse-reflection.svg?branch=master)](https://travis-ci.org/phpactor/worse-reflection)

This library aims to provide a light class-based AST based "reflection" library.

## Worse than Better

It is influenced by [BetterReflection](https://github.com/Roave/BetterReflection), diffrerences are as follows:

- Can reflect types at offsets.
- Can reflect frames (i.e. all variables in a method).
- Does not aim to implement built-in PHP reflection API.
- Only reflects classes and similar, not functions.
- Uses the amazing [Tolerant Parser](https://github.com/Microsoft/tolerant-php-parser).
- Uses the PHPStorm stubs to provide reflections of internal classes.

It is being developed to provide support for the
[Phpactor](https://github.com/dantleech/phpactor) introspection and
refactoring tool. And is therefore driven by that use case.

If you want comprehsnsive reflection, use BetterReflection. If you want faster
class-based reflection with no support and frequent BC breaks, then you can
use this one.

## Usage

```php
$reflector = new Reflector(new StringSourceLocator(SourceCode::fromString('<?php ...')));

$class = $reflector->reflectClass('Foobar');

$class->methods()->get('foobar')->visiblity()    == Visibility::public();
$class->properties()->get('barbar')->visiblity() == Visibility::public();

/** @var ReflectionMethod */
foreach ($class->methods() as $method) {
    echo $method->name();                        // methodName
    echo $method->returnType()->short();         // Foobar
    echo (string) $method->returnType();         // This\Is\Foobar
    echo (string) $method->inferredReturnType(); // from docblock if it exists

    foreach ($method->parameters() as $parameter) {
        $parameter->name();                      // paramName
        (string) $parameter->inferredType();     // Fully\Qualified\ParamType
    }

}

foreach ($class->traits() as $trait) {
    // ...
}

foreach ($class->interfaes() as $interface) {
    // ...
}

foreach ($class->method('foobar')->frame() as $variable) {
    $variable->offset()->asInt(); // byte offset
    $variable->type();            // variable type (if available )
    $variable->value();           // variable value (if available)
}

$offset = $reflection->reflectOffset(
    SourceCode::fromString('...'), 
    Offset::fromInt(1234)
);

$offset->value()->type();    // type at offset (if available)
$offset->value()->value();   // value (e.g. 1234)
$offset->frame();            // return frame
```

See tests for more examples...
