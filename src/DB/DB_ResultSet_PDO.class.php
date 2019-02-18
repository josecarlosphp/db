<?php
class DB_ResultSet_PDO extends DB_ResultSet
{
	/**
	 * @var PDOStatement
	 */
	//public $Set;

	public function FetchRow()
    {
		return is_object($this->Set) ? $this->Set->fetch(PDO::FETCH_NUM) : array();
    }

	public function FetchAssoc()
    {
		return is_object($this->Set) ? $this->Set->fetch(PDO::FETCH_ASSOC) : array();
    }

	protected function _data_seek($rowindex)
	{
		//TODO:

		trigger_error('Method _data_seek not implemented for class DB_ResultSet_PDO', E_ERROR);

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