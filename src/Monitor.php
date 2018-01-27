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
		'Antminer L3+' => 500,
		'Antminer D3' => 18000
	];
	const DEVICES_ALLOWED = ['Antminer L3+', 'Antminer D3'];

	public $hashRateReset = false;

	/**
	 * Server host.
	 * @var string
	 */
	protected $host;
	protected $username;
	protected $password;
	protected $antminer;
	protected $cgminer;
	protected $timeLowHashRate;
	protected $timeReboot;

	public function __construct($host, $username, $password)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;

		$this->antminer = new AntMinerWWW($host, $username, $password);
		if ($this->antminer->index()) {
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
			echo "NOT OK\r\n";
		}
		$this->cgminer = new CGMinerApi($host);
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
		echo "Addr: " . $this->host . "\r\n";
		echo $this->antminer->getMinerType() . ":\t";

		if ($this->getState() === static::STATE_REBOOTED) {
			$t = time() - $this->timeReboot;
			echo "Waiting($t)...";
		} else {
			$this->cgminer->sendSummary();
			$resultSummary = $this->cgminer->getResultSummary();

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
					echo "ERROR\r\n";
				}

				echo round($hashRate, 2) . "\t";
				echo round($hashRateAVG, 2) . "\t";
				echo round($percent, 2) . '%' . "\t";
				echo "\r\n";
			}
		}
	}

	public function IsDevicesAllowed($minerType)
	{
		return in_array($minerType, static::DEVICES_ALLOWED);
	}

	private function onLowHashRate($hashRate, $hashRateAVG, $percent)
	{
		echo "Low hash rate\r\n";
		if (empty($this->timeLowHashRate)) {
			$this->timeLowHashRate = time();
		} elseif (time() - $this->timeLowHashRate > 60) {
			$this->timeLowHashRate = NULL;
			$this->timeReboot = time();
			echo "REBOOT\r\n";
		}
	}

	/**
	 * Get state
	 * @return int
	 */
	private function getState()
	{
		if (!empty($this->timeReboot)) {
			return static::STATE_REBOOTED;
		} elseif (!empty($this->timeReboot)) {
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
