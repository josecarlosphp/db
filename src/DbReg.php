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
 * @copyright   2010-2025 José Carlos Cruz Parra
 * @license     https://www.gnu.org/licenses/gpl.txt GPL version 3
 * @desc        Base class that represents a register from bd, and from which they can inherit more complicated things.
 */

namespace josecarlosphp\db;

class DbReg
{
	/**
	 * @var mixed
	 */
	protected $_id = null;
	/**
	 * @var string
	 */
	protected $_tabla = '';
	/**
	 * @var array
	 */
	protected $_data = array();
	/**
     * @var array
     */
    protected $_masData = array();
    /**
	 * @var array
	 */
	protected static $_tablasdb = array();
    /**
	 * @var array
	 */
	protected $_tablashija = array();
	/**
     * @var bool
     */
    protected $_readonly = false;
	/**
	 * @var DbConnection
	 */
	protected $_db;
	/**
	 * @var array
	 */
	protected $_errores;
    /**
     * @var array
     */
    protected $_flags;

	/**
	 * Constructor
	 *
	 * @param DbConnection $db
	 * @param string $tabla
	 * @param mixed $campoid
	 */
	public function __construct(&$db, $tabla, $campoid='id')
	{
		$this->_db = &$db;
		$this->_tabla = $tabla;

        if(!isset(self::$_tablasdb[$tabla]))
        {
            self::$_tablasdb[$tabla] = array(
                'campoid'=>$campoid,
                'fields'=>$this->_db->GetFields($tabla, true, true),
                'camposrequeridos'=>array(),
                'camposreadonly'=>array(),
                'camposexcluidos'=>array(),
                'camposencriptados'=>array(),
                'camposserializados'=>array(),
                'funcionencriptar'=>'md5',
            );
        }

        if(is_array($campoid))
        {
            $this->_id = array();
            foreach($campoid as $campo)
            {
                $this->_id[$campo] = null;
            }
        }

		$this->ResetData();
	}

    protected function AddTablaHija($q, $tabla, $campoid, $fijos=false, $camposrequeridos=array(), $class=null)
    {
        $this->_tablashija[$q] = array(
            'tabla'=>$tabla,
            'campoid'=>$campoid,
            'registros'=>array(),
            'fijos'=>$fijos,
            'camposrequeridos'=>$camposrequeridos,
            'class'=>is_null($class) ? 'josecarlosphp\db\DbReg' : $class,
            );
    }

    protected function AddRegistroHijo($q, $key)
    {
        if($this->ExistsTablaHija($q))
        {
            $class = $this->_tablashija[$q]['class'];

            $this->_tablashija[$q]['registros'][$key] = new $class($this->_db, $this->_tablashija[$q]['tabla'], $this->_tablashija[$q]['campoid']);
            $this->_tablashija[$q]['registros'][$key]->CamposRequeridos($this->_tablashija[$q]['camposrequeridos']);
            $this->_tablashija[$q]['registros'][$key]->id(array($this->_id, $key));

            return true;
        }
        else
        {
            $this->_errores[] = 'No existe tabla hija '.$q;
        }

        return false;
    }

    protected function ExistsTablaHija($q)
    {
        return isset($this->_tablashija[$q]);
    }

    protected function ExistsRegistroHijo($q, $key)
    {
        return isset($this->_tablashija[$q]['registros'][$key]);
    }

    public function ExistsCampo($campo)
    {
        return isset($this->_data[$campo]);
    }

    public function ExistsCampoRegistroHijo($q, $key, $campo)
    {
        if($this->ExistsRegistroHijo($q, $key))
        {
            $reg = $this->_tablashija[$q]['registros'][$key];
            if(is_object($reg))
            {
                return $reg->ExistsCampo($campo);
            }
        }

        //TODO: recoger errores?

        return false;
    }
    /**
	 * Asigna el identificador de la conexión a la base de datos
	 *
     * @param resource
     */
    public function SetLinkIdentifier($dbcon)
    {
    	$this->_db->SetLinkIdentifier($dbcon);
    }
    /**
     * Obtiene las especificaciones de los campos de la tabla
     *
     * @return array
     */
    protected function _getFields()
    {
        return self::$_tablasdb[$this->_tabla]['fields'];
    }

    protected function _getCampoId()
    {
        return self::$_tablasdb[$this->_tabla]['campoid'];
    }
    /**
    protected function _fieldExists($campo)
    {
        return isset(self::$_tablasdb[$this->_tabla]['fields'][$campo]);
    }
    */
	/**
	 * Resetea los datos (campos)
	 */
	public function ResetData($resetErrores=true)
	{
		$this->_data = array();
        $this->ResetMasData();
        $campoid = $this->_getCampoId();
        $fields = $this->_getFields();
		foreach($fields as $field)
		{
            if(is_array($campoid) && in_array($field['Field'], $campoid))
            {
                //id no lo reseteamos
            }
            elseif(!is_array($campoid) && $field['Field'] == $campoid) //if($field['Key'] == 'PRI')
			{
				//$this->_id = $this->_type2val($field['Type']); //id no lo reseteamos
			}
			elseif(!$this->IsCampoEspecial('camposexcluidos', $field['Field']))
			{
				$this->_data[$field['Field']] = $field['Default'] != '' ? $field['Default'] : $this->_type2val($field['Type']);
			}
		}

        foreach($this->_tablashija as $q=>$aux)
        {
            if(empty($aux['fijos']))
            {
                $this->_tablashija[$q]['registros'] = array();
            }
            else
            {
                foreach(array_keys($aux['registros']) as $key)
                {
                    $this->_tablashija[$q]['registros'][$key]->ResetData($resetErrores);
                }
            }
        }

        if($resetErrores)
        {
            $this->_errores = array();
        }

        $this->_flags = array();
	}

