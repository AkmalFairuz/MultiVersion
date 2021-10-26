<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use function count;

class CreativeContentPacketTranslator{

    public static function serialize(CreativeContentPacket $packet, int $protocol) {
        $packet->putUnsignedVarInt(count($packet->getEntries()));
        foreach($packet->getEntries() as $entry){
            $packet->writeGenericTypeNetworkId($entry->getEntryId());
            Serializer::putItemStackWithoutStackId($packet, $entry->getItem(), $protocol);
        }
    }
}