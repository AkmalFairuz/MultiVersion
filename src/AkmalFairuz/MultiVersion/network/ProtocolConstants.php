<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ProtocolConstants{

    /* Support*/
    public const BEDROCK_1_16_220_50 = 429;
    public const BEDROCK_1_16_220_51 = 430;
    public const BEDROCK_1_16_220 = 431;
    public const BEDROCK_1_16_230_50 = 433;
    public const BEDROCK_1_16_230_52 = 434;
    public const BEDROCK_1_16_230_54 = 435;
    public const BEDROCK_1_17_0 = 440;
    public const BEDROCK_1_17_10_20 = 441;
    public const BEDROCK_1_17_10 = 448;
    public const BEDROCK_1_17_20_20 = 453;
    public const BEDROCK_1_17_20_21 = 455;
    public const BEDROCK_1_17_20_22 = 456;
    public const BEDROCK_1_17_20_23 = 459;
    public const BEDROCK_1_17_30_20 = 462;
    public const BEDROCK_1_17_30_22 = 464;
    public const BEDROCK_1_17_30 = 465;
    /* Support but Block is Weird (No Block States)*/
    public const BEDROCK_1_17_0_50 = 437;
    
    public const SUPPORTED_PROTOCOLS = [
        self::BEDROCK_1_16_220_50,
        self::BEDROCK_1_16_220_51,
        self::BEDROCK_1_16_220,
        self::BEDROCK_1_16_230_50,
        self::BEDROCK_1_16_230_52,
        self::BEDROCK_1_16_230_54,
        self::BEDROCK_1_17_0,
        self::BEDROCK_1_17_10_20,
        self::BEDROCK_1_17_10,
        self::BEDROCK_1_17_20_20,
        self::BEDROCK_1_17_20_21,
        self::BEDROCK_1_17_20_22,
        self::BEDROCK_1_17_20_23,
        self::BEDROCK_1_17_30_20,
        self::BEDROCK_1_17_30_22,
        self::BEDROCK_1_17_30,
        ProtocolInfo::CURRENT_PROTOCOL
    ];
}
