<?php
class DB_ResultSet_MySQLi extends DB_ResultSet
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