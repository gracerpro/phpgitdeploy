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
	 * @param string $mode
	 * @return boolean
	 */
	public function putFile($fileName, $sourcePath, $mode = FTP_ASCII)
	{
		return ftp_put($this->connection, $fileName, $sourcePath, $mode);
	}
}
