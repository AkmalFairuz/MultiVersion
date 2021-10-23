<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use function count;

class PlayerListPacketTranslator{

    public static function serialize(PlayerListPacket $packet, int $protocol) {
        ($packet->buffer .= \chr($packet->type));
        $packet->putUnsignedVarInt(count($packet->entries));
        foreach($packet->entries as $entry){
            if($packet->type === $packet::TYPE_ADD){
                $packet->putUUID($entry->uuid);
                $packet->putEntityUniqueId($entry->entityUniqueId);
                $packet->putString($entry->username);
                $packet->putString($entry->xboxUserId);
                $packet->putString($entry->platformChatId);
                ($packet->buffer .= (\pack("V", $entry->buildPlatform)));
                Serializer::putSkin($entry->skinData, $packet, $protocol);
                ($packet->buffer .= ($entry->isTeacher ? "\x01" : "\x00"));
                ($packet->buffer .= ($entry->isHost ? "\x01" : "\x00"));
            }else{
                $packet->putUUID($entry->uuid);
            }
        }
        if($packet->type === $packet::TYPE_ADD){
            foreach($packet->entries as $entry){
                ($packet->buffer .= ($entry->skinData->isVerified() ? "\x01" : "\x00"));
            }
        }
    }
}