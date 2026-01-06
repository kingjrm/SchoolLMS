<?php
/**
 * Database Connection Class
 * Handles all database operations with prepared statements
 */

require_once __DIR__ . '/config.php';

class Database {
    private $connection;
    private $stmt;

    public function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    /**
     * Prepare a statement
     */
    public function prepare($query) {
        $this->stmt = $this->connection->prepare($query);
        
        if (!$this->stmt) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }
        
        return $this;
    }

    /**
     * Bind parameters
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = MYSQLI_TYPE_LONG;
                    break;
                case is_float($value):
                    $type = MYSQLI_TYPE_DOUBLE;
                    break;
                case is_bool($value):
                    $type = MYSQLI_TYPE_TINY;
                    break;
                default:
                    $type = MYSQLI_TYPE_STRING;
            }
        }
        
        $this->stmt->bind_param($type, $value);
        return $this;
    }

    /**
     * Execute the prepared statement
     */
    public function execute() {
        if (!$this->stmt->execute()) {
            throw new Exception("Execute failed: " . $this->stmt->error);
        }
        return $this;
    }

    /**
     * Get single result
     */
    public function getResult() {
        return $this->stmt->get_result();
    }

    /**
     * Fetch single row as associative array
     */
    public function fetch() {
        return $this->getResult()->fetch_assoc();
    }

    /**
     * Fetch all results as array
     */
    public function fetchAll() {
        return $this->getResult()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get row count
     */
    public function rowCount() {
        return $this->stmt->affected_rows;
    }

    /**
     * Get last insert ID
     */
    public function lastId() {
        return $this->connection->insert_id;
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->stmt) {
            $this->stmt->close();
        }
        $this->connection->close();
    }

    /**
     * Get raw connection for special cases
     */
    public function getConnection() {
        return $this->connection;
    }
}
