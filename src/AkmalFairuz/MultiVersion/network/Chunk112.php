<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\tile\Spawnable;
use function chr;

class Chunk112{

    public static function serialize(Level $level, LevelChunkPacket $origin): ?LevelChunkPacket{
        $x = $origin->getChunkX();
        $z = $origin->getChunkZ();
        $chunk = $level->getChunk($x, $z);
        if($chunk !== null){
            $payload = self::networkSerialize($chunk);
            return LevelChunkPacket::create($x, $z, $origin->getSubChunkCount() - 4, false, $origin->getUsedBlobHashes(),  $payload);
        }
        return null;
    }

    public static function networkSerialize(Chunk $chunk) {
        $result = "";
        $subChunkCount = $chunk->getSubChunkSendCount();
        for($y = 0; $y < $subChunkCount; ++$y){
            $result .= $chunk->getSubChunk($y)->networkSerialize();
        }
        $result .= $chunk->getBiomeIdArray() . chr(0); //border block array count
        //Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

        foreach($chunk->getTiles() as $tile){
            if($tile instanceof Spawnable){
                $result .= $tile->getSerializedSpawnCompound();
            }
        }

        return $result;
    }
}
