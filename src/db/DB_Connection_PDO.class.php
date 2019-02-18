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
 * @desc        Class to work with a database PDO.
 */

namespace josecarlosphp\db;

class DB_Connection_PDO extends DB_Connection
{
	/**
	 * @var PDO
	 */
	//protected $_dbcon;

	public function __construct($ip='localhost', $dbport=3306, $dbname='', $dbuser='root', $dbpass='root', $connect=true, $charset=null, $debug=false)
	{
		$this->_class = 'PDO';
		parent::__construct($ip, $dbport, $dbname, $dbuser, $dbpass, $connect, $charset, $debug);
	}

	protected function _setCharset($charset)
	{
		return $this->Execute('SET NAMES \''.$charset.'\''); //return $this->Execute('SET CHARACTER SET '.$charset);
	}

	protected function _connect($new)
	{
		return $this->_getPDO($this->_dbhost, $this->_dbuser, $this->_dbpass, $this->_dbname);
	}

	protected function _select_db($dbname)
	{
		return $this->Execute('USE `'.$dbname.'`');
	}

	protected function _ping()
	{
		try {
			$this->Execute('SELECT 1');
		} catch (PDOException $e) {
			return false;
		}

		return true;
	}

	protected function _query($query)
	{
		return $this->_dbcon->query($query);
	}

	protected function _affected_rows()
	{
		return $this->_result->rowCount();
	}

	protected function _insert_id()
	{
		return $this->_dbcon->lastInsertId();
	}

	protected function _close()
	{
		$this->_dbcon = null;

		return true;
	}

	protected function _error()
	{
		$arr = $this->_dbcon->errorInfo();

		return ($arr[0] == '00000') ? '' : $arr[2];
	}

	public function real_escape_string($str)
	{
		$aux = $this->_dbcon->quote($str);

		//Nota: quote() no es exactamente lo mismo que mysql_real_escape_string()
		if(mb_substr($aux, 0, 1) == "'" && mb_substr($aux, -1) == "'")
		{
			$aux = mb_substr($aux, 1, -1);
		}

		return $aux;
	}

	public function quote($str)
	{
		return $this->_dbcon->quote($str);
	}

	public function get_server_info()
	{
		return $this->_dbcon->query('SELECT version()')->fetchColumn();
	}

	public function get_server_version()
	{
		//TODO:

        trigger_error('Method get_server_version not implemented for class DB_Connection_PDO', E_ERROR);

        return false;
	}

	protected static function _getPDO($host, $user, $password, $dbname, $timeout = 5)
	{
		$dsn = 'mysql:';

		if ($dbname) {
			$dsn .= 'dbname='.$dbname.';';
		}

		$matches = array();
		if (preg_match('/^(.*):([0-9]+)$/', $host, $matches)) {
			$dsn .= 'host='.$matches[1].';port='.$matches[2];
		} elseif (preg_match('#^.*:(/.*)$#', $host, $matches)) {
			$dsn .= 'unix_socket='.$matches[1];
		} else {
			$dsn .= 'host='.$host;
		}

		return new \PDO($dsn, $user, $password, array(\PDO::ATTR_TIMEOUT=>$timeout, \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true));
	}
}