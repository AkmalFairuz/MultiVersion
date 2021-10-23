<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ProtocolConstants{

    public const BEDROCK_1_17_30 = 465;

    public const SUPPORTED_PROTOCOLS = [
        ProtocolInfo::CURRENT_PROTOCOL,
        self::BEDROCK_1_17_30
    ];
}