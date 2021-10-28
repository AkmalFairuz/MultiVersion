<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;

class ResourcePacksInfoPacketTranslator{

    public static function serialize(ResourcePacksInfoPacket $packet, int $protocol) {
        $packet->putBool($packet->mustAccept);
        $packet->putBool($packet->hasScripts);
        if($protocol >= ProtocolConstants::BEDROCK_1_17_10){
            $packet->putBool($packet->forceServerPacks);
        }
        $packet->putLShort(count($packet->behaviorPackEntries));
        foreach($packet->behaviorPackEntries as $entry){
            $packet->putString($entry->getPackId());
            $packet->putString($entry->getPackVersion());
            $packet->putLLong($entry->getPackSize());
            $packet->putString(""); //TODO: encryption key
            $packet->putString(""); //TODO: subpack name
            $packet->putString(""); //TODO: content identity
            $packet->putBool(false); //TODO: has scripts (?)
        }
        $packet->putLShort(count($packet->resourcePackEntries));
        foreach($packet->resourcePackEntries as $entry){
            $packet->putString($entry->getPackId());
            $packet->putString($entry->getPackVersion());
            $packet->putLLong($entry->getPackSize());
            $packet->putString(""); //TODO: encryption key
            $packet->putString(""); //TODO: subpack name
            $packet->putString(""); //TODO: content identity
            $packet->putBool(false); //TODO: seems useless for resource packs
            $packet->putBool(false); //TODO: supports RTX
        }
    }
}