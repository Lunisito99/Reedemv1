<?php

namespace Redeem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\item\ItemFactory;

class Main extends PluginBase {

    private Config $codes;
    private Config $used;

    public function onEnable(): void {
        @mkdir($this->getDataFolder()); // crea /plugin_data/Redeem si no existe

        $this->saveResource("codes.yml"); // copia desde /resources a /plugin_data si no existe
        $this->codes = new Config($this->getDataFolder() . "codes.yml", Config::YAML);
        $this->used = new Config($this->getDataFolder() . "used.yml", Config::YAML);

        $this->getLogger()->info("§a[Redeem] Plugin activado.");
    }

    public function onDisable(): void {
        $this->used->save();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "code") {
            if (!$sender instanceof Player) {
                $sender->sendMessage("§cEste comando solo lo puede usar un jugador.");
                return true;
            }

            if (count($args) !== 1) {
                $sender->sendMessage("§eUsa: /code <código>");
                return true;
            }

            $code = strtoupper($args[0]);
            $playerName = strtolower($sender->getName());

            if (!$this->codes->exists($code)) {
                $sender->sendMessage("§cCódigo inválido o no registrado.");
                return true;
            }

            $usedCodes = $this->used->get($playerName, []);
            if (in_array($code, $usedCodes)) {
                $sender->sendMessage("§eYa has usado este código.");
                return true;
            }

            $data = $this->codes->get($code);
            $itemId = (int)($data["id"] ?? 0);
            $amount = (int)($data["amount"] ?? 1);

            $item = ItemFactory::getInstance()->get($itemId, 0, $amount);
            $sender->getInventory()->addItem($item);
            $sender->sendMessage("§aCódigo canjeado. Recibiste $amount del ítem ID $itemId.");

            $usedCodes[] = $code;
            $this->used->set($playerName, $usedCodes);
            $this->used->save();
            return true;
        }

        return false;
    }
}
