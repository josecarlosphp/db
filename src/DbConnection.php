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
 * @desc        Class to work with a database.
 */

namespace josecarlosphp\db;

abstract class DbConnection
{
    protected $_dbhost;
    protected $_dbport;
    protected $_dbname;
    protected $_dbuser;
    protected $_dbpass;
	protected $_dbsock;
    protected $_dbcon;
    protected $_quote_style;
    protected $_charset;
    protected $_connected;
    protected $_cache;
	protected $_debug;
	protected $_autoRetryOnDeadlock;
	protected $_autoRetryOnHasGoneAway;
	protected $_autoRemoveSqlMode;
	protected $_sleepSeconds;
	protected $_maxIteration;
	protected $_error;
	protected $_class;
	protected $_result;
    /**
     * Contador de consultas
     * @var int
     */
    protected $_nqueries;
    /**
     * @var bool
     */
    protected $_defaultHtmlentities = true;
    /**
     * Obtiene un objeto DbConnection, ya sea MySQL, MySQLi, PDO.
     * Devuelve false en caso de error (y lanza error).
     *
     * @param string $ip
     * @param int $dbport
     * @param string $dbname
     * @param string $dbuser
     * @param string $dbpass
     * @param boolean $connect
     * @param string $charset
     * @param boolean $debug
     * @param string $class
     * @return DbConnection
     */
	public static function Factory($ip='localhost', $dbport=3306, $dbname='', $dbuser='root', $dbpass='root', $connect=true, $charset=null, $debug=false, $class=null, $defaultHtmlentities=true)
	{
		if(is_null($class) || !self::validateClass($class))
		{
			$class = self::getClass();
		}

		if($class)
		{
			$class = __NAMESPACE__.'\DbConnection_'.$class;

			return new $class($ip, $dbport, $dbname, $dbuser, $dbpass, $connect, $charset, $debug, $defaultHtmlentities);
		}

		trigger_error('Can not use any DbConnection class (PDO, MySQLi nor MySQL)', E_USER_ERROR);

		return false;
	}
	/**
	 * Get child layer class
	 *
	 * @return string
	 */
	private static function getClass()
	{
		$classes = array('PDO', 'MySQLi', 'MySQL'); //El orden es importante
		foreach($classes as $class)
		{
			if(self::validateClass($class))
			{
				return $class;
			}
		}

		return false;
	}

	protected static function validateClass($class)
	{
		switch($class)
		{
			case 'PDO':
				return PHP_VERSION_ID >= 50200 && extension_loaded('pdo_mysql');
			case 'MySQLi':
				return mb_substr(phpversion(), 0, 3) >= '5.5' && extension_loaded('mysqli');
			case 'MySQL':
				return extension_loaded('mysql');
		}

		return false;
	}
	/**
	 * Obtiene el nombre de la extensión de PHP que se está usando como cliente de la base de datos (PDO, MySQLi, MySQL).
	 *
	 * @return string
	 */
	public function GetExtension()
	{
		return $this->_class;
	}
    /**
	 * @return DbConnection()
	 * @desc Constructor
	 */
	public function __construct($ip='localhost', $dbport=3306, $dbname='', $dbuser='root', $dbpass='root', $connect=true, $charset=null, $debug=false, $defaultHtmlentities=true)
    {
        //Por si acaso alguien incluye el puerto en el host ($ip)
        if(($pos = mb_strpos($ip, ':')) !== false)
        {
            $this->_dbhost = mb_substr($ip, 0, $pos);
			$aux = mb_substr($ip, $pos + 1);
			if(is_numeric($aux))
			{
				$this->_dbport = $aux;
			}
			else
			{
				$this->_dbport = $dbport;
				$this->_dbsock = $aux;
			}
        }
        else
        {
            $this->_dbhost = $ip;
            $this->_dbport = $dbport;
			$this->_dbsock = '';
        }
        $this->_dbname = $dbname;
        $this->_dbuser = $dbuser;
        $this->_dbpass = $dbpass;
        $this->_connected = false;

		if($connect)
        {
            $this->Connect();
        }

		$this->_quote_style = defined('ENT_HTML5') ? ENT_COMPAT | ENT_HTML5 : ENT_COMPAT;

		if(!is_null($charset))
        {
            $this->SetCharset($charset);
        }

		$this->_cache = new \MyCache();

		$this->_debug = $debug ? true : false;

		$this->_autoRetryOnDeadlock = true;
		$this->_autoRetryOnHasGoneAway = true;
		$this->_autoRemoveSqlMode = true;
		$this->_sleepSeconds = 1;
		$this->_maxIteration = 3;
		$this->_error = '';

        $this->_nqueries = 0;

        $this->_defaultHtmlentities = $defaultHtmlentities ? true : false;
    }
    /**
	 * Establece/Obtiene dbhost
	 * Se mantiene el método por compatibilidad hacia atrás
	 *
	 * @deprecated since version 1.0.0
	 * @param string $val
	 * @return string
	 */
	public function ip($val=null)
    {
        return $this->dbhost($val);
    }
	/**
	 * Establece/Obtiene dbhost
	 *
	 * @param string $val
	 * @return string
	 */
	public function dbhost($val=null)
    {
        if(!is_null($val))
        {
            $this->_dbhost = $val;
        }
        return $this->_dbhost;
    }
    /**
	 * Establece/Obtiene dbport
	 *
	 * @param int $val
	 * @return int
	 */
	public function dbport($val=null)
    {
        if(!is_null($val))
        {
            $this->_dbport = $val;
        }
        return $this->_dbport;
    }
    /**
	 * Establece/Obtiene dbname
	 *
	 * @param string $val
	 * @return string
	 */
	public function dbname($val=null)
    {
        if(!is_null($val))
        {
            $this->_dbname = $val;
        }
        return $this->_dbname;
    }
    /**
	 * Establece/Obtiene dbuser
	 *
	 * @param string $val
	 * @return string
	 */
	public function dbuser($val=null)
    {
        if(!is_null($val))
        {
            $this->_dbuser = $val;
        }
        return $this->_dbuser;
    }
    /**
	 * Establece/Obtiene dbpass
	 *
	 * @param string $val
	 * @return string
	 */
	public function dbpass($val=null)
    {
        if(!is_null($val))
        {
            $this->_dbpass = $val;
        }
        return $this->_dbpass;
    }
    /**
	 * Establece/Obtiene defaultHtmlentities
	 *
	 * @param bool $val
	 * @return bool
	 */
    public function defaultHtmlentities($val=null)
    {
        if(!is_null($val))
        {
            $this->_defaultHtmlentities = $val ? true : false;
        }
        return $this->_defaultHtmlentities;
    }
    /**
	 * Establece el modo debug o no
	 *
	 * @param bool $debug
	 * @return bool
	 */
	public function SetDebug($debug)
	{
		$this->_debug = $debug ? true : false;

		return true;
	}
	/**
	 * Obtiene el quote_style establecido
	 *
	 * @param long $quotestyle
	 * @return bool
	 */
	public function SetQuoteStyle($quotestyle)
	{
		$this->_quote_style = $quotestyle;

		return true;
	}
	/**
	 * Obtiene el quote_style establecido
	 *
	 * @return long
	 */
	public function GetQuoteStyle()
	{
		return $this->_quote_style;
	}
	/**
	 * Establece el juego de caracteres
	 * @example $db->SetCharset('ISO-8859-1');
	 * @example $db->SetCharset('UTF-8');
	 *
	 * @param string $charset
	 */
	public function SetCharset($charset)
    {
        $charset = mb_strtoupper($charset);
        $this->_charset = $charset;

        if($this->_connected)
        {
            switch($charset)
            {
                case 'ISO-8859-1':
                    $charset = 'latin1';
                    break;
                case 'UTF-8':
                    $charset = 'utf8';
                    break;
				default:
                    return false;
            }

            $this->_setCharset($charset);
        }

        return false;
    }