    protected function ResetMasData()
    {
        $this->_masData = array();
    }

    protected function LoadMasData()
    {
        return true;
    }

    protected function SaveMasData()
    {
        return true;
    }

    protected function DeleteMasData()
    {
        return true;
    }

    protected function SetIdFromData()
    {
        $campoid = $this->_getCampoId();
        if(is_array($campoid))
        {
            foreach($campoid as $campo)
            {
                $this->_id[$campo] = $this->_data[$campo];
            }
        }
        else
        {
            $this->_id = $this->_data[$campoid];
        }
    }

    protected function UnsetIdFromData()
    {
        $campoid = $this->_getCampoId();
        if(is_array($campoid))
        {
            foreach($campoid as $campo)
            {
                unset($this->_data[$campo]);
            }
        }
        else
        {
            unset($this->_data[$campoid]);
        }
    }

    protected function _getRow()
    {
        return $this->_db->GetRow(sprintf("SELECT r.* FROM `%s` AS r WHERE %s", $this->_tabla, $this->GetFilterId()), true, false);
    }
	/**
	 * Carga un registro
	 *
	 * @param mixed $id
	 * @return bool
	 */
	public function Load($id=null)
	{
        if($this->id($id) !== false)
        {
            if($this->Exists())
            {
                $this->_data = $this->_getRow();
                $this->UnsetIdFromData();
                $this->UnserializarCamposSerializados();

                return $this->LoadRegistrosHijos() && $this->LoadMasData();
            }

            $campoid = $this->_getCampoId();
            $this->_errores[] = 'No existe el registro '.(is_array($campoid) ? (implode(' + ', $campoid).' = '.implode(' + ', $this->_id)) : ($campoid.' = '.$this->_id));
        }

		$this->ResetData(false);

		return false;
	}
    /**
     * Carga un registro buscándolo por valor en determinado campo.
     *
     * @param mixed $value
     * @param string $fieldname
     * @return boolean
     */
	public function LoadByField($value, $fieldname)
    {
        return $this->LoadByFields(array($value), array($fieldname));
    }
    /**
     * Carga un registro buscándolo por valores en determinados campos.
     *
     * @param array $values
     * @param array $fieldnames
     * @return boolean
     */
	public function LoadByFields($values, $fieldnames)
	{
		$size_valores = sizeof($values);
		$size_campos = sizeof($fieldnames);

		if($size_valores > 0 && $size_valores == $size_campos)
		{
			if($this->ExistsByFields($values, $fieldnames))
			{
				$query = "SELECT * FROM `".$this->_tabla."` WHERE 1";

				for($c = 0; $c < $size_valores; $c++)
				{
					$query .= sprintf(" AND `%s` = %s", $fieldnames[$c], $this->_db->quote($values[$c]));
				}

				$this->_data = $this->_db->GetRow($query, true, false);
                $this->SetIdFromData();
                $this->UnsetIdFromData();
                $this->UnserializarCamposSerializados();

                return $this->LoadRegistrosHijos() && $this->LoadMasData();
			}
			else
			{
				$this->_errores[] = 'No existe el registro';
				$this->ResetData(false);
			}
		}
		else
		{
			$this->_errores[] = 'Array de values y/o de fieldnames no válido';
		}

		return false;
	}

    public function LoadByTablaHijaField($q, $value, $fieldname)
    {
        return $this->LoadByTablaHijaFields($q, array($value), array($fieldname));
    }

    public function LoadByTablaHijaFields($q, $values, $fieldnames)
	{
		$size_valores = sizeof($values);
		$size_campos = sizeof($fieldnames);

		if($size_valores > 0 && $size_valores == $size_campos)
		{
            $query = "SELECT * FROM `".$this->_tablashija[$q]['tabla']."` WHERE 1";

            for($c = 0; $c < $size_valores; $c++)
            {
                $query .= sprintf(" AND `%s` = %s", $fieldnames[$c], $this->_db->quote($values[$c]));
            }

            $row = $this->_db->GetRow($query, true, false);
            if(!empty($row) && is_array($row))
            {
                $id = $row[$this->_tablashija[$q]['campoid'][0]];

                return $this->Load($id);
            }
			else
			{
				$this->_errores[] = 'No existe el registro';
				$this->ResetData(false);
			}
		}
		else
		{
			$this->_errores[] = 'Array de values y/o de fieldnames no válido';
		}

		return false;
	}

