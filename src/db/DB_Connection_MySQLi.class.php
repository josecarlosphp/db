<?php
class DB_Connection_MySQLi extends DB_Connection
{
	public function __construct($ip='localhost', $dbport=3306, $dbname='', $dbuser='root', $dbpass='root', $connect=true, $charset=null, $debug=false)
	{
		$this->_class = 'MySQLi';
		parent::__construct($ip, $dbport, $dbname, $dbuser, $dbpass, $connect, $charset, $debug);
	}

	protected function _setCharset($charset)
	{
		if(function_exists('mysqli_set_charset'))
		{
			return mysqli_set_charset($this->_dbcon, $charset);
		}
		else
		{
			return $this->Execute('SET CHARACTER SET '.$charset);
		}
	}

	protected function _connect($new)
	{
		/*
		if($this->_dbsock)
		{
			return new mysqli(null, $this->_dbuser, $this->_dbpass, $this->_dbname, null, $this->_dbsock);
        }
		elseif($this->_dbport)
		{
			return new mysqli($this->_dbhost, $this->_dbuser, $this->_dbpass, $this->_dbname, $this->_dbport);
		}

		return new mysqli($this->_dbhost, $this->_dbuser, $this->_dbpass, $this->_dbname);
		*/

		return mysqli_connect($this->_dbhost, $this->_dbuser, $this->_dbpass, $this->_dbname, $this->_dbport, $this->_dbsock);
	}

	protected function _select_db($dbname)
	{
		return @mysqli_select_db($this->_dbcon, $dbname);
	}

	protected function _ping()
	{
		return mysqli_ping($this->_dbcon);
	}

	protected function _query($query)
	{
		return mysqli_query($this->_dbcon, $query);
	}

	protected function _affected_rows()
	{
		return @mysqli_affected_rows($this->_dbcon);
	}

	protected function _insert_id()
	{
		return mysqli_insert_id($this->_dbcon);
	}

	protected function _close()
	{
		return @mysqli_close($this->_dbcon);
	}

	protected function _error()
	{
		return @mysqli_error($this->_dbcon);
	}

	public function real_escape_string($str)
	{
		return mysqli_real_escape_string($this->_dbcon, $str);
	}

	public function quote($str)
	{
		return "'".$this->real_escape_string($str)."'";
	}

	public function get_server_info()
	{
		return mysqli_get_server_info($this->_dbcon);
	}

	/*public function get_server_version()
	{
		return mysqli_get_server_version($this->_dbcon);
	}*/
}