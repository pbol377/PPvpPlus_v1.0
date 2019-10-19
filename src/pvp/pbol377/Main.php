<?php
namespace pvp\pbol377;

use pocketmine\entity\Entity;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener{
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents ($this, $this);
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
        $this->db = $this->data->getAll();
        $this->game = new Config($this->getDataFolder() . "game.yml", Config::YAML);
        $this->gm = $this->game->getAll();
        $this->alldata = new Config($this->getDataFolder() . "alldata.yml", Config::YAML);
        $this->ad = $this->alldata->getAll();
        $this->point = new Config($this->getDataFolder() . "point.yml", Config::YAML);
        $this->pt = $this->point->getAll();
        $this->gamedata = new Config($this->getDataFolder() . "gamedata.yml", Config::YAML,[
      "ges" => "0:0:0:world",
      "gsp" => "0:0:0:0:0:0:world",
      "gt" => 40
         ]);
         $this->gd= $this->gamedata->getAll();
        if(!isset($this->db)) $this->db=[];
        $ttask = new TTask($this);
         $this->getScheduler()-> scheduleRepeatingTask($ttask, 20);
		}
		
	public function onDisable(){
		unset($this->db);
		}
	
	public function onCommand(Commandsender $sender, Command $command, string $label, array $args) : bool{
	$name = $sender->getName();
	$cmd = $command->getName();
	$pf="§f[ §6대전 §f] §7";/*
	if (!$sender instanceof Player) {
		$sender->sendMessage("§c§lProhibited in Console");
		return true;
		}*/
	if($cmd == "대전"){
		if(!isset($args[0])){
			$sender->sendMessage($pf."/대전 < 입장 | 퇴장 | 대기열 | 순위 | 내포인트 >");
			if($sender->isOp()){
				$sender->sendMessage($pf."/대전 < 스폰설정 | 시간설정 >");
				$sender->sendMessage($pf."/대전 < 스폰설정 > < x1:y1:z1:x2:y2:z2:(월드명) >");
				$sender->sendMessage($pf."/대전 < 대기실설정 > < x1:y1:z1:(월드명) >");
				$sender->sendMessage($pf."/대전 < 시간설정 > < (초) >");
				return true;
				}
			}
		else{
			switch($args[0]){
				case "입장":
				array_push($this->db, $name);
				$sender->sendMessage($pf."대기열에 참여하셨습니다. \n==============================\n진행중인 대전이 종료되면 매칭이 시작됩니다.\n==============================");
				break;
				
				case "퇴장":
				if($this->gm["player1"]==$name){
					$sender->sendMessage($pf."이미 대전이 시작됬습니다. 퇴장이 불가합니다.");
					break;
					}
				else if($this->gm["player2"]==$name){
					$sender->sendMessage($pf."이미 대전이 시작됬습니다. 퇴장이 불가합니다.");
					break;
					}
					
				if(!isset($this->db[$name])){
					$sender->sendMessage($pf."대기열에 참가하지 않으셨습니다. 확인 후 퇴장해주시기 바랍니다");
					break;
					}
				unset($this->db[$name]);
				$sender->sendMessage($pf."성공적으로 대기가 취소되었습니다");
				break;
				
				case "대기열":
				foreach($this->db as $key => $val){
					$sender->sendMessage($pf."[ {$key} ] :: {$val}");
					}
				break;
				
				case "순위":
				$this->onRank($sender);
				break;
				
				case "내포인트":
				$sender->sendMessage($pf.$this->pt[$name]."포인트");
				break;
				
				case "스폰설정":
				if($sender->isOp()){
					if(!isset($args[1])){
						$sender->sendMessage($pf."/대전 < 스폰설정 > < x1:y1:z1:x2:y2:z2:(월드명) >");
						break;
						}
					else{
						$this->gd["gsp"]=$args[1];
						$sender->sendMessage($pf."스폰설정이 완료되었습니다");
						break;
						}
					}
				break;
				
				case "시간설정":
				if($sender->isOp()){
					if(!isset($args[1])){
						$sender->sendMessage($pf."/대전 < 시간설정 > < (초) >");
						break;
						}
					else{
						$this->gd["gt"]=$args[1];
						$sender->sendMessage($pf."시간설정이 완료되었습니다");
						break;
						}
					}
					break;
					
				case "대기실설정":
				if($sender->isOp()){
					if(!isset($args[1])){
						$sender->sendMessage($pf."/대전 < 대기실설정 > < x1:y1:z1:(월드명) >");
						break;
						}
					else{
						$this->gd["ges"]=$args[1];
						$sender->sendMessage($pf."대기실 스폰설정이 완료되었습니다");
						break;
						}
					}
				break;
					
				default:
				$sender->sendMessage($pf."/대전 < 입장 | 퇴장 | 대기열 | 순위 | 내포인트 >");
			    if($sender->isOp()){
				$sender->sendMessage($pf."/대전 < 스폰설정 | 시간설정 >");
				$sender->sendMessage($pf."/대전 < 스폰설정 > < x1:y1:z1:x2:y2:z2:(월드명) >");
				$sender->sendMessage($pf."/대전 < 대기실설정 > < x1:y1:z1:(월드명) >");
				$sender->sendMessage($pf."/대전 < 시간설정 > < (초) >");
				break;
				}
				break;
				
				}
				$this->save();
				return true;
			}
		}
		return true;
	}
	
	//중간의 내용은 최종보고서에 탑재되어 있습니다. 참고 부탁드립니다. 깃헙 커밋시 확인 후 프라이벗 접근권한 드리겠습니다.
	
	class TTask extends Task{
	private $owner;
	public function __construct(Main $owner){
				$this->owner = $owner;
			}
	public function onRun( $currentTick ) {
		$this->owner->Check();
		}//onrun
	}//class