    protected function LoadRegistrosHijos()
    {
        //Nota: Si tiene tablas hija damos por hecho que $this->_getCampoId() no es un array
        //(y por tanto $this->_id tampoco)

        $ok = true;

        foreach($this->_tablashija as $q=>$aux)
        {
            if(empty($aux['fijos']))
            {
                $this->_tablashija[$q]['registros'] = array();
                $class = $aux['class'];
                $keys = $this->_db->GetValuesQuery(
                    sprintf(
                        "SELECT `%s` FROM `%s` WHERE `%s` = %s",
                        $aux['campoid'][1],
                        $aux['tabla'],
                        $aux['campoid'][0],
                        $this->_db->quote($this->_id)
                        ),
                    false
                    );
                foreach($keys as $key)
                {
                    $this->_tablashija[$q]['registros'][$key] = new $class($this->_db, $aux['tabla'], $aux['campoid']);
                }
            }

            foreach(array_keys($this->_tablashija[$q]['registros']) as $key)
            {
                if(!$this->_tablashija[$q]['registros'][$key]->Load(array($this->_id, $key)))
                {
                    $ok = false;
                    $this->_errores[] = $this->_tablashija[$q]['registros'][$key]->Error();
                }
            }
        }

        return $ok;
    }

    protected function SaveRegistrosHijos()
    {
        $ok = true;
        foreach($this->_tablashija as $q=>$aux)
        {
            $keys = array_keys($aux['registros']);

            foreach($keys as $key)
            {
                $this->_tablashija[$q]['registros'][$key]->id(array($this->_id, $key));
                if(!$this->_tablashija[$q]['registros'][$key]->Save())
                {
                    $ok = false;
                    $this->_errores[] = $this->_tablashija[$q]['registros'][$key]->Error();
                }
            }

            if(empty($aux['fijos']))
            {
                //Eliminar registros que no están en $aux['registros']

                if(!$this->_db->Execute(sprintf(
                    "DELETE FROM `%s` WHERE `%s` = %s AND `%s` NOT IN('%s')",
                    $aux['tabla'],
                    $aux['campoid'][0],
                    $this->_db->quote($this->_id),
                    $aux['campoid'][1],
                    implode("', '", $keys)
                    )))
                {
                    $ok = false;
                    $this->_errores[] = 'No se pudo eliminar posibles registros residuales de la tabla '.$aux['tabla'];
                }
            }
        }

        return $ok;
    }

    protected function UnserializarCamposSerializados()
    {
        foreach($this->CamposSerializados() as $campo)
        {
            if(!empty($this->_data[$campo]) && !is_array($this->_data[$campo]) && !is_object($this->_data[$campo]))
            {
                $this->_data[$campo] = unserialize($this->_data[$campo]);
            }
        }
    }

    protected function SerializarCamposSerializados($data)
    {
        foreach($this->CamposSerializados() as $campo)
        {
            if(!empty($data[$campo]))
            {
                $data[$campo] = serialize($data[$campo]);
            }
        }

        return $data;
    }
	/**
	 * De los elementos de $_data devuelve sólo los que figuran en la tabla como campos.
	 *
	 * @return array
	 */
	public function GetDataFields()
	{
        $campoid = $this->_getCampoId();
        $fields = $this->_getFields();
		$data = array();
        foreach($this->_data as $key=>$value)
		{
			if(array_key_exists($key, $fields) && (is_array($campoid) ? !in_array($key, $campoid) : $key != $campoid))
			{
				$data[$key] = $value;
			}
		}

		return $data;
	}
	/**
	 * Guarda el registro
	 *
	 * @return bool
	 */
	public function Save()
	{
        if(true) //Ya no hago la comprobación previa $this->ValidateData() porque los datos son validados desde los métodos Set.
        {
            $data = $this->SerializarCamposSerializados($this->GetDataFields());
            $campoid = $this->_getCampoId();
            if($this->_idVacio($this->_id))
            {
                if($this->_db->Execute(buildQuery_Insert($data, $this->_tabla)))
                {
                    if(is_array($campoid))
                    {
                        foreach($campoid as $campo)
                        {
                            $this->_id[$campo] = $this->_data[$campo];
                        }
                    }
                    else
                    {
                        $this->_id = $this->_db->Insert_id(); //Se da por hecho que es autoincrement
                    }

                    return $this->SaveRegistrosHijos() && $this->SaveMasData();
                }
                else
                {
                    $this->_errores[] = $this->_db->Error();
                }
            }
            elseif($this->Exists())
            {
                if($this->_readonly)
                {
                    $this->_errores[] = 'Los registros de la tabla '.$this->_tabla.' son de sólo lectura';
                }
                elseif($this->_db->Execute(buildQuery_Update($data, $this->_tabla, is_array($campoid) ? $this->_id : array($campoid=>$this->_id))))
                {
                    return $this->SaveRegistrosHijos() && $this->SaveMasData();
                }
                else
                {
                    $this->_errores[] = $this->_db->Error();
                }
            }
            else
            {
                //Tengo un id pero no existe el registro correspondiente, creo uno con el mismo id
                if(is_array($campoid))
                {
                    foreach($campoid as $campo)
                    {
                        $data[$campo] = $this->_id[$campo];
                    }
                }
                else
                {
                    $data[$campoid] = $this->_id;
                }

                if($this->_db->Execute(buildQuery_Insert($data, $this->_tabla)))
                {
                    return $this->SaveRegistrosHijos() && $this->SaveMasData();
                }
                else
                {
                    $this->_errores[] = $this->_db->Error();
                }
            }
        }

		return false;
	}
	/**
	 * Establece los valores de los campos
	 *
	 * @param array $data
	 * @param bool $errorEnNoExistentes
	 * @param bool $skipSoloLectura
	 * @return bool
	 */
	public function SetData($data, $errorEnNoExistentes=true, $skipSoloLectura=false)
	{
        $ok = true;

        foreach(array_keys($this->_masData) as $key)
        {
            if(isset($data[$key]))
            {
                //if (!$skipSoloLectura || !$this->IsCampoEspecial('camposreadonly', $key)) {
                    $ok &= $this->_setValue($key, $data[$key]);
                    unset($data[$key]);
                //}
            }
        }

		$campoid = $this->_getCampoId();
		foreach($data as $key=>$value)
		{
			if(is_array($campoid) && in_array($key, $campoid))
			{
				$this->_id[$key] = $value;
			}
			elseif(!is_array($campoid) && $key == $campoid)
			{
				$this->_id = $value;
			}
            elseif($errorEnNoExistentes || isset($this->_data[$key]) || $this->ExistsTablaHija($key))
			{
				if (!$skipSoloLectura || !$this->IsCampoEspecial('camposreadonly', $key)) {
                    $ok &= $this->SetValue($key, $value);
                }
			}
		}

        return $ok;
	}
	/**
	 * Establece el valor de un campo
	 *
	 * @param string $campo
	 * @param mixed $valor
	 * @return bool
	 */
	public function SetValue($campo, $valor)
	{
        if(!$this->IsCampoEspecial('camposreadonly', $campo))
        {
            return $this->_setValue($campo, $valor);
        }
        elseif($valor == $this->_data[$campo])
        {
            return true;
        }
        else
        {
            $this->_errores[] = 'El campo '.$campo.' es de sólo lectura';
        }

		return false;
	}
	/**
	 * Establece el valor de un campo, aunque sea readonly
	 *
	 * @param string $campo
	 * @param mixed $valor
	 * @return bool
	 */
	protected function _setValue($campo, $valor)
	{
        unset($this->_flags[__FUNCTION__]);

        if(array_key_exists($campo, $this->_masData))
		{
            $valor = $this->FormatValue($campo, $valor);
            if($this->ValidateValue($campo, $valor))
            {
                $this->_masData[$campo] = $valor;

                return true;
            }
		}
        elseif(array_key_exists($campo, $this->_data))
		{
            $valor = $this->FormatValue($campo, $valor);
            if($this->ValidateValue($campo, $valor))
            {
                $this->_data[$campo] = $this->IsCampoEspecial('camposencriptados', $campo) ? ($valor === '' ? '' : eval('return '.$this->FuncionEncriptar().'($valor);')) : $valor;

                return true;
            }
		}
        elseif($this->ExistsTablaHija($campo))
        {
            return $this->SetDataTablaHija($campo, $valor);
        }
        else
		{
            $this->_flags[__FUNCTION__] = true;

			$this->_errores[] = 'No existe el campo '.$campo;
		}

		return false;
	}

