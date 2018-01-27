<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace AdachSoft\AntMiner;

class CGMinerApi
{

	/**
	 * Server host.
	 * @var string
	 */
	protected $host;

	/**
	 * Server port.
	 * @var int
	 */
	protected $port = 4028;

	/**
	 * Connection stream.
	 * @var resource
	 */
	protected $socket;
	protected $resultSummary;
	protected $resultPools;

	public function __construct($host, $port = 4028)
	{
		$this->host = $host;
		$this->port = $port;
	}

	public function printPools()
	{
		if (isset($this->resultPools['POOLS'])) {
			foreach ($this->resultPools['POOLS'] as $val) {
				echo $val['URL'] . "\t";
				echo $val['Status'] . "\t";
				echo $val['User'] . "\t";
				echo "\r\n";
			}
		}
	}

	public function sendPools()
	{
		$this->resultPools = $this->sendCommand('pools');
	}

	public function printSummary()
	{
		if (isset($this->resultSummary['SUMMARY'])) {
			foreach ($this->resultSummary['SUMMARY'] as $val) {
				echo $val['GHS 5s'] . "\t";
				echo $val['GHS av'] . "\t";
				echo "\r\n";
			}
		}
	}

	public function getResultSummary()
	{
		return $this->resultSummary;
	}

	public function sendSummary()
	{
		$this->resultSummary = $this->sendCommand('summary');
		return $this->resultSummary;
	}

	public function sendCommand($cmd, $param = NULL)
	{
		$this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
		if (!$this->socket) {
			$message = 'Connection to "' . $this->host . ':' . $this->port . '" failed (errno ' . $errno . '): ' . $errstr;
			throw new Exception($message, $errno);
		}

		$arr['command'] = $cmd;
		if (!is_null($param)) {
			$arr['parameter'] = $param;
		}
		$jsonCmd = json_encode($arr);

		fwrite($this->socket, $jsonCmd);
		fflush($this->socket);
		$response = fgets($this->socket);

		$len = strlen($response);
		if (ord($response[$len - 1]) === 0) {
			$response = substr($response, 0, $len - 1);
		}

		$res = json_decode($response, true);
		if (is_null($res)) {
			var_dump($response);
			$message = json_last_error_msg();
			echo "ERROR: " . $message . "\r\n";
			throw new Exception($message);
		}

		fclose($this->socket);
		return $res;
	}
}
