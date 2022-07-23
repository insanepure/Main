<?php
ini_set ('display_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$server_adr = '.';
include_once 'classes/header.php';
ignore_user_abort(true);   
set_time_limit(30);
$p = $argv[1];

echo 'Start<br/>';

if($p == 'logout')
{
  echo 'Logout Users';
  $timeOut = 60;
  $where = 'TIMESTAMPDIFF(MINUTE, lastaction, NOW()) < '.$timeOut;
	$accountDB->Delete('chatusers',$where,999999999);
}
if($p == 'clear')
{
  echo 'Clear Users';
	$accountDB->Truncate('chatmessages');
	$accountDB->Truncate('chatusers');
  $accountDB->Delete('interactions', ' time < NOW() - INTERVAL 60 DAY', 9999999999);
}
echo 'End<br/>';
?>