    protected function SetDataTablaHija($q, $data)
    {
        $ok = true;

        if(empty($this->_tablashija[$q]['fijos']))
        {
            $this->_tablashija[$q]['registros'] = array();
        }

        foreach($data as $key=>$aux)
        {
            if(!$this->ExistsRegistroHijo($q, $key))
            {
                $this->AddRegistroHijo($q, $key);
            }

            if(!$this->_tablashija[$q]['registros'][$key]->SetData($aux))
            {
                $ok = false;
                $this->_errores[] = $this->_tablashija[$q]['registros'][$key]->Error();
            }
        }

        return $ok;
    }
	/**
	 * Obtiene el array asociativo con los valores de los campos actualmente cargados
	 *
	 * @param bool $htmlentities
	 * @return array
	 */
	public function GetData($htmlentities=true)
	{
        $arr = array();

        $campoid = $this->_getCampoId();
        if (is_array($campoid)) {
            foreach ($campoid as $campo) {
                $arr[$campo] = $htmlentities ? $this->HtmlEntities($this->_id[$campo]) : $this->_id[$campo];
            }
        } else {
            $arr[$campoid] = $htmlentities ? $this->HtmlEntities($this->_id) : $this->_id;
        }

        foreach ($this->_data as $key => $val) {
            if (!$htmlentities || $this->IsCampoEspecial('camposserializados', $key)) {
                $arr[$key] = $val;
            } else {
                $arr[$key] = $this->HtmlEntities($val);
            }
        }

        foreach ($this->_tablashija as $q=>$aux) {
            $arr[$q] = array();
            foreach ($aux['registros'] as $key=>$dbreg) {
                $arr[$q][$key] = $dbreg->GetData($htmlentities);
            }
        }

        foreach ($this->_masData as $key=>$val) {
			$arr[$key] = $htmlentities ? $this->HtmlEntities($val) : $val;
		}

		return $arr;
	}

    public function GetDataTablaHija($q, $htmlentities=true)
    {
        if($this->ExistsTablaHija($q))
        {
            $arr = array();
            foreach($this->_tablashija[$q]['registros'] as $key=>$dbreg)
            {
                $arr[$key] = $dbreg->GetData($htmlentities);
            }

            return $arr;
        }
        else
        {
            $this->_errores[] = 'No se encuentra tabla hija '.$q;
        }

        return null;
    }

