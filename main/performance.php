<?php
phpinfo();
exit();

function get_processor_cores_number() {
    $command = "cat /proc/cpuinfo | grep processor | wc -l";

    return  (int) shell_exec($command);
}


$cpuLoad = sys_getloadavg();

echo 'Cores: '.get_processor_cores_number().'<br/>';
echo 'CPU Load (1 Minute): '.$cpuLoad[0].'<br/>';
echo 'CPU Load (5 Minutes): '.$cpuLoad[1].'<br/>';
echo 'CPU Load (15 Minutes): '.$cpuLoad[2].'<br/>';


phpSysInfo();
?>