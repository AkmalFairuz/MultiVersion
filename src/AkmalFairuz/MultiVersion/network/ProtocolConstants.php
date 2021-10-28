<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ProtocolConstants{

    public const BEDROCK_1_17_0 = 440;
    public const BEDROCK_1_17_10 = 448;
    public const BEDROCK_1_17_30 = 465;

    public const SUPPORTED_PROTOCOLS = [
        self::BEDROCK_1_17_0,
        self::BEDROCK_1_17_10,
        self::BEDROCK_1_17_30,
        ProtocolInfo::CURRENT_PROTOCOL
    ];
}