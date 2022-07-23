<?php
if(!session_start())
{
  echo 'Session kann nicht gestartet werden.<br/>';
  echo 'Es wird gerade an dem Problem gearbeitet.<br/>';
  exit();
}

set_time_limit(10);
?>