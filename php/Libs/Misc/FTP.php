<?php
namespace Libs;

/**
 * Implements basic FTP functions as a class.
 */
class FTP {
	
	/**
	 * The connection identifier.
	 * 
	 * @var resource
	 */
	protected $conn;
	
	/**
	 * Current path.
	 * 
	 * @var string
	 */
	protected $path;
	
	/**
	 * Connects to the server.
	 * 
	 * @param string $host Hostname to connect to
	 * @param int $port Port number to use
	 */
	public function __construct($host, $port=21) {
		$this->conn = ftp_connect($host, $port);
	}
	
	/**
	 * Returns the currently selected FTP path.
	 * 
	 * @return string
	 */
	public function pwd() {
		return $this->path = ftp_pwd($this->conn);
	}
	
	/**
	 * Logs into the FTP server, returning current path on success or false on
	 * failure.
	 * 
	 * @param string $username FTP username
	 * @param string $password FTP password
	 * @return mixed Current path or false on failure
	 */
	public function login($username, $password) {
		return ftp_login($this->conn, $username, $password)? $this->pwd(): false;
	}
	
	/**
	 * Changes the current FTP path.
	 * 
	 * @param string $path FTP path to change to
	 * @return mixed Current path or false on failure
	 */
	public function chdir($path) {
		return ftp_chdir($this->conn, $path)? $this->pwd(): false;
	}
	
	/**
	 * Returns the list of files in the currently selected FTP directory.
	 * 
	 * @param string $match Regular expression the filenames have to match
	 * @return mixed Array of filenames or false on failure
	 */
	public function ls($match=null) {
		$out = array();
		$files = ftp_nlist($this->conn, '.');
		if (!$files) return null;
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') continue;
			if ($match && !preg_match($match, $file)) continue;
			$out[] = $file;
		};
		sort($out);
		return $out;
	}
	
	/**
	 * Downloads a file from the FTP server into a local path.
	 * 
	 * @param string $remote_path Filename to download
	 * @param string $local_path Local path to save to file into
	 * @return bool Success
	 */
	public function get($remote_path, $local_path) {
		return ftp_get($this->conn, $local_path, $remote_path, FTP_BINARY);
	}
	
	/**
	 * Deletes a file from the FTP server.
	 * 
	 * @param string $path Filename to delete
	 * @return bool Success
	 */
	public function delete($path) {
		return ftp_delete($this->conn, $path);
	}
	
	/**
	 * Closes the FTP connection.
	 * 
	 * @return bool Success
	 */	
	public function close() {
		return ftp_close($this->conn);
	}
}
