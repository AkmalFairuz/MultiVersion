<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\task;

use AkmalFairuz\MultiVersion\Loader;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function var_dump;
use function zlib_decode;

class DecompressTask extends AsyncTask{

    /** @var string */
    private $buffer;

    /** @var bool */
    private $fail = false;

    public function __construct(BatchPacket $packet, callable $callback) {
        $packet->get(1);
        $this->buffer = $packet->getRemaining();
        $this->storeLocal([$packet, $callback]);
    }

    public function onRun(){
        try{
            $this->setResult(zlib_decode($this->buffer, 1024 * 1024 * 2));
        } catch(\Exception $e) {
            var_dump($e);
            $this->fail = true;
        }
    }

    public function onCompletion(Server $server){
        if($this->fail) {
            Loader::getInstance()->getLogger()->error("Failed to decompress batch packet");
            return;
        }
        [$packet, $callback] = $this->fetchLocal();
        /** @var BatchPacket $packet */
        $packet->isEncoded = false;
        $packet->offset = 0;
        $packet->payload = $this->getResult();
        $callback($packet);
    }
}