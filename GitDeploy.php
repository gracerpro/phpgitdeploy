<?php
namespace gracerpro\gitdeploy;

include_once __DIR__ . '/GitHelper.php';
include_once __DIR__ . '/FtpHelper.php';
include_once __DIR__ . '/Config.php';

use gracerpro\gitdeploy\GitHelper;
use gracerpro\gitdeploy\FtpHelper;
use gracerpro\gitdeploy\Config;

class GitDeploy
{
	/**
	 * @var \gracerpro\gitdeploy\GitHelper
	 */
	protected $gitHelper;

	/**
	 * @var \gracerpro\gitdeploy\Config
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $lastDeployedCommit;

	/**
	 * 
	 */
	public function __construct()
	{
		$this->gitHelper = new GitHelper($this);
		$this->config = Config::getInstance();

		$this->changeCurrentDirectory();
	}

	public function deploy()
	{
		$ftpHelper = new \gracerpro\gitdeploy\FtpHelper();
		$ftpHelper->setHost($this->config->getValue('ftp.host'));
		$ftpHelper->setPort($this->config->getValue('ftp.port'));
		$ftpHelper->setUsername($this->config->getValue('ftp.username'));
		$ftpHelper->setPassword($this->config->getValue('ftp.password'));

		$ftpHelper->connect();
		$ftpHelper->setPasv(true);
		$ftpHelper->changeDir($this->config->getValue('ftp.chdir'));

		echo 'Server`s file system: ' . $ftpHelper->getSysType(), "\n";

		/* get files */
		$deletedFiles = $this->gitHelper->getFilesForDeleting();
		$updatedFiles = $this->gitHelper->getFilesForUpdating();

		echo "\tDeleting...\n";
		foreach ($deletedFiles as $name) {
			$result = $ftpHelper->deleteFile($name);
			echo $result ? 'OK' : 'FAILED', " $name\n";
		}

		echo "\tUpdating/Creating...\n";
		$projectDir = $this->config->getProjectDir();
		foreach ($updatedFiles as $name) {
			$sourcePath = $projectDir . '/' . $name;
			$result = $ftpHelper->putFile($name, $sourcePath);
			echo $result ? 'OK' : 'FAILED', " $name\n";
		}

		$this->endDeploy();
	}

	/**
	 * exclude self project, "git" found target project
	 */
	private function changeCurrentDirectory() {
		if ($this->config->getProjectDir()) {
			echo "Change local dir: {$this->config->getProjectDir()}\n";
			chdir($this->config->getProjectDir());
		}
	}

	/**
	 * @return string
	 */
	public static function getSettingFileName()
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
			$filePath = $this->config->getSettingDir() . '/' . self::getLastCommitFileName();
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
		if ($this->gitHelper->getDiffMode() === GitHelper::DIFF_COMMITS) {
			return false;
		}
		$commit = $this->gitHelper->gitGetLastCommitHash();

		// write current commit
		$result = false;
		$filePath = $this->config->getSettingDir() . '/' . self::getLastCommitFileName();
		$h = fopen($filePath, 'w');
		if ($h) {
			$result = fwrite($h, $commit) > 0;
			fclose($h);
		}

		return $result;
	}
}
