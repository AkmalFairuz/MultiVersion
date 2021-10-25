<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\utils;

use ReflectionException;

class Utils{

    public static function forceGetProps($object, string $propName) {
        try{
            $reflection = new \ReflectionClass($object);
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            return $prop->getValue($object);
        } catch(ReflectionException $e) {
            return null;
        }
    }

    public static function forceSetProps($object, string $propName, $value) {
        try{
            $reflection = new \ReflectionClass($object);
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            $prop->setValue($object, $value);
        } catch(ReflectionException $e) {
        }
    }

    public static function forceCallMethod($object, string $methodName, ...$args) {
        try{
            $reflection = new \ReflectionClass($object);
            $method = $reflection->getMethod($methodName);
            $method->setAccessible(true);
            ($method->getClosure($object))(...$args);
        } catch(ReflectionException $e) {
        }
    }
}