	abstract protected function _setCharset($charset);
    /**
	 * Obtiene el charset establecido
	 *
	 * @return string
	 */
	public function GetCharSet()
	{
		return $this->_charset;
	}
	/**
	 * Activa o desactiva autoRetryOnDeadlock
	 *
	 * @param bool $val
	 * @return bool
	 */
	public function SetAutoRetryOnDeadlock($val)
	{
		$this->_autoRetryOnDeadlock = $val ? true : false;

		return true;
	}
	/**
	 * Obtiene si autoRetryOnDeadlock está activado o no
	 *
	 * @return bool
	 */
	public function GetAutoRetryOnDeadlock()
	{
		return $this->_autoRetryOnDeadlock ? true : false;
	}
	/**
	 * Una combinación de SetAutoRetryOnDeadlock() y GetAutoRetryOnDeadlock()
	 * Este método puede asignar y en cualquier caso siempre devuelve si autoRetryOnDeadlock está activado o no
	 *
	 * @param bool $val
	 * @return bool
	 */
	public function AutoRetryOnDeadlock($val=null)
	{
		if(!is_null($val))
		{
			$this->SetAutoRetryOnDeadlock($val);
		}

		return $this->GetAutoRetryOnDeadlock();
	}
	/**
	 * Activa o desactiva autoRetryOnHasGoneAway
	 *
	 * @param bool $val
	 * @return bool
	 */
	public function SetAutoRetryOnHasGoneAway($val)
	{
		$this->_autoRetryOnHasGoneAway = $val ? true : false;

		return true;
	}
	/**
	 * Obtiene si autoRetryOnHasGoneAway está activado o no
	 *
	 * @return bool
	 */
	public function GetAutoRetryOnHasGoneAway()
	{
		return $this->_autoRetryOnHasGoneAway ? true : false;
	}
	/**
	 * Una combinación de SetAutoRetryOnHasGoneAway() y GetAutoRetryOnHasGoneAway()
	 * Este método puede asignar y en cualquier caso siempre devuelve si autoRetryOnHasGoneAway está activado o no
	 *
	 * @param bool $val
	 * @return bool
	 */
	public function AutoRetryOnHasGoneAway($val=null)
	{
		if(!is_null($val))
		{
			$this->SetAutoRetryOnHasGoneAway($val);
		}

		return $this->GetAutoRetryOnHasGoneAway();
	}
	/**
	 * Activa o desactiva autoRemoveSqlMode
	 *
	 * @param bool $val
	 * @return bool
	 */
	public function SetAutoRemoveSqlMode($val)
	{
		$this->_autoRemoveSqlMode = $val ? true : false;

		return true;
	}
	/**
	 * Obtiene si autoRemoveSqlMode está activado o no
	 *
	 * @return bool
	 */
	public function GetAutoRemoveSqlMode()
	{
		return $this->_autoRemoveSqlMode ? true : false;
	}
	/**
	 * Una combinación de SetAutoRemoveSqlMode() y GetAutoRemoveSqlMode()
	 * Este método puede asignar y en cualquier caso siempre devuelve si autoRemoveSqlMode está activado o no
	 *
	 * @param bool $val
	 * @return bool
	 */
	public function AutoRemoveSqlMode($val=null)
	{
		if(!is_null($val))
		{
			$this->SetAutoRemoveSqlMode($val);
		}

		return $this->GetAutoRemoveSqlMode();
	}
    /**
     * Obtiene cuántas consultas se han ejecutado desde que se creó el objeto hasta ahora.
     *
     * @return int
     */
    public function GetQueriesCount()
    {
        return $this->_nqueries;
    }
	/**
	 * Obtiene el host (ip o nombre) junto con el puerto.
	 * Este método está obsoleto, en su lugar usar GetServer().
	 *
	 * @deprecated since version 1.0.0
	 * @return string
	 */
	public function GetDBHost()
    {
        return $this->GetServer();
    }
	/**
	 * Obtiene el host (ip o nombre) junto con el puerto
	 *
	 * @return string
	 */
	public function GetServer()
    {
        return $this->_dbhost.':'.($this->_dbsock ? $this->_dbsock : $this->_dbport);
    }
    /**
	 * @return bool
	 * @param bool $selectDB
	 * @param bool $new
	 * @desc Connects to server
	 */
	public function Connect($selectDB=true, $new=true)
    {
        $this->_dbcon = $this->_connect($new);
        if($this->_dbcon)
        {
            $this->_connected = true;

			if($this->_charset)
            {
                $this->SetCharset($this->_charset);
            }

            return $selectDB ? $this->SelectDB($this->_dbname) : true;
        }

        return false;
    }
	abstract protected function _connect($new);
    /**
	 * @return bool
	 * @param $dbname string
	 * @desc Selects the database to work with. Returns true if exists, false if not.
	 */
	public function SelectDB($dbname=null)
    {
        if(is_null($dbname))
        {
            $dbname = $this->_dbname;
        }
        else
        {
            $this->_dbname = $dbname;
        }

        return $this->_select_db($dbname);
    }
	abstract protected function _select_db($dbname);
    /**
	 * Checks whether or not the connection to the server is working.
	 * If it has gone down and PHP version < 5.0.13 or $reconnect, an automatic reconnection is attempted .
	 *
     * @param bool $reconnect
	 * @return bool
	 */
	public function Ping($reconnect=false)
    {
        $r = $this->_ping();

        if($reconnect && phpversion() >= '5.0.13')
        {
            $this->Connect();
            $r = $this->Ping(false);
        }

        return $r;
    }
	abstract protected function _ping();
    /**
	 * @return mixed
	 * @param $query string
	 * @desc Execute a SQL sentence
	 */
	public function Execute($query)
    {
        return self::expectBool($query) ? (bool)$this->_Execute($query) : $this->_Execute($query);
    }
	/**
	 * @return mixed
	 * @param string $query
	 * @param string $iteration
	 * @desc Execute a SQL sentence, tenemos el método público Execute() que llama al protegido _Execute, es para evitar que el parámetro $iteration sea público
	 */
	protected function _Execute($query, $iteration=0)
	{
		/*if($this->_connected)
		{*/
			$this->_result = $this->_query($query);
            $this->_nqueries++;
			/*
			$file = 'db.log';
			$fp = fopen($file, 'a');
			fwrite($fp, $query.";\n----------------------------------------------------\n");
			fwrite($fp, var_export($this->_result, true).";\n================================================\n");
			fclose($fp);
            */
			if($this->_result === false)
			{
				$this->_error = $this->_error();
				if($this->_autoRetryOnDeadlock && mb_stripos($this->_error, 'Deadlock found when trying to get lock; try restarting transaction') !== false)
				{
					$this->_MsgDbg("Deadlock found when trying to get lock<br />\n".$this->HtmlEntities($query));

					$iteration++;

					if($iteration > $this->_maxIteration)
					{
						$this->_MsgDbg('Too much iteration');

						throw new \Exception('Too much iteration');
					}
					else
					{
						sleep($this->_sleepSeconds);
						$this->_result = $this->_Execute($query, $iteration);
					}
				}
				elseif($this->_autoRetryOnHasGoneAway && mb_stripos($this->_error, 'MySQL server has gone away') !== false)
				{
					$this->_MsgDbg("MySQL server has gone away<br />\n".$this->HtmlEntities($query));

					sleep($this->_sleepSeconds * 10);
					if($this->Connect())
					{
						$this->_result = $this->_Execute($query);
					}
					else
					{
						$this->_MsgDbg('Can not re-connect after MySQL server has gone away');

						throw new \Exception('Can not re-connect after MySQL server has gone away');
					}
				}
				elseif($this->_autoRemoveSqlMode && (mb_stripos($this->_error, 'doesn\'t have a default value') !== false || mb_stripos($this->_error, 'Incorrect datetime value') !== false || mb_stripos($this->_error, 'Incorrect date value') !== false || mb_stripos($this->_error, 'Data too long for column') !== false))
                {
                    $this->_MsgDbg($this->_error."<br />\n".$this->HtmlEntities($query));

                    $iteration++;

                    if($iteration > $this->_maxIteration)
					{
						$this->_MsgDbg('Too much iteration');

						throw new \Exception('Too much iteration');
					}
					elseif($this->_Execute("SET SQL_MODE = ''"))
					{
						$this->_result = $this->_Execute($query, $iteration);
					}
                }
				else
				{
					$this->_MsgDbg("Error SQL<br />\n".$this->HtmlEntities($query)."<br />\n".$this->_error);
				}
			}

			return $this->_result;
		/*}

		$this->_error = 'Not connected';

		$this->_MsgDbg("Error SQL<br />\n".$this->HtmlEntities($query)."<br />\n".$this->_error);

		return false;*/
	}
	abstract protected function _query($query);
	/**
	 * @return mixed
	 * @desc Get number of affected rows in previous SQL operation. Not yet operative for ODBC databases.
	 */
	public function AffectedRows()
    {
        return $this->_affected_rows();
    }
	abstract protected function _affected_rows();
    /**
	 * Returns the ID generated for an AUTO_INCREMENT column by the previous INSERT query on success,
	 * 0 if the previous query does not generate an AUTO_INCREMENT value,
	 * or FALSE if no MySQL connection was established.
	 * @return mixed
	 */
	public function Insert_id()
    {
        return $this->_insert_id();
    }
	abstract protected function _insert_id();
    /**
	 * Comprueba si existe un registro en una tabla con el valor(es) especificado(s) para el campo(s) dado(s). El filtro sólo permite where.
     * Se puede guardar el resultado en cache.
	 *
	 * @param mixed $valores
	 * @param mixed $campos
	 * @param string $tabla
	 * @param string $filtro
     * @param bool $cache
	 * @return bool
	 */
	public function Exists($valores, $campos, $tabla, $filtro='', $cache=false)
    {
        if(trim($filtro) == '')
        {
            $filtro = 'WHERE 1';
        }

        if(is_array($valores) && is_array($campos))
        {
            $size_valores = sizeof($valores);
            $size_campos = sizeof($campos);

            if($size_valores > 0 && $size_valores == $size_campos)
            {
				$query = "SELECT `{$campos[0]}` FROM `{$tabla}` {$filtro}";

                for($c=0; $c<$size_valores; $c++)
                {
                    $query .= sprintf(" AND `%s` = '%s'", $campos[$c], addcslashes($valores[$c], "\\'"));
                }
            }
        }
        else
        {
            $query = sprintf("SELECT `%s` FROM `%s` %s AND `%s` = '%s'", $campos, $tabla, $filtro, $campos, addcslashes($valores, "\\'"));
        }

        return $this->ExistsQuery($query, $cache);
    }
    /**
	 * Comprueba si la consulta indicada devuelve al menos una fila.
     * Se puede guardar el resultado en cache.
	 *
	 * @param string $query
     * @param bool $cache
	 * @return bool
	 */
	public function ExistsQuery($query, $cache=false)
    {
        if($cache && $this->_cache->Exists('ExistsQuery', $query))
        {
            return $this->_cache->Get('ExistsQuery', $query);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);
        $r = $rs->NumRows() > 0;

		if($cache)
        {
            $this->_cache->Set($r, 'ExistsQuery', $query);
        }

		return $r;
    }
    /**
	 * Obtiene la primera fila resultado de la consulta $query
	 *
	 * @param string $query
	 * @param bool $assoc
	 * @param bool $htmlentities
     * @param bool $cache
	 * @return array
	 */
	public function GetRow($query, $assoc=true, $htmlentities=null, $cache=false)
    {
        if(is_null($htmlentities))
        {
            $htmlentities = $this->_defaultHtmlentities;
        }

        $ckey = sprintf('%s-%s', $assoc, $htmlentities);
		if($cache && $this->_cache->Exists('GetRow', $query, $ckey))
        {
            return $this->_cache->Get('GetRow', $query, $ckey);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);

        $row = null;
        if($assoc)
        {
            if(($reg = $rs->FetchAssoc()))
            {
                $row = $htmlentities ? $this->HtmlEntities($reg) : $reg;
            }
        }
        else
        {
            if(($reg = $rs->FetchRow()))
            {
                $row = $htmlentities ? $this->HtmlEntities($reg) : $reg;
            }
        }

        if($cache)
        {
            $this->_cache->Set($row, 'GetRow', $query, $ckey);
        }

        return $row;
    }
    /**
	 * Obtiene las filas resultado de la consulta $query como un array asociativo.
	 * Si se pasa $indexField, el índice de cada fila será el valor de ese campo.
	 *
	 * @param string $query
	 * @param bool $assoc
	 * @param bool $htmlentities
	 * @param string $indexField
	 * @return array
	 */
	public function GetRows($query, $assoc=true, $htmlentities=null, $indexField=null, $cache=false)
    {
        if(is_null($htmlentities))
        {
            $htmlentities = $this->_defaultHtmlentities;
        }

        $ckey = sprintf('%s-%s-%s', $assoc, $htmlentities, $indexField);
		if($cache && $this->_cache->Exists('GetRows', $query, $ckey))
        {
            return $this->_cache->Get('GetRows', $query, $ckey);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);
        $rows = array();

		if($assoc)
		{
			if(is_null($indexField))
			{
				while($reg = $rs->FetchAssoc())
				{
					$rows[] = $htmlentities ? $this->HtmlEntities($reg) : $reg;
				}
			}
			else
			{
				while($reg = $rs->FetchAssoc())
				{
					$rows[$reg[$indexField]] = $htmlentities ? $this->HtmlEntities($reg) : $reg;
				}
			}
		}
		else
		{
			if(is_null($indexField))
			{
				while($reg = $rs->FetchRow())
				{
					$rows[] = $htmlentities ? $this->HtmlEntities($reg) : $reg;
				}
			}
			else
			{
				while($reg = $rs->FetchRow())
				{
					$rows[$reg[$indexField]] = $htmlentities ? $this->HtmlEntities($reg) : $reg;
				}
			}
		}

		if($cache)
        {
            $this->_cache->Set($rows, 'GetRows', $query, $ckey);
        }

        return $rows;
    }
    /**
	 * Obtiene el número de filas resultantes de la consulta $query
	 * o de la tabla si se pasa el nombre de una tabla como parámetro
	 *
	 * @param string $query
	 * @return int
	 */
	public function GetCount($query)
    {
		if(mb_strpos($query, ' ') === false)
		{
			return $this->GetValueQuery(sprintf("SELECT COUNT(*) FROM `%s`", $query), false);
		}

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);
        return $rs->NumRows();
    }
	/**
	 * Obtiene el valor de un campo de una tabla para un determinado registro identificado por $ids
	 *
	 * @param string $campo
	 * @param string $tabla
	 * @param mixed $ids
	 * @param bool $htmlentities
	 * @param bool $cache
	 * @return mixed
	 */
	public function GetValueById($campo, $tabla, $ids, $htmlentities=null, $cache=false)
    {
		return $this->GetValue($campo, $tabla, self::ids2where($ids), true, $htmlentities, $cache);
    }
    /**
	 * Obtiene el valor de un campo del primer registro de una tabla filtrada (nulo si no hay registros)
	 *
	 * @param string $campo
	 * @param string $tabla
	 * @param string $filtro
	 * @param bool $addcomilla
	 * @param bool $htmlentities
	 * @param bool $cache
	 * @return mixed
	 */
	public function GetValue($campo, $tabla, $filtro="WHERE 1", $addcomilla=true, $htmlentities=null, $cache=false)
    {
        $query = $addcomilla ? "SELECT `$campo` FROM `$tabla` $filtro" : "SELECT $campo FROM $tabla $filtro";
        return $this->GetValueQuery($query, $htmlentities, $cache);
    }
    /**
	 * Obtiene el valor de un campo del primer registro del resultado de una consulta (nulo si no hay)
	 *
	 * @param string $query
	 * @param bool $htmlentities
	 * @param bool $cache
	 * @return mixed
	 */
	public function GetValueQuery($query, $htmlentities=null, $cache=false)
    {
        if(is_null($htmlentities))
        {
            $htmlentities = $this->_defaultHtmlentities;
        }

        $ckey = sprintf('%s', $htmlentities);
        if($cache && $this->_cache->Exists('GetValueQuery', $query, $ckey))
        {
            return $this->_cache->Get('GetValueQuery', $query, $ckey);
        }

        $r = null;
        $rs = DbResultSet::Factory($this->_class);
        if(($rs->Set = $this->Execute($query)))
        {
            if(($reg = $rs->FetchRow()))
            {
                $r = $htmlentities ? $this->HtmlEntities($reg[0]) : $reg[0];
            }
        }

        if($cache)
        {
            $this->_cache->Set($r, 'GetValueQuery', $query, $ckey);
        }

        return $r;
    }
    /**
	 * Obtiene un array con los valores de los campos de una tabla para un determinado registro identificado por $ids
	 *
	 * @param array $campos
	 * @param string $tabla
	 * @param mixed $ids
	 * @param bool $htmlentities
	 * @param bool $cache
	 * @return mixed
	 */
	public function GetValuesById($campos, $tabla, $ids, $htmlentities=null, $cache=false)
    {
        return $this->GetValues($campos, $tabla, self::ids2where($ids), $htmlentities, $cache);
    }
    /**
	 * Obtiene un array con los valores de un campo(s) de los registros de una tabla filtrada
	 *
	 * @param mixed $campos
	 * @param string $tabla
	 * @param string $filtro
	 * @param bool $htmlentities
	 * @param bool $cache
	 * @return array
	 */
	public function GetValues($campos, $tabla, $filtro="WHERE 1", $htmlentities=null, $cache=false)
    {
        if(is_null($htmlentities))
        {
            $htmlentities = $this->_defaultHtmlentities;
        }

        $campos_select = "";
        if(is_array($campos))
        {
            for($c = 0, $size = sizeof($campos); $c < $size; $c++)
            {
                if($c > 0)
                {
                    $campos_select .= ",";
                }
                $campos_select .= "`{$campos[$c]}`";
            }
        }
        else
        {
            $campos_select = "`$campos`";
        }

        return $this->GetValuesQuery("SELECT $campos_select FROM `$tabla` $filtro", $htmlentities, $cache);
    }
    /**
	 * Obtiene un array con los valores resultado de una consulta
	 *
	 * @param string $query
	 * @param bool $htmlentities
	 * @param bool $cache
	 * @return array
	 */
	public function GetValuesQuery($query, $htmlentities=null, $cache=false)
    {
        if(is_null($htmlentities))
        {
            $htmlentities = $this->_defaultHtmlentities;
        }

        $ckey = sprintf('%s', $htmlentities);
		if($cache && $this->_cache->Exists('GetValuesQuery', $query, $ckey))
        {
            return $this->_cache->Get('GetValuesQuery', $query, $ckey);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);
        $values = array();
        while($reg = $rs->FetchAssoc())
        {
            if(sizeof($reg) > 1)
            {
                $values[] = $htmlentities ? $this->HtmlEntities($reg) : $reg;
            }
            else
            {
                $keys = array_keys($reg);
                $values[] = $htmlentities ? $this->HtmlEntities($reg[$keys[0]]) : $reg[$keys[0]];
            }
        }

		if($cache)
        {
            $this->_cache->Set($values, 'GetValuesQuery', $query, $ckey);
        }

        return $values;
    }

    public function GetArrayForHTMLSelectMulti($index_field, $text_field, $padre_field, $table, $idPadre = 0, $order = "", $option = null, $htmlentities = true)
    {
        $array = array();
        if(is_array($option) && is_array($option[0]))
        {
            foreach($option as $opt)
            {
                if(is_array($opt) && sizeof($opt) == 2)
                {
                    $array[$opt[0]] = $opt[1];
                }
            }
        }
        elseif(is_array($option) && sizeof($option) == 2)
        {
            $array[$option[0]] = $option[1];
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute(sprintf("SELECT `%s`, `%s`, `%s` FROM `%s` WHERE `%s` = '%s' %s", $index_field, $text_field, $padre_field, $table, $padre_field, $idPadre, $order));
        while($reg = $rs->FetchAssoc())
        {
            $count = $this->GetValueQuery(sprintf("SELECT COUNT(`%s`) FROM `%s` WHERE `%s` = '%s'", $index_field, $table, $padre_field, $reg[$index_field]), false);
            if($count > 0)
            {
                $array[$reg[$text_field]] = $this->GetArrayForHTMLSelectMulti($index_field, $text_field, $padre_field, $table, $reg[$index_field], $order, null, $htmlentities);
            }
            else
            {
                $array[$reg[$index_field]] = $htmlentities ? $this->HtmlEntities($reg[$text_field]) : $reg[$text_field];
            }
        }

        return $array;
    }

    public function GetArrayForHTMLSelect($index_field, $text_field, $table, $filter='', $option=null, $addcomilla=true, $htmlentities=null, $cache=false)
    {
        $query = $addcomilla ? "SELECT `$index_field`, `$text_field` FROM `$table` $filter" : "SELECT $index_field, $text_field FROM $table $filter";
        return $this->GetArrayForHTMLSelectQuery($query, $option, $htmlentities, $cache);
    }

    public function GetArrayForHTMLSelectQuery($query, $option=null, $htmlentities=null, $cache=false)
    {
        if(is_null($htmlentities))
        {
            $htmlentities = $this->_defaultHtmlentities;
        }

        $ckey = sprintf('%s-%s', serialize($option), $htmlentities);
		if($cache && $this->_cache->Exists('GetArrayForHTMLSelectQuery', $query, $ckey))
        {
            return $this->_cache->Get('GetArrayForHTMLSelectQuery', $query, $ckey);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);
        $array = array();
        if(is_array($option) && is_array($option[0]))
        {
            foreach($option as $opt)
            {
                if(is_array($opt) && sizeof($opt) == 2)
                {
                    $array[$opt[0]] = $opt[1];
                }
            }
        }
        elseif(is_array($option) && sizeof($option) == 2)
        {
            $array[$option[0]] = $option[1];
        }

        while($reg = $rs->FetchRow())
        {
            $array[$reg[0]] = $htmlentities ? $this->HtmlEntities($reg[1]) : $reg[1];
        }

		if($cache)
        {
            $this->_cache->Set($array, 'GetArrayForHTMLSelectQuery', $query, $ckey);
        }

        return $array;
    }
    /**
     * @return resource
     */
    public function GetLinkIdentifier()
    {
        return $this->_dbcon;
    }
    /**
     * @param resource
     */
    public function SetLinkIdentifier($dbcon)
    {
        $this->_dbcon = $dbcon;
    }
    /**
	 * @return bool
	 * @desc Closes the connection
	 */
	public function Close()
    {
        $this->_connected = false;
        return $this->_close();
    }
	abstract protected function _close();
    /**
	 * @return string
	 * @desc Gets the last error message from an operation with this connection.
	 */
	public function Error()
    {
        return $this->_error;
    }
	abstract protected function _error();
    /**
     * @return void
     * @desc Destructor implementation to ensure that we close.
     */
    public function __destruct()
    {
        $this->Close();
    }
    /**
	 * @return string
	 * @param $tables array
	 * @param $exportstructure bool
	 * @param $exportdata bool
	 * @param $droptable bool
	 * @desc Gets the database as an SQL script. If $tables is empty, write down all tables.
	 */
	public function GetSQLDatabase($tables=array(), $exportstructure=true, $exportdata=true, $droptable=true)
    {
        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute("SELECT VERSION()");
        list($serverversion) = $rs->FetchRow();
		$sql = "# DbConnection by josecarlosphp\r\n"
			."#\r\n"
			."# Host: ".$this->_dbhost."   Database: ".$this->_dbname."\r\n"
			."# --------------------------------------------------------\r\n"
			."# Server version {$serverversion}\r\n";

		if(sizeof($tables) > 0)
        {
            foreach($tables as $table)
            {
                $sql .= $this->GetSQLTable($table, $exportstructure, $exportdata, $droptable);
            }
        }
        else
        {
            $rs = DbResultSet::Factory($this->_class);
            $rs->Set = $this->Execute("SHOW TABLES");
            while(list($table) = $rs->FetchRow())
            {
                $sql .= $this->GetSQLTable($table, $exportstructure, $exportdata, $droptable);
            }
        }

        return $sql;
    }
    /**
	 * @return string
	 * @param $table string
	 * @param $exportstructure bool
	 * @param $exportdata bool
	 * @param $droptable bool
	 * @desc Gets a table as an SQL script.
	 */
	public function GetSQLTable($table, $exportstructure=true, $exportdata=true, $droptable=true)
    {
        $sql = "";

        if($exportstructure)
        {
            $rs = DbResultSet::Factory($this->_class);
            $rs->Set = $this->Execute("SHOW CREATE TABLE `$table`");
            $reg = $rs->FetchAssoc();
            $sql .= "#\r\n"
				."# Table structure for table `$table`\r\n"
				."#\r\n"
				.($droptable ? "DROP TABLE IF EXISTS `$table`;\r\n" : "")
				.$reg["Create Table"] . ";\r\n";
        }

        if($exportdata)
        {
            $c = 0;
            $max = 1; //10
            $sql .= "#\r\n"
				."# Dumping data for table `$table`\r\n"
				."#\r\n";
            $rs->Set = $this->Execute("SELECT * FROM `$table`");
            $numRows = $rs->NumRows();
            while($reg = $rs->FetchRow())
            {
                if ($c % $max == 0) {
                    $sql .= "INSERT INTO `$table` VALUES";
                }

                $c++;

                $sql .= "(";
                for($i=0, $regsize=sizeof($reg); $i<$regsize; $i++)
                {
                    $myreg = $reg[$i];

					if(is_null($myreg))
					{
						$myreg = 'NULL';
					}
					else
					{
						switch($rs->FieldType($i))
						{
							case 'string':
							case 'datetime':
							case 'unknown':
								$myreg = "'".addcslashes($myreg, "\\'")."'"; //$myreg = $this->quote($myreg);
						}
					}

                    $sql .= $myreg.(($i < $regsize - 1) ? ", " : (($c % $max == 0) || ($c == $numRows) ? ");\r\n" : "),\r\n"));
                }
            }
        }

        return $sql;
    }
	/**
	 * @return string
	 * @param $filename string
	 * @param $table string
     * @param $mode string
	 * @param $exportstructure bool
	 * @param $exportdata bool
	 * @param $droptable bool
	 * @desc Write in a file a table as an SQL script.
	 */
	public function WriteSQLTable($filename, $table, $mode='w', $exportstructure=true, $exportdata=true, $droptable=true)
    {
		if(($fp = fopen($filename, $mode)))
		{
			if($exportstructure)
			{
				$rs = DbResultSet::Factory($this->_class);
				$rs->Set = $this->Execute("SHOW CREATE TABLE `$table`");
				$reg = $rs->FetchAssoc();
				$str = "#\r\n"
					."# Table structure for table `$table`\r\n"
					."#\r\n"
					.($droptable ? "DROP TABLE IF EXISTS `$table`;\r\n" : "")
					.$reg["Create Table"] . ";\r\n";
				if($this->Write($fp, $str) === false)
				{
					return false;
				}
			}
//TODO: Ajustar a max_allowed_packet size (normalmente 1 Gb)
			if($exportdata)
			{
                $c = 0;
                $max = 10;
				$str = "#\r\n"
					."# Dumping data for table `$table`\r\n"
					."#\r\n";
				if($this->Write($fp, $str) === false)
				{
					return false;
				}

				$rs->Set = $this->Execute("SELECT * FROM `$table`");
                $numRows = $rs->NumRows();
				while($reg = $rs->FetchRow())
				{
                    $str = ($c % $max == 0) ? "INSERT INTO `$table` VALUES" : "";

                    $c++;

                    $str .= "(";
                    for($i=0, $regsize=sizeof($reg); $i<$regsize; $i++)
					{
						$myreg = $reg[$i];

						if(is_null($myreg))
						{
							$myreg = 'NULL';
						}
						else
						{
							switch($rs->FieldType($i))
							{
								case 'string':
								case 'datetime':
								case 'unknown':
									$myreg = "'".addcslashes($myreg, "\\'")."'"; //$myreg = $this->quote($myreg);
							}
						}

						$str .= $myreg.(($i < $regsize - 1) ? ", " : (($c % $max == 0) || ($c == $numRows) ? ");\r\n" : "),\r\n"));
					}

					if($this->Write($fp, $str) === false)
					{
						return false;
					}
				}
			}

			fclose($fp);

			return true;
		}
		else
		{
			$this->_error = 'Can not open file to write: '.$filename;
		}

		return false;
    }

	protected function Write($fp, $str)
	{
		$r = fwrite($fp, $str);
		if($r === false)
		{
			$this->_error = 'Can not write';
			fclose($fp);
		}

		return $r;
	}

	abstract public function real_escape_string($str);

	abstract public function quote($str);
	/**
	 * Obtiene los datos de acceso (dbhost, dbport, dbname, dbuser, dbpass) en forma de array asociativo.
	 * Repite dbhost con el índice ip, por compatibilidad hacia atrás.
	 *
	 * @return array
	 */
    public function GetAccessData()
    {
        return array(
            'ip' => $this->_dbhost,
            'dbhost' => $this->_dbhost,
            'dbport' => $this->_dbport,
            'dbname' => $this->_dbname,
            'dbuser' => $this->_dbuser,
            'dbpass' => $this->_dbpass
        );
    }
	/**
	 * Establece los datos de conexión (dbhost, dbport, dbname, dbuser, dbpass) mediante un array asociativo $data
	 *
	 * @param array $data
	 * @param bool $connect
	 * @param bool $selectDb
	 * @param bool $new
	 * @return boolean
	 */
    public function SetAccessData($data, $connect=false, $selectDb=true, $new=true)
    {
        $this->_dbhost = isset($data['dbhost']) ? $data['dbhost'] : $data['ip'];
        $this->_dbport = $data['dbport'];
        $this->_dbname = $data['dbname'];
        $this->_dbuser = $data['dbuser'];
        $this->_dbpass = $data['dbpass'];
        if($connect)
        {
            return $this->Connect($selectDb, $new);
        }
        return true;
    }
    /**
     * Obtiene el conjunto de nombres de tablas de la base de datos actual.
     *
     * @param string $prefix
     * @param bool $exclude
     * @return array
     */
    public function GetTables($prefix='', $exclude=false)
    {
        $query = "SHOW TABLES";

        if ($prefix) {
            $query .= " WHERE Tables_in_" . $this->_dbname;
            if ($exclude) {
                $query .= " NOT";
            }
            $query .= " LIKE '" . $this->real_escape_string($prefix) . "%'";
        }

        $tables = array();
        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($query);
        while(list($table) = $rs->FetchRow())
        {
            $tables[] = $table;
        }

        return $tables;
    }
	/**
	 * Obtiene los campos de una tabla
	 *
	 * @param string $tabla
	 * @param bool $comoShowFields
     * @param bool $cache
	 * @return array
	 */
    public function GetFields($tabla, $comoShowFields=false, $cache=false)
    {
        if($cache && $this->_cache->Exists('GetFields', $tabla, $comoShowFields))
        {
            return $this->_cache->Get('GetFields', $tabla, $comoShowFields);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute($comoShowFields ? "SHOW FIELDS FROM `{$tabla}`" : "SELECT COLUMN_NAME AS name, DATA_TYPE AS type, COLUMN_COMMENT AS comment FROM INFORMATION_SCHEMA.Columns WHERE TABLE_SCHEMA = '" . $this->_dbname . "' AND TABLE_NAME = '{$tabla}' ORDER BY ORDINAL_POSITION ASC");
        $rows = array();
        if($comoShowFields)
		{
			while($reg = $rs->FetchAssoc())
			{
				foreach($reg as $key=>$val)
				{
					$reg[strtolower($key)] = $val;
				}

				if(!isset($reg['Name']))
				{
					$reg['Name'] = $reg['name'] = $reg['field'];
				}

				$rows[$reg['name']] = $reg;
			}
		}
		else
		{
			while($reg = $rs->FetchAssoc())
			{
				foreach($reg as $key=>$val)
				{
					$reg[ucfirst($key)] = $val;
				}

				$reg['field'] = $reg['Field'] = $reg['Name'];

				$rows[$reg['name']] = $reg;
			}
		}

        if($cache)
        {
            $this->_cache->Set($rows, 'GetFields', $tabla, $comoShowFields);
        }

        return $rows;
    }

	public function GetFieldsNames($tabla, $cache=false)
    {
        if($cache && $this->_cache->Exists('GetFieldsNames', $tabla))
        {
            return $this->_cache->Get('GetFieldsNames', $tabla);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute("SELECT COLUMN_NAME AS name FROM INFORMATION_SCHEMA.Columns WHERE TABLE_SCHEMA = '" . $this->_dbname . "' AND TABLE_NAME = '{$tabla}' ORDER BY ORDINAL_POSITION ASC");
        $rows = array();
        while($reg = $rs->FetchAssoc())
        {
            $rows[] = $reg['name'];
        }

        if($cache)
        {
            $this->_cache->Set($rows, 'GetFieldsNames', $tabla);
        }

        return $rows;
    }
	/**
	 * Obtiene el nombre de los campos que son clave primaria,
	 * o todos si no hay ninguno y $allIfNone es true
	 *
	 * @param string $tabla
	 * @param bool $allIfNone
	 * @return array
	 */
	public function GetPrimaryKeys($tabla, $allIfNone=false)
	{
		$rs = DbResultSet::Factory($this->_class);
		$rs->Set = $this->Execute("SHOW FIELDS FROM `{$tabla}` WHERE `key` = 'PRI'");
		$keys = array();
		while($reg = $rs->FetchAssoc())
		{
			$keys[] = $reg['Field'];
		}

		if(empty($keys) && $allIfNone)
		{
			$keys = $this->GetValuesQuery("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.Columns WHERE TABLE_SCHEMA = '".$this->_dbname."' AND TABLE_NAME = '{$tabla}' ORDER BY ORDINAL_POSITION ASC", false, false);
		}

		return $keys;
	}
    /**
	 * Comprueba si existe una tabla
	 *
	 * @param string $tabla
	 * @param bool $cache
	 * @return bool
	 */
	public function TableExists($tabla, $cache=true)
    {
        if($cache && $this->_cache->Exists('TableExists', $tabla))
        {
            return $this->_cache->Get('TableExists', $tabla);
        }

        $rs = DbResultSet::Factory($this->_class);
        $rs->Set = $this->Execute("SHOW TABLES"); // WHERE `Tables_in_".$this->_dbname."` = '".$tabla."'
        while($reg = $rs->FetchRow())
        {
            if($reg[0] == $tabla)
            {
                if($cache)
                {
                    $this->_cache->Set(true, 'TableExists', $tabla);
                }
                return true;
            }
        }

        if($cache)
        {
            $this->_cache->Set(false, 'TableExists', $tabla);
        }

        return false;
    }
    /**
	 * Comprueba si existe un campo en una tabla.
     * Por defecto, se guarda el resultado en cache.
	 *
	 * @param string $tabla
	 * @param string $field
     * @param bool $cache
	 * @return bool
	 */
	public function FieldExists($tabla, $field, $cache=true)
    {
        return $this->ExistsQuery(sprintf("SHOW FIELDS FROM `%s` WHERE Field = '%s'", addcslashes($tabla, "\\'"), addcslashes($field, "\\'")), $cache);
    }
    /**
	 * Aplica htmlentities con el quotestyle y charset establecidos,
	 * directamente si $var es una cadena, y recursivamente si es un array
	 *
	 * @param mixed $var
	 * @return mixed
	 */
	public function HtmlEntities($var)
	{
		if (is_array($var)) {
			foreach ($var as $key=>$item) {
				$var[$key] = $this->HtmlEntities($item);
			}
		} elseif (is_string($var)) {
            $charset = $this->GetCharSet();
            switch ($charset) {
                case 'UTF8MB4':
                    $charset = 'UTF-8';
                    break;
            }

			$var = htmlentities($var, $this->GetQuoteStyle(), $charset);
		}

		return $var;
	}
	/**
	 * Construye la sentencia para ajustar el valor de AUTO_INCREMENT a una tabla
	 *
	 * @param string $tabla
	 * @param string $campo_id
	 * @param bool $sobreMaxId
	 * @return mixed
	 */
	public function GetQueryAjustarAutoIncrement($tabla, $campo_id='id', $sobreMaxId=true)
	{
		return sprintf("ALTER TABLE `{$tabla}` AUTO_INCREMENT = %u", $sobreMaxId ? 1 + intval($this->GetValue("MAX(`{$campo_id}`)", $tabla, 'WHERE 1', false, false)) : 1);
	}
    /**
	 * Ajusta el valor de AUTO_INCREMENT a una tabla
	 *
	 * @param string $tabla
	 * @param string $campo_id
	 * @param bool $sobreMaxId
	 * @return bool
	 */
	public function AjustarAutoIncrement($tabla, $campo_id='id', $sobreMaxId=true)
    {
		return $this->Execute($this->GetQueryAjustarAutoIncrement($tabla, $campo_id, $sobreMaxId));
    }
	/**
	* @return string
	* @param mixed $ids
	* @param bool $devolverVacio
	* @param bool $not
	*/
	public static function ids2where($ids, $devolverVacio=false, $not=false)
	{
		$op = $not ? '!=' : '=';
		$where = '';

		if(is_array($ids))
		{
			$keys = array_keys($ids);
			for($c=0,$size=sizeof($ids); $c<$size; $c++)
			{
				$where .= sprintf(" %s `%s` %s '%s'", $c > 0 ? ' AND ' : ' WHERE ', $keys[$c], $op, addcslashes($ids[$keys[$c]], "\\'"));
			}
		}
		elseif(!is_null($ids) && $ids != '')
		{
			$where = sprintf(" WHERE id %s '%s'", $op, addcslashes($ids, "\\'"));
		}
		elseif(!$devolverVacio)
		{
			$where = ' WHERE 0 '; //Muy importante, si no hay ids no quiero que me coja ninguno
		}

		return $where;
	}

	public static function array2set($arr)
	{
		$str = '';

		$sep = '';
		foreach($arr as $key=>$val)
		{
			$str .= sprintf("%s `%s` = '%s'", $sep, $key, addcslashes($val, "\\'"));
			$sep = ',';
		}

		return $str;
	}

	public static function array2values($arr)
	{
		$str = '';

		$sep = '';
		foreach($arr as $val)
		{
			$str .= sprintf("%s '%s'", $sep, addcslashes($val, "\\'"));
			$sep = ',';
		}

		return $str;
	}

	protected function _MsgDbg($str)
	{
		if($this->_debug)
		{
			echo "<br />\n".$str."<br />\n";
		}
	}

	abstract public function get_server_info();

	/*abstract public function get_server_version();*/

    protected static function expectBool($query)
    {
        switch(self::getCommand($query))
        {
            case 'DROP':
            case 'TRUNCATE':
            case 'CREATE':
            case 'ALTER':
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
                return true;
        }

        return false;
    }

    protected static function getCommand($query, $toUpper = true)
    {
        $query = trim($query);
        $pos = mb_strpos($query, ' ');
        $command = $pos !== false ? mb_substr($query, 0, $pos) : $query;

        return $toUpper ? mb_strtoupper($command) : $command;
    }

    public function SelectQuery2Csv($query, $filename, $delimiter=',', $enclosure='"', $escape_char='\\')
    {
        if(($fp = fopen($filename, 'w')) !== false)
        {
            $rs = DbResultSet::Factory($this->_class);
            $rs->Set = $this->Execute($query);
            $primero = true;
            while($reg = $rs->FetchAssoc())
            {
                if($primero)
                {
                    fputcsv($fp, array_keys($reg), $delimiter, $enclosure, $escape_char);
                    $primero = false;
                }

                fputcsv($fp, $reg, $delimiter, $enclosure, $escape_char);
            }

            fclose($fp);

            return true;
        }
        else
        {
            $this->_error = 'Can not open file to write: '.$filename;
        }

        return false;
    }
}
