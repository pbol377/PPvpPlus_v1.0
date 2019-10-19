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
	
	public function onUseOfCommand(PlayerCommandPreprocessEvent $event){
		$player=$event->getPlayer();
		$name=$player->getName();
		$pf="§f[ §6대전 §f] §7";
		if(isset($this->gm["player1"])){
		if($this->gm["player1"]==$name){
					$player->sendMessage($pf."이미 대전이 시작됬습니다. 명령어 사용이 불가합니다.");
					$event->setCancelled();
					return;
					}
				else if($this->gm["player2"]==$name){
					$player->sendMessage($pf."이미 대전이 시작됬습니다. 명령어 사용이 불가합니다.");
					$event->setCancelled();
					return;
					}
		}
		}
	
	public function onJoin(PlayerJoinEvent $event){
		$player=$event->getPlayer();
		if(!isset($this->ad[$player->getName()])) $this->ad[$player->getName()]=0;
		if(!isset($this->pt[$player->getName()])) $this->pt[$player->getName()]=1000;
		$this->save();
		}
		
	public function onQuit(PlayerQuitEvent $event){
		$pf="§f[ §6대전 §f] §7";
		$player=$event->getPlayer();
		$name=$player->getName();
		if(isset($this->gm["player1"])){
		if($this->gm["player1"]==$name){
		$player2 = $this->getServer()->getPlayer($this->gm["player2"]);
		$pm=$player2->getName();
		$this->pt[$pm]+=50;
		$this->pt[$name]-=30;
		$player2->sendMessage($pf."대전이 종료됬습니다. 대전 승자는 §a".$pm." §7입니다.");
		$player2->sendTip($pf."경기가 종료되었습니다. 승자는 §b".$pm."§7입니다");
		$this->ad[$pm]++;
		if($this->db[0]==$name){
			unset($this->db[0]);
			}
		if($this->db[1]==$player2->getName()){
			unset($this->db[1]);
			}
		$clv=explode(":",$this->gd["ges"]);
		$player2->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		unset($this->gm);
		$this->save();
					return;
					}
				else if($this->gm["player2"]==$name){
					$player1 = $this->getServer()->getPlayer($this->gm["player1"]);
		$pm=$player1->getName();
		$this->pt[$pm]+=50;
		$this->pt[$name]-=30;
		$player1->sendMessage($pf."대전이 종료됬습니다. 대전 승자는 §a".$pm." §7입니다.");
		$player1->sendTip($pf."경기가 종료되었습니다. 승자는 §b".$pm."§7입니다");
		$this->ad[$pm]++;
		if($this->db[1]==$name){
			unset($this->db[1]);
			}
		if($this->db[0]==$player1->getName()){
			unset($this->db[0]);
			}
		$clv=explode(":",$this->gd["ges"]);
		$player1->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		unset($this->gm);
		$this->save();
					return;
					}
		}
	}
	
	public function onDeath(PlayerDeathEvent $event){
		$player=$event->getPlayer();
		$name=$player->getName();
		$pf="§f[ §6대전 §f] §7";
		if(isset($this->gm["player1"])){
			$player1 = $this->getServer()->getPlayer($this->gm["player1"]);
		    $player2 = $this->getServer()->getPlayer($this->gm["player2"]);
		$pn=$player1->getName();
		$pm=$player2->getName();
		$this->pt[$pm]+=50;
		$this->pt[$pn]-=30;
			if($this->gm["player1"]==$name){
				$player1->sendMessage($pf."대전이 종료됬습니다. 대전 승자는 §a".$pm." §7입니다.");
		        $player2->sendMessage($pf."대전이 종료됬습니다. 대전 승자는 §a".$pm." §7입니다.");
				$player1->sendTip($pf."경기가 종료되었습니다. 승자는 §b".$pm."§7입니다");
		        $player2->sendTip($pf."경기가 종료되었습니다. 승자는 §b".$pm."§7입니다");
		$this->ad[$pm]++;
		if($this->db[0]==$player1->getName()){
			unset($this->db[0]);
			}
		if($this->db[1]==$player2->getName()){
			unset($this->db[1]);
			}
		$clv=explode(":",$this->gd["ges"]);
		$player1->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		$player2->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		unset($this->gm);
		$this->save();
		return;
				}
			else if($this->gm["player2"]==$name){
				$this->pt[$pn]+=50;
		        $this->pt[$pm]-=30;
				$player1->sendMessage($pf."대전이 종료됬습니다. 대전 승자는 §a".$pn." §7입니다.");
		        $player2->sendMessage($pf."대전이 종료됬습니다. 대전 승자는 §a".$pn." §7입니다.");
				$player1->sendTip($pf."경기가 종료되었습니다. 승자는 §b".$pn."§7입니다");
		        $player2->sendTip($pf."경기가 종료되었습니다. 승자는 §b".$pn."§7입니다");
		$this->ad[$pn]++;
		if($this->db[0]==$player1->getName()){
			unset($this->db[0]);
			}
		if($this->db[1]==$player2->getName()){
			unset($this->db[1]);
			}
		$clv=explode(":",$this->gd["ges"]);
		$player1->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		$player2->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		unset($this->gm);
		$this->save();
		return;
				}
			}
		}
	
	public function Check(){
		
	$pf="§f[ §6대전 §f] §7";
	if(isset($this->gm["player1"])){
		$player1 = $this->getServer()->getPlayer($this->gm["player1"]);
		$player2 = $this->getServer()->getPlayer($this->gm["player2"]);
		if(!isset($this->gm["tm"])){
			unset($this->gm);
			$this->save();
			return false;
			}
		if($this->gm["tm"]-time()<0){
			if(!is_null($this->getServer()->getPlayer($this->gm["player1"]))){
				$player1->sendTip($pf."경기가 종료되었습니다.");
				}
			if(!is_null($this->getServer()->getPlayer($this->gm["player2"]))){
				$player2->sendTip($pf."경기가 종료되었습니다.");
				}
		if($this->db[0]==$player1->getName()){
			unset($this->db[0]);
			}
		if($this->db[1]==$player2->getName()){
			unset($this->db[1]);
			}
		$clv=explode(":",$this->gd["ges"]);
		$player1->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		$player2->teleport(new Position((float) $clv[0], (float)  $clv[1],(float) $clv[2], $this->getServer()->getLevelByName($clv[3])));
		unset($this->gm);
		$this->save();
		return false;
			}
		else{
			$gt=$this->gm["tm"]-time();
		if($this->gm["tm"]-time()<=10){
			$player1->sendTip($pf."§c§l대전 종료까지 §f".$gt."§c초 남았습니다.");
		    $player2->sendTip($pf."§c§l대전 종료까지 §f".$gt."§c초 남았습니다.");
		return false;
			}
		else{
		if($this->gm["tm"]-time()<=30){
			$player1->sendTip($pf."§e§l대전 종료까지 §f".$gt."§e초 남았습니다.");
		    $player2->sendTip($pf."§e§l대전 종료까지 §f".$gt."§e초 남았습니다.");
		return false;
			}
		else{
			$player1->sendTip($pf."§a§l대전 종료까지 §f".$gt."§a초 남았습니다.");
		    $player2->sendTip($pf."§a§l대전 종료까지 §f".$gt."§a초 남았습니다.");
		return false;
			}
			}
			}
		}
	if(isset($this->db[0]) && isset($this->db[1])){
		$this->gm["player1"]=$this->db[0];
		$this->gm["player2"]=$this->db[1];
		foreach($this->db as $key => $val){
			if($key>=2){
			$this->db[$key-2]=$this->db[$key];
			}
		}
		if(!is_null($this->getServer()->getPlayer($this->gm["player1"]))){
			if(!is_null($this->getServer()->getPlayer($this->gm["player2"]))){
		$player1 = $this->getServer()->getPlayer($this->gm["player1"]);
		$player2 = $this->getServer()->getPlayer($this->gm["player2"]);
		$clv=explode(":",$this->gd["gsp"]);
		$player1->teleport(new Position((float) $clv[0], (float)  $clv[1], (float) $clv[2], $this->getServer()->getLevelByName($clv[6])));
		$player2->teleport(new Position((float) $clv[3], (float)  $clv[4],(float) $clv[5], $this->getServer()->getLevelByName($clv[6])));
		$this->gm["tm"]=time()+$this->gd["gt"];
		$player1->sendMessage($pf."대전이 시작됬습니다. 대전 상대는 §a".$player2->getName()." §7입니다.");
		$player2->sendMessage($pf."대전이 시작됬습니다. 대전 상대는 §a".$player1->getName()." §7입니다.");
		$this->save();
		return false;
		}
		}
		}
	}
	
	public function merge_sort($my_array){
if(count($my_array) == 1 ) return $my_array;
$mid = count($my_array) / 2;
$left = array_slice($my_array, 0, $mid);
$right = array_slice($my_array, $mid);
$left = merge_sort($left);
$right = merge_sort($right);
return merge($left, $right);
}
public function merge($left, $right){
$res = array();
while (count($left) > 0 && count($right) > 0){
if($left[0] > $right[0]){
$res[] = $right[0];
$right = array_slice($right , 1);
}else{
$res[] = $left[0];
$left = array_slice($left, 1);
}
}
while (count($left) > 0){
$res[] = $left[0];
$left = array_slice($left, 1);
}
while (count($right) > 0){
$res[] = $right[0];
$right = array_slice($right, 1);
}
return $res;
}

public function onRank($player){
$prefix="§f[ §6대전 §f] §7";
$ary=[];
$point=$this->pt;
foreach($this->pt as $key => $val){
array_push($ary,$val);
}
$arr=$this->merge_sort($ary);
foreach($arr as $key => $val){
foreach($point as $key1 => $val1){
if($val1==$val){
$key2=$key+1;
$player->sendMessage($prefix.$key2."위 ".$key1."\n");
unset($point[$key1]);
}
}
}
return ;
}
	
	public function save(){
		$this->data->setAll($this->db);
		$this->data->save();
		$this->point->setAll($this->pt);
		$this->point->save();
		$this->gamedata->setAll($this->gd);
		$this->gamedata->save();
		$this->alldata->setAll($this->ad);
		$this->alldata->save();
		}
	
}

	
	class TTask extends Task{
	private $owner;
	public function __construct(Main $owner){
				$this->owner = $owner;
			}
	public function onRun( $currentTick ) {
		$this->owner->Check();
		}//onrun
	}//class
