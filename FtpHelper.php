<?php
namespace gracerpro\gitdeploy;

class FtpHelper
{
	/**
	 * @var resource
	 */
	protected $connection;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * @var boolean
	 */
	protected $pasv;

	/**
	 * @var integer
	 */
	protected $port = 21;

	public function __construct($host = false, $username = false, $password = false)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
	}

	public function __destruct()
	{
		if ($this->connection) {
			ftp_close($this->connection);
		}
	}

	/**
	 * @return boolean
	 * @throws Exception
	 */
	public function connect()
	{
		$this->connection = ftp_connect($this->host, $this->port);
		if (!$this->connection) {
			throw new Exception("Could't connect to host {$this->host} and port {$this->port}.");
		}
		$loginResult = ftp_login($this->connection, $this->username, $this->password);
		if (!$loginResult) {
			throw new Exception("Failed to login for user {$this->username}\n");
		}

		return true;
	}

	public function close()
	{
		ftp_close($this->connection);
	}

	/**
	 * @return string
	 */
	function getUsername()
	{
		return $this->username;
	}

	/**
	 * @return string
	 */
	function getPassword()
	{
		return $this->password;
	}

	/**
	 * @return string
	 */
	function getHost()
	{
		return $this->host;
	}

	/**
	 * @return integer
	 */
	function getPort()
	{
		return $this->port;
	}

	/**
	 * @param string $username
	 * @return \gracerpro\gitdeploy\FtpHelper
	 */
	function setUsername($username)
	{
		$this->username = $username;
		return $this;
	}

	/**
	 * @param string $password
	 * @return \gracerpro\gitdeploy\FtpHelper
	 */
	function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}

	/**
	 * @param string $host
	 * @return \gracerpro\gitdeploy\FtpHelper
	 */
	function setHost($host)
	{
		$this->host = $host;
		return $this;
	}

	/**
	 * @param integer $port
	 * @return \gracerpro\gitdeploy\FtpHelper
	 */
	function setPort($port)
	{
		$this->port = $port > 0 ? $port : 21;
		return $this;
	}

	/**
	 * @param boolean
	 */
	public function changeDir($directory)
	{
		echo "Change remote directory: $directory\n";
		return ftp_chdir($this->connection, $directory);
	}

	/**
	 * @param boolean $value
	 * @return \gracerpro\gitdeploy\FtpHelper
	 */
	public function setPasv($value)
	{
		$this->pasv = $value;
		$result = ftp_pasv($this->connection, $value);
		if (!$result) {
			echo "Warning: set passive mode failed\n";
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSysType()
	{
		return ftp_systype($this->connection);
	}

	/**
	 * @param string $fileName
	 * @return boolean
	 */
	public function deleteFile($fileName)
	{
		return ftp_delete($this->connection, $fileName);
	}

	/**
	 * @param string $fileName
	 * @param string $sourcePath
	 * @param boolean $secondary If this is true then file will not put secondary
	 * @return boolean
	 */
	public function putFile($fileName, $sourcePath, $secondary = false)
	{
		$txtExtensions = ['txt', 'php', 'js', 'css', 'less', 'html', 'xml'];
		$ext = substr($fileName, strrpos($fileName, '.') + 1);
		$mode = in_array($ext, $txtExtensions) ? FTP_ASCII : FTP_BINARY;
		$putResult = ftp_put($this->connection, $fileName, $sourcePath, $mode);
		if (!$putResult && !$secondary) {
			// try to create a directory
			$directories = explode('/', $fileName);
			$config = \gracerpro\gitdeploy\Config::getInstance();
			$dir = $config->getValue('ftp.chdir') === '/' ? '' : $config->getValue('ftp.chdir');
			for ($i = 0; $i < count($directories) - 1; ++$i) {
				$dir .= '/' . $directories[$i];
				$mkdirResult = ftp_mkdir($this->connection, $dir);
				if ($mkdirResult) {
					echo "Create remote directory: $dir\n";
				}
			}
			// secondary put a file
			$putResult = $this->putFile($fileName, $sourcePath);
		}
		return $putResult;
	}
}
