<?php
namespace gracerpro\gitdeploy;

include_once __DIR__ . '/Config.php';

class GitHelper
{
	//
	const FILE_CREATE = 'A';
	const FILE_DELETE = 'D';
	const FILE_UPDATE = 'M';

	// `git diff` between
	const DIFF_COMMITS             = 'commits';  // git diff sha1 sha1
	const DIFF_CURRENT_BRANCH      = 'branch';   // git diff master branch
	const DIFF_BRANCHES            = 'branches'; // git diff branch1 branch2
	const DIFF_ORIGINMASTER_MASTER = 'origin/master master';

	/**
	 * @var string
	 */
	protected $diffMode;

	/**
	 * @var array
	 */
	protected $gitNameStatusFiles;

	/**
	 * @var \gracerpro\gitdeploy\GitDeploy
	 */
	protected $gitDeploy;

	public function __construct($gitDeploy)
	{
		$this->gitDeploy = $gitDeploy;
	}

	/**
	 * @return string
	 */
	function getDiffMode() {
		return $this->diffMode;
	}

	/**
	 * @param string $diffMode
	 */
	function setDiffMode($diffMode) {
		$this->diffMode = $diffMode;
		return $this;
	}

	/**
	 * @return string
	 */
	private function getGitCommand()
	{
		$config = \gracerpro\gitdeploy\Config::getInstance();
		$gitDir = $config->getBinGitDir();
		return $gitDir ? $gitDir . '/git' : 'git';
	}

	/**
	 * @return string Sha1 hash
	 */
	public function gitGetLastCommitHash()
	{
		$command = $this->getGitCommand() . ' rev-parse HEAD';
		$ret = $this->executeCommand($command);

		return trim($ret[0]);
	}

	/**
	 * @param string $earlyCommit
	 * @param string $lastCommit
	 * @return string
	 */
	protected function gitGetDiffCommitsNameStatus($earlyCommit, $lastCommit)
	{
		$params = "diff $earlyCommit $lastCommit --name-status";
		$command = $this->getGitCommand() . ' ' . $params;
		echo $command, "\n";

		$ret = $this->executeCommand($command);

		return $ret[0];
	}

	/**
	 * @return string
	 */
	protected function gitGetDiffNameStatus()
	{
		if (empty($this->diffMode)) {
			$this->diffMode = 'origin/master master';
		}
		$params = 'diff ' . $this->diffMode . ' --name-status';
		$command = $this->getGitCommand() . ' ' . $params;
		echo $command, "\n";

		$ret = $this->executeCommand($command);

		return $ret[0];
	}

	/**
	 * @return array
	 */
	private function getFiles()
	{
		if ($this->gitNameStatusFiles === null) {
			$files = [];

			$output = $this->gitGetDiffNameStatus();
			if (($count = preg_match_all('/([\w]+)\t([\w\._\-\\/]+)\n/', $output, $matches)) > 0) {
				$statuses = $matches[1];
				$fileNames = $matches[2];
				for ($i = 0; $i < $count; ++$i) {
					$files[] = [
						'status' => $statuses[$i],
						'name' => $fileNames[$i],
					];
				}
			}
			$this->gitNameStatusFiles = $files;
		}
		return $this->gitNameStatusFiles;
	}

	/**
	 * Get files from root for a deleting
	 * @return array
	 */
	public function getFilesForDeleting()
	{
		$files = [];

		foreach ($this->getFiles() as $item) {
			if ($item['status'] === self::FILE_DELETE) {
				$files[] = $item['name'];
			}
		}

		return $files;
	}

	/**
	 * Get files, from root, for an updating
	 * @return array
	 */
	public function getFilesForUpdating()
	{
		$files = [];

		foreach ($this->getFiles() as $item) {
			if ($item['status'] === self::FILE_UPDATE || $item['status'] === self::FILE_CREATE) {
				$files[] = $item['name'];
			}
		}

		return $files;
	}

	/**
	 * @param string $command
	 * @return array
	 * @throws \Exception
	 */
	private function executeCommand($command)
	{
		$descriptors = [
			0 => ['pipe', 'r'], // stdin - read channel
			1 => ['pipe', 'w'], // stdout - write channel
			2 => ['pipe', 'w'], // stdout - error channel
			3 => ['pipe', 'r'], // stdin - This is the pipe we can feed the password into
		];
		$process = proc_open($command, $descriptors, $pipes);
		if (!is_resource($process)) {
			throw new \Exception("Can't open resource with proc_open.");
		}
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		fclose($pipes[3]);

		$code = proc_close($process);
		return [$output, $error, $code];
	}
}
