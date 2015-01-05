<?php
/****************************************************************
 ****************************************************************
 * 
 * gyg-database - Database interface module using gyg-modules.
 * Copyright (C) 2014-2015 Mikael Hernvall (mikael.hernvall@gmail.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 ****************************************************************
 ****************************************************************/


/**
 * \brief Basic properties for table using GygDatabase.
 */
class GygDatabaseTable
{
	// GygDatabase object.
	protected $gygDb;
	
	// Name of table.
	protected $tableName;
	
	/**
	 * \brief Constructor
	 *
	 * \param gygDb object GygDatabase object.
	 * \param tableName string Name of table.
	 */
	public function __construct($gygDb, $tableName)
	{
		 $this->gygDb = $gygDb;
		 $this->tableName = $tableName;
	}
};