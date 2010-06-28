<?php

abstract class Object {
	protected $id = NULL;
	protected $created = NULL;
	protected $updated = NULL;
    protected $table = NULL;
    protected $table_name = NULL;
	
	protected $values = array();
	protected $externals = array();

    private $pk = "id";
	
	public function __set($var, $val) {
		if (property_exists($this, $var)) {
			$this->$var = $val;
		} else {
			$this->values[$var] = $val;
		}
	}
	
	public function getTable() {
		if (!isset($this->table)) {
			$table = strtolower(get_class($this));
			$this->table = "app_{$table}";
		}
		return $this->table;
	}

	public function __get($var) {
		if (isset($this->$var)) {
			return $this->$var;
		} else if (isset($this->values[$var])) {
			return $this->values[$var];
		} else if (isset($this->externals[$var])) {
			return $this->externals[$var];
		} else if (isset($this->values[$var."_id"])) {
			$col = $this->getColumnInfo($var."_id");
			$this->externals[$var] = Table::factory($col["table"])->read($this->values[$var."_id"]);
			return $this->externals[$var];
		} else if (substr($var, -1) == "s" && ($table = $this->getHasManyInfo($var))) {
			// one -> many
			$foreign_id = strtolower(get_class($this))."_id";
			$this->externals[$var] = Table::factory($table)->findAll("`{$foreign_id}` = ?", array($this->getId()));
			return $this->externals[$var];
		}
		return null;
	}
	
	public function setValues($values) {
		$this->values = $values;
		return TRUE;
	}
	
	public function getTableName() {
		if (!isset($this->table_name)) {
			$this->table_name = get_class($this)."s";
		}
		return $this->table_name;
	}
	
	public function getColumnInfo($column) {
		$table = Table::factory($this->getTableName());
		return $table->getColumnInfo($column);
	}
	
	public function getHasManyInfo($column) {
		$table = Table::factory($this->getTableName());
		return $table->getHasManyInfo($column);
	}

    public function getId() {
        $pk = $this->pk;
        return $this->$pk;
    }
    
    public function getColumns() {
    	return Table::factory($this->getTableName())->getColumns();
    }
    
    public function save() {
    	$sql = "";
    	$values = array();
    	if ($this->getId()) {
    		$sql = "UPDATE `".$this->getTable()."` SET `updated` = NOW(),";
    		foreach ($this->getColumns() as $key => $val) {
    			if (isset($this->values[$key])) {
	    			$sql .= "`{$key}` = ?,";
	    			$values[] = $this->values[$key];
	    		}
    		}
    		$sql = substr($sql, 0, -1);
    	} else {
    		$sql = "INSERT INTO `".$this->getTable()."` (`created`, `updated`,";
    		$params = "";
    		foreach ($this->getColumns() as $key => $val) {
    			if (isset($this->values[$key])) {
    				$sql .= "`{$key}`,";
    				$params .= "?,";
    				$values[] = $this->values[$key];
    			}
    		}
    		$sql = substr($sql, 0, -1);
    		$params = substr($params, 0, -1);
    		$sql .= ") VALUES (NOW(),NOW(),".$params.")";
    	}

   		$dbh = Db::getInstance();
		$sth = $dbh->prepare($sql);
        $sth->execute($values);
		return $dbh->lastInsertId();
    }
}
