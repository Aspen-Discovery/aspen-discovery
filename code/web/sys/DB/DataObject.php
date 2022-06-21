<?php

/**
 * Class DataObject
 *
 * Represents a class of data that can be loaded and saved to the database.
 *
 * All properties must be public or protected to handle __get and __set properly
 * Each property that starts with __ is used to store and load data from the database (query information etc).
 * Each property that starts with _ is runtime data that is reset for each object
 * Each property that starts with [a-zA-Z] is a property that is saved to the database
 */
abstract class DataObject
{
	public $__table;
	public $__primaryKey = 'id';
	public $__displayNameColumn = null;
	protected $__N;
	/** @var PDOStatement */
	private $__queryStmt;
	private $__selectAllColumns = true;
	private $__additionalSelects = [];
	private $__orderBy;
	private $__groupBy;
	private $__where;
	private $__limitStart;
	private $__limitCount;
	protected $__lastQuery;
	protected $__lastError;
	private $__joins = [];
	protected $__fetchingFromDB = false;

	protected $_data = [];

	protected $_changedFields = [];

	public $_deleteOnSave;

	/**
	 * @return string[]
	 */
	function getNumericColumnNames() : array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	function getEncryptedFieldNames() : array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	function getSerializedFieldNames() : array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getCompressedColumnNames() : array
	{
		return [];
	}

	function __toString()
	{
		$stringProperty = $this->__primaryKey;
		if ($this->__displayNameColumn != null){
			$stringProperty = $this->__displayNameColumn;
		}
		if ($this->$stringProperty == null){
			return 'new ' . get_class($this);
		}else{
			return $this->$stringProperty;
		}
	}

	function getPrimaryKeyValue()
	{
		$primaryKeyProperty = $this->__primaryKey;
		return $this->$primaryKeyProperty;
	}

	/**
	 * @return string
	 * @noinspection PhpUnused
	 */
	function getPrimaryKey() : string{
		return $this->__primaryKey;
	}

	public function find($fetchFirst = false) : bool {
		if (!isset($this->__table)) {
			echo("Table not defined for class " . self::class);
			die();
		}

		$this->__N = 0;
		if ($this->__queryStmt != null){
			$this->__queryStmt->closeCursor();
			$this->__queryStmt = null;
		}

		global $aspen_db;
		global $timer;
		$query = $this->getSelectQuery($aspen_db);
		$this->__lastQuery = $query;
		$this->__queryStmt = $aspen_db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$this->__queryStmt->setFetchMode(PDO::FETCH_INTO, $this);
		if ($this->__queryStmt->execute()){
			$this->__N = $this->__queryStmt->rowCount();
			if ($this->__N != 0 && $fetchFirst) {
				$this->fetch();
				//If we are fetching the first record, we can cleanup since it won't be used again.
				$this->__queryStmt->closeCursor();
				$this->__queryStmt = null;
			}
		} else {
			echo("Failed to execute " . $query);
		}
		if (IPAddress::logAllQueries()){
			global $logger;
			$logger->log($query, Logger::LOG_ERROR);
		}
		$timer->logTime($query);
		return $this->__N > 0;
	}

	/**
	 * @return DataObject|false|null
	 */
	public function fetch()
	{
		$this->__fetchingFromDB = true;
		if ($this->__queryStmt == null){
			return null;
		}else{
			$return = $this->__queryStmt->fetch(PDO::FETCH_INTO);
		}
		$this->clearRuntimeDataVariables();
		$this->decryptFields();
		$this->__fetchingFromDB = false;
		return $return;
	}

	public function fetchAssoc(){
		$this->__fetchingFromDB = true;
		$return = $this->__queryStmt->fetch(PDO::FETCH_ASSOC);
		$this->__fetchingFromDB = false;
		return $return;
	}

	/**
	 * Retrieves all objects for the current query if name and value are null
	 * Retrieves a list of all field values if only fieldName is provided
	 * Retrieves an associated array if both fieldName and fieldValue are provided
	 * @param null $fieldName
	 * @param null $fieldValue
	 * @return DataObject[]
	 */
	public function fetchAll($fieldName = null, $fieldValue = null) : array
	{
		$this->__fetchingFromDB = true;
		$results = array();
		if ($this->find() > 0) {
			$result = $this->fetch();
			while ($result != null) {
				if ($fieldName != null && $fieldValue != null) {
					$results[$result->$fieldName] = $result->$fieldValue;
				}elseif ($fieldName != null) {
					$results[$result->$fieldName] = $result->$fieldName;
				} else {
					$results[] = clone $result;
				}
				$result = $this->fetch();
			}
		}
		$this->__queryStmt->closeCursor();
		$this->__queryStmt = null;
		$this->__fetchingFromDB = false;
		return $results;
	}

