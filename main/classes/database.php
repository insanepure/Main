<?php


//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;

class Database
{
	//Variables
	private $mysqli;
	private $host = 'localhost';
	
	private $selectCount;
	private $updateCount;
	private $insertCount;
	private $deleteCount;
	private $truncateCount;
	private $copyCount;
	private $countCounts;
	private $debug = false;
	private $selectEnabled = true;
	private $updateEnabled = true;
	private $insertEnabled = true;
	private $deleteEnabled = true;
	private $truncateEnabled = true;
	private $copyEnabled = true;
	private $countEnabled = true;
  private $dbName = '';
	
	//Constructor
	function __construct($db, $user, $pw) 
	{
    $this->dbName = $db;
    $this->trackTable = null;
		$this->mysqli = new mysqli($this->host, $user, $pw, $db);
    $this->mysqli->set_charset("utf8");
		if ($this->mysqli->connect_errno) 
		{
			//die("Verbindung fehlgeschlagen: " . $this->mysqli->connect_error);
      include_once '///404.php';
		}
		
		$this->selectCount = 0;
		$this->updateCount = 0;
		$this->insertCount = 0;
		$this->deleteCount = 0;
		$this->truncateCount = 0;
		$this->copyCount = 0;
		$this->countCounts = 0;
	}
	
	//Destructor
	function __destruct() 
	{
    $thread = $this->mysqli->thread_id;
    $this->mysqli->kill($thread);
    $this->mysqli->close();
	}
	
	public function Debug()
	{
		$this->debug = true;
	}
	
	public function HasBadWords($text)
	{
		$badwords = array("penis", 
											"hrensohn", 
											"hure", 
											"muschi", 
											"arsch", 
											"mistgeburt", 
											"wichser", 
											"wixxer", 
											"nigger", 
											"neger", 
											"vagina", 
											"bastard", 
											"bastart",
											"schwuchtel",
											"azzlack",
											"scheiße",
											"shit",
											"scheisse",
											"schwanz",
											"opfer",
											"noob",
											"fotze",
											"schlampe",
											"nutte",
											"kanacke",
											"Wichs",
											"geh sterben",
											"hoden",
											"mistkind",
											"fick",

								);
		$hasBadWords = 0;
		
		$text = str_replace('3', 'e', $text);
		$text = str_replace('1', 'i', $text);
		$text = str_replace('7', 't', $text);
		$text = str_replace('5', 's', $text);
		$text = preg_replace('/[^A-Za-z ]/', "", $text);
		$text = str_ireplace($badwords, '****', $text, $hasBadWords);
		return $hasBadWords != 0;
	}
	
	public function CountEnable($value)
	{
		$this->countEnabled = $value;
	}
	
	public function TruncateEnable($value)
	{
		$this->truncateEnabled = $value;
	}
	
	public function CopyEnable($value)
	{
		$this->copyEnabled = $value;
	}
	
	public function SelectEnable($value)
	{
		$this->selectEnabled = $value;
	}
	
	public function UpdateEnable($value)
	{
		$this->updateEnabled = $value;
	}
	
	public function InsertEnable($value)
	{
		$this->insertEnabled = $value;
	}
	
	public function DeleteEnable($value)
	{
		$this->deleteEnabled = $value;
	}
	
	public function IsCountEnabled()
	{
		return $this->countEnabled;
	}
	
	public function IsTruncateEnabled()
	{
		return $this->truncateEnabled;
	}
	
	public function IsCopyEnabled()
	{
		return $this->copyEnabled;
	}
	
	public function IsSelectEnabled()
	{
		return $this->selectEnabled;
	}
	
	public function IsUpdateEnabled()
	{
		return $this->updateEnabled;
	}
	
	public function IsInsertEnabled()
	{
		return $this->insertEnabled;
	}
	
	public function IsDeleteEnabled()
	{
		return $this->deleteEnabled;
	}
  
	private function GetIP()
	{
    $ip = hash('sha256', 'itsa'.$this->GetRealIP().'ip');
    return $ip;
	}
  
	private function GetRealIP()
	{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $ip  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    return $ip;
	}
  
  private function runSQL($sql, $isMulti=false)
  {
    $ip = $this->GetIP();
    $time = date('Y-m-d H:i:s');
    
    $result = null;
    if(!$isMulti)
		  $result = $this->mysqli->query($sql);
    else
    {
		  $result = $this->mysqli->multi_query($sql);
  	  while (mysqli_next_result($this->mysqli));
    }
    return $result;
  }
	
	private function FormatSQL($sql, $where, $join, $limit, $order, $orderType, $group='')
	{
    
		if($join != '')
		{
		$sql = $sql.' JOIN '.$join;
		}
    
		if ($where != '')
		{
			$sql = $sql.' WHERE '.$where;
		}
		
		if($group != '')
		{
		$sql = $sql.' GROUP BY '.$group;
		}
		
		if ($order != '')
		{
			$sql = $sql.' ORDER BY '.$order.' '.$orderType;
		}
		
		if($limit != '')
		{
			$sql = $sql.' LIMIT '.$limit;
		}
		
		return $sql;
	}
  
	public function Error()
	{
		return $this->mysqli->error;
	}
	
	public function ShowColumns($table)
	{
		$sql = 'SHOW COLUMNS from '.$table;
    return $this->runSQL($sql);
	}
	public function ShowTables()
	{
		$sql = 'SHOW TABLES';
    return $this->runSQL($sql);
	}
	
	public function CountRows($table, $where='', $limit = '', $order = '', $orderType='ASC', $join='', $group='')
	{
		$sql = 'SELECT * FROM '.$table;
		$sql = $this->FormatSQL($sql, $where, $join, $limit, $order, $orderType, $group);
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->countEnabled)
		{
			return true;
		}
		
