<?php

declare(strict_types=1);

namespace terpz710\deathsigns;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;

use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\block\VanillaBlocks;

use pocketmine\utils\Config;

use pocketmine\world\Position;
use pocketmine\world\BlockTransaction;

use DaPigGuy\libPiggyUpdateChecker\libPiggyUpdateChecker;

class DeathSigns extends PluginBase implements Listener {

    protected function onEnable() : void{
        $this->checkVirion();
        
        $this->saveDefaultConfig();
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        libPiggyUpdateChecker::init($this);
    }

    private function checkVirion() : void{
        foreach (
            [
                "libPiggyUpdateChecker" => libPiggyUpdateChecker::class
            ] as $virion => $class
        ) {
            if (!class_exists($class)) {
                $this->getLogger()->error($virion . " virion not found. Download DeathSigns at https://poggit.pmmp.io/p/DeathSign");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event) : void{
        $player = $event->getPlayer();
        $playerName = $player->getName();
        $deathLocation = $player->getPosition();
        $config = $this->getConfig();
        $allWorlds = $config->get("all_worlds", true);
        $allowedWorlds = $config->get("worlds", []);
        
        if ($allWorlds || in_array($deathLocation->getWorld()->getFolderName(), $allowedWorlds)) {
            $this->createDeathSign($deathLocation, $playerName);
        }
    }

    public function createDeathSign(Position $position, string $playerName) : void{
        $world = $position->getWorld();
        $transaction = new BlockTransaction($world);
        $signBlock = VanillaBlocks::OAK_SIGN();
        
        $transaction->addBlock($position, $signBlock);
        $transaction->apply();
        
        $date = date("m/d/Y");
        $config = $this->getConfig();
        $signText = $config->get("sign_text");
        $signText = str_replace("{player}", $playerName, $signText);
        $signText = str_replace("{date}", $date, $signText);
        $signTile = $world->getTile($position);
        
        if ($signTile instanceof Sign) {
            $signTile->setText(new SignText($signText));
        }
    }
}
