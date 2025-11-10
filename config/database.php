<?php
class Database {
    // ⚙️ Replace these with your actual InfinityFree database credentials
    private $host = "sql100.infinityfree.com";   // e.g. sql300.infinityfree.com
    private $db_name = "if0_40353110_emp_portal";   // your database name
    private $username = "if0_40353110";         // your InfinityFree username
    private $password = "0yVKk5fhQCUA";         // your database password
    private $port = "3306";                      // default MySQL port

    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
