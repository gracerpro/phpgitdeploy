<?php
namespace gracerpro\gitdeploy;

include_once './GitHelper.php';

class GitDeploy
{
	/**
	 * @var \gracerpro\gitdeploy\GitHelper
	 */
	protected $gitHelper;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $settingDir;

	/**
	 * @var string
	 */
	protected $lastDeployedCommit;

	/**
	 * 
	 */
	public function __construct()
	{
		$this->gitHelper = new \gracerpro\gitdeploy\GitHelper($this);

		$this->readConfig();
	}

	public function deploy()
	{
		$ftpHelper = new \gracerpro\gitdeploy\FtpHelper();
		$ftpHelper->setHost($this->config['ftp.host']);
		$ftpHelper->setPort($this->config['ftp.port']);
		$ftpHelper->setUsername($this->config['ftp.username']);
		$ftpHelper->setPassword($this->config['ftp.password']);

		$ftpHelper->connect();
		$ftpHelper->setPasv(true);
		$ftpHelper->changeDir($this->config['ftp.chdir']);

		echo 'Server file system: ' . $ftpHelper->getSysType(), "\n";

		/* get files */
		$deletedFiles = $this->gitHelper->getFilesForDeleting();
		$updatedFiles = $this->gitHelper->getFilesForUpdating();

		echo "\tDeleting...\n";
		foreach ($deletedFiles as $name) {
			echo $name, "\n";
			$ftpHelper->deleteFile($name);
		}

		echo "\tUpdating/Creating...\n";
		$projectDir = '';
		foreach ($updatedFiles as $name) {
			$sourcePath = $projectDir . '/' . $name;
			echo $name, "\n";
			$ftpHelper->putFile($name, $sourcePath, FTP_TEXT);
		}

		$this->endDeploy();
	}

	/**
	 * @return boolean
	 * @throws Exception
	 */
	private function readConfig()
	{
		$config = parse_ini_file(self::getSettingFileName());
		if (!$config) {
			throw new Exception('Failed to open file: ' . self::getSettingFileName());
		}
		if (empty($config['ftp.username']) || empty($config['ftp.password']) || empty($config['ftp.host'])) {
			throw new Exception("Empty ftp.username, ftp.password or ftp.host\n");
		}

		$this->config = $config;

		return true;
	}

	/**
	 * @return string
	 */
	public function getSettingDir()
	{
		return $this->settingDir;
	}

	/**
	 * @param string $settingDir
	 * @return \gracerpro\gitdeploy\GitDeploy
	 */
	public function setSettingDir($settingDir)
	{
		$this->settingDir = $settingDir;
		return $this;
	}

	/**
	 * @return string
	 */
	protected static function getSettingFileName()
	{
		return 'deploy.properties';
	}

	/**
	 * @return string
	 */
	protected static function getLastCommitFileName()
	{
		return 'last_commit.txt';
	}

	/**
	 * @return string
	 */
	public function readLastDelpoyedCommit()
	{
		if ($this->lastDeployedCommit === null) {
			$filePath = $this->settingDir . '/' . self::getLastCommitFileName();
			if (!file_exists($filePath)) {
				$h = fopen($filePath, 'w');
				fclose($h);
			}
			$commit = file_get_contents($filePath);
			if (empty($commit)) {
				$commit = 'HEAD~1';
			}
			$this->lastDeployedCommit = $commit;
		}
		return $this->lastDeployedCommit;
	}

	/**
	 * 
	 */
	public function beginDeploy()
	{

	}

	/**
	 * @return boolean
	 */
	public function endDeploy()
	{
		if ($this->diffMode === self::DIFF_COMMITS) {
			return false;
		}
		$commit = $this->gitHelper->gitGetLastCommitHash();

		// write current commit
		$result = false;
		$filePath = $this->settingDir . '/' . self::getLastCommitFileName();
		$h = fopen($filePath, 'w');
		if ($h) {
			$result = fwrite($h, $commit) > 0;
			fclose($h);
		}

		return $result;
	}
}
