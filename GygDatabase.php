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

/*
 * Basic database functions for inserting and fetching data.
 */
class GygDatabase
{
	// PHP PDO
	private $PDO = null;
	private $stmt = null;
	private $queries = null;
	private $numQueries = 0;
	
	/**
	 * \brief Constructor
	 *
	 * Establish connection to database
	 * through PDO object.
	 *
	 * \param dsn string DSN path to database
	 */
	public function __construct($dsn)
	{
		$this->PDO = new PDO($dsn);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->PDO->exec('PRAGMA foreign_keys=1;');
	}
	
	/**
	 * \brief Execute SQL query and return result.
	 *
	 * \param query string SQL query to execute.
	 * \param params array SQL parameters to execute query with.
	 *
	 * \return array Result of query.
	 */
	public function selectAndFetchAll($query, $params = [])
	{	
		$this->stmt = $this->PDO->prepare($query);
		$this->queries[] = $query;
		$this->numQueries++;

		foreach($params as $param)
			if(is_array($param))
				throw new Exception("GygDatabase::selectAndFetchAll (main.php): Recursive SQL parameter array is not allowed.");
		
		$this->stmt->execute($params);
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}



	/**
	 * \brief Execute SQL query.
	 *
	 * \param query string SQL query to execute.
	 * \param params array SQL parameters to execute query with.
	 */
	public function executeQuery($query, $params = []) 
	{
		$this->stmt = $this->PDO->prepare($query);
		$this->queries[] = $query;
		$this->numQueries++;
		
		foreach($params as $param)
			if(is_array($param))
				throw new Exception("GygDatabase::selectAndFetchAll (main.php): Recursive SQL parameter array is not allowed.");
		
		return $this->stmt->execute($params);
	}
	
	/*
	 * \brief Set PDO attributes.
	 *
	 * \param attribute PDOAttribute Attribute to set.
	 * \param value	integer Attribute value.
	 */
	public function setAttribute($attribute, $value) 
	{
		$this->PDO->SetAttribute($attribute, $value);
	}
	
	/*
	 * \brief Select rows of a table.
	 *
	 * The $param parameter must have the following format
	 *
	 * \param table string Name of table.
	 * \param params arrray "WHERE" parameters.
	 *
	 * Keys of $params array must correspond to columns in table.
	 * Values of $params array must correspond to the value types of
	 * the columns pointed at by array key.
	 * 
	 *
	 * \return array Result from select query.
	 */
	 public function select($table, $params = [], $columns = '*', $order = null, $orderDirection = 'DESC')
	 {
		if(is_array($columns))
			$columns = implode(',', $columns);
	 
		$where = null;
		if(count($params) > 0)
		{
			$where = array_keys($params);
			$where = implode('=? AND ', $where);
			$where = "WHERE {$where}=?";
		}
		
		$params = array_values($params);

		if($order !== null)
		{
			if(is_array($order))
				$order = implode(',', $order);
				
			$order = "ORDER BY {$order} {$orderDirection}";
		}
	 
		return $this->selectAndFetchAll("SELECT {$columns} FROM {$table} {$where} {$order}", $params);
	 }
	
	
	/**
	 * \brief Drop a table, if it exists.
	 *
	 * \param table string name of table.
	 */
	public function drop($table)
	{
		$this->executeQuery("DROP TABLE IF EXISTS {$table};");
	}
	
	/**
	 * \brief Create table
	 *
	 * Create table, if it doesn't exist, with
	 * the given properties. 
	 *
	 * The tableColumns array is structured as the
	 * following example:
	 *	$tableColumns = 
	 *	[
	 *		"id			INTEGER PRIMARY KEY",
	 *		"key		TEXT KEY", 
	 *		"title		TEXT", 
	 *		"data		TEXT", 
	 *		"filter		TEXT", 
	 *		"userId		INT", 
	 *		"created	DATETIME default (datetime('now'))", 
	 *		"updated	DATETIME default NULL", 
	 *		"deleted	DATETIME default NULL", 
	 *		"FOREIGN	KEY(userId) REFERENCES User(id)"
	 *	];
	 *
	 * \param table string, ID of table.
	 * \param tableColumns array, columns of table.
	 * \param isEnabled bool, false makes the table unavailable, true does not.
	 */
	public function create($table, $tableColumns, $isEnabled = true)
	{
		$columns = implode(',', $tableColumns);
		$this->executeQuery("CREATE TABLE IF NOT EXISTS {$table} ({$columns});");
	}
	
	
	