		$this->countCounts++;
		$result = $this->runSQL($sql);
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    return $result->num_rows;
	}
	
	public function Select($variables, $table, $where='', $limit = '', $order = '', $orderType='ASC', $join='', $group='')
	{
		$sql = 'SELECT '.$variables.' FROM '.$table;
		$sql = $this->FormatSQL($sql, $where, $join, $limit, $order, $orderType, $group);
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->selectEnabled)
		{
			return true;
		}
		
		$this->selectCount++;
		
    $msc = microtime(true);
    $result = $this->runSQL($sql);
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    $msc = microtime(true)-$msc;
    $this->Report($msc, 'SQL: '.$sql.'<br/>', 'Database Report - '.$this->dbName);
    
    return $result;
	}
	
	public function GetError()
	{
		return $this->mysqli->error;
	}
	
	public function Update($variables, $table, $where='', $limit = '',$order='',$orderType='ASC', $set='')
	{
		$sql = '';
		if($set != '')
		{
			$sql = $set;
		}
		$sql = $sql.' UPDATE '.$table.' SET '.$variables;
    $join = '';
		$sql = $this->FormatSQL($sql, $where, $join, $limit,$order,$orderType);
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->updateEnabled)
		{
			return true;
		}
		
		$this->updateCount++;
    
    $msc = microtime(true);
		if($set == '')
		{
			$result = $this->runSQL($sql);
		}
    else
    {
      $result = $this->runSQL($sql, true);
    }
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    $msc = microtime(true)-$msc;
    $this->Report($msc, 'SQL: '.$sql.'<br/>', 'Database Report - '.$this->dbName);
    
		return $result;
	}
	
	public function Insert($variables, $values, $table)
	{
		$sql = 'INSERT INTO '.$table.' ('.$variables.') VALUES ('.$values.')';
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->insertEnabled)
		{
			return true;
		}
		
		$this->insertCount++;
    
    $msc = microtime(true);
		$result = $this->runSQL($sql);
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    $msc = microtime(true)-$msc;
    $this->Report($msc, 'SQL: '.$sql.'<br/>', 'Database Report - '.$this->dbName);
    
    return $result;
	}
	
	public function Truncate($table)
	{
		$sql = 'TRUNCATE TABLE '.$table;
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->truncateEnabled)
		{
			return true;
    }
    
    $msc = microtime(true);
		$result = $this->runSQL($sql);
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    $msc = microtime(true)-$msc;
    $this->Report($msc, 'SQL: '.$sql.'<br/>', 'Database Report - '.$this->dbName);
      
    return $result;
	}
	
	public function Delete($table, $where)
	{
		$sql = 'DELETE FROM '.$table.' WHERE '.$where;
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->deleteEnabled)
		{
			return true;
		}
		
		$this->deleteCount++;
    
    $msc = microtime(true);
		$result = $this->runSQL($sql);
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    $msc = microtime(true)-$msc;
    $this->Report($msc, 'SQL: '.$sql.'<br/>', 'Database Report - '.$this->dbName);
    
    return $result;
	}
	
	public function Copy($srcTable, $tgtTable)
	{
    $sql = 'INSERT INTO '.$tgtTable.' SELECT * FROM '.$srcTable;
		
		//For debugging purpose
		if($this->debug)
		{
		echo $sql.'<br/>';
		}
		
		if(!$this->copyEnabled)
		{
			return true;
		}
		
		$this->copyCount++;
    
    $msc = microtime(true);
		$result = $this->runSQL($sql);
		if($this->debug && !$result)
		{
      echo ' - Error message: '. $this->mysqli->error.'<br/>';
		}
    $msc = microtime(true)-$msc;
    $this->Report($msc, 'SQL: '.$sql.'<br/>', 'Database Report - '.$this->dbName);
    
    return $result;
	}
  
  public function Report($seconds, $text, $topic)
  {
    //Disable for now
    return;
    
		if($this->debug)
		{
      echo ' - Took: '.$seconds.' Seconds.<br/>';
    }
    
    
    $currentHour = date('H');
    
    if($seconds <= 0.3 || $currentHour == 23 || $currentHour == 0)
      return;
    
    $email = 'pure@db-bg.de';
    $sender   = "noreply@db-bg.de";
    
    $content = $text;
    $content = $content.'Took '.$seconds.' Seconds<br/>';
    $content = $content.'Callstack: <br/>';
    
    $callstack = debug_backtrace();
    $callStackText = '';
    foreach($callstack as &$singleCall)
    {
      $text = '';
      if(isset($singleCall['function']))
        $text = $singleCall['function'].'()';
      
      $text = $text.' called at Line: ['.$singleCall['file'].':'.$singleCall['line'].']';
      $text = $text.'<br/>';
      
      $callStackText = $callStackText.$text;
    }
    $content = $content.$callStackText;
    
    $mail = new PHPMailer(true);
    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    //Set who the message is to be sent from
    $mail->setFrom($sender, $topic);
    //Set an alternative reply-to address
    $mail->addReplyTo($sender, $topic);
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
	
	public function GetLastID()
	{
		return $this->mysqli->insert_id;
	}
	
	public function EscapeString($string)
	{
		return $this->mysqli->real_escape_string($string);
	}
	
	public function GetSelects()
	{
		return $this->selectCount;
	}
	
	public function GetUpdates()
	{
		return $this->updateCount;
	}
	
	public function GetInserts()
	{
		return $this->insertCount;
	}
	
	public function GetDeletes()
	{
		return $this->deleteCount;
	}
	
	public function GetTruncates()
	{
		return $this->truncateCount;
	}
	
	public function GetCopys()
	{
		return $this->copyCount;
	}
	
	public function GetCounts()
	{
		return $this->countCounts;
	}
}
?>