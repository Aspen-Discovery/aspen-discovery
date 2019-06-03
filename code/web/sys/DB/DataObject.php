<?php

abstract class DataObject
{
    public $__table;
    public $__primaryKey = 'id';
    public $N;
    /** @var PDOStatement */
    private $__queryStmt;
    private $__selectAllColumns = true;
    private $__additionalSelects = [];
    private $__orderBy;
    private $__groupBy;
    private $__where;
    private $__limitStart;
    private $__limitCount;
    private $__lastQuery;
    private $__lastError;
    private $__joins = [];

    function getNumericColumnNames(){
        return [];
    }

    public function find($fetchFirst = false){
        if (!isset($this->__table)) {
            echo("Table not defined for class " . self::class);
            die();
        }

        $this->N = 0;
        $this->__queryStmt = null;
        /** @var PDO $aspen_db  */
        global $aspen_db;
        $query = $this->getSelectQuery($aspen_db);
        $this->__lastQuery = $query;
        $this->__queryStmt = $aspen_db->prepare($query);
        $this->__queryStmt->setFetchMode(PDO::FETCH_INTO, $this);
        if ($this->__queryStmt->execute()){
            $this->N = $this->__queryStmt->rowCount();
            if ($this->N != 0 && $fetchFirst) {
                $this->fetch();
            }
        } else {
            echo("Failed to execute " . $query);
        }

        return $this->N > 0;
    }

    public function fetch(){
        $return = $this->__queryStmt->fetch(PDO::FETCH_INTO);
        return $return;
    }

    /**
     * Retrieves all objects for the current query if name and value are null
     * Retrieves a list of all field values if only fieldName is provided
     * Retrieves an associated array if both fieldName and fieldValue are provided
     * @param null $fieldName
     * @param null $fieldValue
     * @return array
     */
    public function fetchAll($fieldName = null, $fieldValue = null){
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

        return $results;
    }

    public function orderBy($fieldsToOrder){
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

    public function groupBy($fieldsToGroup){
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

    public function whereAdd($cond = false, $logic = 'AND'){
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
        /** @var PDO $aspen_db */
        global $aspen_db;
        $insertQuery = 'INSERT INTO ' . $this->__table;

        $numericColumns = $this->getNumericColumnNames();

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
                    if ($value === true){
                        $propertyValues .= 1;
                    }else if ($value == false) {
                        $propertyValues .= 0;
                    } elseif (is_numeric($value)) {
                        $propertyValues .= $value;
                    } else {
                        $propertyValues .= 'NULL';
                    }
                } else {
                    $propertyValues .= $aspen_db->quote($value);
                }
            }
        }
        $insertQuery .= '(' . $propertyNames . ') VALUES (' . $propertyValues . ');';
        $response = $aspen_db->prepare($insertQuery)->execute();
        $this->{$this->__primaryKey} = $aspen_db->lastInsertId();
        return $response;
    }

    public function update(){
        /** @var PDO $aspen_db */
        global $aspen_db;
        $updateQuery = 'UPDATE ' . $this->__table;

        $numericColumns = $this->getNumericColumnNames();

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
                } else {
                    $updates .= $name . ' = ' . $aspen_db->quote($value);
                }
            }
        }
        $primaryKey = $this->__primaryKey;
        $updateQuery .= ' SET ' . $updates . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
        $this->__lastQuery = $updateQuery;
        $response = $aspen_db->prepare($updateQuery)->execute();
        return $response;
    }

    public function get($columnName, $value = null){
        if ($value == null) {
            $value = $columnName;
            $columnName = $this->__primaryKey;
        }
        $this->$columnName = $value;
        return $this->find(true);
    }

    public function delete($useWhere = false){
        /** @var PDO $aspen_db */
        global $aspen_db;
        $primaryKey = $this->__primaryKey;

        if ($useWhere){
	        /** @noinspection SqlWithoutWhere */
	        $deleteQuery = 'DELETE from ' . $this->__table . $this->getWhereClause($aspen_db);
        }else{
	        $deleteQuery = 'DELETE from ' . $this->__table . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
        }

        $response = $aspen_db->prepare($deleteQuery)->execute();
        return $response;
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

        /** @var PDO $aspen_db  */
        global $aspen_db;
        $query = 'SELECT COUNT(*) from ' . $this->__table;

	    $query .= $this->getWhereClause($aspen_db);
        $this->__lastQuery = $query;
        $this->__queryStmt = $aspen_db->prepare($query);
        if ($this->__queryStmt->execute()){
            if ($this->__queryStmt->rowCount()) {
                $data = $this->__queryStmt->fetch();
                return $data[0];
            }
        } else {
            echo("Failed to execute " . $query);
        }

        return 0;
    }

    public function query($query){
        if (!isset($this->__table)) {
            echo("Table not defined for class " . self::class);
            die();
        }

        /** @var PDO $aspen_db  */
        global $aspen_db;

        $this->__lastQuery = $query;
        $this->__queryStmt = $aspen_db->prepare($query);
        $this->__queryStmt->setFetchMode(PDO::FETCH_INTO, $this);
        if ($this->__queryStmt->execute()){
            $this->N = $this->__queryStmt->rowCount();
        } else {
            echo("Failed to execute " . $query);
            $this->__lastError = $this->__queryStmt->errorInfo();
        }

        return $this->N > 0;
    }

    public function escape($variable){
        /** @var PDO $aspen_db  */
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
        /** @var PDO $aspen_db  */
        global $aspen_db;
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

        return " {$join['joinType']}  JOIN ({$subQuery}) AS {$join['alias']} ON {$this->__table}.{$join['mainTableField']} = {$join['alias']}.{$join['joinedTableField']} ";
    }

    /**
     * @param PDO $aspen_db
     * @return string
     */
    public function getSelectQuery(PDO $aspen_db): string
    {
        $selectClause = '*';
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

        $properties = get_object_vars($this);
        $where = '';
        foreach ($properties as $name => $value) {
            if ($value != null && $name[0] != '_') {
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
			if ($value != null && $name[0] != '_') {
				$propertiesToSerialize[] = $name;
			}
		}
		return $propertiesToSerialize;
	}
}