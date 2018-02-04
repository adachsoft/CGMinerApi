<?php
$config = [
	[
		'host' => '',
		'username' => '',
		'password' => ''
	]
];
$monitors = [];

foreach ($config as $val) {
	$monitors[] = new \AdachSoft\AntMiner\Monitor($val['host'], $val['username'], $val['password']);
}

foreach ($monitors as $monitor) {
	$monitor->check();
	echo "----------------------\r\n";
	echo "\r\n";
	sleep(1);
}
