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
 * @copyright   2008-2023 José Carlos Cruz Parra
 * @license     https://www.gnu.org/licenses/gpl.txt GPL version 3
 * @desc        Class to work with a database MySQL.
 */

namespace josecarlosphp\db;

class DbConnection_MySQL extends DbConnection
{
	public function __construct($ip='localhost', $dbport=3306, $dbname='', $dbuser='root', $dbpass='root', $connect=true, $charset=null, $debug=false, $defaultHtmlentities=true)
	{
		$this->_class = 'MySQL';
		parent::__construct($ip, $dbport, $dbname, $dbuser, $dbpass, $connect, $charset, $debug, $defaultHtmlentities);
	}

	protected function _setCharset($charset)
	{
		if(function_exists('mysql_set_charset'))
		{
			return mysql_set_charset($charset, $this->_dbcon);
		}
		else
		{
			return $this->Execute('SET CHARACTER SET '.$charset);
		}
	}

	protected function _connect($new)
	{
		return mysql_connect($this->GetServer(), $this->_dbuser, $this->_dbpass, $new);
	}

	protected function _select_db($dbname)
	{
		return @mysql_select_db($dbname, $this->_dbcon);
	}

	protected function _ping()
	{
		return mysql_ping($this->_dbcon);
	}

	protected function _query($query)
	{
		return mysql_query($query, $this->_dbcon);
	}

	protected function _affected_rows()
	{
		return @mysql_affected_rows($this->_dbcon);
	}

	protected function _insert_id()
	{
		return mysql_insert_id($this->_dbcon);
	}

	protected function _close()
	{
		return @mysql_close($this->_dbcon);
	}

	protected function _error()
	{
		return @mysql_error($this->_dbcon);
	}

	public function real_escape_string($str)
	{
		return mysql_real_escape_string($str, $this->_dbcon);
	}

	public function quote($str)
	{
		return "'".$this->real_escape_string($str)."'";
	}

	public function get_server_info()
	{
		return mysql_get_server_info($this->_dbcon);
	}

	/*public function get_server_version()
	{
		$str = $this->get_server_info();

		$aux = '';
		$len = mb_strlen($str);
		for($c=0; $c<$len; $c++)
		{
			$char = mb_substr($str, $c, 1);
			if(mb_strpos('0123456789.', $char) !== false)
			{
				$aux .= $char;
			}
		}

		$arr = explode('.', $aux);
		if(sizeof($arr) == 3)
		{
			return (($arr[0] * 10000) + ($arr[1] * 100)) + $arr[2];
		}

		return false;
	}*/
}