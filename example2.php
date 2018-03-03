<?php
require_once './src/CGMinerApi.php';
require_once './src/AntMinerWWW.php';
require_once './src/Monitor.php';
require_once 'config.php';

$monitors = [];

foreach ($config as $val) {
	$monitors[] = new \AdachSoft\AntMiner\Monitor($val['host'], $val['username'], $val['password']);
}

while(1){
    foreach ($monitors as $monitor) {
	$monitor->check();
	echo "----------------------\r\n";
	echo "\r\n";
	sleep(1);
    }
}

