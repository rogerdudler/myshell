<?php

/**
 * Database.
 */
class Database {
    
    private $database;
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $dbname = 'test';
    private $port;
    private $connected = false;
    private $querycount = 0;
    private $charset = 'utf8';
    private $collation = 'utf8';
    
    /**
     * This method constructs a new database abstraction object with 
     * several methods for creating and using a database connection.
     * 
     * @param string $host Hostname or IP address
     * @param string $username Username
     * @param string $password Password
     * @param string $dbname Name of the database
     * @param int $port default is 3306
     */
    public function __construct($host, $username, $password, $dbname, $port = 3306) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->port = $port;
    }
    
    /**
     * It is not allowed to call connect from outside this class,
     * because of performance optimization issues. If no query will
     * be executed during current request, there is no need for
     * a database connection, so it will not be created if not needed.
     */
    public function connect() {
        if (!$this->connected) {
            $this->database = new mysqli(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->dbname, 
                $this->port
            );
            
            // Check for connection errors
        	if (mysqli_connect_error()) {
			    throw new Exception('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			}
			
			// Change encoding for this connection to UTF-8
            $this->database->query("SET NAMES '" . $this->collation . "'");
            $this->database->query("SET CHARACTER SET '" . $this->charset . "'");
            
            // Disable autocommit
            // $this->database->autocommit(false);
            $this->connected = true;
        }
        return $this->connected;
    }
    
    /**
     * Executes a query against currently connected database and
     * returns the result set if available.
     * 
     * @param string $sql
     * @return mysqli result set
     */
    public function query($sql, $silent = false) {
        $this->connect();
        $this->querycount++;
        $result = $this->database->query($sql);
        if ($this->database->error && !$silent) {
        	throw new Exception($this->database->error . ': ' . $sql);
        }
        return $result;
    }
    
    /**
     * Executes a query against currently connected database and
     * returns the result as associative array with column names as
     * keys.
     * 
     * @param $query
     * @return array
     */
    public function data($query, $keycolumn = null, $iskeycolumnunique = true, $silent = false) {
    	$result = $this->query($query, $silent);
    	if ($result == null) {
    		return null;
    	}
    	$data = array();
    	while($row = $result->fetch_assoc()) {
    		if ($keycolumn != null) {
    			if ($iskeycolumnunique) {
    		        $data[$row[$keycolumn]] = $row;
    			} else {
    				$data[$row[$keycolumn]][] = $row;
    			}
    		} else {
    			$data[] = $row;
    		}
    	}
    	$result->close();
    	return $data;
    }
    
    public function single($query) {
    	$data = $this->data($query);
    	if (sizeof($data) > 0) {
    	   return $data[0];
    	}
    	return null;
    }
    
    public function insert($table, $data) {
    	
    	$query = 'INSERT INTO `' . table($table) . '` (';
    	$columns = array_keys($data);
    	$values = array_values($data);
    	$column_count = 0;
    	while(list($index, $column) = each($columns)) {
    		if ($data[$column] != null) {
	    	    if ($column_count > 0) {
	                $query .= ',';
	            }
	    		$query .= '`' . $column . '`';
	    		$column_count++;
    		}
    	}
    	$query .= ') VALUES (';
    	$value_count = 0;
        while(list($index, $value) = each($values)) {
        	if ($value != null) {
	            if ($value_count > 0) {
	                $query .= ',';
	            }
	            $query .= '\'' . addslashes($value) . '\'';
	            $value_count++;
        	}
        }
        $query .= ')';
        $this->query($query);
        return $this->database->insert_id;
    }
    
    public function lastId() {
    	return $this->database->insert_id;
    }
    
    public function update($table, $data, $condition = array()) {
        
        $query = 'UPDATE `' . table($table) . '` SET ';
        $columns = array_keys($data);
        $values = array_values($data);
        $column_count = 0;
        while(list($column, $value) = each($data)) {
           if ($column_count > 0) {
                $query .= ',';
            }
            if ($value == null) {
                $query .= '`' . $column . '`=NULL';
            } else {
            	$query .= '`' . $column . '`=\'' . addslashes($value) . '\'';
            }
            $column_count++;
        }
        $query .= ' WHERE ';
        $column_count = 0;
        while(list($column, $value) = each($condition)) {
           if ($column_count > 0) {
                $query .= ' AND ';
            }
            $query .= '`' . $column . '`=\'' . addslashes($value) . '\'';
            $column_count++;
        }
        $this->query($query);
    }
    
    function exists($table, $data) {
    	$query = "SELECT 1 FROM `" . table($table) . "` WHERE ";
    	$count = 0;
    	foreach($data as $column => $value) {
    		if ($count > 0) {
    			$query .= ' AND ';
    		}
    		$query .= "`" . $column . "` = '" . $value . "'";
    		$count++;
    	}
    	$query .= " LIMIT 1";
    	$result = $this->data($query);
    	return (sizeof($result) > 0);
    }
    
    function delete($table, $data) {
        $query = "DELETE FROM `" . table($table) . "` WHERE ";
        $count = 0;
        foreach($data as $column => $value) {
            if ($count > 0) {
                $query .= ' AND ';
            }
            $query .= "`" . $column . "` = '" . $value . "'";
            $count++;
        }
        $this->query($query);
    }
    
    public function autocommit($autocommit) {
    	$this->connect();
    	$this->database->autocommit($autocommit);
    }
    
    /**
     * Commits current transaction.
     */
    public function commit() {
        $this->database->commit();
    }
    
    /**
     * Rollbacks current transaction.
     */
    public function rollback() {
        $this->database->rollback();
    }
    
    public function isConnected() {
        return $this->connected;
    }
    
    public function setCharset($charset) {
        $this->charset = $charset;
    }
    
    public function setCollation($collation) {
        $this->collation = $collation;
    }
    
    public function escape($string) {
    	$this->connect();
        return $this->database->real_escape_string($string);
    }
    
    public function getQueryCount() {
    	return $this->querycount;
    }
    
    /**
     * This close method needs to be called by clients after they 
     * finished using the database, so the open connection can be
     * closed properly.
     */
    public function close() {
        if ($this->database != null) {
            $this->commit();
            $this->database->close();
        }
    }
    
}

?>
