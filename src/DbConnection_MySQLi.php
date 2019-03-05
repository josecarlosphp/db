<?php
/**
 * This file is part of josecarlosphp/db - PHP classes to interact with databases.
 *
 * josecarlosphp/db is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * @see         https://github.com/josecarlosphp/db
 * @copyright   2008-2019 José Carlos Cruz Parra
 * @license     https://www.gnu.org/licenses/gpl.txt GPL version 3
 * @desc        Class to work with a database MySQLi.
 */

namespace josecarlosphp\db;

class DbConnection_MySQLi extends DbConnection
{
	public function __construct($ip='localhost', $dbport=3306, $dbname='', $dbuser='root', $dbpass='root', $connect=true, $charset=null, $debug=false, $defaultHtmlentities=true)
	{
		$this->_class = 'MySQLi';
		parent::__construct($ip, $dbport, $dbname, $dbuser, $dbpass, $connect, $charset, $debug, $defaultHtmlentities);
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
			return new \mysqli(null, $this->_dbuser, $this->_dbpass, $this->_dbname, null, $this->_dbsock);
        }
		elseif($this->_dbport)
		{
			return new \mysqli($this->_dbhost, $this->_dbuser, $this->_dbpass, $this->_dbname, $this->_dbport);
		}

		return new \mysqli($this->_dbhost, $this->_dbuser, $this->_dbpass, $this->_dbname);
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