    public function GetDataRegistroHijo($q, $key, $htmlentities=true)
    {
        if($this->ExistsRegistroHijo($q, $key))
        {
            $reg = $this->_tablashija[$q]['registros'][$key];
            if(is_object($reg))
            {
                return $reg->GetData($htmlentities);
            }
            else
            {
                $this->_errores[] = 'El registro hijo no es un objeto '.$q.' - '.$key;
            }
        }
        else
        {
            $this->_errores[] = 'No se encuentra registro hijo '.$q.' - '.$key;
        }

        return null;
    }
	/**
	 * Obtiene el valor de un campo (null si no lo encuentra)
	 *
	 * @param string $campo
	 * @param bool $htmlentities
	 * @return mixed
	 */
	public function GetValue($campo, $htmlentities=true)
	{
        if (array_key_exists($campo, $this->_masData)) {
            $value = $this->_masData[$campo];
        } elseif (array_key_exists($campo, $this->_data)) {
			$value = $this->_data[$campo];

            if ($this->IsCampoEspecial('camposserializados', $campo)) {
                $htmlentities = false;
            }
		} else {
			$this->_errores[] = 'No existe el campo ' . $campo;

            return null;
		}

        //$value = $this->FormatValue($campo, $value); //TODO: Descomentar para autoformat ? Y en GetData() ?

        return $htmlentities ? $this->HtmlEntities($value) : $value;
	}

    public function GetValueRegistroHijo($q, $key, $campo, $htmlentities=true)
	{
        if($this->ExistsRegistroHijo($q, $key))
        {
            $reg = $this->_tablashija[$q]['registros'][$key];
            if(is_object($reg))
            {
                return $reg->GetValue($campo, $htmlentities); //TODO: Recoger posible error?
            }
            else
            {
                $this->_errores[] = 'El registro hijo no es un objeto '.$q.' - '.$key;
            }
        }
        else
        {
            $this->_errores[] = 'No se encuentra registro hijo '.$q.' - '.$key;
        }

        return null;
	}

    public function SetValueRegistroHijo($q, $key, $campo, $valor)
	{
        if($this->ExistsRegistroHijo($q, $key))
        {
            $reg = $this->_tablashija[$q]['registros'][$key];
            if(is_object($reg))
            {
                return $reg->SetValue($campo, $valor); //TODO: Recoger posible error?
            }
            else
            {
                $this->_errores[] = 'El registro hijo no es un objeto '.$q.' - '.$key;
            }
        }
        else
        {
            $this->_errores[] = 'No se encuentra registro hijo '.$q.' - '.$key;
        }

        return null;
	}
    /**
     * Compara los datos pasados como parámetro con los datos actuales del objeto.
     * Devuelve un array con los datos encontrados distintos, con sus valores actuales.
     *
     * @param array $data
     * @return array
     */
    public function CompareData($data)
    {
        $diff = array();
        $current = $this->GetData();
        foreach($data as $key=>$val)
        {
            if(isset($current[$key]) && $current[$key] != $this->FormatValue($key, $val))
            {
                $diff[$key] = $current[$key];
            }
        }

        return $diff;
    }

    public function ValidateData($data=null)
    {
        if(is_null($data))
        {
            $data = $this->_data;
        }

        $ok = true;

        foreach($this->CamposRequeridos() as $key)
        {
            if(!isset($data[$key]) || $data[$key] === '')
            {
                $ok = false;
                $this->_errores[] = 'Falta el dato: '.$key;
            }
        }

        foreach($data as $key=>$val)
        {
            $ok &= $this->ValidateValue($key, $val);
        }

        foreach($this->_tablashija as $aux)
        {
            foreach($aux['registros'] as $dbreg)
            {
                if(!$dbreg->ValidateData())
                {
                    $ok = false;
                    $this->_errores[] = $dbreg->Error();
                }
            }
        }

        return $ok;
    }

    final protected function IsCampoEspecial($q, $campo)
    {
        return in_array($campo, self::$_tablasdb[$this->_tabla][$q]);
    }

    final public function IsField($campo)
    {
        return isset(self::$_tablasdb[$this->_tabla]['fields'][$campo]);
    }

