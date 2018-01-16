<?php
require_once './src/CGMinerApi.php';

echo "DIR: " . __DIR__ . "\r\n";


if ($argc == 1) {
	echo "php -f example.php <ip>\r\n";
} else {
	$cgminer = new CGMinerApi($argv[1]);
	$cgminer->sendSummary();
	$cgminer->printSummary();
}



