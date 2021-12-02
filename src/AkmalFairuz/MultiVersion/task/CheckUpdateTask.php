<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\task;

use AkmalFairuz\MultiVersion\Loader;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;
use function date;
use function json_decode;

class CheckUpdateTask extends AsyncTask{

    public static function init(string $pluginVersion){
        Server::getInstance()->getAsyncPool()->submitTask(new self($pluginVersion));
    }

    const PLUGIN_NAME = "MultiVersion";

    const API_URL = "https://poggit.pmmp.io/releases.min.json?name=" . self::PLUGIN_NAME;

    /** @var string */
    private $currentVersion;

    public function __construct(string $currentVersion){
        $this->currentVersion = $currentVersion;
    }

    public function onRun() : void{
        try{
            $response = json_decode(Internet::getURL(self::API_URL), true);
            $ver = $this->currentVersion;
            $api = null;
            $downloadUrl = null;
            $date = null;
            foreach($response as $plugins) {
                if(version_compare($ver, $plugins["version"], ">=")){
                    continue;
                }
                $ver = $plugins["version"];
                $api = $plugins["api"][0]["from"] . " - " . $plugins["api"][0]["to"];
                $downloadUrl = $plugins["artifact_url"] . "/" . self::PLUGIN_NAME . ".phar";
                $date = $plugins["last_state_change_date"];
            }
            if($ver === $this->currentVersion) {
                $this->setResult(null);
            } else{
                $this->setResult([$ver, $api, $downloadUrl, $date]);
            }
        } catch(\Throwable $exception) {
            $this->setResult(null);
        }
    }

    public function onCompletion(Server $server) : void{
        $res = $this->getResult();
        if($res === null) {
            return;
        }
        [$ver, $api, $url, $date] = $res;
        $date = date("d F y H:i", $date) . " UTC";
        $messages = [
            "=========== Update Available ===========",
            "Version: " . $ver,
            "Time: " . $date,
            "API: " . $api,
            "Download URL: " . $url,
            "========================================"
        ];
        foreach($messages as $m) {
            Loader::getInstance()->getLogger()->notice(TextFormat::GOLD . $m);
        }
    }
}