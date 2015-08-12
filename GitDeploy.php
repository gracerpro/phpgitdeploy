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

		$this->gitHelper->setDiffMode($this->config->getGitDiff());

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

		$currentErrorReporting = error_reporting();
		if ($this->config->getHideWarnings()) {
			error_reporting($currentErrorReporting & ~E_WARNING);
		}

		$excludeProjectDir = $this->config->getExcludeProjectDir();
		echo "\tDeleting...\n";
		$i = 0;
		$deletedLimit = $this->config->getUpdatedFilesLimit();
		foreach ($deletedFiles as $name) {
			if ($deletedLimit >= 0 && $i >= $deletedLimit) {
				break;
			}
			if ($excludeProjectDir) {
				$name = substr($name, $excludeProjectDir);
			}
			if (empty($name)) {
				continue;
			}
			$result = $ftpHelper->deleteFile($name);
			echo $result ? 'OK' : 'FAILED', " $name\n";
			++$i;
		}

		echo "\tUpdating/Creating...\n";
		$projectDir = $this->config->getProjectDir();
		$updatedLimit = $this->config->getUpdatedFilesLimit();
		$i = 0;
		foreach ($updatedFiles as $name) {
			if ($updatedLimit >= 0 && $i >= $updatedLimit) {
				break;
			}
			$sourcePath = $projectDir . '/' . $name;
			if ($excludeProjectDir) {
				$name = substr($name, $excludeProjectDir);
			}
			if (empty($name)) {
				continue;
			}
			$result = $ftpHelper->putFile($name, $sourcePath);
			echo $result ? 'OK' : 'FAILED', " $name\n";
			++$i;
		}

		error_reporting($currentErrorReporting);

		$this->endDeploy();
	}

	/**
	 * exclude self project, "git" found target project
	 */
	private function changeCurrentDirectory() {
		$result = false;
		if ($this->config->getProjectDir()) {
			echo "Change local dir: {$this->config->getProjectDir()}\n";
			$result = chdir($this->config->getProjectDir());
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public static function getSettingFileName()
	{
		return 'deploy.properties';
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

	}
}
