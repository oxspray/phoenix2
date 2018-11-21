<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Database Core Functionality
Framework File Signature: com.ph2.framework.php.core.dbcore
Description:
Basic database object for handling mysql-queries
---
/*/

//+
class Table
{
	// INSTANCE VARS
	// -------------
	public $select;
	public $from;
	public $where;
	public $groupby;
	public $having;
	public $orderby;
	public $limit;
	public $lastQuery;
	private $_dbconnector;
	private $_dbhost;
	private $_dbusername;
	private $_dbuserpass;
	private $_dbname;
	private $_tablename;
	private $_fieldlist;
	private $_hasCache;
	private $_cache;
	private $_latestCacheKey;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $tablename , $hasCache=FALSE )
	/*/
	A new Table object is constructed by referencing an
	existing table of the database.
	---
	@param tablename: the table to be selected from the database
	@type  tablename: string
	@param hasCache: if TRUE, this
	Table object will cache queries.
	@type  hasCache: bool
	/*/
	{
		assert ($tablename != NULL);
		
		$this->_dbconnect  = NULL;
		$this->_dbhost     = PH2_DB_HOST;
		if ($_SESSION['isGuest']) {
			$this->_dbusername = PH2_DB_USER_READONLY;
			$this->_dbuserpass = PH2_DB_PASSWORD_READONLY;
		} else {
			$this->_dbusername = PH2_DB_USER;
			$this->_dbuserpass = PH2_DB_PASSWORD;
		}
		$this->_dbname     = PH2_DB_NAME;
		$this->_tablename  = $tablename;
		$this->_fieldlist  = array();
		
		// if caching is activated, assign an emtpy array (=cache) to $_cache
		if ($hasCache) {
			$this->_hasCache = TRUE;
			$this->_cache = array();
		} else {
			$this->_hasCache = FALSE;
		}
		
		// get table description and store results in $fieldlist
		$description = $this->_describeTable();
		foreach ($description as $row => $values) {
			$this->_fieldlist[] = $values['Field']; //store each field's name
			if ($values['Key'] == "PRI") {
				$this->_fieldlist[$values['Field']] = array( 'pkey' => TRUE ); // if field is part of primary key
			}
		}
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function query ( $query , $cache=FALSE )
	/*/
	Querries the database with the submitted query
	string (no composition; all instance variables are ignored).
	---
	@param query: the MySQL query
	@type  query: string
	@param cache: if caching is
	activated for this Table, FALSE will prevent the submitted query
	from being added to the cache.
	@type  cache: bool
	-
	@return: the MySQL
	result; NULL if the resultset is empty
	@rtype:  assiciative array (field/value-pairs)
	/*/
	{
		// check if caching is activated for this Table object
		if ($this->_hasCache) {
			// if so, check whether the submitted querry is in the cache
			if (array_key_exists($query, $this->_cache)) {
				//print 'from cache: ' . $query . "<br/>";
				return $this->_cache[$query];
			}
		}
		
		//echo $query . "<br/>\n";
		
		// if caching is not activated or the submitted query is not in the cache, perform the db request
		$data = array();
		
		// connect to the database
		$this->_connect();
		
		// run query
		#echo $query . '<br /><br />';
		$result = mysql_query($query, $this->_dbconnector) or $error = mysql_error();
		
		if (! $error) {
			// convert the result in associative array with key/value-pairs for each row starting at 0
			// only if query returns rows (which is the case if $result is NOT a boolean value)
			if (!is_bool($result)) {
				while ($row = mysql_fetch_assoc($result)) {
					$data[] = $row;
				}
				mysql_free_result($result);
			}
			
			// if caching is activated, store the result in the cache
			if ($this->_hasCache and $cache==TRUE) {
				$this->_cache[$query] = $data;
				$this->_latestCacheKey = $query;
			}
			$this->lastQuery = $query;
			// finally, release db-ressource and return data if applicable
			//echo $query;
			return $data;
		} else {
			return "MYSQL ERROR: $error";
		}
		
	} //query
	
	//+ 
	function get ( $where=NULL )
	/*/
	Selects database entries. Any submitted $where
	string replaces $this->where. a) if $this->where is of type string,
	it is literally used as WHERE-clause b) if $this->where is of type
	array, a WHERE-clause is constructed (KEY1='VAL1' AND KEY2='VAL2'
	...)
	---
	@param where: the WHERE part
	of the mysql query
	@type  where: string
	-
	@return: the MySQL
	result; NULL if the rsultset is empty
	@rtype:  associative array (field/value-pairs)
	/*/
	{
		// compose query string
		if ($where) {
			// if $custom_query is provided, it is treated as the whole sql query
			$this->where = $where;	
		}
		
		// construct query parts
		// select; default is al fields (*)
		empty($this->select) ? $select_str = "*" : $select_str = $this->select;
		// from; default is current table of this instance
		empty($this->from) ? $from_str = $this->_tablename : $from_str = $this->from;
		// group by
		empty($this->groupby) ? $groupby_str = NULL : $groupby_str = "GROUP BY $this->groupby";
		// having
		empty($this->having) ? $having_str = NULL : $having_str = "HAVING $this->having";
		// order by
		empty($this->orderby) ? $orderby_str = NULL : $orderby_str = "ORDER BY $this->orderby";
		// limit
		empty($this->limit) ? $limit_str = NULL : $limit_str = "LIMIT $this->limit";
		
		// where
		if (empty($this->where)) {
			$where_str = NULL;
		} else {
			if (is_array($this->where)) {
				// if an array is stored, expand it to a valid WHERE-string
				$where_str = $this->_toSqlString($this->where);
			} else {
				// if a string is provided, escape it and use it as the WHERE-clause
				$where_str = $this->where; //UPD: was mysql_real_escape_string($this->where) until Version 0.2 Alpha, Build 100.
			}
			$where_str = "WHERE " . $where_str;
		}
		
		// build the query string
		$query = "SELECT $select_str FROM $from_str $where_str $groupby_str $having_str $orderby_str $limit_str";
		return $this->query($query, TRUE /*important: allow caching*/);
		
	} //get
	
	//+ 
	function insert ( $new_record )
	/*/
	Adds a new record to this instance's table.
	Array keys that do not correspond to a $_fieldlist entry are
	ignored.
	---
	@param new_record: the new row to be
	inserted
	@type  new_record: associative array (field/value-pairs)
	/*/
	{
		// sort out key/value-pairs that are not part of the fieldlist
		$new_record = $this->_removeUnknownFields($new_record);
		
		// compose query string
		$query = "INSERT INTO $this->_tablename SET " . $this->_toSQLString($new_record, ', ', '=');
		
		// run query
		return $this->query($query);
		
	} //insert
	
	//+ 
	function insertRowsAtLowestPossibleID ( $primary_key , $new_rows )
	/*/
	Inserts the given row such that its ID is the lowest possible ID that is currently not 
	used in this table. This is helpful to overcome large "spare blocks" in case a range of 
	entries has been deleted.
	---
	@param primary_key: the name of the primary key field
	@type  primary_key: string
	@param new_rows: the new rows to be inserted, excluding the primary key field
	@type  new_rows: associative array of (field/value-pairs)
	-
	@return: an array containing the IDs used for the insertions
	@rtype:  array(int)
	/*/
	{
	
		$used_ids = array();
		// get the first lowest unused ID
		$id = $this->_getLowestUnusedID($primary_key);
		foreach ($new_rows as $row)  {
			// add the new ID to the current row
			$row[$primary_key] = $id;
			$success = $this->insert($row);
			if( is_string($success) && startsWith($success,"MYSQL ERROR") ) {
				// if the key allready exists, find the next lowest unused ID
				$id = $this->_getLowestUnusedID($primary_key);
				$row[$primary_key] = $id;
				$this->insert($row);
				$used_ids[] = $id;
			} else {
				$used_ids[] = $id;
			}
			// otherwise, just increase the ID by one
			$id++;
		}
		return $used_ids;
	} //insertRowsAtLowestPossibleID
	
	//+ 
	function _getLowestUnusedID ( $primary_key )
	/*/
	returns the lowest unused ID of this table, given its primary key.
	---
	@param primary_key: the name of the primary key
	@type  primary_key: string
	-
	@return: the lowest unused ID
	@rtype:  int
	/*/
	{
		$tablename = $this->_tablename;
		// Note: this is not too fast and should be used as rarely as possible
		$query =   "SELECT MIN(t1.$primary_key + 1) AS lowestUnusedID
					FROM $tablename t1
					   LEFT JOIN $tablename t2
						   ON t1.$primary_key + 1 = t2.$primary_key
					WHERE t2.$primary_key IS NULL";
		$result = $this->query($query);
		return $result[0]['lowestUnusedID'];
	} //_getLowestUnusedID
	
	//+ 
	function checkAdd ( $entry )
	/*/
	Takes an array of fields/values and checks
	whether it allready exists in this Table. If so, the corresponding
	ID is returned; otherwise, the a new entry will be created and its
	ID will be returned.
	---
	@param entry: the row (data) that
	should be checked or created
	@type  entry: associative array (field/value-pairs)
	-
	@return: the ID of the existing or created entry
	@rtype:  int
	/*/
	{
		$checkdata = $this->get($entry);
		if ($checkdata) {
			// if there are results matching the submitted entry, check whether there is
			// exactly one match
			if (count($checkdata) == 1) {
				// if there is exactly one match, return its ID (assuming ID = first column)
				return array_shift($checkdata[0]);
			} else {
				echo 'FrwError: Ambiguous result for checkAdd. Query must match exactly one row.';
			}
		} else {
			// if no the entry doesn't exist yet, create it
			$this->insert($entry);
			// important: clear the cache for this entry as it is not NULL anymore and should be
			// checked on a next iteration
			if ($this->_hasCache) {
				$this->_removeLatestCacheEntry();
			}
			// then, return the ID of the created entry
			return $this->getLastID();
		}
	} //checkAdd
	
	//+ 
	function update ( $update_record )
	/*/
	Updates a row in the database. Primary key
	fields and all fields that are not part of this table's
	$this->_fieldlist are ignored. Note that a WHERE-Clause must
	previously be stored in this instance's $this->where field.
	---
	@param update_record: the new values (paired with existing fields)
	@type  update_record: associative array (key/value-pairs)
	/*/
	{
		// sort out key/value-pairs that are not part of the fieldlist and any primary key fields
		$update_record = $this->_removeUnknownFields($update_record, TRUE);
		
		// compose where substring
		if (is_string($this->where)) {
			$where = $this->where;
		} else {
			$where = $this->_toSQLString($this->where);
		}
		
		// compose query string
		$query = "UPDATE $this->_tablename SET " . $this->_toSQLString($update_record, ", ") . " WHERE " . $where;
		
		//run query
		$this->query($query);
		
	} //update
	
	//+ 
	function delete ( $where , $ignore=FALSE )
	/*/
	Deletes rows matching the key/value-pairs
	provided in $where. $where cannot be empty; to delete all records
	from a table, refer to $this->clear().
	---
	@param where: the WHERE
	identification: matching rows are deleted
	@type  where: assiciative array (key/value-pairs)
	@param ignore: if TRUE, the
	IGNORE-flag will be added to the query (overriding foreign key
	constraints etc.)
	@type  ignore: bool
	/*/
	{
		assert(!empty($where));
		
		// sort out key/value-pairs that are not part of the fieldlist
		if (is_array($where)) {
			$where = $this->_removeUnknownFields($where);
			$where = $this->_toSQLString($where);
		}
		
		// compose query string
		$ignore ? $ignore = "IGNORE" : $ignore = "";
		$query = "DELETE $ignore FROM $this->_tablename WHERE " . $where;
		
		// run query
		$this->query($query);
		
	} //delete
	
	//+ 
	function clear ( $ignore=FALSE )
	/*/
	Deletes all rows from this instance's table.
	---
	@param ignore: if TRUE, the
	IGNORE-flag will be added to the query (overriding foreign key
	constraints etc.)
	@type  ignore: bool
	/*/
	{
		$ignore ? $ignore = "IGNORE" : $ignore = "";
		$this->query("DELETE $ignore FROM $this->_tablename");
		
	} //clear
	
	//+ 
	function getLastID ( )
	/*/
	Returns the last auto-generated
	auto_increment value (LAST_INSERT_ID()).
	-
	@return: the last
	inserted ID (whole database!)
	@rtype:  int
	/*/
	{
		$result = $this->query("SELECT LAST_INSERT_ID()");
		return $result[0]['LAST_INSERT_ID()'];
		
	} //getLastID
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _connect ( )
	/*/
	Connect to the mysql server
	and select the relevant database (std settings).
	/*/
	{
		if (!$this->_dbconnector) {
			$this->_dbconnector = mysql_connect($this->_dbhost, $this->_dbusername, $this->_dbuserpass);
			mysql_select_db($this->_dbname);
			mysql_set_charset('utf8',$this->_dbconnector); 
		}
	} //_connect
	
	//+ 
	private function _describeTable ( $tablename=NULL )
	/*/
	Extract the schema of a
	given table.
	---
	@param tablename: The table to be described. If left blank, the current table
	will be described.
	@type  tablename: string
	-
	@return: the MySQL
	description result
	@rtype:  associative array (field/value-pairs)
	/*/
	{
		$description = array();
		
		// use this instance's table name if no argument is provided
		if (empty($tablename)) {
			$tablename = $this->_tablename;
		}
		
		// connect to the database or raise error
		$this->_connect();
		
		// build the query string and run it
		$query = "DESCRIBE $tablename";
		$result = mysql_query($query, $this->_dbconnector);
		
		// if there is no matching entry, return 0
		if (!$result) return 0;
		
		// convert the result in associative array with key/value-pairs for each row starting at 0
		while ($row = mysql_fetch_assoc($result)) {
			$description[] = $row;
		}
		
		// finally, release db-ressource and return multi-dimensional array
		mysql_free_result($result);
		return $description;
		
	} //_describeTable
	
	//+ 
	private function _toSQLString ( $field_value_pairs , $sep=' AND ' , $link='=' )
	/*/
	Converts an associative
	array of field=value-pairs to an SQL string like (Field='Value' AND
	Field2='Value2' ...) and escapes all values with
	mysql_real_escape_string().
	---
	@param field_value_pairs: None
	@type  field_value_pairs: associative array (field/value-pairs)
	@param sep: the keyword
	linking the field/value-statements
	@type  sep: string
	@param link: the operator between
	a field and its value (field[$link]value)
	@type  link: string
	-
	@return: the escaped sql (part) string
	@rtype:  string
	/*/
	{
		$result = "";
		foreach ($field_value_pairs as $item => $value) {
			if ($value === NULL) {
				// use NULL for empty values
				$result .= '`' . $item . '`' . $link . "NULL" . $sep;
			} else {
				$result .= '`' . $item . '`' . $link . "'" . mysql_real_escape_string($value) . "'" . $sep;
			}
		}
		return rtrim($result, $sep);
		
	} //_toSQLString
	
	//+ 
	private function _removeUnknownFields ( $record , $removePrimaryKeyFields=FALSE )
	/*/
	Removes all
	key/value-pairs in $record where no corresponding key exists in
	$this->_fieldlist.
	---
	@param record: the fieldset from
	which unknown fields should be removed
	@type  record: associative array (field/value-pairs)
	@param removePrimaryKeyFields: true if primary key fields (altough not unknown) should be
	removed, false otherwise
	@type  removePrimaryKeyFields: bool
	-
	@return: $record with
	all unknown fields (and optionally all primary key fields)
	removed
	@rtype:  associative array (field/value-pairs)
	/*/
	{
		$result = array();
		foreach ($record as $key => $value) {
			if (in_array($key, $this->_fieldlist)) {
				if (!$removePrimaryKeyFields || !$this->_fieldlist[$key]['pkey']) {
					$result[$key] = $value;
				}
			}
		}
		return $result;
		
	} //_removeUnknownFields
	
	//+ 
	private function _removeLatestCacheEntry ( )
	/*/
	Removes the
	latest cache entry
	/*/
	{
		if (isset($this->_latestCacheKey)) {
			unset($this->_cache[$this->_latestCacheKey]);
			unset($this->_latestCacheKey);
		} else {
			echo 'FrmWarning: Cannot rollback cache history more than one time subsequently.';
		}
	} //_removeLatestCacheEntry
	
	
}


/**
 * Converts an associative array of field-value-pairs to an SQL string like (Field like 'Value' AND Field2
 * like 'Value2' ...) and escapes all values with mysql_real_escape_string().
 * Adds only field-value-pairs to the SQL string whose value != null. E.g., does not add NULL or empty string values
 * to the SQL string.
 *
 * @param field_value_pairs : The array to convert.
 * @type  field_value_pairs: associative array (field/value-pairs)
 * @param sep : the keyword linking the field/value-statements
 * @type  sep: string
 * @param link : the operator between a field and its value (field[$link]value)
 * @type  link: string
 *
 * @return: the escaped sql (part) string
 * @rtype:  string
 */
function toSQLStringOptional($field_value_pairs, $sep = ' AND ', $link = ' like ') {
    $result = "";
    foreach ($field_value_pairs as $item => $value) {
        if ($value != NULL) {
            $result .= '`' . $item . '`' . $link . "'" . mysql_real_escape_string($value) . "'" . $sep;
        }
    }
    return rtrim($result, $sep);
}

?>