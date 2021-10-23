<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class Translator{

    public static function fromClient(DataPacket $packet, int $protocol) : DataPacket{
        // todo
        return $packet;
    }

    public static function fromServer(DataPacket $packet, int $protocol) : DataPacket {
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet */
                $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);
                $packet->blockRuntimeId = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                return $packet;
            case CraftingDataPacket::NETWORK_ID:
                if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
                    $packet->putVarInt(0); //material reducer recipe count
                }
                return $packet;
        }
        return $packet;
    }
}