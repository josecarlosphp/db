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
 * @desc        Class sql select query.
 */

namespace josecarlosphp\db;

/**
 * Clase consulta sql select
 * Nos sirve para descomponer una consulta
 *
 * //TODO:
 * Aunque como está ahora mismo la clase nos sirve perfectamente, se puede completar:
 * - Modificadores después del contenido (por ejemplo, WITH ROLLUP para GROUP BY)
 * - Otras keywords: PROCEDURE, INTO...
 * - Y: FOR UPDATE, LOCK IN SHARE MODE
 *
 * DOCUMENTACION
 * Sintaxis de select:
 *
 * SELECT
 *    [ALL | DISTINCT | DISTINCTROW ]
 *      [HIGH_PRIORITY]
 *      [STRAIGHT_JOIN]
 *      [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
 *      [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
 *    select_expr, ...
 *    [FROM table_references
 *    [WHERE where_condition]
 *    [GROUP BY {col_name | expr | position}
 *      [ASC | DESC], ... [WITH ROLLUP]]
 *    [HAVING where_condition]
 *    [ORDER BY {col_name | expr | position}
 *      [ASC | DESC], ...]
 *    [LIMIT {[offset,] row_count | row_count OFFSET offset}]
 *    [PROCEDURE procedure_name(argument_list)]
 *    [INTO OUTFILE 'file_name' export_options
 *      | INTO DUMPFILE 'file_name'
 *      | INTO var_name [, var_name]]
 *    [FOR UPDATE | LOCK IN SHARE MODE]]
 */
