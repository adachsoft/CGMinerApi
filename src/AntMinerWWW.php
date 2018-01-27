<?php
/**
 * Description of AntMinerWWW
 *
 * @author arek
 */
namespace AdachSoft\AntMiner;

class AntMinerWWW
{

	/**
	 * Server host.
	 * @var string
	 */
	protected $host;
	protected $username;
	protected $password;
	protected $sysInfo;

	public function __construct($host, $username, $password)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
	}

	public function index()
	{
		$url = 'http://' . $this->host;
		$res = $this->getPage($url);
		$output = $res['output'];
		$info = $res['header'];

		if ($info['http_code'] !== 200) {
			return false;
		} elseif (!preg_match('/Miner\s+Type/i', $output)) {
			return false;
		}
		$this->getSystemInfoCGI();
		return true;
	}

	public function getMinerType()
	{
		return $this->getSys('minertype');
	}

	public function getMac()
	{
		return $this->getSys('macaddr');
	}

	public function getCGminerVersion()
	{
		return $this->getSys('cgminer_version');
	}

	public function getSys($key)
	{
		if (!isset($this->sysInfo[$key])) {
			return false;
		}
		return $this->sysInfo[$key];
	}

	public function getSystemInfoCGI()
	{
		$url = 'http://' . $this->host . '/cgi-bin/get_system_info.cgi';
		$res = $this->getPage($url);
		$output = $res['output'];
		$info = $res['header'];

		if ($info['http_code'] !== 200) {
			return false;
		}
		$this->sysInfo = json_decode($output, true);
		return $this->sysInfo;
	}

	public function reboot()
	{
		$url = 'http://' . $this->host;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);

		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		var_dump($output);
		var_dump($info);

		$this->reboot2();
	}

	public function rebootCGI()
	{
		$url = 'http://' . $this->host . '/cgi-bin/reboot.cgi';

		$res = $this->getPage($url);
		$output = $res['output'];
		$info = $res['header'];

		if ($info['http_code'] !== 200) {
			echo "-------------------\r\n";
			var_dump($output);
			var_dump($info);

			return false;
		}

		var_dump($output);
		var_dump($info);

		return true;
	}

	private function getPage($url)
	{
		$username = $this->username;
		$password = $this->password;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);


		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return [
			'output' => $output,
			'header' => $info
		];
	}
}