	/**
	 * @param string[]|string $fieldsToOrder
	 */
	public function orderBy($fieldsToOrder)
	{
		if ($fieldsToOrder == null) {
			$this->__orderBy = null;
		}else {
			if (is_array($fieldsToOrder)){
				$fieldsToOrder = implode(',', $fieldsToOrder);
			}
			if (strlen($this->__orderBy) > 0) {
				$this->__orderBy .= ', ' . $fieldsToOrder;
			}else {
				$this->__orderBy .= ' ORDER BY ' . $fieldsToOrder;
			}
		}
	}

	/**
	 * @param string[]|string $fieldsToGroup
	 */
	public function groupBy($fieldsToGroup)
	{
		if ($fieldsToGroup == null) {
			$this->__groupBy = null;
		}else {
			if (is_array($fieldsToGroup)){
				$fieldsToGroup = implode(',', $fieldsToGroup);
			}
			if (strlen($this->__groupBy) > 0) {
				$this->__groupBy .= ', ' . $fieldsToGroup;
			}else {
				$this->__groupBy .= ' GROUP BY ' . $fieldsToGroup;
			}
		}
	}

	/**
	 * @param string|bool $cond
	 * @param string $logic
	 */
	public function whereAdd($cond = false, string $logic = 'AND'){
		if ($cond == false) {
			$this->__where = null;
		}else {
			if (strlen($this->__where) > 0) {
				$this->__where .= ' ' . $logic. ' (' . $cond . ')';
			}else {
				$this->__where .= '(' . $cond . ')';
			}
		}
	}

	public function whereAddIn($field, $values, $escapeValues, $logic = 'AND') {
		if ($escapeValues) {
			foreach ($values as $index => $value) {
				$values[$index] = $this->escape($value);
			}
		}
		$valuesString = implode(', ', $values);
		$whereClause = "$field IN ($valuesString)";
		if (strlen($this->__where) > 0) {
			$this->__where .= ' ' . $logic . ' ' . $whereClause;
		}else {
			$this->__where .= $whereClause;
		}
	}

