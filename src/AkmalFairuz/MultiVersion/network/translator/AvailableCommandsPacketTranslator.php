<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use AkmalFairuz\MultiVersion\utils\Utils;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;

class AvailableCommandsPacketTranslator{

    public static function serialize(AvailableCommandsPacket $packet, int $protocol) {
        /** @var int[] $enumValueIndexes */
        $enumValueIndexes = [];
        /** @var int[] $postfixIndexes */
        $postfixIndexes = [];
        /** @var int[] $enumIndexes */
        $enumIndexes = [];
        /** @var CommandEnum[] $enums */
        $enums = [];

        $addEnumFn = static function(CommandEnum $enum) use (&$enums, &$enumIndexes, &$enumValueIndexes) : void{
            if(!isset($enumIndexes[$enum->enumName])){
                $enums[$enumIndexes[$enum->enumName] = count($enumIndexes)] = $enum;
            }
            foreach($enum->enumValues as $str){
                $enumValueIndexes[$str] = $enumValueIndexes[$str] ?? count($enumValueIndexes);
            }
        };
        foreach($packet->hardcodedEnums as $enum){
            $addEnumFn($enum);
        }
        foreach($packet->commandData as $commandData){
            if($commandData->aliases !== null){
                $addEnumFn($commandData->aliases);
            }
            /** @var CommandParameter[] $overload */
            foreach($commandData->overloads as $overload){
                /** @var CommandParameter $parameter */
                foreach($overload as $parameter){
                    if($parameter->enum !== null){
                        $addEnumFn($parameter->enum);
                    }

                    if($parameter->postfix !== null){
                        $postfixIndexes[$parameter->postfix] = $postfixIndexes[$parameter->postfix] ?? count($postfixIndexes);
                    }
                }
            }
        }

        $packet->putUnsignedVarInt(count($enumValueIndexes));
        foreach($enumValueIndexes as $enumValue => $index){
            $packet->putString((string) $enumValue); //stupid PHP key casting D:
        }

        $packet->putUnsignedVarInt(count($postfixIndexes));
        foreach($postfixIndexes as $postfix => $index){
            $packet->putString((string) $postfix); //stupid PHP key casting D:
        }

        $packet->putUnsignedVarInt(count($enums));
        foreach($enums as $enum){
            Utils::forceCallMethod($packet, "putEnum", $enum, $enumValueIndexes);
        }

        $packet->putUnsignedVarInt(count($packet->commandData));
        foreach($packet->commandData as $data){
            self::putCommandData($packet, $data, $enumIndexes, $postfixIndexes, $protocol);
        }

        $packet->putUnsignedVarInt(count($packet->softEnums));
        foreach($packet->softEnums as $enum){
            Utils::forceCallMethod($packet, "putSoftEnum", $enum);
        }

        $packet->putUnsignedVarInt(count($packet->enumConstraints));
        foreach($packet->enumConstraints as $constraint){
            Utils::forceCallMethod($packet, "putEnumConstraint", $constraint, $enumIndexes, $enumValueIndexes);
        }
    }

    private static function putCommandData(AvailableCommandsPacket $packet, CommandData $data, array $enumIndexes, array $postfixIndexes, int $protocol){
        $packet->putString($data->commandName);
        $packet->putString($data->commandDescription);
        if($protocol >= ProtocolConstants::BEDROCK_1_17_10){
            $packet->putLShort($data->flags);
        } else {
            $packet->putByte($data->flags);
        }
        $packet->putByte($data->permission);

        if($data->aliases !== null){
            $packet->putLInt($enumIndexes[$data->aliases->enumName] ?? -1);
        }else{
            $packet->putLInt(-1);
        }

        $packet->putUnsignedVarInt(count($data->overloads));
        foreach($data->overloads as $overload){
            /** @var CommandParameter[] $overload */
            $packet->putUnsignedVarInt(count($overload));
            foreach($overload as $parameter){
                $packet->putString($parameter->paramName);

                if($parameter->enum !== null){
                    $type = $packet::ARG_FLAG_ENUM | $packet::ARG_FLAG_VALID | ($enumIndexes[$parameter->enum->enumName] ?? -1);
                }elseif($parameter->postfix !== null){
                    $key = $postfixIndexes[$parameter->postfix] ?? -1;
                    if($key === -1){
                        throw new \InvalidStateException("Postfix '$parameter->postfix' not in postfixes array");
                    }
                    $type = $packet::ARG_FLAG_POSTFIX | $key;
                }else{
                    $type = $parameter->paramType;
                }

                $packet->putLInt($type);
                $packet->putBool($parameter->isOptional);
                $packet->putByte($parameter->flags);
            }
        }
    }
}