	/**
	 * \brief Get last inserted ID from PDO.
	 */
	public function lastInsertId($name = null)
	{
		return $this->PDO->lastInsertId($name);
	}
	
	
	
	/**
	 * Insert data into table.
	 *
	 * \param table string ID of table
	 * \param data array Data to be inserted. If the array lacks 
	 * string keys, the array's integer type indices will be used.
	 * 
	 * If you're not using string keys to denote which column data to update,
	 * make sure that you know how the table is organized. The order of the
	 * items in the data array will correspond to the column order in the table.
	 * Thus, if you pass the array [1, 'happy', 'karl'] to a table that has the columns
	 * count, emotion and name, the following assignments will occur:
	 *		(0) count	= (0) 1
	 *		(1) emotion	= (1) 'happy'
	 *		(2) name	= (2) 'karl'
	 *
	 * The location of the primary key affects the behaviour of the function. Take the previous
	 * example, but include the primary key "id" as the first column of the table. The following
	 * assignments will then occur:
	 *		(0) id
	 *		(1) count 	= (0) 1
	 *		(2) emotion = (1) 'happy'
	 *		(3) name	= (2) 'karl'
	 *
	 * If the primary key's column position in the table is located in the middle of the table,
	 * the following assignments will occur:
	 *		(0) count	= (0) 1
	 *		(1) emotion	= (1) 'happy'
	 *		(2) id
	 *		(3) name	= (2) 'karl'
	 *
	 * Conclusively, all the integer type key values equal to or larger than the column position
	 * of the primary key will be incremented. For this reason, unless you're familiar with the
	 * structure of the table you're working with, please use string keys in the following
	 * fashion:
	 *		count 	= ('count') 1
	 *		emotion	= ('emotion') 'happy'
	 *		name	= ('name') 'karl'
	 *
	 */
	public function insert($table, $data)
	{	
		$keys = [];
		$values = [];
		$incr = 0;
		foreach($data as $key => $column)
		{
			if(gettype($key) === "integer")
			{
				$tableInfo = $this->selectAndFetchAll("PRAGMA table_info({$table});");
				
				// If $key corresponds to the primary key of the $table,
				// increment all integer type $keys.
				if($tableInfo[$key]['pk'] == true)
					$incr++;

				array_push($keys, $tableInfo[$key + $incr]['name']);
			}
			else		
				array_push($keys, $key);
				
			array_push($values, '?');
		}
		
		$keys = implode(',', $keys);
		$values = implode(',', $values);

		// Strip keys off of data array.
		$data = array_values($data);
		
		
		try 
		{
			return $this->selectAndFetchAll("INSERT INTO {$table}({$keys}) VALUES ({$values})", $data);
		}
		catch(Exception$e) 
		{
			die("$e<br/>Failed to open database.");
		}
	}
	
	
	/**
	 * Update data of a table.
	 *
	 * \param table string ID of table
	 * \param data array Column names and the data
	 * those columns are to be assigned.
	 * \param array Optional argument. If not used,
	 * the update will be applied to all rows of the table. If used,
	 * the update will be applied to all rows of the table that 
	 * match the attributes of the array.
	 *
	 * DATA:
	 * The data array must strictly have the following structure,
	 * else the function will throw an exception.
	 *		$data = 
	 *		[
	 *			'columnId1' => value,
	 * 			'columnId2' => value,
	 *			'columnId3' => value,
	 *			etc ...
	 *		];
	 *
	 * In short, the data array's keys must correspond to the names of the
	 * table's columns. 
	 *
	 * ROWATTRIBUTES:
	 * The array keys must correspond to the names of the table's columns,
	 * just like the $data parameter.
	 */
	public function update($table, $data, $rowAttributes = [])
	{
		// Put the names of all the table's columns into $colNames. Primary keys are excluded.
		$tableInfo = $this->selectAndFetchAll("PRAGMA table_info({$table});");
		$whereColNames = [];
		$colNames = [];
		foreach($tableInfo as $col)
		{
			array_push($whereColNames, $col['name']);
			// Exclude primary keys.
			if($col['pk'] == false)
				array_push($colNames, $col['name']);
		}
		
		// Loop through the data array and add 
		// the columns names to $columns. These
		// will be formatted in accordance to 
		// SQL's SET statement.
		$columns = [];
		foreach($data as $key => $item)
		{
			// If the structure of the data array doesn't match the requirements, throw exception.
			if(gettype($key) === "integer" || !in_array($key, $colNames))
			{

				throw new Exception("GygDatabase::update (functions.php): Structure of data array is incorrect. All array keys must be strings and correspond to a column's name in the table.");
				die();
			}
			
			// Else, push it.
			array_push($columns, $key . "=?");
		}
		
		// Perform final formatting to fit the update statement.
		$columns = implode(',', $columns);
		
		
		// Perform the same formatting on the search options.
		// If $rowAttributes is not defined, set $searchAttributes
		// to '*' to perform update on all rows.
		$searchAttributes = null;
		if(count($rowAttributes) > 0)
		{
			$searchAttributes = [];
			foreach($rowAttributes as $key => $attr)
			{
				// If the structure of the data array doesn't match the requirements, throw exception.
				if(gettype($key) === "integer" || !in_array($key, $whereColNames))
				{
					throw new Exception("GygDatabase::update (functions.php): Structure of data array is incorrect. All array keys must be strings and correspond to a column's name in the table.");
					die();
				}				
				
				// Else, push it.
				array_push($searchAttributes, $key . "=?");
			}
			
			$searchAttributes = "WHERE " . implode(',', $searchAttributes);
		}

		
		// Remove keys of $data and $rowAttributes and merge them into one array.
		$sqlParams = array_merge(array_values($data), array_values($rowAttributes));
		
		return $this->selectAndFetchAll("UPDATE {$table} SET {$columns} {$searchAttributes};", $sqlParams);
	}
	
