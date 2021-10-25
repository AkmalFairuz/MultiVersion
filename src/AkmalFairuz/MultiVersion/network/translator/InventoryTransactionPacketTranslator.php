<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use AkmalFairuz\MultiVersion\network\Serializer;
use AkmalFairuz\MultiVersion\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use UnexpectedValueException as PacketDecodeException;
use function count;

class InventoryTransactionPacketTranslator{

    public static function serialize(InventoryTransactionPacket $packet, int $protocol) {
        $packet->writeGenericTypeNetworkId($packet->requestId);
        if($packet->requestId !== 0){
            $packet->putUnsignedVarInt(count($packet->requestChangedSlots));
            foreach($packet->requestChangedSlots as $changedSlots){
                $changedSlots->write($packet);
            }
        }

        $trData = $packet->trData;
        $packet->putUnsignedVarInt($trData->getTypeId());

        /** @var NetworkInventoryAction[] $actions */
        $actions = Utils::forceGetProps($trData, "actions");
        $packet->putUnsignedVarInt(count($actions));
        foreach($actions as $action) {
            $packet->putUnsignedVarInt($action->sourceType);

            switch($action->sourceType){
                case $action::SOURCE_TODO:
                case $action::SOURCE_CONTAINER:
                    $packet->putVarInt($action->windowId);
                    break;
                case $action::SOURCE_WORLD:
                    $packet->putUnsignedVarInt($action->sourceFlags);
                    break;
                case $action::SOURCE_CREATIVE:
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown inventory action source type $action->sourceType");
            }

            $packet->putUnsignedVarInt($action->inventorySlot);
            Serializer::putItem($packet, $protocol, $action->oldItem->getItemStack(), $action->oldItem->getStackId());
            Serializer::putItem($packet, $protocol, $action->newItem->getItemStack(), $action->newItem->getStackId());
        }
        if($trData instanceof ReleaseItemTransactionData) {
            $packet->putUnsignedVarInt($trData->getActionType());
            $packet->putVarInt($trData->getHotbarSlot());
            /** @var ItemStackWrapper $itemInHand */
            $itemInHand = $trData->getItemInHand();
            Serializer::putItem($packet, $protocol, $itemInHand->getItemStack(), $itemInHand->getStackId());
            $packet->putVector3($trData->getHeadPos());
        } elseif ($trData instanceof UseItemOnEntityTransactionData) {
            $packet->putEntityRuntimeId($trData->getEntityRuntimeId());
            $packet->putUnsignedVarInt($trData->getActionType());
            $packet->putVarInt($trData->getHotbarSlot());
            $itemInHand = $trData->getItemInHand();
            Serializer::putItem($packet, $protocol, $itemInHand->getItemStack(), $itemInHand->getStackId());
            $packet->putVector3($trData->getPlayerPos());
            $packet->putVector3($trData->getClickPos());
        } elseif ($trData instanceof UseItemTransactionData) {
            $packet->putUnsignedVarInt($trData->getActionType());
            $packet->putBlockPosition($trData->getBlockPos()->x, $trData->getBlockPos()->y, $trData->getBlockPos()->z);
            $packet->putVarInt($trData->getFace());
            $packet->putVarInt($trData->getHotbarSlot());
            $itemInHand = $trData->getItemInHand();
            Serializer::putItem($packet, $protocol, $itemInHand->getItemStack(), $itemInHand->getStackId());
            $packet->putVector3($trData->getPlayerPos());
            $packet->putVector3($trData->getClickPos());
            $block = RuntimeBlockMapping::fromStaticRuntimeId($trData->getBlockRuntimeId());
            $packet->putUnsignedVarInt(MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol));
        } else {
            Utils::forceCallMethod($trData, "encodeData", $packet);
        }
    }

    public static function deserialize(InventoryTransactionPacket $packet, int $protocol) {
        $packet->requestId = $packet->readGenericTypeNetworkId();
        $packet->requestChangedSlots = [];
        if($packet->requestId !== 0){
            for($i = 0, $len = $packet->getUnsignedVarInt(); $i < $len; ++$i){
                $packet->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($packet);
            }
        }

        $transactionType = $packet->getUnsignedVarInt();

        switch($transactionType){
            case $packet::TYPE_NORMAL:
                $packet->trData = new NormalTransactionData();
                break;
            case $packet::TYPE_MISMATCH:
                $packet->trData = new MismatchTransactionData();
                break;
            case $packet::TYPE_USE_ITEM:
                $packet->trData = new UseItemTransactionData();
                break;
            case $packet::TYPE_USE_ITEM_ON_ENTITY:
                $packet->trData = new UseItemOnEntityTransactionData();
                break;
            case $packet::TYPE_RELEASE_ITEM:
                $packet->trData = new ReleaseItemTransactionData();
                break;
            default:
                throw new PacketDecodeException("Unknown transaction type $transactionType");
        }

        $trData = $packet->trData;
        $actionCount = $packet->getUnsignedVarInt();
        $actions = [];
        for($i = 0; $i < $actionCount; ++$i){
            $action = new NetworkInventoryAction();
            $action->sourceType = $packet->getUnsignedVarInt();

            switch($action->sourceType){
                case $action::SOURCE_TODO:
                case $action::SOURCE_CONTAINER:
                    $action->windowId = $packet->getVarInt();
                    break;
                case $action::SOURCE_WORLD:
                    $action->sourceFlags = $packet->getUnsignedVarInt();
                    break;
                case $action::SOURCE_CREATIVE:
                    break;
                default:
                    throw new \UnexpectedValueException("Unknown inventory action source type $action->sourceType");
            }

            $action->inventorySlot = $packet->getUnsignedVarInt();
            $action->oldItem = Serializer::getItemStackWrapper($packet, $protocol);
            $action->newItem = Serializer::getItemStackWrapper($packet, $protocol);
            $actions[] = $action;
        }
        Utils::forceSetProps($trData, "actions", $actions);

        if($trData instanceof ReleaseItemTransactionData) {
            Utils::forceSetProps($trData, "actionType", $packet->getUnsignedVarInt());
            Utils::forceSetProps($trData, "hotbarSlot", $packet->getVarInt());
            Utils::forceSetProps($trData, "itemInHand", Serializer::getItemStackWrapper($packet, $protocol));
            Utils::forceSetProps($trData, "headPos", $packet->getVector3());
        } elseif ($trData instanceof UseItemOnEntityTransactionData) {
            Utils::forceSetProps($trData, "entityRuntimeId", $packet->getEntityRuntimeId());
            Utils::forceSetProps($trData, "actionType", $packet->getUnsignedVarInt());
            Utils::forceSetProps($trData, "hotbarSlot", $packet->getVarInt());
            Utils::forceSetProps($trData, "itemInHand", Serializer::getItemStackWrapper($packet, $protocol));
            Utils::forceSetProps($trData, "playerPos", $packet->getVector3());
            Utils::forceSetProps($trData, "clickPos", $packet->getVector3());
        } elseif ($trData instanceof UseItemTransactionData) {
            Utils::forceSetProps($trData, "actionType", $packet->getUnsignedVarInt());
            $x = $y = $z = 0;
            $packet->getBlockPosition($x, $y, $z);
            Utils::forceSetProps($trData, "blockPos", new Vector3($x, $y, $z));
            Utils::forceSetProps($trData, "face", $packet->getVarInt());
            Utils::forceSetProps($trData, "hotbarSlot", $packet->getVarInt());
            Utils::forceSetProps($trData, "itemInHand", Serializer::getItemStackWrapper($packet, $protocol));
            Utils::forceSetProps($trData, "playerPos", $packet->getVector3());
            Utils::forceSetProps($trData, "clickPos", $packet->getVector3());
            $block = MultiVersionRuntimeBlockMapping::fromStaticRuntimeId($packet->getUnsignedVarInt(), $protocol);
            Utils::forceSetProps($trData, "blockRuntimeId", RuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1]));
        } else {
            Utils::forceCallMethod($trData, "decodeData", $packet);
        }
    }
}