    public function ValidateValue($campo, $valor)
    {
        if($valor === '' && in_array($campo, $this->CamposRequeridos()))
        {
            $this->_errores[] = 'Falta el dato: '.$campo;

            return false;
        }

        if($this->IsField($campo))
        {
            if(!self::ValidateFieldValue(self::$_tablasdb[$this->_tabla]['fields'][$campo], $valor))
            {
                $this->_errores[] = 'Valor no admitido para '.$campo.': '.var_export($valor, true);

                return false;
            }
        }

        return true;
    }
    /**
     * Realiza una validación básica conforme al tipo de dato en la tabla.
     *
     * @param field $especificacion
     * @param string $valor
     * @return boolean
     */
    final public static function ValidateFieldValue($especificacion, $valor)
    {
        //TODO: Mejorar la validación (pro ejemplo comprobar la longitud según tipo de dato)

        if(is_null($valor) && $especificacion['Null'] == 'NO')
        {
            return false;
        }

        if (($posA = mb_strpos($especificacion['Type'], '(')) !== false) {
            $posB = mb_strpos($especificacion['Type'], ')');
            $type = mb_substr($especificacion['Type'], 0, $posA);
            $length = mb_substr($especificacion['Type'], $posA + 1, $posB - mb_strlen($especificacion['Type']));
        } else {
            $type = $especificacion['Type'];
            $length = 0;
        }

        switch($type)
        {
            case 'tinyint':
                if(is_bool($valor) || empty($valor))
                {
                    $valor = $valor ? 1 : 0;
                }
                //Sigue
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'bit':
            case 'real':
			case 'double':
			case 'float':
			case 'decimal':
            case 'numeric':
                return is_numeric($valor);
            case 'varchar':
            case 'string':
            case 'tinyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
            case 'tinytext':
            case 'text':
            case 'mediumtext':
            case 'longtext':
                return true;
			case 'character':
			case 'char':
                return mb_strlen($valor) <= ($length ? $length : 1);
            case 'timestamp':
                return is_numeric($valor);
            case 'year':
                return checkdate(1, 1, $valor);
            case 'date':
                return ($especificacion['Null'] != 'NO' && (is_null($valor) || $valor === ''))
                    || (mb_strlen($valor) == 10 && checkdate(mb_substr($valor, 5, 2), mb_substr($valor, 8, 2), mb_substr($valor, 0, 4)));
            case 'time':
                $len = mb_strlen($valor);
                if($len == 0)
                {
                    $valor = '00:00:00';
                }
                elseif($len == 2)
                {
                    $valor .= ':00:00';
                }
                elseif($len == 5)
                {
                    $valor .= ':00';
                }
                $d = \DateTime::createFromFormat('H:i:s', $valor);
                return $d && $d->format('H:i:s') == $valor || $valor = '00:00:00';
            case 'datetime':
                $len = mb_strlen($valor);
                if($len == 0)
                {
                    if($especificacion['Null'] == 'NO')
                    {
                        $valor = '0000-00-00 00:00:00';
                    }
                    else
                    {
                        return true;
                    }
                }
                elseif($len == 10)
                {
                    $valor .= ' 00:00:00';
                }
                elseif($len == 16)
                {
                    $valor .= ':00';
                }
                return $valor == '0000-00-00 00:00:00' || (($d = \DateTime::createFromFormat('Y-m-d H:i:s', $valor)) && $d->format('Y-m-d H:i:s') == $valor);
        }

        return true;
    }
    /**
     * Formatea los datos como si fueran del objeto.
     *
     * @param array $data
     * @return array
     */
    public function FormatData($data)
    {
        foreach($data as $key=>$val)
        {
            $data[$key] = $this->FormatValue($key, $val);
        }

        return $data;
    }

    public function FormatValue($campo, $valor)
    {
        if(isset(self::$_tablasdb[$this->_tabla]['fields'][$campo]))
        {
            return self::FormatFieldValue(self::$_tablasdb[$this->_tabla]['fields'][$campo], $valor);
        }

        return $valor;
    }
    /**
     * Aplica un formato básico conforme al tipo de dato en la tabla.
     *
     * @param field $especificacion
     * @param string $valor
     * @return string
     */
    final public static function FormatFieldValue($especificacion, $valor)
    {
        $type = ($pos = mb_strpos($especificacion['Type'], '(')) !== false ? mb_substr($especificacion['Type'], 0, $pos) : $especificacion['Type'];
        switch($type)
        {
            case 'tinyint':
                if($valor == 'on')
                {
                    $valor = 1;
                }
                //Sigue
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'bit':
            case 'real':
			case 'double':
			case 'float':
			case 'decimal':
            case 'numeric':
                return $valor === '' ? 0 : $valor;
            case 'varchar':
            case 'string':
            case 'tinyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
            case 'tinytext':
            case 'text':
            case 'mediumtext':
            case 'longtext':
                return (is_array($valor) || is_object($valor)) ? $valor : trim($valor);
			case 'time':
                $len = mb_strlen($valor);
                if($len == 0)
                {
                    $valor = '00:00:00';
                }
                elseif($len == 2)
                {
                    $valor .= ':00:00';
                }
                elseif($len == 5)
                {
                    $valor .= ':00';
                }
                return $valor;
            case 'datetime':
                $len = mb_strlen($valor);
                if($len == 0)
                {
                    $valor = '0000-00-00 00:00:00';
                }
                elseif($len == 10)
                {
                    $valor .= ' 00:00:00';
                }
                elseif($len == 16)
                {
                    $valor .= ':00';
                }
                return $valor;
        }

        return $valor;
    }
	/**
	 * Obtiene el valor del campo índice,
	 * y también sirve para establecerlo si se le pasa como parámetro, pero sin cargar los datos
	 *
	 * @param mixed $id
	 * @return mixed
	 */
	public function id($id=null)
	{
		if($this->_idVacio($id))
        {
            if(!is_null($id))
            {
                $this->_errores[] = 'Identificador vacío';
                return false;
            }
        }
        else
		{
            if($this->ValidateId($id))
            {
                $campoid = $this->_getCampoId();
                if(is_array($campoid) && $this->array_is_num($id))
                {
                    //Para que sea un array no numérico, sino con los campos como índices
                    $this->_id = array();
                    foreach($campoid as $i=>$campo)
                    {
                        $this->_id[$campo] = $id[$i]; //Espero los valores de id en el mismo orden que tengo declarados los campos
                    }
                }
                else
                {
                    $this->_id = $id;
                }
            }
            else
            {
                $this->_errores[] = 'Identificador no válido';
                return false;
            }
		}

		return $this->_id;
	}

