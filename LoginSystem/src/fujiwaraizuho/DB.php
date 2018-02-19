<?php
/**
 * Created by PhpStorm.
 * User: izuho
 * Date: 2018/02/12
 * Time: 22:34
 */

namespace fujiwaraizuho;

/* Base */
use pocketmine\Player;

/* Utils */
use pocketmine\utils\MainLogger;


class DB
{
	public function __construct(string $dir, Login $owner)
	{
		$this->owner = $owner;

		$this->db = new \SQLite3($dir ."data.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS userdata (
				name TEXT PRIMARY KEY,
				pass TEXT,
				ip   TEXT,
				xuid TEXT,
				lang TEXT
		)");
	}


	/**
	 * @param Player $player
	 */
	public function register(Player $player)
	{
		$value = "INSERT INTO userdata (name, pass, ip, xuid, lang) VALUES (:name, :pass, :ip, :xuid, :lang)";
		$db = $this->db->prepare($value);

		$name = strtolower($player->getName());
		$pass = $player->pass;
		$ip   = $player->getAddress();
		$xuid = $player->getXuid();
		$lang = $player->lang;

		$pass_hash = password_hash($pass, PASSWORD_DEFAULT);

		$db->bindValue(":name", $name     , SQLITE3_TEXT);
		$db->bindValue(":pass", $pass_hash, SQLITE3_TEXT);
		$db->bindValue(":ip"  , $ip       , SQLITE3_TEXT);
		$db->bindValue(":xuid", $xuid     , SQLITE3_TEXT);
		$db->bindValue(":lang", $lang     , SQLITE3_TEXT);

		$db->execute();

		unset($player->lang);
		unset($player->pass);

		MainLogger::getLogger()->notice($name ." Register Account！");

		$player->setImmobile(false);
	}


	/**
	 * @param Player $player
	 * @return bool
	 * @info アカウントあればtrue、アカウントがなければfalseを返す
	 */
	public function isRegister(Player $player): bool
	{
		$name = strtolower($player->getName());

		$value = "SELECT name FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

		$result = $db->execute()->fetchArray(SQLITE3_ASSOC);

		if (empty($result)) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * @param Player $player
	 */
	public function unRegister(Player $player)
	{
		$name = strtolower($player->getName());

		MainLogger::getLogger()->info("§a". $name ." Delete Account Start...");
		$value = "DELETE FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

		$db->execute();

		MainLogger::getLogger()->info("§a". $name ." Deleted Account！");
	}


	/**
	 * @param Player $player
	 * @return bool|null
	 * @info ログインが必要であればtrue、必要なければfalseを返す
	 */
	public function login(Player $player)
	{
		$name = strtolower($player->getName());
		$xuid = $player->getXuid();
		$ip   = $player->getAddress();

		$result = $this->isRegister($player);

		if ($result) {
			$data = $this->getUserData($player);
			if (is_null($data)) return;
			if ($data["name"] === $name && $data["xuid"] === $xuid) {
				if ($data["ip"] == $ip) {
					return false;
				} else {
					return true;
				}
			} else {
				return null;
			}
		}
	}


	/**
	 * @param Player $player
	 */
	public function updateIp(Player $player)
	{
		$name = strtolower($player->getName());
		$ip   = $player->getAddress();

		$value = "UPDATE userdata SET ip = :ip WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);
		$db->bindValue(":ip"  , $ip  , SQLITE3_TEXT);

		$db->execute();

		MainLogger::getLogger()->notice($name ." Updated IPAddress！");
	}


	/**
	 * @param $player
	 * @return array|bool
	 */
	public function getUserData($player)
	{
		$name = strtolower($player->getName());
		$data = [];

		$value = "SELECT * FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

		$result = $db->execute()->fetchArray(SQLITE3_ASSOC);

		if (empty($result)) return null;

		foreach ($result as $key => $value) {
			$data[$key] = $value;
		}

		return $data;
	}


	/**
	 * @param Player $player
	 * @return array|bool
	 * @info jpn|eng
	 */
	public function getLang(Player $player)
	{
		$name = strtolower($player->getName());

		$value = "SELECT lang FROM userdata WHERE name = :name";
		$db = $this->db->prepare($value);

		$db->bindValue(":name", $name, SQLITE3_TEXT);

		$result = $db->execute()->fetchArray(SQLITE3_ASSOC);

		if (empty($result)) return false;

		return $result;
	}

}