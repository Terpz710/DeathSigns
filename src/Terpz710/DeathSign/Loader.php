<?php

declare(strict_types=1);

namespace Terpz710\DeathSign;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\BlockTransaction;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class Loader extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
    }

    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $victimName = $player->getName();
        $cause = $player->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if ($killer instanceof Player) {
                $killerName = $killer->getName();
                $deathLocation = $player->getPosition();
                $config = $this->getConfig();
                $allWorlds = $config->get("all_worlds", true);
                $allowedWorlds = $config->get("worlds", []);
                if ($allWorlds || in_array($deathLocation->getWorld()->getFolderName(), $allowedWorlds)) {
                    $this->createDeathSign($deathLocation, $victimName, $killerName);
                }
            }
        }
    }

    public function createDeathSign(Position $position, string $victimName, string $killerName) {
        $world = $position->getWorld();
        $transaction = new BlockTransaction($world);
        $signBlock = VanillaBlocks::OAK_SIGN();
        $transaction->addBlock($position, $signBlock);
        $transaction->apply();
        $date = date("m/d/Y");
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $signText = $config->get("sign_text");
        $signText = str_replace("{player}", $victimName, $signText);
        $signText = str_replace("{killer}", $killerName, $signText);
        $signText = str_replace("{date}", $date, $signText);
        $signTile = $world->getTile($position);
        if ($signTile instanceof Sign) {
            $signTile->setText(new SignText($signText));
        }
    }
}
