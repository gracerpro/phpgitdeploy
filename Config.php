<?php
namespace gracerpro\gitdeploy;

class Config
{
	/**
	 * @var Config
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private static $config;

	/**
	 * @var string
	 */
	private $projectDir = '..';

	/**
	 * @var string
	 */
	private $settingDir = '.';

	/**
	 * @var string Path to git directory
	 */
	public $gitBinDir = '';

	/**
	 * @var string
	 */
	private $gitDiff = '';

	private function __construct() {
		$config = parse_ini_file(\gracerpro\gitdeploy\GitDeploy::getSettingFileName());
		if (!$config) {
			throw new \Exception('Failed to open file: ' . self::getSettingFileName());
		}
		if (empty($config['ftp.username']) || empty($config['ftp.password']) || empty($config['ftp.host'])) {
			throw new \Exception("Empty ftp.username, ftp.password or ftp.host\n");
		}
		$this->gitBinDir = empty($config['gitBinDir']) ? '' : trim($config['gitBinDir']);
		if (!empty($config['projectDir'])) {
			$this->projectDir = trim($config['projectDir']);
		}
		if (!empty($config['settingDir'])) {
			$this->settingDir = trim($config['settingDir']);
		}
		if (!empty($config['git.diff'])) {
			$this->gitDiff = trim($config['git.diff']);
		}

		self::$config = $config;
	}

	/**
	 * @return gracerpro\gitdeploy\Config
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Config();
		}
		return self::$instance;
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getValue($name)
	{
		if (isset(self::$config[$name])) {
			return trim(self::$config[$name]);
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getProjectDir() {
		return $this->projectDir;
	}

	/**
	 * @return string
	 */
	public function getBinGitDir() {
		return $this->gitBinDir;
	}

	/**
	 * @return string
	 */
	public function getSettingDir()
	{
		return $this->settingDir;
	}

	/**
	 * @param string $dir
	 * @return \gracerpro\gitdeploy\Config
	 */
	public function setSettingDir($dir)
	{
		$this->settingDir = $dir;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGitDiff()
	{
		return $this->gitDiff;
	}
}
