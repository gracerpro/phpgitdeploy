<?php
namespace gracerpro\gitdeploy;

include_once __DIR__ . '/GitHelper.php';
include_once __DIR__ . '/FtpHelper.php';
include_once __DIR__ . '/Config.php';
include_once __DIR__ . '/InputReader.php';

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
	 * @var array
	 */
	protected $arguments = [];

	/**
	 * 
	 */
	public function __construct()
	{
		$this->gitHelper = new GitHelper($this);
		$this->config = Config::getInstance();

		$this->changeCurrentDirectory();

		$this->readArguments();
	}

	/**
	 * Read arguments from command line
	 * PHP seeks first '--'
	 * @return boolean
	 */
	private function readArguments() {
		$this->arguments = [];
		global $argv;

		if (isset($argv) && is_array($argv)) {
			$count = count($argv);
			for ($i = 1; $i < $count; ++$i) {
				$arg = $argv[$i];
				$isParamName = $arg{0} === '-';
				if ($isParamName) {
					$paramName = ltrim($arg, '-');
					$this->arguments[$paramName] = null;
					if (++$i >= $count) {
						break;
					}
					$nextArg = $argv[$i];
					$this->arguments[$paramName] = $nextArg;
				}
				else {
					$this->arguments[] = $arg;
				}
			}
		}
		return true;
	}

	/**
	 * @param string $name
	 * @return string|boolean
	 */
	private function getArgValue($name) {
		return isset($this->arguments[$name]) ? $this->arguments[$name] : false;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	private function getArg($name) {
		return array_key_exists($name, $this->arguments);
	}

	public function deploy()
	{
		if ($this->getArg('i')) {
			$inputReader = new \gracerpro\gitdeploy\InputReader($this->config);
			$inputReader->read();
		}

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
		echo "\tDeleting " . count($deletedFiles) . " files...\n";
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

		echo "\tUpdating/Creating " . count($updatedFiles) . " files...\n";
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