	public function insert(){
		global $aspen_db;
		if (!isset($aspen_db)){
			return false;
		}
		$insertQuery = 'INSERT INTO ' . $this->__table;

		$numericColumns = $this->getNumericColumnNames();
		$encryptedFields = $this->getEncryptedFieldNames();
		$serializedFields = $this->getSerializedFieldNames();
		$compressedFields = $this->getCompressedColumnNames();

		$properties = get_object_vars($this);
		$propertyNames = '';
		$propertyValues = '';
		foreach ($properties as $name => $value) {
			if (!is_null($value) && !is_array($value) && $name[0] != '_' && $name[0] != 'N') {
				if (strlen($propertyNames) != 0) {
					$propertyNames .= ', ';
					$propertyValues .= ', ';
				}
				$propertyNames .= $name;
				if (in_array($name, $numericColumns)) {
					if ($value === true) {
						$propertyValues .= 1;
					} else if ($value == false) {
						$propertyValues .= 0;
					} elseif (is_numeric($value)) {
						$propertyValues .= $value;
					} else {
						$propertyValues .= 'NULL';
					}
				}elseif (in_array($name, $encryptedFields)) {
					$propertyValues .= $aspen_db->quote(EncryptionUtils::encryptField($value));
				}elseif (in_array($name, $serializedFields)) {
					if (!empty($value)) {
						$propertyValues .= $aspen_db->quote(serialize($value));
					}else{
						$propertyValues .= "''";
					}
				}elseif (in_array($name, $compressedFields)) {
					if (!empty($value)) {
						$propertyValues .= 'COMPRESS(' . $aspen_db->quote($value) . ')';
					}else{
						$propertyValues .= "''";
					}
				} else {
					$propertyValues .= $aspen_db->quote($value);
				}
			}elseif (is_array($value) && in_array($name, $serializedFields)){
				if (strlen($propertyNames) != 0) {
					$propertyNames .= ', ';
					$propertyValues .= ', ';
				}
				$propertyNames .= $name;
				if (!empty($value)) {
					$propertyValues .= $aspen_db->quote(serialize($value));
				}else{
					$propertyValues .= "''";
				}
			}elseif (is_array($value) && in_array($name, $compressedFields)){
				if (strlen($propertyNames) != 0) {
					$propertyNames .= ', ';
					$propertyValues .= ', ';
				}
				$propertyNames .= $name;
				if (!empty($value)) {
					$propertyValues .= 'COMPRESS(' . $aspen_db->quote($value) . ')';
				}else{
					$propertyValues .= "''";
				}
			}
		}
		$insertQuery .= '(' . $propertyNames . ') VALUES (' . $propertyValues . ');';
		$response = $aspen_db->exec($insertQuery);
		global $timer;
		if (IPAddress::logAllQueries()){
			global $logger;
			$logger->log($insertQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($insertQuery);
		$this->{$this->__primaryKey} = $aspen_db->lastInsertId();
		return $response;
	}

	public function update(){
		$primaryKey = $this->__primaryKey;
		if (empty($this->$primaryKey)){
			return $this->insert();
		}
		global $aspen_db;
		if (!isset($aspen_db)){
			return false;
		}
		$updateQuery = 'UPDATE ' . $this->__table;

		$numericColumns = $this->getNumericColumnNames();
		$encryptedFields = $this->getEncryptedFieldNames();
		$serializedFields = $this->getSerializedFieldNames();
		$compressedFields = $this->getCompressedColumnNames();

		$properties = get_object_vars($this);
		$updates = '';
		foreach ($properties as $name => $value) {
			if ($value !== null && !is_array($value) && $name[0] != '_' && $name != 'N' && $name != $this->__primaryKey) {
				if (strlen($updates) != 0) {
					$updates .= ', ';
				}
				if (in_array($name, $numericColumns)) {
					if ($value === true){
						$updates .= $name . ' = 1';
					}else if ($value == false) {
						$updates .= $name . ' = 0';
					} else if (is_numeric($value)) {
						$updates .= $name . ' = ' . $value;
					} else {
						$updates .= $name . ' = NULL';
					}
				}elseif (in_array($name, $encryptedFields)){
					$updates .= $name . ' = ' . $aspen_db->quote(EncryptionUtils::encryptField($value));
				}elseif (in_array($name, $serializedFields)) {
					if (!empty($value)) {
						$updates .= $name . ' = ' . $aspen_db->quote(serialize($value));
					}else{
						$updates .= $name . ' = ' .  $aspen_db->quote('');
					}
				}elseif (in_array($name, $compressedFields)) {
					if (!empty($value)) {
						$updates .= $name . ' = COMPRESS(' . $aspen_db->quote($value) . ')';
					}else{
						$updates .= $name . ' = ' .  $aspen_db->quote('');
					}
				} else {
					$updates .= $name . ' = ' . $aspen_db->quote($value);
				}
			}elseif (is_array($value) && in_array($name, $serializedFields)){
				if (!empty($value)) {
					$updates .= $name . ' = ' . $aspen_db->quote(serialize($value));
				}else{
					$updates .= $name . ' = ' .  $aspen_db->quote('');
				}
			}elseif (is_array($value) && in_array($name, $compressedFields)){
				if (!empty($value)) {
					$updates .= $name . ' = COMPRESS(' . $aspen_db->quote($value) . ')';
				}else{
					$updates .= $name . ' = ' .  $aspen_db->quote('');
				}
			}
		}
		$updateQuery .= ' SET ' . $updates . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
		$this->__lastQuery = $updateQuery;
		$response = $aspen_db->exec($updateQuery);
		global $timer;
		if (IPAddress::logAllQueries()){
			global $logger;
			$logger->log($updateQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($updateQuery);
		return $response;
	}

	public function get($columnName, $value = null) : bool
	{
		if ($value == null) {
			$value = $columnName;
			$columnName = $this->__primaryKey;
		}
		$this->$columnName = $value;
		return $this->find(true);
	}

	public function delete($useWhere = false){
		global $aspen_db;
		if (!isset($aspen_db)){
			return false;
		}
		$primaryKey = $this->__primaryKey;

		if ($useWhere){
			/** @noinspection SqlWithoutWhere */
			$deleteQuery = 'DELETE from ' . $this->__table . $this->getWhereClause($aspen_db);
		}else{
			$deleteQuery = 'DELETE from ' . $this->__table . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
		}

		$result = $aspen_db->exec($deleteQuery);
		global $timer;
		if (IPAddress::logAllQueries()){
			global $logger;
			$logger->log($deleteQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($deleteQuery);
		return $result;
	}

	public function limit($start, $count){
		$this->__limitStart = $start;
		$this->__limitCount = $count;
	}

	public function count(){
		if (!isset($this->__table)) {
			echo("Table not defined for class " . self::class);
			die();
		}

		global $aspen_db;
		if (!isset($aspen_db)){
			return false;
		}
		$query = 'SELECT COUNT(*) from ' . $this->__table;
		foreach ($this->__joins as $join) {
			$query .= $this->getJoinQuery($join);
		}
		$query .= $this->getWhereClause($aspen_db);
		$query .= $this->__groupBy;
		$this->__lastQuery = $query;
		$this->__queryStmt = $aspen_db->prepare($query);
		try {
			if ($this->__queryStmt->execute()) {
				if (!empty($this->__groupBy)) {
					return $this->__queryStmt->rowCount();
				} else {
					if ($this->__queryStmt->rowCount()) {
						$data = $this->__queryStmt->fetch();
						return $data[0];
					}
				}
			} else {
				echo("Failed to execute " . $query);
			}
		} finally {
			global $timer;
			if (IPAddress::logAllQueries()){
				global $logger;
				$logger->log($query, Logger::LOG_ERROR);
			}
			$timer->logTime($query);
		}

		return 0;
	}

	public function query($query) : bool
	{
		if (!isset($this->__table)) {
			echo("Table not defined for class " . self::class);
			die();
		}

		global $aspen_db;
		if (!isset($aspen_db)){
			return false;
		}

		$this->__lastQuery = $query;
		$this->__queryStmt = $aspen_db->prepare($query);
		$this->__queryStmt->setFetchMode(PDO::FETCH_INTO, $this);
		if ($this->__queryStmt->execute()){
			$this->__N = $this->__queryStmt->rowCount();
		} else {
			echo("Failed to execute " . $query);
			$this->__lastError = $this->__queryStmt->errorInfo();
		}
		global $timer;
		if (IPAddress::logAllQueries()){
			global $logger;
			$logger->log($query, Logger::LOG_ERROR);
		}
		$timer->logTime($query);

		return $this->__N > 0;
	}

	public function escape($variable){
		global $aspen_db;
		return $aspen_db->quote($variable);
	}

	public function selectAdd($condition = null){
		if ($condition == null) {
			$this->__selectAllColumns = false;
			$this->__additionalSelects = array();
		} else {
			$this->__additionalSelects[] = $condition;
		}
	}

	public function table(){
		global $aspen_db;
		if (!isset($aspen_db)){
			return false;
		}
		//Get the columns defined in this table
		$query = 'SHOW COLUMNS FROM ' . $this->__table;
		$results = $aspen_db->query($query, PDO::FETCH_ASSOC);
		$columns = array();
		$row = $results->fetchObject();
		while ($row != null) {
			$columns[$row->Field] = $row;
			$row = $results->fetchObject();
		}
		return $columns;
	}

	protected function setLastError($errorMessage) {
		$this->__lastError = $errorMessage;
	}

	public function getLastError(){
		return $this->__lastError;
	}

	public function joinAdd(DataObject $objectToJoin, string $joinType, string $alias, string $mainTableField, string $joinedTableField) : void
	{
		$this->__joins[] = [
			'object' => $objectToJoin,
			'joinType' => $joinType,
			'alias' => $alias,
			'mainTableField' => $mainTableField,
			'joinedTableField' => $joinedTableField
		];
	}

	private function getJoinQuery($join) : string
	{
		global $aspen_db;
		/** @var DataObject $joinObject */
		$joinObject = $join['object'];
		$subQuery = $joinObject->getSelectQuery($aspen_db);

		return " {$join['joinType']}  JOIN ($subQuery) AS {$join['alias']} ON $this->__table.{$join['mainTableField']} = {$join['alias']}.{$join['joinedTableField']} ";
	}

	/**
	 * @param PDO $aspen_db
	 * @return string
	 */
	public function getSelectQuery(PDO $aspen_db): string
	{
		$properties = get_object_vars($this);
		$compressedFields = $this->getCompressedColumnNames();
		if (count($compressedFields) == 0){
			$selectClause = $this->__table . '.*';
		}else{
			$selectClause = '';
			foreach ($properties as $name => $value) {
				if ($name[0] != '_') {
					if (!empty($selectClause)) {
						$selectClause .= ', ';
					}
					if (in_array($name, $compressedFields)) {
						$selectClause .= 'UNCOMPRESS(' . $this->__table . '.' . $name . ') as ' . $name;
					} else {
						$selectClause .= $this->__table . '.' . $name;
					}
				}
			}
		}

		if (count($this->__additionalSelects) > 0) {
			$selectClause = implode($this->__additionalSelects, ',');
			if ($this->__selectAllColumns) {
				$selectClause = '*, ' . $selectClause;
			}
		}

		$query = 'SELECT ' . $selectClause . ' from ' . $this->__table;

		foreach ($this->__joins as $join) {
			$query .= $this->getJoinQuery($join);
		}

		$where = '';
		foreach ($properties as $name => $value) {
			if ($value !== null && $name[0] != '_') {
				if (strlen($where) != 0) {
					$where .= ' AND ';
				}
				if (count($this->__joins) > 0){
					$where .= $this->__table . '.' . $name . ' = ' . $aspen_db->quote($value);
				}else{
					$where .= $name . ' = ' . $aspen_db->quote($value);
				}

			}
		}

		if (strlen($this->__where) > 0 && strlen($where) > 0) {
			$query .= ' WHERE ' . $this->__where . ' AND ' . $where;
		} else if (strlen($this->__where) > 0) {
			$query .= ' WHERE ' . $this->__where;
		} else if (strlen($where) > 0) {
			$query .= ' WHERE ' . $where;
		}
		if ($this->__groupBy != null) {
			$query .= $this->__groupBy;
		}
		if ($this->__orderBy != null) {
			$query .= $this->__orderBy;
		}
		if (isset($this->__limitCount) && isset($this->__limitStart)) {
			$query .= ' LIMIT ' . $this->__limitStart . ', ' . $this->__limitCount;
		} else if (isset($this->__limitCount)) {
			$query .= ' LIMIT ' . $this->__limitCount;
		} else if (isset($this->__limitStart)) {
			//This really shouldn't happen
			$query .= ' OFFSET ' . $this->__limitCount;
		}
		return $query;
	}

	/**
	 * @param PDO $aspen_db
	 * @return string
	 */
	private function getWhereClause(PDO $aspen_db): string
	{
		$properties = get_object_vars($this);
		$where = '';
		foreach ($properties as $name => $value) {
			if ($value != null && $name[0] != '_') {
				if (strlen($where) != 0) {
					$where .= ' AND ';
				}
				$where .= $name . ' = ' . $aspen_db->quote($value);
			}
		}
		if (strlen($this->__where) > 0 && strlen($where) > 0) {
			$where = ' WHERE ' . $this->__where . ' AND ' . $where;
		} else if (strlen($this->__where) > 0) {
			$where = ' WHERE ' . $this->__where;
		} else if (strlen($where) > 0) {
			$where = ' WHERE ' . $where;
		}
		return $where;
	}

	public function __sleep()
	{
		$propertiesToSerialize = [];
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($value != null && strpos($name, '__') === false && $name != 'N') {
				$propertiesToSerialize[] = $name;
			}
		}
		return $propertiesToSerialize;
	}

	public function __destruct()
	{
		if ($this->__queryStmt){
			$this->__queryStmt->closeCursor();
			$this->__queryStmt = null;
		}
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($value instanceof DataObject) {
				$value->__destruct();
				unset($this->$name);
			}
		}
		$this->_data = null;
	}

	protected function saveOneToManyOptions($oneToManySettings, $keyOther)
	{
		/** @var DataObject $oneToManyDBObject */
		foreach ($oneToManySettings as $oneToManyDBObject) {
			if ( $oneToManyDBObject->_deleteOnSave == true){
				$oneToManyDBObject->delete();
			}else{
				if (isset($oneToManyDBObject->{$oneToManyDBObject->__primaryKey}) && is_numeric($oneToManyDBObject->{$oneToManyDBObject->__primaryKey})){ // (negative ids need processed with insert)
					$oneToManyDBObject->update();
				}else{
					$oneToManyDBObject->$keyOther = $this->{$this->__primaryKey};
					$oneToManyDBObject->insert();
				}
			}
		}
	}

	protected function clearOneToManyOptions($oneToManyDBObjectClassName, $keyOther) {
		/** @var DataObject $oneToManyDBObject */
		$oneToManyDBObject = new $oneToManyDBObjectClassName();
		$oneToManyDBObject->$keyOther = $this->{$this->__primaryKey};
		$oneToManyDBObject->delete(true);
	}

	public function __clone()
	{
		$className = get_class($this);
		$clone = new $className;
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value){
			if (!is_null($value) && $name[0] == '_' && $name != '__queryStmt'){
				$clone->$name = $value;
			}
		}
		$clone->clearRuntimeDataVariables();
		return $clone;
	}

	protected function clearRuntimeDataVariables(){
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($name[0] == '_' && strlen($name) > 1 && $name[1] != '_') {
				if ($name == '_data'){
					$this->_data = [];
				}else {
					$this->$name = null;
				}
			}
		}
	}

	/**
	 * @return integer
	 */
	public function getNumResults() : int
	{
		return $this->__N;
	}

	public function copy(array $propertiesToChange, $saveCopy) : DataObject
	{
		$newObject = clone $this;
		$newObject->__primaryKey = null;
		foreach ($propertiesToChange as $name => $value){
			$propertiesToChange->$name = $value;
		}
		if ($saveCopy){
			$newObject->insert();
		}
		return $newObject;
	}

	public function isEqualTo(DataObject $other) : bool
	{
		$properties = get_object_vars($this);
		$equal = true;
		foreach ($properties as $name => $value){
			if ($name[0] != '_'){
				if ($other->$name != $value){
					$equal = false;
					break;
				}
			}
		}
		return $equal;
	}

	/**
	 * @param string $propertyName
	 * @param $newValue
	 * @param array|null $propertyStructure
	 *
	 * @return boolean true if the property changed, or false if it did not
	 * @noinspection PhpUnused
	 */
	public function setProperty(string $propertyName, $newValue, ?array $propertyStructure) : bool
	{
		$propertyChanged = $this->$propertyName != $newValue || (is_null($this->$propertyName) && !is_null($newValue));
		if ($propertyChanged) {
			$this->_changedFields[] = $propertyName;
			$oldValue = $this->$propertyName;
			if ($propertyStructure['type'] == 'checkbox'){
				if ($newValue == 'off' || $newValue == false){
					$newValue = 0;
				}elseif ($newValue == 'on' || $newValue == true){
					$newValue = 1;
				}
			}
			$this->$propertyName = $newValue;
			if ($propertyStructure != null && !empty($propertyStructure['forcesReindex'])){
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				SystemVariables::forceNightlyIndex();
			}
			//Add the change to the history unless tracking the history is off (passwords)
			if ($propertyStructure['type'] != 'password' && $propertyStructure['type'] != 'storedPassword') {
				require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
				$history = new DataObjectHistory();
				$history->objectType = get_class($this);
				$primaryKey = $this->__primaryKey;
				if (!empty($this->$primaryKey)) {
					if (strlen($oldValue) >= 65535) {
						$oldValue = 'Too long to track history';
					}
					if (strlen($newValue) >= 65535) {
						$newValue = 'Too long to track history';
					}
					$history->objectId = $this->$primaryKey;
					$history->oldValue = $oldValue;
					$history->propertyName = $propertyName;
					$history->newValue = $newValue;
					$history->changedBy = UserAccount::getActiveUserId();
					$history->changeDate = time();
					$history->insert();
				}
			}

			return true;
		}else{
			return false;
		}
	}

	protected function decryptFields(){
		$encryptedFields = $this->getEncryptedFieldNames();
		foreach ($encryptedFields as $fieldName){
			$this->$fieldName = EncryptionUtils::decryptField($this->$fieldName);
		}
		//compressed fields also get serialized automatically
		$serializedFields = $this->getSerializedFieldNames();
		foreach ($serializedFields as $fieldName) {
			if (!empty($this->$fieldName) && $this->$fieldName !== null && is_string($this->$fieldName)) {
				$this->$fieldName = unserialize($this->$fieldName);
			}
		}
	}

	public function toArray() : array
	{
		$return = [];
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($name[0] != '_'){
				$return[$name] = $value;
			}else if ($name[0] == '_' && strlen($name) > 1 && $name[1] != '_') {
				if ($name != '_data'){
					$return[substr($name, 1)] = $value;
				}
			}
		}
		return $return;
	}

	public function canActiveUserEdit(){
		return true;
	}
}