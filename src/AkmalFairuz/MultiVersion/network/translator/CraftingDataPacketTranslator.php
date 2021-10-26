<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionItemTranslator;
use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use function count;
use function str_repeat;

class CraftingDataPacketTranslator{

    private static function writeEntry($entry, NetworkBinaryStream $stream, int $pos, int $protocol) : int{
        if($entry instanceof ShapelessRecipe){
            return self::writeShapelessRecipe($entry, $stream, $pos, $protocol);
        }elseif($entry instanceof ShapedRecipe){
            return self::writeShapedRecipe($entry, $stream, $pos, $protocol);
        }elseif($entry instanceof FurnaceRecipe){
            return self::writeFurnaceRecipe($entry, $stream, $protocol);
        }
        //TODO: add MultiRecipe

        return -1;
    }

    private static function writeShapelessRecipe(ShapelessRecipe $recipe, NetworkBinaryStream $stream, int $pos, int $protocol) : int{
        $stream->putString((\pack("N", $pos))); //some kind of recipe ID, doesn't matter what it is as long as it's unique
        $stream->putUnsignedVarInt($recipe->getIngredientCount());
        foreach($recipe->getIngredientList() as $item){
            Serializer::putRecipeIngredient($stream, $item, $protocol);
        }

        $results = $recipe->getResults();
        $stream->putUnsignedVarInt(count($results));
        foreach($results as $item){
            Serializer::putItemStackWithoutStackId($stream, $item, $protocol);
        }

        $stream->put(str_repeat("\x00", 16)); //Null UUID
        $stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
        $stream->putVarInt(50); //TODO: priority
        $stream->writeGenericTypeNetworkId($pos); //TODO: ANOTHER recipe ID, only used on the network

        return CraftingDataPacket::ENTRY_SHAPELESS;
    }

    private static function writeShapedRecipe(ShapedRecipe $recipe, NetworkBinaryStream $stream, int $pos, int $protocol) : int{
        $stream->putString((\pack("N", $pos))); //some kind of recipe ID, doesn't matter what it is as long as it's unique
        $stream->putVarInt($recipe->getWidth());
        $stream->putVarInt($recipe->getHeight());

        for($z = 0; $z < $recipe->getHeight(); ++$z){
            for($x = 0; $x < $recipe->getWidth(); ++$x){
                Serializer::putRecipeIngredient($stream, $recipe->getIngredient($x, $z), $protocol);
            }
        }

        $results = $recipe->getResults();
        $stream->putUnsignedVarInt(count($results));
        foreach($results as $item){
            Serializer::putItemStackWithoutStackId($stream, $item, $protocol);
        }

        $stream->put(str_repeat("\x00", 16)); //Null UUID
        $stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
        $stream->putVarInt(50); //TODO: priority
        $stream->writeGenericTypeNetworkId($pos); //TODO: ANOTHER recipe ID, only used on the network

        return CraftingDataPacket::ENTRY_SHAPED;
    }

    private static function writeFurnaceRecipe(FurnaceRecipe $recipe, NetworkBinaryStream $stream, int $protocol) : int{
        $input = $recipe->getInput();
        if($input->hasAnyDamageValue()){
            [$netId, ] = MultiVersionItemTranslator::getInstance()->toNetworkId($input->getId(), 0, $protocol);
            $netData = 0x7fff;
        }else{
            [$netId, $netData] = MultiVersionItemTranslator::getInstance()->toNetworkId($input->getId(), $input->getDamage(), $protocol);
        }
        $stream->putVarInt($netId);
        $stream->putVarInt($netData);
        Serializer::putItemStackWithoutStackId($stream, $recipe->getResult(), $protocol);
        $stream->putString("furnace"); //TODO: blocktype (no prefix) (this might require internal API breaks)
        return CraftingDataPacket::ENTRY_FURNACE_DATA;
    }

    public static function serialize(CraftingDataPacket $packet, int $protocol) {
        $packet->putUnsignedVarInt(count($packet->entries));

        $writer = new NetworkBinaryStream();
        $counter = 0;
        foreach($packet->entries as $d){
            $entryType = self::writeEntry($d, $writer, ++$counter, $protocol);
            if($entryType >= 0){
                $packet->putVarInt($entryType);
                ($packet->buffer .= $writer->getBuffer());
            }else{
                $packet->putVarInt(-1);
            }

            $writer->reset();
        }
        $packet->putUnsignedVarInt(count($packet->potionTypeRecipes));
        foreach($packet->potionTypeRecipes as $recipe){
            $packet->putVarInt($recipe->getInputItemId());
            $packet->putVarInt($recipe->getInputItemMeta());
            $packet->putVarInt($recipe->getIngredientItemId());
            $packet->putVarInt($recipe->getIngredientItemMeta());
            $packet->putVarInt($recipe->getOutputItemId());
            $packet->putVarInt($recipe->getOutputItemMeta());
        }
        $packet->putUnsignedVarInt(count($packet->potionContainerRecipes));
        foreach($packet->potionContainerRecipes as $recipe){
            $packet->putVarInt($recipe->getInputItemId());
            $packet->putVarInt($recipe->getIngredientItemId());
            $packet->putVarInt($recipe->getOutputItemId());
        }
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $packet->putUnsignedVarInt(count($packet->materialReducerRecipes));
            foreach($packet->materialReducerRecipes as $recipe){
                $packet->putVarInt(($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
                $packet->putUnsignedVarInt(count($recipe->getOutputs()));
                foreach($recipe->getOutputs() as $output){
                    $packet->putVarInt($output->getItemId());
                    $packet->putVarInt($output->getCount());
                }
            }
        }
        ($packet->buffer .= ($packet->cleanRecipes ? "\x01" : "\x00"));
    }
}