<?php
/**
 * Description of Monitor
 *
 * @author arek
 */
namespace AdachSoft\AntMiner;

class Monitor
{

	const STATE_OK = 0;
	const STATE_LOW_HASH_RATE = 1;
	const STATE_REBOOTED = 2;
	const HASH_RATE = [
		'Antminer L3+' => 504,
		'Antminer D3' => 18700,
	];
	const DEVICES_ALLOWED = ['Antminer L3+', 'Antminer D3'];

	public $hashRateReset = false;

	/**
	 * Server host.
	 * @var string
	 */
	private $host;
	private $username;
	private $password;
	private $antminer;
	private $cgminer;
	private $timeLowHashRate;
	private $timeReboot;
	private $lastTimeReboot;
	private $numberOfReboot;
	private $failedLogin;
        private $hashRateArray;

	public function __construct($host, $username, $password)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;

		echo "VERSION: " . $this->getVersion() . "\r\n";

		$this->antminer = new AntMinerWWW($host, $username, $password);
		if ($this->antminer->index()) {
			$this->failedLogin = true;
			echo "OK\r\n";
			echo 'Miner type: ' . $this->antminer->getMinerType() . "\r\n";
			echo 'Mac addr: ' . $this->antminer->getMac() . "\r\n";
			echo 'CGminer version: ' . $this->antminer->getCGminerVersion() . "\r\n";
			if (!$this->IsDevicesAllowed($this->antminer->getMinerType())) {
				echo "Unknown device\r\n";
			} else {
				$this->hashRateReset = static::HASH_RATE[$this->antminer->getMinerType()];
			}
		} else {
			$this->failedLogin = false;
			echo "NOT OK\r\n";
		}
		$this->cgminer = new CGMinerApi($host);
	}

	public function getVersion()
	{
		return 0.5;
	}

	public function IsDevicesAllowed($minerType)
	{
		return in_array($minerType, static::DEVICES_ALLOWED);
	}

	public function monitor()
	{
		while (true) {
			$this->check();
			sleep(1);
		}
	}

	public function check()
	{
		if ($this->failedLogin !== true) {
			echo "Failed login\r\n";
			return;
		}

		echo "Addr: " . $this->host . "\r\n";
		echo $this->antminer->getMinerType() . ":\t";

		if ($this->getState() === static::STATE_REBOOTED) {
			$t = 90 - (time() - $this->timeReboot);
			echo "Waiting($t)...";
			if ($t <= 0) {
				$this->timeReboot = NULL;
			}
		} else {
			try {
				$this->checkHashRate();
			} catch (\Exception $e) {
				echo "Error connect\r\n";
			}
		}
	}

	private function checkHashRate()
	{
		$resultSummary = $this->cgminer->sendSummary();
		if (isset($resultSummary['SUMMARY'][0])) {
			$val = $resultSummary['SUMMARY'][0];

			$hashRate = $val['GHS 5s'];
			$hashRateAVG = $val['GHS av'];
			$percent = 0;
			if ($this->isNumber($hashRate) && $this->isNumber($hashRateAVG)) {
				$hashRate = (float) $hashRate;
				$percent = round(($hashRate / $this->hashRateReset) * 100.00, 2);
				if ($percent < 90) {
					$this->onLowHashRate($hashRate, $hashRateAVG, $percent);
				}
			} else {
				echo "ERROR: not a number\r\n";
			}

			echo round($hashRate, 2) . "\t";
			echo round($hashRateAVG, 2) . "\t";
			echo round($percent, 2) . '%' . "\t";
			echo "\r\n";
		} else {
			echo "Error in summary\r\n";
		}
	}

	private function onLowHashRate($hashRate, $hashRateAVG, $percent)
	{
		echo "Low hash rate\r\n";
		if (empty($this->timeLowHashRate)) {
			$this->timeLowHashRate = time();
                        $this->hashRateArray = [$hashRate];
		} elseif (time() - $this->timeLowHashRate <= 60) {
                    $this->hashRateArray[] = $hashRate;
                }elseif (time() - $this->timeLowHashRate > 60) {
                        $average = array_sum($this->hashRateArray) / count($this->hashRateArray);
                        echo "average: $average\r\n";
			//$this->reboot();
		}
	}

	private function reboot()
	{
		if (!$this->IsRebootAvailable()) {
			echo "Reboot unavailable\r\n";
			return;
		}

		$this->timeLowHashRate = NULL;
		$this->timeReboot = time();
		$this->lastTimeReboot = time();
		$this->numberOfReboot++;
		$this->antminer->rebootCGI();
		echo "REBOOT\r\n";
	}

	private function IsRebootAvailable()
	{
		return is_null($this->lastTimeReboot) || time() - $this->lastTimeReboot > 60 * 15;
	}

	/**
	 * Get state
	 * @return int
	 */
	private function getState()
	{
		if (!empty($this->timeReboot)) {
			return static::STATE_REBOOTED;
		} elseif (!empty($this->timeLowHashRate)) {
			return static::STATE_LOW_HASH_RATE;
		}
		return static::STATE_OK;
	}

	/**
	 * Is number
	 * @param string $val
	 * @return bool
	 */
	private function isNumber($val)
	{
		return is_numeric($val);
	}
}
