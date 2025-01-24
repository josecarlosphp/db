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
 * @desc        Class to work with a database resultset MySQLi.
 */

namespace josecarlosphp\db;

class DbResultSet_MySQLi extends DbResultSet
{
	public function FetchRow()
    {
        return mysqli_fetch_row($this->Set);
    }

	public function FetchAssoc()
    {
        return mysqli_fetch_assoc($this->Set);
    }

	protected function _data_seek($rowindex)
	{
		return mysqli_data_seek($this->Set, $rowindex);
	}

	public function NumRows()
    {
		return mysqli_num_rows($this->Set);
    }

	public function NumFields()
    {
        return mysqli_num_fields($this->Set);
    }

	public function FieldName($fieldindex)
    {
        $properties = mysqli_fetch_field_direct($this->Set, $fieldindex);
		return is_object($properties) ? $properties->name : null;
    }

	protected function _field_type($fieldindex)
	{
		$properties = mysqli_fetch_field_direct($this->Set, $fieldindex);
		return is_object($properties) ? $properties->type : null;
	}

	public function FreeResult()
    {
        return mysqli_free_result($this->Set);
    }
}