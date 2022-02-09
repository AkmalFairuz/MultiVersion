<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\convert;

use AkmalFairuz\MultiVersion\Loader;
use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use function array_key_exists;
use function file_get_contents;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;

class MultiVersionItemTypeDictionary{
    use SingletonTrait;

    /**
     * @var ItemTypeEntry[][]
     */
    private $itemTypes;
    /**
     * @var string[][]
     */
    private $intToStringIdMap = [];
    /**
     * @var int[][]
     */
    private $stringToIntMap = [];

    const PROTOCOL = [
        ProtocolConstants::BEDROCK_1_16_220 => "_1_16_220",
        ProtocolConstants::BEDROCK_1_17_0 => "_1_17_0",
        ProtocolConstants::BEDROCK_1_17_10 => "_1_17_10",
        ProtocolConstants::BEDROCK_1_17_30 => "_1_17_30",
        ProtocolConstants::BEDROCK_1_17_40 => "_1_17_40",
        ProtocolConstants::BEDROCK_1_18_0 => "_1_18_0",
    ];

    private static function make() : self{
        $itemTypes = [];
        foreach(self::PROTOCOL as $protocol => $file){
            if(Loader::getInstance()->isProtocolDisabled($protocol)) {
                continue;
            }
            $data = file_get_contents(Loader::$resourcesPath . 'vanilla/required_item_list'.$file.'.json');
            if($data === false) throw new AssumptionFailedError("Missing required resource file");
            $table = json_decode($data, true);
            if(!is_array($table)){
                throw new AssumptionFailedError("Invalid item list format");
            }

            $params = [];
            foreach($table as $name => $entry){
                if(!is_array($entry) || !is_string($name) || !isset($entry["component_based"], $entry["runtime_id"]) || !is_bool($entry["component_based"]) || !is_int($entry["runtime_id"])){
                    throw new AssumptionFailedError("Invalid item list format");
                }
                $params[] = new ItemTypeEntry($name, $entry["runtime_id"], $entry["component_based"]);
            }
            $itemTypes[$protocol] = $params;
        }
        return new self($itemTypes);
    }

    /**
     * @param ItemTypeEntry[][] $itemTypes
     */
    public function __construct(array $itemTypes){
        $this->itemTypes = $itemTypes;
        foreach($this->itemTypes as $protocol => $types){
            foreach($types as $type){
                $this->stringToIntMap[$protocol][$type->getStringId()] = $type->getNumericId();
                $this->intToStringIdMap[$protocol][$type->getNumericId()] = $type->getStringId();
            }
        }
    }

    /**
     * @param int $protocol
     * @return ItemTypeEntry[]
     * @phpstan-return list<ItemTypeEntry>
     */
    public function getEntries(int $protocol) : array{
        return $this->itemTypes[$protocol];
    }

    public function getAllEntries() {
        return $this->itemTypes;
    }

    public function fromStringId(string $stringId, int $protocol) : int{
        if(!array_key_exists($stringId, $this->stringToIntMap[$protocol])){
            return ItemTypeDictionary::getInstance()->fromStringId($stringId); // custom item check
        }
        return $this->stringToIntMap[$protocol][$stringId];
    }

    public function fromIntId(int $intId, int $protocol) : string{
        if(!array_key_exists($intId, $this->intToStringIdMap[$protocol])){
            return ItemTypeDictionary::getInstance()->fromIntId($intId); // custom item check
        }
        return $this->intToStringIdMap[$protocol][$intId];
    }
}