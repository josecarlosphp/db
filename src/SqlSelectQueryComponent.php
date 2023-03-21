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
 * @desc        Class sql select query component.
 */

namespace josecarlosphp\db;

/**
 * Clase componente de una consulta sql select
 *
 */
class SqlSelectQueryComponent
{
	private $keyword;
	private $modifiers;
	private $content;

	public function __construct($keyword, $content, $modifiers='')
	{
		$this->keyword = trim($keyword);
		$this->content = trim($content);
		$this->modifiers = trim($modifiers);
	}

	public function ToString()
	{
		$str = '';
		if($this->content)
		{
			$str = $this->keyword;
			if($this->modifiers)
			{
				$str .= " {$this->modifiers}";
			}
			$str .= " {$this->content}";
		}
		return $str;
	}

	public function GetContent()
	{
		return $this->content;
	}

	public function SetContent($content)
	{
		$this->content = $content;
	}

	public function SetModifiers($modifiers)
	{
		$this->modifiers = $modifiers;
	}

	public function GetModifiers()
	{
		return $this->modifiers;
	}
}
