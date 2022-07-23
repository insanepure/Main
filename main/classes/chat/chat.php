<?php
include_once 'chatuser.php';
include_once 'chatmessage.php';

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;

class Chat
{
	private $database;
  
  private $messages;
  private $users;
  
  private $channel;
  
  private $user = null;
  
  
	function __construct($db, $sessionID)
  {
    $this->database = $db;
    $this->user = $this->GetUserBySession($sessionID);
    $this->UpdateLastAction();
  }
  
  static function GetUserCount($db, $channel)
  {
    return $db->CountRows('chatusers', 'channel="'.$channel.'"');
  }
  
  function PostToDiscord($message)
  {
    $message = html_entity_decode($message);
    $message = str_replace("@", "@ ", $message);
    if(strtolower($this->GetChannel()) == 'dbbg')
    {
      $data = array("content" => $message, "username" => "DBBG-BoT");
      $curl = curl_init("https://discordapp.com/api/webhooks/617677516953616394/cuExCxLgYkVDnMJI3Dq1o4xcXpYHR9efx-K3p2y_9C-B2W3S7Dc-caiKBxwqYy40ummd");
    }
    else if(strtolower($this->GetChannel()) == 'nbg')
    {
      $data = array("content" => $message, "username" => "NBG-BoT");
      $curl = curl_init("https://discordapp.com/api/webhooks/617678307126673416/FrPeIz5GysHC4GJw_8V_UgMogVCRsZC6B_uWwgnPf4ReuCuIeTZVr6hYwUQ7z454C0AM");
    }
    else
      return;
    
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      return curl_exec($curl);
  }
  
  public function IsLogged()
  {
    return $this->user != null;
  }
  
  public function IsAdmin()
  {
    if($this->user == null)
      return;
    
    return $this->user->GetAdmin() >= 2;
  }
  
