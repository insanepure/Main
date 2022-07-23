<?php
//Import the PHPMailer class into the global namespace

include_once 'mailheader.php';

error_reporting(E_ALL & ~E_NOTICE);
include_once 'database.php';

$accdb = 'main';
$accuser = 'root';
$accpw = '';
$accountDB = new Database($accdb, $accuser, $accpw);

include_once 'account.php';
include_once 'logintracker.php';

LoginTracker::TrackReferer();

include_once 'imagehandler.php';
include_once 'chat/chat.php';


if(isset($_POST['pw']))
{
  $pwError = 0;  
  if(!preg_match("#[A-Z]+#", $_POST['pw']))
  {
    $pwError = 1;
  }
  else if(!preg_match("/[\'^£$%&*()}{@#~?><>,|=_+!-]/", $_POST['pw']))
  {
    $pwError = 2;
  }
  else if(!preg_match("#[0-9]+#", $_POST['pw']))
  {
    $pwError = 3;
  }
  
  $safedPW = Account::GetPassword($_POST['pw']);
  unset($_POST['pw']);
}
if(isset($_POST['pw2']))
{
  $safedPW2 = Account::GetPassword($_POST['pw2']);
  unset($_POST['pw2']);
}

?>