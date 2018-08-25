<?php

/*
 * This file is part of the PHINT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace Ahc\Phint\Util;

use CrazyFactory\DocBlocks\DocBlock;

class Metadata
{
    public function forClass(string $classFqcn): array
    {
        return $this->forReflectionClass(new \ReflectionClass($classFqcn));
    }

    public function forReflectionClass(\ReflectionClass $class): array
    {
        $texts = (new DocBlock($class))->texts();
        $title = \array_shift($texts);

        $metadata = [
            'classFqcn'   => $class->name,
            'classPath'   => $class->getFileName(),
            'name'        => $class->getShortName(),
            'isTrait'     => $class->isTrait(),
            'isAbstract'  => $class->isAbstract(),
            'isInterface' => $class->isInterface(),
            'newable'     => $class->isInstantiable(),
            'title'       => $title,
            'texts'       => $texts,
            'methods'     => [],
        ];

        foreach ($class->getMethods() as $method) {
            if ($method->class !== $class->name) {
                continue;
            }

            $metadata['methods'][$method->name] = $this->forReflectionMethod($method);
        }

        return $metadata;
    }

    public function forMethod(string $classFqcn, string $method): array
    {
        $reflMethod = (new \ReflectionClass($classFqcn))->getMethod($method);

        return $this->forReflectionMethod($reflMethod);
    }

    public function forReflectionMethod(\ReflectionMethod $method): array
    {
        $parser = new DocBlock($method);
        $texts  = $parser->texts();
        $title  = \array_shift($texts);

        $metadata = [
            'name'       => $method->name,
            'inClass'    => $method->getDeclaringClass()->name,
            'isStatic'   => $method->isStatic(),
            'isFinal'    => $method->isFinal(),
            'isPublic'   => $method->isPublic(),
            'isAbstract' => $method->isAbstract(),
            'maybeMagic' => \substr($method->name, 0, 2) === '__',
            'title'      => $title,
            'texts'      => $texts,
            'return'     => 'void',
            'params'     => [],
        ];

        foreach ($parser->find('param') as $param) {
            $metadata['params'][] = \preg_replace(['/(.*\$\w+)(.*)/', '/ +/'], ['$1', ' '], $param->getValue());
        }

        if (null !== $return = $parser->first('return')) {
            $metadata['return'] = \preg_replace('/(\S+)(.*)/', '$1', $return->getValue());
        }

        return $metadata;
    }
}