class SqlSelectQuery
{
	/**
	 * La consulta sql select
	 *
	 * @var string
	 */
	private $query;
	/**
	 * Array de componentes de la consulta
	 *
	 * @var array
	 */
	private $components;
	/**
	 * Constructor
	 *
	 * @param string $query
	 * @return SqlSelectQuery
	 */
	public function __construct($query)
	{
		$this->query = $query;

		$this->components['SELECT'] = $this->getComponent('SELECT', array('ALL','DISTINCT','DISTINCTROW','HIGH_PRIORITY','STRAIGHT_JOIN','SQL_SMALL_RESULT','SQL_BIG_RESULT','SQL_BUFFER_RESULT','SQL_CACHE','SQL_NO_CACHE','SQL_CALC_FOUND_ROWS'), 'FROM');
		$this->components['FROM'] = $this->getComponent('FROM');
		$this->components['WHERE'] = $this->getComponent('WHERE', array(), array('GROUP BY','HAVING','ORDER BY','LIMIT'));
		$this->components['GROUP BY'] = $this->getComponent('GROUP BY', array(), array('HAVING','ORDER BY','LIMIT'));
		$this->components['HAVING'] = $this->getComponent('HAVING', array(), array('ORDER BY','LIMIT'));
		$this->components['ORDER BY'] = $this->getComponent('ORDER BY', array(), array('LIMIT'));
		$this->components['LIMIT'] = $this->getComponent('LIMIT', array(), array(''));
	}
	/**
	 * Obtiene el componente de la consulta indicado por $keyword
	 *
	 * @param string $keyword
	 * @param array $skip
	 * @param array $stop
	 * @return SqlSelectQueryComponent
	 */
	private function getComponent($keyword, $skip=array(), $stop=array('FROM','WHERE','GROUP BY','HAVING','ORDER BY','LIMIT'))
	{
		$str = $this->query;
        $keyword .= ' ';
		$incremento = strlen($keyword);
		$detectar = true;
		$prevchar = false;
		if(!is_array($skip))
		{
			$skip = explode(',', $skip);
		}
		$modifiers = '';
		$content = '';
		for($c=0,$size=strlen($str); $c<$size; $c++)
		{
			$char = $str[$c];
			if($detectar)
			{
				if($char == '`' || $char == '(')
				{
					$detectar = false;
				}
				elseif($prevchar == ' ' || !$prevchar)
				{
					if(strcasecmp(substr($str, $c, $incremento), $keyword) == 0)
					{
						foreach($skip as $modifier)
						{
							$modifier .= ' ';
							$len = strlen($modifier);
							//echo '['.substr($str, $c+$incremento, $len).'] - ['.$modifier.']<br />';
							if(strcasecmp(substr($str, $c+$incremento, $len), $modifier) == 0)
							{
								$modifiers .= $modifier;
								$c = $c + $len;
							}
						}
						$i = $c+$incremento;
						$content = $this->getSubstr($str, $stop, $i);
						break;
					}
				}
			}
			else
			{
				if($char == '`' || $char == ')')
				{
					$detectar = true;
				}
			}
			$prevchar = $char;
		}
		return new SqlSelectQueryComponent($keyword, $content, $modifiers);
	}
	/**
	 * Obtiene una subcadena
	 *
	 * @param string $str
	 * @param array $stop
	 * @param int $i
	 * @return string
	 */
	private function getSubstr($str, $stop, &$i)
	{
		$detectar = true;
		$substr = '';
		if(!is_array($stop))
		{
			$stop = explode(',', $stop);
		}
		$prevchar = false;
		for($c=$i,$size=strlen($str); $c<$size; $c++)
		{
			$char = $str[$c];
			if($detectar)
			{
				if($char == '`' || $char == '(')
				{
					$substr .= $char;
					$detectar = false;
				}
				elseif($prevchar == ' ' || !$prevchar)
				{
					$encontrado = false;
					foreach($stop as $item)
					{
						$item .= ' ';
						$incremento = strlen($item);
						if(strcasecmp(substr($str, $c, $incremento), $item) == 0)
						{
							$encontrado = true;
							break;
						}
					}
					if($encontrado)
					{
						break;
					}
					else
					{
						$substr .= $char;
					}
				}
				else
				{
					$substr .= $char;
				}
			}
			else
			{
				$substr .= $char;
				if($char == '`' || $char == ')')
				{
					$detectar = true;
				}
			}
			$prevchar = $char;
		}
		$i = $c + $incremento;
		return trim($substr);
	}
	/**
	 * Obtiene palabras
	 *
	 * @param string $str
	 * @param array $stop
	 * @param int $i
	 * @return array
	 */
	/*private function getPalabras($str, $stop, &$i)
	{
		$detectar = true;
		$palabra = '';
		$palabras = array();
		if(!is_array($stop))
		{
			$stop = explode(',', $stop);
		}
		$prevchar = false;
		for($c=$i,$size=strlen($str); $c<$size; $c++)
		{
			$char = $str[$c];
			if($detectar)
			{
				if($char == '`' || $char == '(')
				{
					$palabra .= $char;
					$detectar = false;
				}
				elseif($char == ',')
				{
					$palabras[] = trim($palabra);
					$palabra = '';
				}
				elseif($prevchar == ' ' || !$prevchar)
				{
					$encontrado = false;
					foreach($stop as $item)
					{
						$item .= ' ';
						$incremento = strlen($item);
						if(strcasecmp(substr($str, $c, $incremento), $item) == 0)
						{
							$palabras[] = trim($palabra);
							$palabra = '';
							$encontrado = true;
							break;
						}
					}
					if($encontrado)
					{
						break;
					}
					else
					{
						$palabra .= $char;
					}
				}
				else
				{
					$palabra .= $char;
				}
			}
			else
			{
				$palabra .= $char;
				if($char == '`' || $char == ')')
				{
					$detectar = true;
				}
			}
			$prevchar = $char;
		}
		$i = $c + $incremento;
		return $palabras;
	}*/
	/**
	 * Construye y obtiene la consulta a partir de los componentes
	 *
	 * @return string
	 */
	public function BuildQuery()
	{
		$query = '';
		foreach($this->components as $component)
		{
			$query .= $component->ToString().' ';
		}

		return trim($query);
	}
	/**
	 * Obtiene el contenido del componente correspondiente a $keyword
	 *
	 * @param string $keyword
	 * @return string
	 */
	public function GetComponentContent($keyword)
	{
		return $this->components[strtoupper($keyword)]->GetContent();
	}
	/**
	 * Establece el contenido para el componente correspondiente a $keyword
	 *
	 * @param string $keyword
	 * @param string $content
	 */
	public function SetComponentContent($keyword, $content)
	{
		$this->components[strtoupper($keyword)]->SetContent($content);
	}
	/**
	 * Establece el contenido y modificadores para el componente correspondiente a $keyword
	 *
	 * @param unknown_type $keyword
	 * @param unknown_type $content
	 * @param unknown_type $modifiers
	 */
	public function SetComponent($keyword, $content, $modifiers)
	{
		$this->components[strtoupper($keyword)]->SetContent($content);
		$this->components[strtoupper($keyword)]->SetModifiers($modifiers);
	}
	/**
	 * Obtiene los modificadores del componente correspondiente a $keyword
	 *
	 * @param string $keyword
	 * @return string
	 */
	public function GetModifiersComponent($keyword)
	{
		return $this->components[strtoupper($keyword)]->GetModifiers();
	}
}
