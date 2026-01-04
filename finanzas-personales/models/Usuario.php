<?php
require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $conn;
    private $table = "usuarios";

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $activo;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register() {
        $query = "INSERT INTO " . $this->table . " (nombre, email, password) VALUES (:nombre, :email, :password)";
        $stmt = $this->conn->prepare($query);

        $this->nombre = sanitize($this->nombre);
        $this->email = sanitize($this->email);
        $this->password = password_hash($this->password, HASH_ALGO, ['cost' => HASH_COST]);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, nombre, email, password, activo FROM " . $this->table . " WHERE email = :email AND activo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if (password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    public function getById($id) {
        $query = "SELECT id, nombre, email, password FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $nombre, $email, $password = null) {
        $query = "UPDATE " . $this->table . " SET nombre = :nombre, email = :email";
        
        if ($password) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $nombre = sanitize($nombre);
        $email = sanitize($email);
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id);
        
        if ($password) {
            $hashed_password = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
            $stmt->bindParam(':password', $hashed_password);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>