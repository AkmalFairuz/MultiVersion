<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\convert;

use AkmalFairuz\MultiVersion\Loader;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use function array_key_exists;
use function file_get_contents;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;

class MultiVersionItemTranslator{
    use SingletonTrait;

    /**
     * @var int[][]
     */
    private $simpleCoreToNetMapping = [];
    /**
     * @var int[][]
     */
    private $simpleNetToCoreMapping = [];

    /**
     * @var int[][][]
     */
    private $complexCoreToNetMapping = [];
    /**
     * @var int[][][]
     */
    private $complexNetToCoreMapping = [];

    private static function make() : self{
        $data = file_get_contents(\pocketmine\RESOURCE_PATH . '/vanilla/r16_to_current_item_map.json');
        if($data === false) throw new AssumptionFailedError("Missing required resource file");
        $json = json_decode($data, true);
        if(!is_array($json) or !isset($json["simple"], $json["complex"]) || !is_array($json["simple"]) || !is_array($json["complex"])){
            throw new AssumptionFailedError("Invalid item table format");
        }

        $legacyStringToIntMapRaw = file_get_contents(\pocketmine\RESOURCE_PATH . '/vanilla/item_id_map.json');
        if($legacyStringToIntMapRaw === false){
            throw new AssumptionFailedError("Missing required resource file");
        }
        $legacyStringToIntMap = json_decode($legacyStringToIntMapRaw, true);
        if(!is_array($legacyStringToIntMap)){
            throw new AssumptionFailedError("Invalid mapping table format");
        }

        /** @phpstan-var array<string, int> $simpleMappings */
        $simpleMappings = [];
        foreach($json["simple"] as $oldId => $newId){
            if(!is_string($oldId) || !is_string($newId)){
                throw new AssumptionFailedError("Invalid item table format");
            }
            if(!isset($legacyStringToIntMap[$oldId])){
                //new item without a fixed legacy ID - we can't handle this right now
                continue;
            }
            $simpleMappings[$newId] = $legacyStringToIntMap[$oldId];
        }
        foreach($legacyStringToIntMap as $stringId => $intId){
            if(isset($simpleMappings[$stringId])){
                throw new \UnexpectedValueException("Old ID $stringId collides with new ID");
            }
            $simpleMappings[$stringId] = $intId;
        }

        /** @phpstan-var array<string, array{int, int}> $complexMappings */
        $complexMappings = [];
        foreach($json["complex"] as $oldId => $map){
            if(!is_string($oldId) || !is_array($map)){
                throw new AssumptionFailedError("Invalid item table format");
            }
            foreach($map as $meta => $newId){
                if(!is_numeric($meta) || !is_string($newId)){
                    throw new AssumptionFailedError("Invalid item table format");
                }
                $complexMappings[$newId] = [$legacyStringToIntMap[$oldId], (int) $meta];
            }
        }

        return new self(MultiVersionItemTypeDictionary::getInstance(), $simpleMappings, $complexMappings);
    }

    /**
     * @param MultiVersionItemTypeDictionary $dictionary
     * @param int[] $simpleMappings
     * @param int[][] $complexMappings
     * @phpstan-param array<string, int> $simpleMappings
     * @phpstan-param array<string, array<int, int>> $complexMappings
     */
    public function __construct(MultiVersionItemTypeDictionary $dictionary, array $simpleMappings, array $complexMappings){
        foreach($dictionary->getAllEntries() as $protocol => $entries){
            if(Loader::getInstance()->isProtocolDisabled($protocol)) {
                continue;
            }
            foreach($entries as $entry){
                $stringId = $entry->getStringId();
                $netId = $entry->getNumericId();
                if(isset($complexMappings[$stringId])){
                    [$id, $meta] = $complexMappings[$stringId];
                    $this->complexCoreToNetMapping[$protocol][$id][$meta] = $netId;
                    $this->complexNetToCoreMapping[$protocol][$netId] = [$id, $meta];
                }elseif(isset($simpleMappings[$stringId])){
                    $this->simpleCoreToNetMapping[$protocol][$simpleMappings[$stringId]] = $netId;
                    $this->simpleNetToCoreMapping[$protocol][$netId] = $simpleMappings[$stringId];
                }else{
                    //not all items have a legacy mapping - for now, we only support the ones that do
                    continue;
                }
            }
        }
    }

    /**
     * @return int[]
     * @phpstan-return array{int, int}
     */
    public function toNetworkId(int $internalId, int $internalMeta, int $protocol) : array{
        if($internalMeta === -1){
            $internalMeta = 0x7fff;
        }
        if(isset($this->complexCoreToNetMapping[$protocol][$internalId][$internalMeta])){
            return [$this->complexCoreToNetMapping[$protocol][$internalId][$internalMeta], 0];
        }
        if(array_key_exists($internalId, $this->simpleCoreToNetMapping[$protocol])){
            return [$this->simpleCoreToNetMapping[$protocol][$internalId], $internalMeta];
        }

        return ItemTranslator::getInstance()->toNetworkId($internalId, $internalMeta); // custom item check
    }

    /**
     * @return int[]
     * @phpstan-return array{int, int}
     */
    public function fromNetworkId(int $networkId, int $networkMeta, ?bool &$isComplexMapping = null, int $protocol) : array{
        if(isset($this->complexNetToCoreMapping[$protocol][$networkId])){
            if($networkMeta !== 0){
                throw new \UnexpectedValueException("Unexpected non-zero network meta on complex item mapping");
            }
            $isComplexMapping = true;
            return $this->complexNetToCoreMapping[$protocol][$networkId];
        }
        $isComplexMapping = false;
        if(isset($this->simpleNetToCoreMapping[$protocol][$networkId])){
            return [$this->simpleNetToCoreMapping[$protocol][$networkId], $networkMeta];
        }
        return ItemTranslator::getInstance()->fromNetworkId($networkId, $networkMeta, $isComplexMapping); // custom item check
    }

    /**
     * @return int[]
     * @phpstan-return array{int, int}
     */
    public function fromNetworkIdWithWildcardHandling(int $networkId, int $networkMeta, int $protocol) : array{
        $isComplexMapping = false;
        if($networkMeta !== 0x7fff){
            $null = null;
            return $this->fromNetworkId($networkId, $networkMeta, $null, $protocol);
        }
        [$id, $meta] = $this->fromNetworkId($networkId, 0, $isComplexMapping, $protocol);
        return [$id, $isComplexMapping ? $meta : -1];
    }
}