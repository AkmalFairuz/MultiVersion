<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\convert;

use AkmalFairuz\MultiVersion\Loader;
use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use AkmalFairuz\MultiVersion\network\Translator;
use pocketmine\inventory\CraftingManager;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Server;
use pocketmine\timings\Timings;

class MultiVersionCraftingManager extends CraftingManager{

    /** @var BatchPacket[] */
    protected $multiVersionCraftingDataCache = [];

    const PROTOCOL = [
        ProtocolConstants::BEDROCK_1_17_30,
        ProtocolConstants::BEDROCK_1_17_10,
        ProtocolConstants::BEDROCK_1_17_0,
        ProtocolConstants::BEDROCK_1_16_220
    ];

    public function buildCraftingDataCache(): void{
        Timings::$craftingDataCacheRebuildTimer->startTiming();
        $c = Server::getInstance()->getCraftingManager();
        foreach(self::PROTOCOL as $protocol){
            if(Loader::getInstance()->isProtocolDisabled($protocol)) {
                continue;
            }
            $pk = new CraftingDataPacket();
            $pk->cleanRecipes = true;

            foreach($c->shapelessRecipes as $list){
                foreach($list as $recipe){
                    $pk->addShapelessRecipe($recipe);
                }
            }
            foreach($c->shapedRecipes as $list){
                foreach($list as $recipe){
                    $pk->addShapedRecipe($recipe);
                }
            }

            foreach($c->furnaceRecipes as $recipe){
                $pk->addFurnaceRecipe($recipe);
            }

            $pk = Translator::fromServer($pk, $protocol);

            $batch = new BatchPacket();
            $batch->addPacket($pk);
            $batch->setCompressionLevel(Server::getInstance()->networkCompressionLevel);
            $batch->encode();

            $this->multiVersionCraftingDataCache[$protocol] = $batch;
        }
        Timings::$craftingDataCacheRebuildTimer->stopTiming();
    }
    
    private static function convertCraftingprotocol(int $protocol) : int{
        switch($protocol){
                case ProtocolConstants::BEDROCK_1_16_220_50:
                case ProtocolConstants::BEDROCK_1_16_220_51:
                case ProtocolConstants::BEDROCK_1_16_230_50:
                case ProtocolConstants::BEDROCK_1_16_230_52:
                case ProtocolConstants::BEDROCK_1_16_230_54:
                    return ProtocolConstants::BEDROCK_1_16_220;
                case ProtocolConstants::BEDROCK_1_17_10_20:
                    return ProtocolConstants::BEDROCK_1_17_0;
                case ProtocolConstants::BEDROCK_1_17_20_20:
                case ProtocolConstants::BEDROCK_1_17_20_21:
                case ProtocolConstants::BEDROCK_1_17_20_22:
                    return ProtocolConstants::BEDROCK_1_17_10;
                case ProtocolConstants::BEDROCK_1_17_20_23:
                case ProtocolConstants::BEDROCK_1_17_30_20:
                case ProtocolConstants::BEDROCK_1_17_30_22:
                    return ProtocolConstants::BEDROCK_1_17_30;
                default:
                    return $protocol;
        }
    }
    
    public function getCraftingDataPacketA(int $protocol): BatchPacket{
        return $this->multiVersionCraftingDataCache[$this->convertCraftingprotocol($protocol)];
    }
}
