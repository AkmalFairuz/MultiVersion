<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ProtocolConstants{

    public const BEDROCK_1_16_0 = 407;
    public const BEDROCK_1_16_20 = 408;
    public const BEDROCK_1_16_100 = 419;
    public const BEDROCK_1_16_200 = 422;
    public const BEDROCK_1_16_210 = 428;
    public const BEDROCK_1_16_220 = 431;
    public const BEDROCK_1_17_0 = 440;
    public const BEDROCK_1_17_10 = 448;
    public const BEDROCK_1_17_30 = 465;

    public const SUPPORTED_PROTOCOLS = [
        self::BEDROCK_1_16_220,
        self::BEDROCK_1_17_0,
        self::BEDROCK_1_17_10,
        self::BEDROCK_1_17_30,
        ProtocolInfo::CURRENT_PROTOCOL
    ];
}