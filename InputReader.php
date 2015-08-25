<?php
namespace gracerpro\gitdeploy;

class InputReader
{
	/**
	 * @param \gracerpro\gitdeploy\Config $config
	 */
	protected $config;

	/**
	 * @param \gracerpro\gitdeploy\Config $config
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	/**
	 * Read commands from command line
	 */
	public function read() {
		$inputStr = '';
		echo "Push 'q', 'exit' or 'bay' to exit\n";
		while (!in_array($inputStr, ['q', 'exit', 'bay'])) {
			fputs(STDOUT, '> ');
			$inputStr = trim(fgets(STDIN));
			$pos = strpos($inputStr, ' ');
			$name = $pos ? substr($inputStr, 0, $pos) : $inputStr;
			$value = trim(substr($inputStr, $pos + 1));
			if ($name === 'git.diff') {
				$this->config->setGitDiff($value);
			}
		}
	}
}