    public function ValidateId($id)
    {
        $campoid = $this->_getCampoId();

        return (!is_array($campoid) && !is_array($id)) || (is_array($campoid) && is_array($id) && (($this->array_is_num($id) && count($campoid) == count($id)) || (!$this->array_is_num($id) && empty(array_diff($campoid, $id)))));
    }
	/**
	 * Comprueba si el valor se considera vacío para ser usado como índice
	 *
	 * @param mixed $id
	 * @return bool
	 */
	protected function _idVacio($id)
	{
        if(is_array($id))
        {
            if(!empty($id))
            {
                foreach($id as $item)
                {
                    if(!is_null($item) && $id != '')
                    {
                        return false;
                    }
                }
            }

            return true;
        }

		return is_null($id) || $id == '';
	}
	/**
	 * Comprueba si existe un registro con el id pasado como parámetro,
	 * o el id del objeto actual si el parámetro es null.
	 * El parámetro $id puede ser un array asociativo de campos=>valores
	 *
	 * @param mixed $id
	 * @param string $filtro
	 * @return bool
	 */
	public function Exists($id=null, $filtro='')
	{
		if(is_null($id))
		{
			$id = $this->_id;
		}

    	if(is_array($id))
    	{
    		$aux1 = array();
    		$aux2 = array();
    		foreach($id as $key=>$value)
    		{
    			$aux1[] = $value;
    			$aux2[] = $key;
    		}

    		return $this->_db->Exists($aux1, $aux2, $this->_tabla, $filtro);
    	}

        return is_null($id) ? false : $this->_db->Exists($id, $this->_getCampoId(), $this->_tabla, $filtro);
	}
	/**
	 * Comprueba si existe un registro con el valor tal en el campo tal.
	 *
	 * @param string $value
	 * @param string $fieldname
	 * @param string $filtro
	 * @return bool
	 */
	public function ExistsByField($value, $fieldname, $filtro='')
	{
		return $this->_db->Exists($value, $fieldname, $this->_tabla, $filtro);
	}
	/**
	 * Comprueba si existe un registro con los valores tales en los campos tales.
	 *
	 * @param string $values
	 * @param string $fieldnames
	 * @param string $filtro
	 * @return bool
	 */
	public function ExistsByFields($values, $fieldnames, $filtro='')
	{
		return $this->_db->Exists($values, $fieldnames, $this->_tabla, $filtro);
	}
	/**
	 * Obtiene un valor por defecto genérico según el tip de campo
	 *
	 * @param type $type
	 * @return type
	 */
	protected function _type2val($type)
	{
		if(strpos($type, 'int') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false)
		{
			return 0;
		}
		/*
		elseif(strpos($type, 'datetime'))
		{
			return '0000-00-00 00:00:00';
		}
		elseif(strpos($type, 'date'))
		{
			return '0000-00-00';
		}
		*/

		return '';
	}
	/**
	 * Asigna/Obtiene el valor de un campo
	 *
	 * @param mixed $val Valor
	 * @param string $key Nombre del campo
	 * @param string $func Nombre de una función a aplicar al valor (opcional)
	 * @param mixed $minVal Valor mínimo (si se intenta asignar uno menor se pone el mínimo)
	 * @param mixed $maxVal Valor máximo (si se intenta asignar uno mayor se pone el máximo)
	 * @return mixed El valor actual (tras la asignación, se haya hecho o no)
	 */
	protected function _val($val, $key, $func='', $minVal=null, $maxVal=null)
	{
		if(!is_null($val))
        {
			if($func && function_exists($func))
			{
				$val = $func($val);
			}

			if(!is_null($minVal) && $val < $minVal)
			{
				$val = $minVal;
			}

			if(!is_null($maxVal) && $val > $maxVal)
			{
				$val = $maxVal;
			}

            $this->SetValue($key, $val);
        }

        return $this->GetValue($key);
	}
	/**
	 * Activa/Desactiva un campo, es decir, le da valor 1 ó 0,
	 * intentando además actualizar el registro en la tabla.
	 * Devuelve true si puede actualizar el registro en la tabla.
	 *
	 * @param bool $valor
	 * @param string $campo
	 * @return bool
	 */
	public function OnOff($valor, $campo='activo')
	{
		if($this->SetValue($campo, $valor ? '1' : '0'))
		{
			if(!$this->_idVacio($this->_id))
			{
				if($this->_db->Execute("UPDATE `".$this->_tabla."` AS r SET r.`".$campo."` = '".$this->GetValue($campo, false)."' WHERE ".$this->GetFilterId()))
				{
					return true;
				}
				else
				{
					$this->_errores[] = $this->_db->Error();
				}
			}
			else
			{
				$this->_errores[] = 'Identificador vacío';
			}
		}

		return false;
	}

