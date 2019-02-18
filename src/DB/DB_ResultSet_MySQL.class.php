<?php
class DB_ResultSet_MySQL extends DB_ResultSet
{
	public function FetchRow()
    {
        return mysql_fetch_row($this->Set);
    }

	public function FetchAssoc()
    {
        return mysql_fetch_assoc($this->Set);
    }

	protected function _data_seek($rowindex)
	{
		return mysql_data_seek($this->Set, $rowindex);
	}

	public function NumRows()
    {
        return mysql_num_rows($this->Set);
    }

	public function NumFields()
    {
        return mysql_num_fields($this->Set);
    }

	public function FieldName($fieldindex)
    {
        return mysql_field_name($this->Set, $fieldindex);
    }

	protected function _field_type($fieldindex)
	{
		return mysql_field_type($this->Set, $fieldindex);
	}

	public function FreeResult()
    {
        return mysql_free_result($this->Set);
    }
}
