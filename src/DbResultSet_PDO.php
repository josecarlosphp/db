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
 * @copyright   2008-2025 José Carlos Cruz Parra
 * @license     https://www.gnu.org/licenses/gpl.txt GPL version 3
 * @desc        Class to work with a database resultset PDO.
 */

namespace josecarlosphp\db;

class DbResultSet_PDO extends DbResultSet
{
	/**
	 * @var PDOStatement
	 */
	//public $Set;

	public function FetchRow()
    {
		return is_object($this->Set) ? $this->Set->fetch(\PDO::FETCH_NUM) : array();
    }

	public function FetchAssoc()
    {
		return is_object($this->Set) ? $this->Set->fetch(\PDO::FETCH_ASSOC) : array();
    }

	protected function _data_seek($rowindex)
	{
		//TODO:

		trigger_error('Method _data_seek not implemented for class DbResultSet_PDO', E_ERROR);

		return false;
	}

	public function NumRows()
    {
		return is_object($this->Set) ? $this->Set->rowCount() : 0;
    }

	public function NumFields()
    {
        return is_object($this->Set) ? $this->Set->columnCount(): 0;
    }

	public function FieldName($fieldindex)
    {
		if(is_object($this->Set))
		{
			$arr = $this->Set->getColumnMeta($fieldindex);
			if(is_array($arr) && isset($arr['name']))
			{
				return $arr['name'];
			}
		}

		return null;
    }

	protected function _field_type($fieldindex)
	{
		if(is_object($this->Set))
		{
			$arr = $this->Set->getColumnMeta($fieldindex);
			if(is_array($arr) && isset($arr['native_type']))
			{
				return $arr['native_type'];
			}
		}

		return null;
	}

	public function FreeResult()
    {
        return is_object($this->Set) ? $this->Set->closeCursor() : true;
    }
}