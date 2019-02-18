<?php
/**
 * Class to work with a database resulset
 *
 * @version 3.1.0
 * @author José Carlos Cruz Parra AKA internia
 * josecarlos@programadorphpfreelance.com
 * https://programadorphpfreelance.com
 * 2008-2016
 * This code is released under the GNU General Public License.
 */
abstract class DB_ResultSet
{
    public $Set;

	public static function Factory($class=null)
	{
		$class = 'DB_ResultSet_'.(is_null($class) ? self::getClass() : $class);

		return new $class();
	}
	/**
	 * Get child layer class
	 *
	 * @return string
	 */
	private static function getClass()
	{
		if(PHP_VERSION_ID >= 50200 && extension_loaded('pdo_mysql'))
		{
			return 'PDO';
		}

		if(mb_substr(phpversion(), 0, 3) >= '5.5' && extension_loaded('mysqli'))
		{
			return 'MySQLi';
		}

		return 'MySQL';

		/*
		$class = mb_substr(phpversion(), 0, 3) >= '5.5' && extension_loaded('mysqli') ? 'MySQLi' : 'MySQL';

		return $class;
		 */
	}
	/**
	 * @return array
	 * @desc Returns the result as an indexed array
	 */
	abstract public function FetchRow();
    /**
	 * @return array
	 * @desc Returns the result as an associative array
	 */
	abstract public function FetchAssoc();
    /**
	 * @return void
	 * @param $format string
	 * @desc Echoes result as HTML table.
	 */
	public function ResultAll($format = "")
    {
        echo $this->GetResultAll($format);
    }
    /**
	 * @return string
	 * @param $format string
	 * @desc Gets result as HTML table with the specified format.
	 */
	public function GetResultAll($format = "")
    {
        return "<table $format>" . $this->GetFieldsAll("th") . $this->GetRowsAll() . "</table>";
    }
    /**
	 * @return string
	 * @param $tag string
	 * @param $format string
	 * @param $separator string
	 * @desc Gets fields names as HTML table headers ($tag = "th") or table data cells ($tag = "td") with the specified format for each one.
	 */
	public function GetFieldsAll($tag = "", $format = "", $separator = ";")
    {
        $tag = strtolower($tag);
        $tags = array(
            "th",
            "td"
        );
        $echo = "";
        $prevtag = "";
        $posttag = "";
        if(!in_array($tag, $tags))
            $posttag = $separator;
        else
        {
            $prevtag = "<" . $tag . " " . $format . ">";
            $posttag = "</" . $tag . ">";
        }
        $echo .= "<tr>";
        for($i = 0; $i < $this->NumFields(); $i++)
            $echo .= $prevtag . $this->FieldName($i) . $posttag;
        $echo .= "</tr>";
        return $echo;
    }
    /**
	 * @return string
	 * @param $distributecolumnsuniformly bool
	 * @param $num_format string
	 * @param $tr_format string
	 * @param $td_format string
	 * @desc Gets rows data as table rows with the specified format for each one and the specified format for each table data cell, and $num_format from numeric data.
	 */
	public function GetRowsAll($distributecolumnsuniformly=false, $num_format="%s", $tr_format="", $td_format="")
    {
        $td_width = "";
        if($distributecolumnsuniformly)
            $td_width = (100 / $this->NumFields()) . "%' ";
        $echo = "";
        if($this->MoveFirst())
            while($reg = $this->FetchRow())
            {
                $echo .= "<tr " . $tr_format . ">";
                for($i = 0, $regsize = sizeof($reg); $i < $regsize; $i++)
                {
                    $myreg = $reg[$i];
                    $echo .= "<td " . $td_format;
                    if($distributecolumnsuniformly && !eregi("width", $td_format))
                        $echo .= " width='" . $td_width;
                    if(!eregi("align", $td_format))
                        switch($this->FieldType($i))
                        {
                            case "numeric":
                                $echo .= " align='right'";
                                break;
                            case "datetime":
                                $echo .= " align='center'";
                                break;
                            case "string":
                                break;
                            case "unknown":
                                $myreg = strip_tags($myreg);
                                $echo .= " align='left'";
                                break;
                        }
                    if($this->FieldType($i) == "numeric")
                        $echo .= ">" . sprintf($num_format, $myreg) . "</td>";
                    else
                        $echo .= ">" . htmlspecialchars($myreg) . "</td>";
                }
                $echo .= "</tr>";
            }
        return $echo;
    }
    /**
	 * @return bool
	 * @param $rowindex int
	 * @desc Sets the inner row pointer to the given $rowindex. Return true if exists, false if not. Note: rows count starts in zero!
	 */
	public function DataSeek($rowindex)
    {
        return ($this->NumRows() > 0) ? $this->_data_seek($this->Set, $rowindex) : false;
    }
	abstract protected function _data_seek($rowindex);
    /**
	 * @return bool
	 * @desc Sets the inner row pointer to the first row. Return true if exists, false if not.
	 */
	public function MoveFirst()
    {
        return $this->DataSeek(0);
    }
    /**
	 * @return int
	 * @desc Returns number of rows.
	 */
	abstract public function NumRows();
    /**
	 * @return int
	 * @desc Returns number of fields.
	 */
	abstract public function NumFields();
    /**
	 * @return string
	 * @param $fieldindex int
	 * @desc Gets the name of the specified field. Field indexes start in zero.
	 */
	abstract public function FieldName($fieldindex);
    /**
	 * @return string
	 * @param $fieldindex int
	 * @desc Gets the type of the specified field. Field indexes start in zero. Types: numeric, string, datetime and unknown.
	 */
	public function FieldType($fieldindex)
    {
        switch($this->_field_type($fieldindex))
        {
            case 'int':
            case 'real':
			case 'integer':
			case 'float':
			case 'decimal':
                return 'numeric';
            case 'string':
            case 'blob':
			case 'character':
			case 'char':
                return 'string';
            case 'timestamp':
            case 'year':
            case 'date':
            case 'time':
            case 'datetime':
                return 'datetime';
            case 'null':
            default:
                return 'unknown';
        }
    }
	abstract protected function _field_type($fieldindex);
	/**
	 * @return bool
	 * @desc Free result memory (cleans Set).
	 */
	abstract public function FreeResult();
}