	public function GetUserBySession($sessionID)
	{
    $result = $this->database->Select('*', 'chatusers', 'sessionid="'.$sessionID.'"', 1);
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
        while($row = $result->fetch_assoc()) 
        {
          $this->SetChannel($row['channel']);
          return new ChatUser($row);
        }
			}
			$result->close();
		}
    
    return null;
  }
  
  public function Report($login)
  {
    $email = 'pure@db-bg.de';
    $sender   = "noreply@db-bg.de";
    
    $userName = $this->user->GetName();
    $userGame = $this->user->GetGame();
    $reporter = '['.$userGame.'] '.$userName.' ('.$login.')';
    
    $topic = 'Chat Report by - '.$reporter;
    
    
    $this->ReloadMessages(0, 1000);
    $messages = $this->GetMessages();
    
    $content = '';
    $content = $content.'Anzahl an Nachrichten: '.count($messages).'<br/><br/>';
    for($i = 0; $i < count($messages); ++$i)
    {
      $message = $messages[$i];
      $content = $content.'ID: '.$message->GetID().' ';
      $content = $content.'Type: '.$message->GetType().' ';
      $content = $content.'Time: '.$message->GetTime().' ';
      if($message->GetType() == 2)
      {
        $content = $content.$message->GetText().'<br/>';
      }
      else
      {
        if($message->GetAcc() == -2)
        {
        $content = $content.'<b><font color="#00aa00">EVENT: '.$message->GetText().'</font></b><br/>';
        }
        else if($message->GetAcc() == -1)
        {
        $content = $content.'<b><font color="#ff0000">SYSTEM: '.$message->GetText().'</font></b><br/>';
        }
        else if($message->GetAcc() == -4)
        {
        $content = $content.'<b><font color="#0000FF">[HANDEL] '.$message->GetName().': '.$message->GetText().'</font></b><br/>';
        }
        else
        {
          $titel = $message->GetTitel();
          $titelcolor = $message->GetTitelColor();
          if($titelcolor != '')
          {
            $titel = '<font color="#'.$titelcolor.'">'.$titel.'</font>';
          }
          $time = date('H:i', strtotime($message->GetTime()));
          $content = $content.'['.$time.'] ';
          if($message->GetGame() != '')
          {
            $content = $content.'<b>['.$message->GetGame().']</b> ';
          }
          $content = $content.'<b>';
          if($message->GetAcc() > 0)
          {
            if($message->GetGame() == 'DBBG')
            {
              $url = 'https://db-bg.de?p=profil&id='.$message->GetAcc();
            }
            else
            {
              $url = 'https://n-bg.de/user.php?id='.$message->GetAcc();
            }
            $content = $content.'<a target="_blank" href="'.$url.'">'.$titel.' '.$message->GetName().'</a></b>: ';
          }
          else
          {
            $content = $content.$titel.' '.$message->GetName().'</b>:';
          }
  
          $content = $content.' '.$message->GetText().'<br/>';
        }
      }
    }
    
    $mail = new PHPMailer(true);
    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    //Set who the message is to be sent from
    $mail->setFrom($sender, 'BG - Chat - Report');
    //Set an alternative reply-to address
    $mail->addReplyTo($sender, 'BG - Chat - Report');
    //Set who the message is to be sent to
    $mail->addAddress($email, 'User');
    //Set the subject line
    $mail->Subject = $topic;
    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    $mail->AltBody = $content;
    
    $mail->send();
  }
  
  public function ReplaceUser($acc, $main, $game, $name, $admin, $sessionID, $titel, $titelcolor)
  {
		  $result = $this->database->Update('name="'.$name.'",main="'.$main.'",acc="'.$acc.'",game="'.$game.'",admin="'.$admin.'",titel="'.$titel.'",titelcolor="'.$titelcolor.'"','chatusers','sessionid = "'.$sessionID.'"',1);
  }
  
	public function AddUser($acc, $main, $game, $name, $admin, $channel, $sessionID, $titel, $titelcolor)
	{
    if($this->user != null && ($this->user->GetAcc() != $acc || $this->user->GetGame() != $game || $this->user->GetName() != $name || $this->user->GetTitel() != $titel))
    {
      $this->ReplaceUser($acc, $main, $game, $name, $admin, $sessionID, $titel, $titelcolor);
      return;
    }
    
    $result = $this->database->Select('*', 'chatusers', 'acc='.$acc.' AND game="'.$game.'"', 1);
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
        while($row = $result->fetch_assoc()) 
        {
          $row['sessionid'] = $sessionID;
          $this->user = new ChatUser($row);
        }
			}
			$result->close();
		}
    
    if($this->user == null)
    {
      $row['acc'] = $acc;
      $row['main'] = $main;
      $row['game'] = $game;
      $row['name'] = $name;
      $row['admin'] = $admin;
      $row['channel'] = $channel;
      $row['sessionid'] = $sessionID;
      $row['titel'] = $titel;
      $row['titelcolor'] = $titelcolor;
		  $result = $this->database->Insert('acc, main, game, name, admin, channel, sessionid, titel, titelcolor',
                                        '"'.$acc.'","'.$main.'","'.$game.'","'.$name.'","'.$admin.'","'.$channel.'","'.$sessionID.'","'.$titel.'","'.$titelcolor.'"', 'chatusers');
      $row['id'] = $this->database->GetLastID();;
      $this->user = new ChatUser($row);
    }
    else
    {
		  $result = $this->database->Update('sessionid="'.$sessionID.'"','chatusers','id = '.$this->user->GetID().'',1);
    }
  }
  
  public function SwitchChannel($channel)
  {
    if($this->user == null)
      return;
    
    $channel = $this->database->EscapeString($channel);
    
    if($channel == '')
      return;
    
		$this->database->Update('channel="'.$channel.'"','chatusers','id = '.$this->user->GetID().'',1);
    $this->UpdateLastAction();
  }
  
  public function UpdateLastAction()
  {
    if($this->user == null)
      return;
    
		$this->database->Update('lastaction=NOW()','chatusers','id = '.$this->user->GetID().'',1);
  }
  
	public function DeleteMessage($id)
	{
		$type = 2;
		$this->SendMessage($id, $type);
	}
	
  public function ReloadUsers()
  {
    $this->users = array();
    $timeOut = 60 * 5;
    $result = $this->database->Select('*', 'chatusers', 'channel ="'.$this->GetChannel().'"', '9999');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
        while($row = $result->fetch_assoc()) 
        {
          $user = new ChatUser($row);
          array_push($this->users, $user);
        }
			}
			$result->close();
		}
  }
  
  public function ReloadMessages($lasttime = 0, $limit=30)
  {
    $this->messages = array();
		$where = 'channel="'.$this->GetChannel().'"';
    
		if($lasttime != 0)
		{
			$where = '('.$where.') AND time > "'.$lasttime.'"';
		}
    $result = $this->database->Select('*', 'chatmessages', $where, $limit, 'time', 'DESC');
		if ($result) 
		{
			if ($result->num_rows > 0)
			{
        while($row = $result->fetch_assoc()) 
        {
          $message = new ChatMessage($row);
					array_unshift($this->messages, $message);
        }
			}
			else
			{
				return false;
			}
			$result->close();
		}
		return true;
  }
  
  public function SendMessage($text, $type=1)
  {
		$text = htmlentities($this->database->EscapeString($text));
    if($this->user == null && $type == 1 || $type == 1 && (strlen($text) == 0 || strlen(trim($text)) == 0))
    {
      return;
    }
    
    $acc = -1;
    $admin = 0;
    $name = '';
    $game = '';
    $titel = '';
    $main = 0;
    $channel = $this->GetChannel();
    if($type == 1)
    {
      $admin = $this->user->GetAdmin();
      $acc = $this->user->GetAcc();
      $main = $this->user->GetMain();
      $name = $this->user->GetName();
      $game = $this->user->GetGame();
      $titel = $this->user->GetTitel();
      $titelcolor = $this->user->GetTitelColor();
    }
    $timestamp = date('Y-m-d H:i:s');
		if($type == 1)
		{
			$textCommands = explode(' ',$text);
			$command = array_shift($textCommands);
			if($admin >= 3 && $command == '/system')
			{
				$text = implode(' ',$textCommands);
				$name = 'SYSTEM';
				$acc = -1;
			}
			else if($admin >= 3 && $command == '/event')
			{
				$text = implode(' ',$textCommands);
				$name = 'EVENT';
				$acc = -2;
			}
			else if(($command == '/handel' || $command == '/h'))
			{
				$text = implode(' ',$textCommands);
				$acc = -4;
			}
			else if(($command == '/technik' || $command == '/tech'))
			{
				$text = implode(' ',$textCommands);
				$acc = -5;
			}

			else if($admin > 0 && $command == '/s')
			{
				$text = implode(' ',$textCommands);
				$channel = 'Support';
			}
      $time = date('H:i', strtotime($timestamp));
      $discordMSG = '['.$time.'] ['.$game.'] '.$name.': '.$text;
      $this->PostToDiscord($discordMSG);
		}
		$result = $this->database->Insert('name, main, acc, game, text, time, channel, admin, type, titel, titelcolor',
                                      '"'.$name.'","'.$main.'","'.$acc.'","'.$game.'","'.$text.'","'.$timestamp.'","'.$channel.'","'.$admin.'","'.$type.'","'.$titel.'","'.$titelcolor.'"', 'chatmessages');
    $this->UpdateLastAction();
  }
  
  public function GetTeam()
  {
    return $this->team;
  }
  
  public function GetFight()
  {
    return $this->fight;
  }
  
  public function GetMessages()
  {
    return $this->messages;
  }
  
  public function GetUsers()
  {
    return $this->users;
  }
  
  public function GetChannel()
  {
    return $this->channel;
  }
  
  public function SetChannel($value)
  {
    $this->channel = $value;
  }
  
}

?>