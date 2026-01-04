<?php
require_once __DIR__ . '/../config/database.php';

class Ingreso {
    private $conn;
    private $table = "ingresos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, fecha, mes, cliente, categoria_id, total, descripcion) 
                  VALUES (:usuario_id, :fecha, :mes, :cliente, :categoria_id, :total, :descripcion)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function getAll($usuario_id, $anio = null, $mes = null, $dia = null) {
        $query = "SELECT i.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " i
                  LEFT JOIN categorias_ingreso c ON i.categoria_id = c.id
                  WHERE i.usuario_id = :usuario_id";
        
        if ($anio) {
            $query .= " AND YEAR(i.fecha) = :anio";
        }
        if ($mes) {
            $query .= " AND i.mes = :mes";
        }
        if ($dia) {
            $query .= " AND DAY(i.fecha) = :dia";
        }
        
        $query .= " ORDER BY i.fecha DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        if ($anio) $stmt->bindParam(':anio', $anio);
        if ($mes) $stmt->bindParam(':mes', $mes);
        if ($dia) $stmt->bindParam(':dia', $dia);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByDateRange($usuario_id, $fecha_inicio, $fecha_fin) {
        $query = "SELECT i.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " i
                  LEFT JOIN categorias_ingreso c ON i.categoria_id = c.id
                  WHERE i.usuario_id = :usuario_id 
                  AND i.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  ORDER BY i.fecha DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id, $usuario_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND usuario_id = :usuario_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update($id, $usuario_id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET fecha = :fecha, mes = :mes, cliente = :cliente, 
                      categoria_id = :categoria_id, total = :total, descripcion = :descripcion
                  WHERE id = :id AND usuario_id = :usuario_id";
        
        $data['id'] = $id;
        $data['usuario_id'] = $usuario_id;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function delete($id, $usuario_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getTotalByPeriod($usuario_id, $anio, $mes, $dia = null) {
        $query = "SELECT COALESCE(SUM(total), 0) as total 
                  FROM " . $this->table . " 
                  WHERE usuario_id = :usuario_id 
                  AND YEAR(fecha) = :anio 
                  AND mes = :mes";
        
        if ($dia) {
            $query .= " AND DAY(fecha) = :dia";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        if ($dia) {
            $stmt->bindParam(':dia', $dia);
        }
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    public function hasRecords($usuario_id, $anio, $mes, $dia = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE usuario_id = :usuario_id 
                  AND YEAR(fecha) = :anio 
                  AND mes = :mes";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    public function getAllCategorias($usuario_id) {
        // Assuming categories table for income is 'categorias_ingreso'
        $query = "SELECT * FROM categorias_ingreso ORDER BY nombre ASC"; 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>