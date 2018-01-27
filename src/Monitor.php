<?php
/**
 * Description of Monitor
 *
 * @author arek
 */
namespace AdachSoft\AntMiner;

class Monitor
{

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

	public function check()
	{
		echo "Addr: " . $this->host . "\r\n";
		echo $this->antminer->getMinerType() . ":\t";

		$this->cgminer->sendSummary();
		$resultSummary = $this->cgminer->getResultSummary();

		if (isset($resultSummary['SUMMARY'][0])) {
			$val = $resultSummary['SUMMARY'][0];
			echo round($val['GHS 5s'], 2) . "\t";
			echo round($val['GHS av'], 2) . "\t";
			echo "\r\n";
		}

		//$this->cgminer->printSummary();
	}

	public function IsDevicesAllowed($minerType)
	{
		return in_array($minerType, static::DEVICES_ALLOWED);
	}
}
