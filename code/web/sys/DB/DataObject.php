<?php
/**
 * Aspen Discovery Layer
 * Created By: Mark Noble
 * Created On: 1/23/2019
 */

abstract class DataObject
{
    public $__table;
    public $__primaryKey = 'id';
    public $N;
    /** @var PDOStatement */
    private $__queryStmt;
    private $__orderBy;
    private $__where;
    private $__limitStart;
    private $__limitCount;
    private $__lastQuery;

    public function find($fetchFirst = false){
        if (!isset($this->__table)) {
            echo("Table not defined for class " . self::class);
            die();
        }

        $this->N = 0;
        $this->queryStmt = null;
        /** @var PDO $aspen_db  */
        global $aspen_db;
        $query = 'SELECT * from ' . $this->__table;
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
        if (strlen($this->__where) > 0 && strlen($where) > 0){
            $query .= ' WHERE ' . $this->__where . ' AND ' . $where;
        }else if (strlen($this->__where) > 0) {
            $query .= ' WHERE ' . $this->__where;
        }else if (strlen($where) > 0) {
            $query .= ' WHERE ' . $where;
        }
        if ($this->__orderBy != null) {
            $query .= $this->__orderBy;
        }
        if (isset($this->__limitCount) && isset($this->__limitStart)){
            $query .= ' LIMIT ' . $this->__limitStart . ', ' . $this->__limitCount;
        } else if (isset($this->__limitCount)) {
            $query .= ' LIMIT ' . $this->__limitCount;
        } else if (isset($this->__limitStart)) {
            //This really shouldn't happen
            $query .= ' OFFSET ' . $this->__limitCount;
        }
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
        $this->__queryStmt->fetch(PDO::FETCH_INTO);
    }

    public function orderBy($fieldsToOrder){
        if ($fieldsToOrder == null) {
            $this->__orderBy = null;
        }else {
            if (strlen($this->__orderBy) > 0) {
                $this->__orderBy .= ', ' . $fieldsToOrder;
            }else {
                $this->__orderBy .= ' ORDER BY ' . $fieldsToOrder;
            }
        }
    }

    public function whereAdd($cond = false, $logic = 'AND'){
        if ($cond == false) {
            $this->__where = null;
        }else {
            if (strlen($this->__where) > 0) {
                $this->__where .= ' ' . $logic. ' ' . $cond;
            }else {
                $this->__where .= $cond;
            }
        }
    }

    public function insert(){
        /** @var PDO $aspen_db */
        global $aspen_db;
        $insertQuery = 'INSERT INTO ' . $this->__table;

        $properties = get_object_vars($this);
        $propertyNames = '';
        $propertyValues = '';
        foreach ($properties as $name => $value) {
            if ($value != null && $name[0] != '_') {
                if (strlen($propertyNames) != 0) {
                    $propertyNames .= ', ';
                    $propertyValues .= ', ';
                }
                $propertyNames .= $name;
                $propertyValues .= $aspen_db->quote($value);
            }
        }
        $insertQuery .= '(' . $propertyNames . ') VALUES (' . $propertyValues . ');';
        $response = $aspen_db->prepare($insertQuery)->execute();
        return $response;
    }

    public function update(){
        /** @var PDO $aspen_db */
        global $aspen_db;
        $updateQuery = 'UPDATE ' . $this->__table;

        $properties = get_object_vars($this);
        $updates = '';
        foreach ($properties as $name => $value) {
            if ($value != null && $name[0] != '_') {
                if (strlen($updates) != 0) {
                    $updates .= ', ';
                }
                $updates .= $name . ' = ' . $aspen_db->quote($value);
            }
        }
        $primaryKey = $this->__primaryKey;
        $updateQuery .= ' SET ' . $updates . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
        $response = $aspen_db->prepare($updateQuery)->execute();
        return $response;
    }

    public function get($columnName, $value){
        $this->$columnName = $value;
        return $this->find(true);
    }

    public function delete(){
        /** @var PDO $aspen_db */
        global $aspen_db;
        $primaryKey = $this->__primaryKey;
        $deleteQuery = 'DELETE from ' . $this->__table . ' WHERE ' . $primaryKey . ' = ' . $aspen_db->quote($this->$primaryKey);
        $response = $aspen_db->prepare($deleteQuery)->execute();
        return $response;
    }

    public function limit($start, $count){
        $this->__limitStart = $start;
        $this->__limitCount = $count;
    }
}