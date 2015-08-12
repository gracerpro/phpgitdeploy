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

	/**
	 * @var integer
	 */
	private $deletedLimit = -1;

	/**
	 * @var integer
	 */
	private $updatedLimit = -1;

	/**
	 * @var integer
	 */
	private $excludeProjectDir = 0;

	/**
	 * @var boolean
	 */
	private $hideWarnings = false;

	private function __construct() {
		$config = parse_ini_file(\gracerpro\gitdeploy\GitDeploy::getSettingFileName());
		if (!$config) {
			throw new \Exception('Failed to open file: ' . \gracerpro\gitdeploy\GitDeploy::getSettingFileName());
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

		if (!empty($config['limit.deletedFiles'])) {
			$this->deletedLimit = (int)$config['limit.deletedFiles'];
		}
		if (!empty($config['limit.updatedFiles'])) {
			$this->updatedLimit = (int)$config['limit.updatedFiles'];
		}

		if (!empty($config['excludeProjectDir'])) {
			$this->excludeProjectDir = (int)$config['excludeProjectDir'];
		}

		$this->hideWarnings = empty($config['reporting.warnings']);

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
	 * @return integer
	 */
	public function getDeletedFilesLimit()
	{
		return $this->deletedLimit;
	}

	/**
	 * @return integer
	 */
	public function getUpdatedFilesLimit()
	{
		return $this->updatedLimit;
	}

	/**
	 * @return integer
	 */
	public function getExcludeProjectDir()
	{
		return $this->excludeProjectDir;
	}

	/**
	 * @return boolean
	 */
	public function getHideWarnings()
	{
		return $this->hideWarnings;
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
