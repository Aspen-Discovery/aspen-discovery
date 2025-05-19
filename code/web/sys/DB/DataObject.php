<?php
/** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Hooks/registry.php';
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
abstract class DataObject implements JsonSerializable {
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

	function objectHistoryEnabled() : bool {
		return true;
	}

	/**
	 * @return string[]
	 */
	function getNumericColumnNames(): array {
		return [];
	}

	/**
	 * @return string[]
	 */
	function getEncryptedFieldNames(): array {
		return [];
	}

	/**
	 * @return string[]
	 */
	function getSerializedFieldNames(): array {
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getCompressedColumnNames(): array {
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array {
		return [];
	}

	public function unsetUniquenessFields() {
		foreach ($this->getUniquenessFields() as $field) {
			unset($this->$field);
		}
		$primaryKey = $this->getPrimaryKey();
		unset($this->$primaryKey);
	}

	public function loadCopyableSubObjects() {

	}

	function __toString() {
		$stringProperty = $this->__primaryKey;
		if ($this->__displayNameColumn != null) {
			$stringProperty = $this->__displayNameColumn;
		}
		if ($this->$stringProperty == null) {
			return 'new ' . get_class($this);
		} else {
			return $this->$stringProperty;
		}
	}

	function getPrimaryKeyValue() {
		$primaryKeyProperty = $this->__primaryKey;
		return $this->$primaryKeyProperty;
	}

	/**
	 * @return string
	 * @noinspection PhpUnused
	 */
	function getPrimaryKey(): string {
		return $this->__primaryKey;
	}

	public function find($fetchFirst = false, $requireOneMatchToReturn = false): bool {
		if (!isset($this->__table)) {
			echo("Table not defined for class " . self::class);
			die();
		}

		$this->__N = 0;
		if ($this->__queryStmt != null) {
			$this->__queryStmt->closeCursor();
			$this->__queryStmt = null;
		}

		global $aspen_db;
		global $timer;
		$query = $this->getSelectQuery($aspen_db);
		$this->__lastQuery = $query;
		$this->__queryStmt = $aspen_db->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
		$this->__queryStmt->setFetchMode(PDO::FETCH_INTO, $this);
		try {
			if ($this->__queryStmt->execute()) {
				$this->__N = $this->__queryStmt->rowCount();
				if ($this->__N > 0 && $fetchFirst) {
					$okToFetch = ($this->__N === 1);
					if (!$okToFetch && !$requireOneMatchToReturn) {
						$okToFetch = true;
					}
					if ($okToFetch) {
						$this->fetch();
						//If we are fetching the first record, we can clean up since it won't be used again.
						$this->__queryStmt->closeCursor();
						$this->__queryStmt = null;
					} else {
						$this->__queryStmt->closeCursor();
						$this->__queryStmt = null;
						return false;
					}
				}
			} else {
				echo("Failed to execute " . $query);
			}
		}catch (PDOException $e) {
			if (IPAddress::showDebuggingInformation() && !($this instanceof AspenError)) {
				$errorToLog = new AspenError("Error finding object of type " . get_class($this) . "<br/>\n" . $e->getMessage() . "<br/>\n" . $e->getTraceAsString());
				$errorToLog->insert();
			}
			$this->setLastError("Error finding object of type " . get_class($this) . "<br/>\n" . $e->getMessage() . "<br/>\n" . $e->getTraceAsString());
			return false;
		}
		if (IPAddress::logAllQueries()) {
			global $logger;
			$logger->log($query, Logger::LOG_ERROR);
		}
		$timer->logTime($query);
		return $this->__N > 0;
	}

	/**
	 * @return DataObject|false|null
	 */
	public function fetch(): bool|DataObject|null {
		$this->__fetchingFromDB = true;
		$this->clearRuntimeDataVariables();
		if ($this->__queryStmt == null) {
			return null;
		} else {
			$return = $this->__queryStmt->fetch(PDO::FETCH_INTO);
		}
		$this->decryptFields();
		$this->__fetchingFromDB = false;
		return $return;
	}

	public function fetchAssoc() {
		$this->__fetchingFromDB = true;
		$return = $this->__queryStmt->fetch(PDO::FETCH_ASSOC);
		$this->__fetchingFromDB = false;
		return $return;
	}

	/**
	 * Retrieves all objects for the current query if name and value are null
	 * Retrieves a list of all field values if only fieldName is provided
	 * Retrieves an associated array if both fieldName and fieldValue are provided
	 * @param string? $fieldName
	 * @param string? $fieldValue
	 * @param bool $lowerCaseKey - Forces the key to be lower cased
	 * @param bool $usePrimaryKey - sets the key of the result to the primary key of the object
	 * @return array
	 */
	public function fetchAll(?string $fieldName = null, ?string $fieldValue = null, bool $lowerCaseKey = false, bool $usePrimaryKey = false): array {
		$this->__fetchingFromDB = true;
		$results = [];
		if ($fieldName != null && $fieldValue != null) {
			$this->selectAdd();
			$this->selectAdd($fieldName);
			$this->selectAdd($fieldValue);
		} elseif ($fieldName != null) {
			$this->selectAdd();
			$this->selectAdd($fieldName);
		}
		if ($this->find() > 0) {
			$result = $this->fetch();
			while ($result != null) {
				if ($fieldName != null && $fieldValue != null) {
					$key = $lowerCaseKey ? strtolower($result->$fieldName) : $result->$fieldName;
					$results[$key] = $result->$fieldValue;
				} elseif ($fieldName != null) {
					$key = $lowerCaseKey ? strtolower($result->$fieldName) : $result->$fieldName;
					$results[$key] = $result->$fieldName;
				} else {
					if ($usePrimaryKey) {
						$results[$result->getPrimaryKeyValue()] = clone $result;
					}else{
						$results[] = clone $result;
					}
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
	public function orderBy($fieldsToOrder) {
		if ($fieldsToOrder == null) {
			$this->__orderBy = null;
		} else {
			if (is_array($fieldsToOrder)) {
				$fieldsToOrder = implode(',', $fieldsToOrder);
			}
			if (!empty($this->__orderBy)) {
				$this->__orderBy .= ', ' . $fieldsToOrder;
			} else {
				$this->__orderBy .= ' ORDER BY ' . $fieldsToOrder;
			}
		}
	}

	/**
	 * @param string[]|string $fieldsToGroup
	 */
	public function groupBy($fieldsToGroup) {
		if ($fieldsToGroup == null) {
			$this->__groupBy = null;
		} else {
			if (is_array($fieldsToGroup)) {
				$fieldsToGroup = implode(',', $fieldsToGroup);
			}
			if (strlen($this->__groupBy) > 0) {
				$this->__groupBy .= ', ' . $fieldsToGroup;
			} else {
				$this->__groupBy .= ' GROUP BY ' . $fieldsToGroup;
			}
		}
	}

	/**
	 * @param string|bool $cond
	 * @param string $logic
	 */
	public function whereAdd($cond = false, string $logic = 'AND') {
		if ($cond == false) {
			$this->__where = null;
		} else {
			if (!empty($this->__where)) {
				$this->__where .= ' ' . $logic . ' (' . $cond . ')';
			} else {
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
		} else {
			$this->__where .= $whereClause;
		}
	}

	/**
	 * @return int|bool
	 */
	public function insert($context = '') {
		global $aspen_db;
		if (!isset($aspen_db)) {
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
				if ($name == $this->getPrimaryKey() && empty($this->getPrimaryKeyValue())){
					continue;
				}
				if (strlen($propertyNames) != 0) {
					$propertyNames .= ', ';
					$propertyValues .= ', ';
				}
				$propertyNames .= $name;
				if (in_array($name, $numericColumns)) {
					if ($value === true) {
						$propertyValues .= 1;
					} elseif ($value == false) {
						$propertyValues .= 0;
					} elseif (is_numeric($value)) {
						$propertyValues .= $value;
					} else {
						$propertyValues .= 'NULL';
					}
				} elseif (in_array($name, $encryptedFields)) {
					$propertyValues .= $aspen_db->quote(EncryptionUtils::encryptField($value));
				} elseif (in_array($name, $serializedFields)) {
					if (!empty($value)) {
						$propertyValues .= $aspen_db->quote(serialize($value));
					} else {
						$propertyValues .= "''";
					}
				} elseif (in_array($name, $compressedFields)) {
					if (!empty($value)) {
						$propertyValues .= 'COMPRESS(' . $aspen_db->quote($value) . ')';
					} else {
						$propertyValues .= "''";
					}
				} else {
					$propertyValues .= $aspen_db->quote($value);
				}
			} elseif (is_array($value) && in_array($name, $serializedFields)) {
				if (strlen($propertyNames) != 0) {
					$propertyNames .= ', ';
					$propertyValues .= ', ';
				}
				$propertyNames .= $name;
				if (!empty($value)) {
					$propertyValues .= $aspen_db->quote(serialize($value));
				} else {
					$propertyValues .= "''";
				}
			} elseif (is_array($value) && in_array($name, $compressedFields)) {
				if (strlen($propertyNames) != 0) {
					$propertyNames .= ', ';
					$propertyValues .= ', ';
				}
				$propertyNames .= $name;
				if (!empty($value)) {
					$propertyValues .= 'COMPRESS(' . $aspen_db->quote($value) . ')';
				} else {
					$propertyValues .= "''";
				}
			}
		}
		$insertQuery .= '(' . $propertyNames . ') VALUES (' . $propertyValues . ');';
		try {
			$response = $aspen_db->exec($insertQuery);
		} catch (PDOException $e) {
			if (IPAddress::showDebuggingInformation() && !($this instanceof AspenError)) {
				$errorToLog = new AspenError("Error inserting " . get_class($this) . "<br/>\n" . $e->getMessage() . "<br/>\n" . $e->getTraceAsString());
				$errorToLog->insert();
			}
			$this->setLastError("Error inserting " . get_class($this) . "<br/>\n" . $e->getMessage() . "<br/>\n" . $e->getTraceAsString());
			$response = false;
		}
		global $timer;
		if (IPAddress::logAllQueries()) {
			global $logger;
			$logger->log($insertQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($insertQuery);
		$this->{$this->__primaryKey} = $aspen_db->lastInsertId();

		//Log the insert into object history
		if (!empty($this->{$this->__primaryKey}) && !($this instanceof DataObjectHistory) && !($this instanceof AspenError)) {
			if ($this->objectHistoryEnabled()) {
				require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
				$history = new DataObjectHistory();
				$history->objectType = get_class($this);
				$history->objectId = $this->{$this->__primaryKey};
				$history->propertyName = '';
				$history->actionType = 1;
				$history->changedBy = UserAccount::getActiveUserId();
				$history->changeDate = time();
				$history->insert();
			}
		}

		do_action('after_object_insert', $this);

		return $response;
	}

	/**
	 * @return int|bool
	 */
	public function update($context = '') {
		$primaryKey = $this->__primaryKey;
		if (empty($this->$primaryKey) && $this->$primaryKey !== "0") {
			$result = $this->insert();
			return $result;
		}
		global $aspen_db;
		if (!isset($aspen_db)) {
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
				//Check to see if we are updating only selected fields and if so, only skip things that aren't changed
				if (!empty($this->_changedFields)) {
					if (!in_array($name, $this->_changedFields)) {
						continue;
					}
				}
				if (strlen($updates) != 0) {
					$updates .= ', ';
				}
				if (in_array($name, $numericColumns)) {
					if ($value === true) {
						$updates .= $name . ' = 1';
					} elseif ($value == false) {
						$updates .= $name . ' = 0';
					} elseif (is_numeric($value)) {
						$updates .= $name . ' = ' . $value;
					} else {
						$updates .= $name . ' = NULL';
					}
				} elseif (in_array($name, $encryptedFields)) {
					$updates .= $name . ' = ' . $aspen_db->quote(EncryptionUtils::encryptField($value));
				} elseif (in_array($name, $serializedFields)) {
					if (!empty($value)) {
						$updates .= $name . ' = ' . $aspen_db->quote(serialize($value));
					} else {
						$updates .= $name . ' = ' . $aspen_db->quote('');
					}
				} elseif (in_array($name, $compressedFields)) {
					if (!empty($value)) {
						$updates .= $name . ' = COMPRESS(' . $aspen_db->quote($value) . ')';
					} else {
						$updates .= $name . ' = ' . $aspen_db->quote('');
					}
				} else {
					$updates .= $name . ' = ' . $aspen_db->quote($value);
				}
			} elseif (is_array($value) && in_array($name, $serializedFields)) {
				if (!empty($value)) {
					$updates .= $name . ' = ' . $aspen_db->quote(serialize($value));
				} else {
					$updates .= $name . ' = ' . $aspen_db->quote('');
				}
			} elseif (is_array($value) && in_array($name, $compressedFields)) {
				if (!empty($value)) {
					$updates .= $name . ' = COMPRESS(' . $aspen_db->quote($value) . ')';
				} else {
					$updates .= $name . ' = ' . $aspen_db->quote('');
				}
			}
		}
		if (empty($updates)) {
			return true;
		}
		$updateQuery .= ' SET ' . $updates . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
		$this->__lastQuery = $updateQuery;
		try {
			$response = $aspen_db->exec($updateQuery);
		} catch (PDOException $e) {
			$this->setLastError("Error updating " . get_class($this) . "<br/>\n" . $e->getMessage() . "<br/>\n" . $e->getTraceAsString());
			$response = false;
		}
		if ($response === false) {
			$errorInfo = $aspen_db->errorInfo();
			$this->setLastError("Error updating " . get_class($this) . "<br/>\n" . implode('<br/>', $errorInfo) . "<br/>");
		}
		global $timer;
		if (IPAddress::logAllQueries()) {
			global $logger;
			$logger->log($updateQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($updateQuery);
		return $response;
	}

	public function get($columnName, $value = null): bool {
		if ($value == null) {
			$value = $columnName;
			$columnName = $this->__primaryKey;
		}
		$this->$columnName = $value;
		return $this->find(true);
	}

	public function delete($useWhere = false) : int {
		global $aspen_db;
		if (!isset($aspen_db)) {
			return false;
		}
		//TODO: Check to see if we need to do any cascading deletes


		$primaryKey = $this->__primaryKey;

		if ($useWhere) {
			/** @noinspection SqlWithoutWhere */
			$deleteQuery = 'DELETE from ' . $this->__table . $this->getWhereClause($aspen_db);
		} else {
			if (empty($this->$primaryKey)) {
				AspenError::raiseError("Called Object Delete, but the primary key was not supplied.");
				return  false;
			} else {
				$deleteQuery = 'DELETE from ' . $this->__table . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
			}
		}
		$this->__lastQuery = $deleteQuery;

		$result = $aspen_db->exec($deleteQuery);
		global $timer;
		if (IPAddress::logAllQueries()) {
			global $logger;
			$logger->log($deleteQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($deleteQuery);
		if ($result && !$useWhere) {
			if ($this->objectHistoryEnabled()) {
				require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
				$history = new DataObjectHistory();
				$history->objectType = get_class($this);
				$history->objectId = $this->{$this->__primaryKey};
				$history->propertyName = '';
				$history->actionType = 3;
				$history->changedBy = UserAccount::getActiveUserId();
				$history->changeDate = time();
				$history->insert();
			}
		}
		return $result;
	}

	public function deleteAll() {
		global $aspen_db;
		if (!isset($aspen_db)) {
			return false;
		}
		$deleteQuery = 'TRUNCATE TABLE ' . $this->__table;
		$this->__lastQuery = $deleteQuery;
		$result = $aspen_db->exec($deleteQuery);
		global $timer;
		if (IPAddress::logAllQueries()) {
			global $logger;
			$logger->log($deleteQuery, Logger::LOG_ERROR);
		}
		$timer->logTime($deleteQuery);
		return $result;
	}

	public function limit($start, $count) {
		$this->__limitStart = $start;
		$this->__limitCount = $count;
	}

	public function count() {
		if (!isset($this->__table)) {
			echo("Table not defined for class " . self::class);
			die();
		}

		global $aspen_db;
		if (!isset($aspen_db)) {
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
			if (IPAddress::logAllQueries()) {
				global $logger;
				$logger->log($query, Logger::LOG_ERROR);
			}
			$timer->logTime($query);
		}

		return 0;
	}

	public function query($query): bool {
		if (!isset($this->__table)) {
			echo("Table not defined for class " . self::class);
			die();
		}

		global $aspen_db;
		if (!isset($aspen_db)) {
			return false;
		}

		$this->__lastQuery = $query;
		$this->__queryStmt = $aspen_db->prepare($query);
		$this->__queryStmt->setFetchMode(PDO::FETCH_INTO, $this);
		if ($this->__queryStmt->execute()) {
			$this->__N = $this->__queryStmt->rowCount();
		} else {
			echo("Failed to execute " . $query);
			$this->__lastError = $this->__queryStmt->errorInfo();
		}
		global $timer;
		if (IPAddress::logAllQueries()) {
			global $logger;
			$logger->log($query, Logger::LOG_ERROR);
		}
		$timer->logTime($query);

		return $this->__N > 0;
	}

	public function escape($variable) : string {
		global $aspen_db;
		return $aspen_db->quote($variable);
	}

	public function selectAdd($condition = null) : void  {
		if ($condition == null) {
			$this->__selectAllColumns = false;
			$this->__additionalSelects = [];
		} else {
			$this->__additionalSelects[] = $condition;
		}
	}

	public function table() {
		global $aspen_db;
		if (!isset($aspen_db)) {
			return false;
		}
		//Get the columns defined in this table
		$query = 'SHOW COLUMNS FROM ' . $this->__table;
		$results = $aspen_db->query($query, PDO::FETCH_ASSOC);
		$columns = [];
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

	public function getLastError() {
		return $this->__lastError;
	}

	public function joinAdd(DataObject $objectToJoin, string $joinType, string $alias, string $mainTableField, string $joinedTableField): void {
		$this->__joins[] = [
			'object' => $objectToJoin,
			'joinType' => $joinType,
			'alias' => $alias,
			'mainTableField' => $mainTableField,
			'joinedTableField' => $joinedTableField,
		];
	}

	private function getJoinQuery($join): string {
		global $aspen_db;
		/** @var DataObject $joinObject */
		$joinObject = $join['object'];
		$subQuery = $joinObject->getSelectQuery($aspen_db);

		$mainTableField = $join['mainTableField'];
		if (strpos($mainTableField, '.') === false) {
			//Add the appropriate table name unless the field name already contains a table (signified by having a dot in the name)
			$mainTableField = "$this->__table.$mainTableField";
		}
		$joinedTableField = $join['joinedTableField'];
		if (strpos($joinedTableField, '.') === false) {
			//Add the appropriate table name unless the field name already contains a table (signified by having a dot in the name)
			$joinedTableField = "{$join['alias']}.$joinedTableField";
		}
		return " {$join['joinType']}  JOIN ($subQuery) AS {$join['alias']} ON $mainTableField = $joinedTableField ";
	}

	/**
	 * @param PDO $aspen_db
	 * @return string
	 */
	public function getSelectQuery(PDO $aspen_db): string {
		$properties = get_object_vars($this);
		$compressedFields = $this->getCompressedColumnNames();
		if (count($compressedFields) == 0) {
			$selectClause = $this->__table . '.*';
		} else {
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
			$selectClause = implode(',', $this->__additionalSelects);
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
				if (count($this->__joins) > 0) {
					$where .= $this->__table . '.' . $name . ' = ' . $aspen_db->quote($value);
				} else {
					if (in_array($name, $this->getNumericColumnNames()) && is_numeric($value)) {
						$where .= $name . ' = ' . $value;
					} else {
						$where .= $name . ' = ' . $aspen_db->quote($value);
					}
				}

			}
		}

		if (!empty($this->__where) && !empty($where)) {
			$query .= ' WHERE ' . $this->__where . ' AND ' . $where;
		} elseif (!empty($this->__where)) {
			$query .= ' WHERE ' . $this->__where;
		} elseif (!empty($where)) {
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
		} elseif (isset($this->__limitCount)) {
			$query .= ' LIMIT ' . $this->__limitCount;
		} elseif (isset($this->__limitStart)) {
			//This really shouldn't happen
			$query .= ' OFFSET ' . $this->__limitCount;
		}
		return $query;
	}

	/**
	 * @param PDO $aspen_db
	 * @return string
	 */
	private function getWhereClause(PDO $aspen_db): string {
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
		if (!empty($this->__where) && !empty($where)) {
			$where = ' WHERE ' . $this->__where . ' AND ' . $where;
		} elseif (!empty($this->__where)) {
			$where = ' WHERE ' . $this->__where;
		} elseif (strlen($where) > 0) {
			$where = ' WHERE ' . $where;
		}
		return $where;
	}

	public function __sleep() {
		$propertiesToSerialize = [];
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($value != null && strpos($name, '__') === false && $name != 'N') {
				$propertiesToSerialize[] = $name;
			}
		}
		return $propertiesToSerialize;
	}

	public function __destruct() {
		if ($this->__queryStmt) {
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

	/**
	 * Saves "one to many" options that optionally take into account the values that an administrator has the ability to change.
	 * This ensures that options that a user does not have access to are not cleared or saved incorrectly.
	 *
	 * @param $newValues [] An array of data objects to be updated
	 * @param $keyOther [] A string that identifies this property used to link this object to the other object. I.e., if saving a Grouped Work Display Setting, the key in the Library table is groupedWorkSettingId
	 * @param $allowableValues ?array An array of values that the current user can administrator (can be null if they can administer all, or if the list is not limited by permissions)
	 * @param $oneToManyDBObjectClassName ?string The name of the object being linked to. Only needed if allowable values is supplied.
	 * @return void
	 */
	protected function saveOneToManyOptions(array $newValues, string $keyOther, ?array $allowableValues = null, ?string $oneToManyDBObjectClassName = null) : void {
		if ($allowableValues == null) {
			/** @var DataObject $oneToManyDBObject */
			foreach ($newValues as $oneToManyDBObject) {
				if ($oneToManyDBObject->_deleteOnSave) {
					if ($oneToManyDBObject->getPrimaryKeyValue() > 0) {
						$oneToManyDBObject->delete();
					}
				} else {
					if (isset($oneToManyDBObject->{$oneToManyDBObject->__primaryKey}) && is_numeric($oneToManyDBObject->{$oneToManyDBObject->__primaryKey})) { // (negative ids need processed with insert)
						if ($oneToManyDBObject->{$oneToManyDBObject->__primaryKey} <= 0) {
							$oneToManyDBObject->$keyOther = $this->{$this->__primaryKey};
							$oneToManyDBObject->insert();
						} else {
							if ($oneToManyDBObject->hasChanges()) {
								$oneToManyDBObject->update();
							}
						}
					} else {
						$oneToManyDBObject->$keyOther = $this->{$this->__primaryKey};
						$oneToManyDBObject->insert();
					}
				}
			}
		} else {
			foreach ($allowableValues as $id => $value) {
				$oneToManyDBObject = new $oneToManyDBObjectClassName();
				$oneToManyDBObject->{$oneToManyDBObject->getPrimaryKey()} = $id;
				if ($oneToManyDBObject->find(true)) {
					if (in_array($id, $newValues)) {
						//We want to apply the scope to this library
						if ($oneToManyDBObject->$keyOther != $this->getPrimaryKeyValue()) {
							$oneToManyDBObject->$keyOther = $this->getPrimaryKeyValue();
							$oneToManyDBObject->update();
						}
					} else {
						//It should not be applied to this scope. Only change if it was applied to the scope
						if ($oneToManyDBObject->$keyOther == $this->getPrimaryKeyValue()) {
							$oneToManyDBObject->$keyOther = -1;
							$oneToManyDBObject->update();
						}
					}
				}
			}
		}
	}

	/**
	 * Clear all options for the given object. If Allowable Values are passed, only entries within the list of allowable values will be cleared.
	 *
	 * @param $oneToManyDBObjectClassName : string
	 * @param $keyOther : string
	 * @param $allowableValues [] - An array of allowable values based on current permissions with the key of the other object as the key of the array.
	 * @return void
	 */
	protected function clearOneToManyOptions(string $oneToManyDBObjectClassName, string $keyOther, ?array $allowableValues = null) : void {
		if ($allowableValues == null) {
			/** @var DataObject $oneToManyDBObject */
			$oneToManyDBObject = new $oneToManyDBObjectClassName();
			$oneToManyDBObject->$keyOther = $this->{$this->__primaryKey};
			$oneToManyDBObject->delete(true);
		}else{
			foreach ($allowableValues as $valueId => $value) {
				/** @var DataObject $oneToManyDBObject */
				$oneToManyDBObject = new $oneToManyDBObjectClassName();
				$oneToManyDBObject->{$oneToManyDBObject->__primaryKey} = $valueId;
				$oneToManyDBObject->$keyOther = $this->{$this->__primaryKey};
				$oneToManyDBObject->delete(true);
			}
		}
	}

	public function __clone() {
		$className = get_class($this);
		$clone = new $className;
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if (!is_null($value) && $name[0] == '_' && $name != '__queryStmt') {
				$clone->$name = $value;
			}
		}
		$clone->clearRuntimeDataVariables();
		return $clone;
	}

	protected function clearRuntimeDataVariables() {
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($name[0] == '_' && strlen($name) > 1 && $name[1] != '_') {
				if ($name == '_data') {
					$this->_data = [];
				} else {
					$this->$name = null;
				}
			}
		}
	}

	/**
	 * @return integer
	 */
	public function getNumResults(): int {
		return $this->__N;
	}

	public function copy(array $propertiesToChange, $saveCopy): DataObject {
		$newObject = clone $this;
		$newObject->__primaryKey = null;
		foreach ($propertiesToChange as $name => $value) {
			$propertiesToChange->$name = $value;
		}
		if ($saveCopy) {
			$newObject->insert();
		}
		return $newObject;
	}

	public function isEqualTo(DataObject $other): bool {
		$properties = get_object_vars($this);
		$equal = true;
		foreach ($properties as $name => $value) {
			if ($name[0] != '_') {
				if ($other->$name != $value) {
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
	public function setProperty(string $propertyName, $newValue, ?array $propertyStructure): bool {
		$propertyChanged = $this->$propertyName != $newValue || (is_null($this->$propertyName) && !is_null($newValue));
		if ($propertyChanged) {
			$this->_changedFields[] = $propertyName;
			$oldValue = $this->$propertyName;
			if ($propertyStructure != null && $propertyStructure['type'] == 'checkbox') {
				if ($newValue == 'off' || $newValue == false) {
					$newValue = 0;
				} elseif ($newValue == 'on' || $newValue == true) {
					$newValue = 1;
				}
			}
			$this->$propertyName = $newValue;
			$this->handlePropertyChangeEffects($propertyName, $oldValue, $newValue, $propertyStructure, 'changed');
			//Add the change to the history unless tracking the history is off (passwords)
			if ($propertyStructure != null && $propertyStructure['type'] != 'password' && $propertyStructure['type'] != 'storedPassword') {
				if ($this->objectHistoryEnabled()) {
					require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
					$history = new DataObjectHistory();
					$history->objectType = get_class($this);
					$primaryKey = $this->__primaryKey;
					if (!empty($this->$primaryKey)) {
						if (is_array($oldValue)) {
							$oldValue = implode(',', $oldValue);
						}
						if (is_array($newValue)) {
							$newValue = implode(',', $newValue);
						}
						if (strlen($oldValue) >= 65535) {
							$oldValue = 'Too long to track history';
						}
						if (strlen($newValue) >= 65535) {
							$newValue = 'Too long to track history';
						}
						$history->actionType = 2;
						$history->objectId = $this->$primaryKey;
						$history->oldValue = $oldValue;
						$history->propertyName = $propertyName;
						$history->newValue = $newValue;
						$history->changedBy = UserAccount::getActiveUserId();
						$history->changeDate = time();
						$history->insert();
					}
				}
			}

			return true;
		} else {
			return false;
		}
	}

	protected function decryptFields() {
		$encryptedFields = $this->getEncryptedFieldNames();
		foreach ($encryptedFields as $fieldName) {
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

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = [];
		$encryptedFields = $this->getEncryptedFieldNames();
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			if ($name[0] != '_') {
				if ($encryptFields && in_array($name, $encryptedFields)) {
					$return[$name] = EncryptionUtils::encryptField($value);
				} else {
					$return[$name] = $value;
				}
			} elseif ($includeRuntimeProperties && $name[0] == '_' && strlen($name) > 1 && $name[1] != '_') {
				if ($name != '_data' && $name != '_changedFields' && $name != '_deleteOnSave' && !is_object($value)) {
					$return[substr($name, 1)] = $value;
				}
			}
		}
		return $return;
	}

	public function getLinksForJSON(): array {
		return [];
	}

	public function canActiveUserChangeSelection() {
		return $this->canActiveUserEdit();
	}

	public function canActiveUserEdit() : bool {
		return true;
	}

	public function canActiveUserDelete() {
		return $this->canActiveUserEdit();
	}

	public function canActiveUserCopy() {
		return true;
	}

	public function getJSONString($includeLinks, $prettyPrint = false) {
		$flags = 0;
		if ($prettyPrint) {
			$flags = JSON_PRETTY_PRINT;
		}

		$baseObject = $this->toArray(false, true);
		if ($includeLinks) {
			$links = $this->getLinksForJSON();
			if (!empty($links)) {
				$baseObject['links'] = $links;
			}
		}
		return json_encode($baseObject, $flags);
	}

	public function loadObjectPropertiesFromJSON($jsonData, $mappings) {
		$encryptedFields = $this->getEncryptedFieldNames();
		$uniquenessFields = $this->getUniquenessFields();
		$sourceEncryptionKey = isset($mappings['passkey']) ? $mappings['passkey'] : '';
		foreach ($jsonData as $property => $value) {
			$okToLoad = true;
			if ($property == 'links') {
				$okToLoad = false;
			}else if ($property == $this->getPrimaryKey()) {
				if (!in_array($property, $uniquenessFields)){
					$okToLoad = false;
				}
			}
			if ($okToLoad) {
				if (in_array($property, $encryptedFields) && !empty($sourceEncryptionKey)) {
					$value = EncryptionUtils::decryptFieldWithProvidedKey($value, $sourceEncryptionKey);
				}
				$this->$property = $value;
			}
		}
	}

	/**
	 * @param $jsonData
	 * @param $mappings
	 * @param $overrideExisting
	 * @return bool
	 */
	public function loadFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting'): bool {
		$this->loadObjectPropertiesFromJSON($jsonData, $mappings);

		if (array_key_exists('links', $jsonData)) {
			$this->loadEmbeddedLinksFromJSON($jsonData['links'], $mappings, $overrideExisting);
		}

		//Check to see if there is an existing ID for the object
		if (!$this->findExistingObjectId()) {
			$this->setLastError('Could not insert object ' . $this . ' could not find existing object id');
		}

		if ($overrideExisting == 'keepExisting') {
			//Only update if we don't have an existing value
			if (empty($this->getPrimaryKeyValue())) {
				$result = $this->update();
				if ($result === false) {
					return false;
				}
			}
		} else if ($overrideExisting != 'doNotSave') {
			$result = $this->update();
			if ($result === false) {
				return false;
			}
		}

		//Load any links (do after loading the existing object id to handle nested objects)
		if (array_key_exists('links', $jsonData)) {
			if ($this->loadRelatedLinksFromJSON($jsonData['links'], $mappings, $overrideExisting)) {
				$result = $this->update();
				if ($result === false) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Load embedded links from json (objects where we directly store the id of the object in this object)
	 * @param $jsonData
	 * @param $mappings
	 * @param $overrideExisting - keepExisting / updateExisting
	 * @return void
	 */
	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {}

	/**
	 * Load related links from json (objects where we there is an intermediary table storing our id and infromation about the other object)
	 * @param $jsonData
	 * @param $mappings
	 * @param $overrideExisting - keepExisting / updateExisting
	 * @return boolean True/False if links were loaded
	 */
	public function loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting'): bool {
		return false;
	}

	/**
	 * Looks for an existing id for the object.  If the primary key is the unique field, it will insert the object
	 *  to avoid errors in future processing.  Returns false if the insert fails.
	 * @return bool
	 */
	public function findExistingObjectId(): bool {
		$thisClass = get_class($this);
		$tmpObject = new $thisClass();
		$uniquenessFields = $this->getUniquenessFields();
		if (!empty($uniquenessFields)) {
			foreach ($uniquenessFields as $fieldName) {
				$tmpObject->$fieldName = $this->$fieldName;
			}
			if ($tmpObject->find(true)) {
				$primaryField = $this->getPrimaryKey();
				$this->$primaryField = $tmpObject->getPrimaryKeyValue();
			} else {
				$primaryField = $this->getPrimaryKey();
				if (count($uniquenessFields) == 1 && $uniquenessFields[0] == $primaryField) {
					//We tricked Aspen, we are filling out the primary key, but it doesn't actually exist.
					if (!$this->insert()) {
						return false;
					}
				}
			}
		}
		return true;
	}

	public function okToExport(array $selectedFilters): bool {
		return false;
	}

	public function getAdditionalListActions(): array {
		return [];
	}

	public function getAdditionalListJavascriptActions(): array {
		return [];
	}

	public function prepareForSharingToCommunity() {
		$this->unsetUniquenessFields();
	}

	/**
	 * Modify the structure of the object based on the object currently being edited.
	 * This can be used to change enums or other values based on the object being edited, so we know relationships
	 *
	 * @param $structure
	 * @return array
	 */
	public function updateStructureForEditingObject($structure) : array {
		return $structure;
	}

	function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name ?? null;
		}else {
			return $this->_data[$name] ?? null;
		}
	}

	function __set($name, $value) {
		if (property_exists($this, $name)) {
			if ($this->__fetchingFromDB) {
				$this->$name = $value;
			} else {
				$propertyChanged = $this->$name != $value || (is_null($this->$name) && !is_null($value));
				if ($propertyChanged) {
					$this->_changedFields[] = $name;
					$this->$name = $value;
				}
			}
		}else {
			$this->_data[$name] = $value;
		}
	}

	function hasChanges() : bool {
		return !empty($this->_changedFields);
	}

	public function jsonSerialize() : mixed {
		$properties = get_object_vars($this);
		$serializedData = [];
		foreach ($properties as $name => $value) {
			if ($name[0] != '_' && $name[0] != 'N') {
				$serializedData[$name] = $value;
			}
		}
		return $serializedData;
	}

	public function finishCopy($sourceId) {

	}

	public function getTextBlockTranslation($fieldName, $languageCode, $returnDefault = true) : string {
		$key = "{$fieldName}_{$languageCode}_$returnDefault";
		if (!empty($this->_data[$key])){
			return $this->_data[$key];
		}
		$loadDefault = $returnDefault;
		if ($languageCode == 'default') {
			$loadDefault = true;
		}else {
			if (!empty($this->getPrimaryKeyValue())) {
				global $validLanguages;
				$languageId = 1;
				foreach ($validLanguages as $language) {
					if ($language->code == $languageCode) {
						$languageId = $language->id;
						break;
					}
				}
				require_once ROOT_DIR . '/sys/Administration/TextBlockTranslation.php';
				$textBlockTranslation = new TextBlockTranslation();
				$textBlockTranslation->objectType = get_class($this);
				$textBlockTranslation->objectId = $this->getPrimaryKeyValue();
				$textBlockTranslation->languageId = $languageId;
				$textBlockTranslation->fieldName = $fieldName;
				if ($textBlockTranslation->find(true)) {
					$this->_data[$key] = $textBlockTranslation->translation;
					return $this->_data[$key];
				}
			}
		}
		if ($loadDefault) {
			$objectStructure = $this::getObjectStructure();
			$fieldDefinition = $this->getFieldDefinition($fieldName, $objectStructure);
			if ($fieldDefinition === false) {
				$this->_data[$key] = '';
			} else {
				$defaultFile = $fieldDefinition['defaultTextFile'];
				if (empty($defaultFile) || !file_exists(ROOT_DIR . '/default_translatable_text_fields/' . $defaultFile)) {
					$this->_data[$key] = '';
				}else {
					require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
					$parsedown = AspenParsedown::instance();
					$this->_data[$key] = $parsedown->parse(file_get_contents(ROOT_DIR . '/default_translatable_text_fields/' . $defaultFile));
				}
			}
		}else{
			$this->_data[$key] = '';
		}
		return $this->_data[$key];
	}

	public function saveTextBlockTranslations($fieldName) : void {
		global $validLanguages;

		require_once ROOT_DIR . '/sys/Administration/TextBlockTranslation.php';
		/** @var TextBlockTranslation[] $existingTranslations */
		$existingTranslations = [];
		$textBlockTranslation = new TextBlockTranslation();
		$textBlockTranslation->objectType = get_class($this);
		$textBlockTranslation->objectId = $this->getPrimaryKeyValue();
		$textBlockTranslation->fieldName = $fieldName;
		$textBlockTranslation->find();
		while ($textBlockTranslation->fetch()) {
			$existingTranslations[$textBlockTranslation->languageId] = clone($textBlockTranslation);
		}

		/** @var Language $language */
		$privateFieldName = '_' . $fieldName;
		$fieldValues = $this->$privateFieldName;
		if ($fieldValues !== null) {
			foreach ($validLanguages as $language) {
				if ($language->code != 'ubb' && $language->code != 'pig') {
					$translationForLanguage = $fieldValues[$language->code];
					//Check to see if we have an existing translation
					if (array_key_exists($language->id, $existingTranslations)) {
						if (empty($translationForLanguage)) {
							//If we get a blank value, we should delete the existing translation
							$existingTranslations[$language->id]->delete();
						}else{
							//Update the existing value
							$existingTranslations[$language->id]->translation = $translationForLanguage;
							$existingTranslations[$language->id]->update();
						}
					}else{
						//New translation, only save if it isn't blank
						if (!empty($translationForLanguage)) {
							$textBlockTranslation = new TextBlockTranslation();
							$textBlockTranslation->objectType = get_class($this);
							$textBlockTranslation->objectId = $this->getPrimaryKeyValue();
							$textBlockTranslation->fieldName = $fieldName;
							$textBlockTranslation->languageId = $language->id;
							$textBlockTranslation->translation = $translationForLanguage;
							$textBlockTranslation->insert();
						}
					}
				}
			}
		}
	}

	public function getFieldDefinition($fieldName, $objectStructure) {
		foreach ($objectStructure as $field) {
			if ($field['property'] == $fieldName) {
				return $field;
			}elseif ($field['type'] == 'section') {
				$subFieldDefinition = $this->getFieldDefinition($fieldName, $field['properties']);
				if ($subFieldDefinition != false) {
					return $subFieldDefinition;
				}
			}
		}
		return false;
	}

	/**
	 * Check to see if this object should not be deleted because it will cause inconsistencies in other objects.
	 * I.e. a browse category group should not be deleted
	 *
	 * @param array $structure - The Object structure of this object
	 * @return array
	 */
	public function getDeletionBlockInformation(array $structure) : array {
		$objectLinkingInfo = [
			'preventDeletion' => false,
			'message' => ''
		];

		$linkedObjectStructure = $this->getLinkedObjectStructure();
		foreach ($linkedObjectStructure as $objectInfo) {
			require_once $objectInfo['class'];
			/** @var DataObject $object */
			$object = new $objectInfo['object'];
			$linkingProperty = $objectInfo['linkingProperty'];
			$object->$linkingProperty = $this->getPrimaryKeyValue();
			$numLinkedObjects = $object->count();
			if ($numLinkedObjects > 0) {
				$objectLinkingInfo['preventDeletion'] = true;
				if (!empty($objectLinkingInfo['message'])) {
					$objectLinkingInfo['message'] .= '<br/>';
				}
				$objectLinkingInfo['message'] .= translate(['text' => $this . ' is linked to %1% %2% that should be unlinked or deleted before deleting this.', 'isAdminFacing'=>true, 1=>$numLinkedObjects, 2=>($numLinkedObjects == 1 ? $objectInfo['objectName'] : $objectInfo['objectNamePlural'])]);
			}
		}

		return $objectLinkingInfo;
	}

	public function getLinkedObjectStructure() : array {
		return [];
	}

	/**
	 * Handle various side effects that may need to occur when a property changes.
	 *
	 * @param string $propertyName The name of the property that was changed.
	 * @param mixed $oldValue The previous value of the property.
	 * @param mixed $newValue The new value of the property.
	 * @param ?array $propertyStructure The property structure containing metadata.
	 * @param string $operation The operation being performed (changed, deleted, etc.).
	 * @param ?string $additionalInfo Optional additional context information.
	 * @return void
	 */
	public function handlePropertyChangeEffects(string $propertyName, mixed $oldValue, mixed $newValue, ?array $propertyStructure, string $operation, ?string $additionalInfo = null): void {
		if ($propertyStructure != null) {
			if (!empty($propertyStructure['forcesReindex'])) {
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				global $logger;
				SystemVariables::forceNightlyIndex();

				$objectType = get_class($this);
				$objectId = $this->getPrimaryKeyValue() ?: 'new';
				$safeOldValue = is_array($oldValue) ? 'array' : (is_object($oldValue) ? 'object' : ($oldValue ?? 'empty'));
				$safeNewValue = is_array($newValue) ? 'array' : (is_object($newValue) ? 'object' : ($newValue ?? 'empty'));
				$userId = UserAccount::getActiveUserId() ?: 'system';
				$additionalContext = $additionalInfo ? " ($additionalInfo)" : '';

				$logger->log("Forcing nightly index because $propertyName on $objectType ($objectId) was $operation from '$safeOldValue' to '$safeNewValue' by user $userId$additionalContext.", Logger::LOG_ALERT);
			}
			if (!empty($propertyStructure['forcesRegroup'])) {
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				global $logger;
				SystemVariables::forceRegrouping();

				$objectType = get_class($this);
				$objectId = $this->getPrimaryKeyValue() ?: 'new';
				$safeOldValue = is_array($oldValue) ? 'array' : (is_object($oldValue) ? 'object' : ($oldValue ?? 'empty'));
				$safeNewValue = is_array($newValue) ? 'array' : (is_object($newValue) ? 'object' : ($newValue ?? 'empty'));
				$userId = UserAccount::getActiveUserId() ?: 'system';
				$additionalContext = $additionalInfo ? " ($additionalInfo)" : '';

				$logger->log("Forcing regrouping because $propertyName on $objectType ($objectId) was $operation from '$safeOldValue' to '$safeNewValue' by user $userId$additionalContext.", Logger::LOG_ALERT);
			}
		}
	}
}