	/**
	 * \brief Delete all rows from table.
	 *
	 * \param table string ID of table.
	 */
	public function clear($table)
	{
		$this->executeQuery("DELETE FROM {$table};");
	}
	
	/**
	 * \brief Check if a table exists.
	 *
	 * \param tableId string ID of table.
	 *
	 * \return bool True is table exists, else false.
	 */
	public function tableExists($tableId)
	{
		$res = $this->selectAndFetchAll("	SELECT name FROM sqlite_master
											WHERE type='table' AND name=?", [$tableId]);
		
		return (count($res) > 0);
	}
	
	 
	/**
	 * \brief Delete row(s) from table.
	 *
	 * \param tableId string ID of table.
	 * \param params array Row properties to search for. 
	 * See GygDatabase::select for array structure.
	 */
	public function delete($tableId, $params)
	{
		$where = null;
		if(count($params) > 0)
		{
			$where = array_keys($params);
			$where = implode('=?,', $where);
			$where = "WHERE {$where}=?";
		}
	 
	 
		$query = 
		"
			DELETE FROM {$tableId}
			{$where}
		";
	 
		$params = array_values($params);
	 
		$this->executeQuery($query, $params);
	}

	/**
	 * \brief Check if row exists
	 *
	 * \param where array Row properties to search for. 
	 * See GygDatabase::select for array structure.
	 */
	public function rowExists($table, $where)
	{
		return count($this->select($table, $where)) > 0;
	}
};