    protected function GetFilterId()
    {
        $campoid = $this->_getCampoId();
        $filtro = '';
        if(is_array($campoid))
        {
            if($this->array_is_num($this->_id))
            {
                $aux = array();
                $sep = '';
                foreach($campoid as $i=>$campo)
                {
                    $aux[$campo] = $this->_id[$i];
                    $filtro .= sprintf("%sr.`%s` = %s", $sep, $campo, $this->_db->quote($this->_id[$i]));
                    $sep = ' AND ';
                }
                $this->_id = $aux; //Para que sea un array no numérico, sino con los campos como índices (por si acaso)
            }
            else
            {
                $sep = '';
                foreach($this->_id as $campo=>$value)
                {
                    $filtro .= sprintf("%sr.`%s` = %s", $sep, $campo, $this->_db->quote($value));
                    $sep = ' AND ';
                }
            }
        }
        else
        {
            $filtro .= sprintf("r.`%s` = %s", $campoid, $this->_db->quote($this->_id));
        }

        return $filtro;
    }
	/**
	 * Elimina el registro
	 *
	 * @param bool $resetData
	 * @return bool
	 */
	public function Delete($resetData=true)
	{
		$ok = true;

		if(!$this->_idVacio($this->_id))
		{
            $campoid = $this->_getCampoId();
			if(!$this->_db->Execute(buildQuery_Delete($this->_tabla, is_array($campoid) ? $this->_id : array($campoid=>$this->_id))))
			{
				$this->_errores[] = $this->_db->Error();

				$ok = false;
			}
            else
            {
                $this->DeleteMasData();

                foreach($this->_tablashija as $q=>$aux)
                {
                    foreach(array_keys($aux['registros']) as $key)
                    {
                        $this->_tablashija[$q]['registros'][$key]->Delete($resetData);
                    }
                }
            }
		}

		if($resetData)
		{
			$this->ResetData(false);
		}

		return $ok;
	}

    protected function CamposEspeciales($q, $arr=null, $merge=true)
    {
        if (is_string($arr)) {
            $arr = array($arr);
        }

        if(!is_null($arr))
        {
            self::$_tablasdb[$this->_tabla][$q] = $merge ? array_merge(self::$_tablasdb[$this->_tabla][$q], $arr) : $arr;
        }

        return self::$_tablasdb[$this->_tabla][$q];
    }

    public function CamposRequeridos($arr=null, $merge=true)
    {
        return $this->CamposEspeciales('camposrequeridos', $arr, $merge);
    }

    public function CamposReadonly($arr=null, $merge=true)
    {
        return $this->CamposEspeciales('camposreadonly', $arr, $merge);
    }

    public function CamposExcluidos($arr=null, $merge=true)
    {
        return $this->CamposEspeciales('camposexcluidos', $arr, $merge);
    }

    public function CamposEncriptados($arr=null, $merge=true)
    {
        return $this->CamposEspeciales('camposencriptados', $arr, $merge);
    }

    public function CamposSerializados($arr=null, $merge=true)
    {
        return $this->CamposEspeciales('camposserializados', $arr, $merge);
    }

    public function FuncionEncriptar($str=null)
    {
        if(!is_null($str))
        {
            self::$_tablasdb[$this->_tabla]['funcionencriptar'] = $str;
        }

        return self::$_tablasdb[$this->_tabla]['funcionencriptar'];
    }
    /**
     * Obtiene un conjunto (array) de registros.
     * El parámetro $filtro sirve para filtrar, ordenar, limitar.
     *
     * @param bool $assoc
     * @param bool $htmlentities
     * @param string $indexField
     * @param bool $cache
     * @return array
     */
    public function GetRows($filtro='WHERE 1', $assoc=true, $htmlentities=true, $indexField=null, $cache=false)
    {
        return $this->_db->GetRows(sprintf("SELECT * FROM `%s` %s", $this->_tabla, $filtro), $assoc, $htmlentities, $indexField, $cache);
    }
	/**
	 * Obtiene el texto del último error, o null si no hay errores registrados
	 *
	 * @return string
	 */
	public function Error()
	{
		$size = sizeof($this->_errores);

		return $size > 0 ? $this->_errores[$size-1] : null;
	}
    /**
     * Obtiene el conjunto de mensajes de error acumulados
     *
     * @return array
     */
    public function ErrorStack()
	{
		return $this->_errores;
	}
	/**
	 * Aplica htmlentities con el quotestyle y charset establecidos,
	 * directamente si $var es una cadena, y recursivamente si es un array
	 *
	 * @param mixed $var
	 * @return mixed
	 */
	protected function HtmlEntities($var)
	{
		return $this->_db->HtmlEntities($var);
	}

    protected function array_is_num($arr)
    {
        if(is_array($arr))
        {
            foreach(array_keys($arr) as $k)
            {
                if(!is_int($k))
                {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}

if (!function_exists('buildQuery_Insert')) {
    function buildQuery_InsertMulti($datas, $table, $onDuplicateKeyUpdate=false)
    {
        return \josecarlosphp\utils\Sql::buildQuery_InsertMulti($datas, $table, $onDuplicateKeyUpdate);
    }

    function buildQuery_Insert($data, $table, $onDuplicateKeyUpdate=false)
    {
        return \josecarlosphp\utils\Sql::buildQuery_Insert($data, $table, $onDuplicateKeyUpdate);
    }

    function buildQuery_Update($data, $table, $ids=null, $devolverVacio=false)
    {
        return \josecarlosphp\utils\Sql::buildQuery_Update($data, $table, $ids, $devolverVacio);
    }

    function buildQuery_Delete($table, $ids=null)
    {
        return \josecarlosphp\utils\Sql::buildQuery_Delete($table, $ids);
    }
}
