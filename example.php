<?php
require_once './src/CGMinerApi.php';

if ($argc == 1) {
	echo "php -f example.php <ip>\r\n";
} else {
	echo "IP: " . $argv[1] . "\r\n";
	$cgminer = new \AdachSoft\AntMiner\CGMinerApi($argv[1]);
	$cgminer->sendStats();
	$cgminer->printStats();
}



