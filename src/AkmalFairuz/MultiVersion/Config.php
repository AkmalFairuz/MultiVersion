<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use function yaml_parse_file;

class Config{

    private static $config = [];

    public static function init(string $path) {
        self::$config = yaml_parse_file($path);
    }

    public static function get(string $key) {
        return self::$config[$key] ?? null;
    }
}