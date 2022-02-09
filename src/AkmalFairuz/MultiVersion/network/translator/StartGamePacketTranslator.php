<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use function count;

class StartGamePacketTranslator {

    public static function serialize(StartGamePacket $packet, int $protocol){
        $packet->putEntityUniqueId($packet->entityUniqueId);
        $packet->putEntityRuntimeId($packet->entityRuntimeId);
        $packet->putVarInt($packet->playerGamemode);

        $packet->putVector3($packet->playerPosition);

        ($packet->buffer .= (\pack("g", $packet->pitch)));
        ($packet->buffer .= (\pack("g", $packet->yaw)));

        //Level settings
        $packet->putVarInt($packet->seed);
        $packet->spawnSettings->write($packet);
        $packet->putVarInt($packet->generator);
        $packet->putVarInt($packet->worldGamemode);
        $packet->putVarInt($packet->difficulty);
        $packet->putBlockPosition($packet->spawnX, $packet->spawnY, $packet->spawnZ);
        ($packet->buffer .= ($packet->hasAchievementsDisabled ? "\x01" : "\x00"));
        $packet->putVarInt($packet->time);
        $packet->putVarInt($packet->eduEditionOffer);
        ($packet->buffer .= ($packet->hasEduFeaturesEnabled ? "\x01" : "\x00"));
        $packet->putString($packet->eduProductUUID);
        ($packet->buffer .= (\pack("g", $packet->rainLevel)));
        ($packet->buffer .= (\pack("g", $packet->lightningLevel)));
        ($packet->buffer .= ($packet->hasConfirmedPlatformLockedContent ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->isMultiplayerGame ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->hasLANBroadcast ? "\x01" : "\x00"));
        $packet->putVarInt($packet->xboxLiveBroadcastMode);
        $packet->putVarInt($packet->platformBroadcastMode);
        ($packet->buffer .= ($packet->commandsEnabled ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->isTexturePacksRequired ? "\x01" : "\x00"));
        Serializer::putGameRules($packet, $packet->gameRules, $protocol);
        $packet->experiments->write($packet);
        ($packet->buffer .= ($packet->hasBonusChestEnabled ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->hasStartWithMapEnabled ? "\x01" : "\x00"));
        $packet->putVarInt($packet->defaultPlayerPermission);
        ($packet->buffer .= (\pack("V", $packet->serverChunkTickRadius)));
        ($packet->buffer .= ($packet->hasLockedBehaviorPack ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->hasLockedResourcePack ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->isFromLockedWorldTemplate ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->useMsaGamertagsOnly ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->isFromWorldTemplate ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->isWorldTemplateOptionLocked ? "\x01" : "\x00"));
        ($packet->buffer .= ($packet->onlySpawnV1Villagers ? "\x01" : "\x00"));
        $packet->putString(ProtocolConstants::MINECRAFT_VERSION[$protocol] ?? "*");
        ($packet->buffer .= (\pack("V", $packet->limitedWorldWidth)));
        ($packet->buffer .= (\pack("V", $packet->limitedWorldLength)));
        ($packet->buffer .= ($packet->isNewNether ? "\x01" : "\x00"));
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $packet->putString(""); //Education URI resource -> buttonName
            $packet->putString(""); //Education URI resource -> link URI
        }
        ($packet->buffer .= ($packet->experimentalGameplayOverride !== null ? "\x01" : "\x00"));
        if($packet->experimentalGameplayOverride !== null){
            ($packet->buffer .= ($packet->experimentalGameplayOverride ? "\x01" : "\x00"));
        }

        $packet->putString($packet->levelId);
        $packet->putString($packet->worldName);
        $packet->putString($packet->premiumWorldTemplateId);
        ($packet->buffer .= ($packet->isTrial ? "\x01" : "\x00"));
        $packet->playerMovementSettings->write($packet);
        ($packet->buffer .= (\pack("VV", $packet->currentTick & 0xFFFFFFFF, $packet->currentTick >> 32)));

        $packet->putVarInt($packet->enchantmentSeed);

        $packet->putUnsignedVarInt(count($packet->blockPalette));
        $nbtWriter = new NetworkLittleEndianNBTStream();
        foreach($packet->blockPalette as $entry){
            $packet->putString($entry->getName());
            ($packet->buffer .= $nbtWriter->write($entry->getStates()));
        }
        $packet->putUnsignedVarInt(count($packet->itemTable));
        foreach($packet->itemTable as $entry){
            $packet->putString($entry->getStringId());
            ($packet->buffer .= (\pack("v", $entry->getNumericId())));
            ($packet->buffer .= ($entry->isComponentBased() ? "\x01" : "\x00"));
        }

        $packet->putString($packet->multiplayerCorrelationId);
        ($packet->buffer .= ($packet->enableNewInventorySystem ? "\x01" : "\x00"));
        if($protocol >= ProtocolConstants::BEDROCK_1_17_0){
            $packet->putString($packet->serverSoftwareVersion);
        }
        if($protocol >= ProtocolConstants::BEDROCK_1_18_0){
            $packet->putLLong(0);
        }
    }
}