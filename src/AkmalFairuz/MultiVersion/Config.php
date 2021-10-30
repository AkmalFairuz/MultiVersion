<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use function yaml_parse_file;

class Config{

    public static $ASYNC_BATCH_COMPRESSION;
    public static $ASYNC_BATCH_DECOMPRESSION;
    public static $ASYNC_BATCH_THRESHOLD;
    public static $DISABLED_PROTOCOLS;

    public static function init(string $path) {
        $cfg = yaml_parse_file($path);
        self::$ASYNC_BATCH_COMPRESSION = $cfg["async_batch_compression"] ?? true;
        self::$ASYNC_BATCH_DECOMPRESSION = $cfg["async_batch_decompression"] ?? true;
        self::$ASYNC_BATCH_THRESHOLD = $cfg["async_batch_threshold"] ?? 1024;
        self::$DISABLED_PROTOCOLS = $cfg["disabled_protocols"] ?? [];
    }
}