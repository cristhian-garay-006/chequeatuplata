<?php
require_once __DIR__ . '/../config/database.php';

class Egreso {
    private $conn;
    private $table = "egresos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, fecha, mes, tipo, descripcion, categoria_id, total) 
                  VALUES (:usuario_id, :fecha, :mes, :tipo, :descripcion, :categoria_id, :total)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function getAll($usuario_id, $anio = null, $mes = null, $dia = null) {
        $query = "SELECT e.*, c.nombre as categoria_nombre, c.tipo as categoria_tipo
                  FROM " . $this->table . " e
                  LEFT JOIN categorias_egreso c ON e.categoria_id = c.id
                  WHERE e.usuario_id = :usuario_id";
        
        if ($anio) {
            $query .= " AND YEAR(e.fecha) = :anio";
        }
        if ($mes) {
            $query .= " AND e.mes = :mes";
        }
        if ($dia) {
            $query .= " AND DAY(e.fecha) = :dia";
        }
        
        $query .= " ORDER BY e.fecha DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        if ($anio) $stmt->bindParam(':anio', $anio);
        if ($mes) $stmt->bindParam(':mes', $mes);
        if ($dia) $stmt->bindParam(':dia', $dia);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByDateRange($usuario_id, $fecha_inicio, $fecha_fin) {
        $query = "SELECT e.*, c.nombre as categoria_nombre, c.tipo as categoria_tipo
                  FROM " . $this->table . " e
                  LEFT JOIN categorias_egreso c ON e.categoria_id = c.id
                  WHERE e.usuario_id = :usuario_id 
                  AND e.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  ORDER BY e.fecha DESC";
        
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
                  SET fecha = :fecha, mes = :mes, tipo = :tipo, 
                      descripcion = :descripcion, categoria_id = :categoria_id, total = :total
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

    public function getByCategory($usuario_id, $anio, $mes = null, $dia = null) {
        $query = "SELECT e.tipo, COALESCE(SUM(e.total), 0) as total
                  FROM " . $this->table . " e
                  WHERE e.usuario_id = :usuario_id 
                  AND YEAR(e.fecha) = :anio";
        
        if ($mes) {
            $query .= " AND e.mes = :mes";
        }

        if ($dia) {
            $query .= " AND DAY(e.fecha) = :dia";
        }
        
        $query .= " GROUP BY e.tipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        
        if ($mes) {
            $stmt->bindParam(':mes', $mes);
        }
        if ($dia) {
            $stmt->bindParam(':dia', $dia);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    public function hasRecords($usuario_id, $anio, $mes, $dia = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
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
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    public function getAllCategorias($usuario_id) {
         // Assuming categories table for expense is 'categorias_egreso'
         $query = "SELECT * FROM categorias_egreso ORDER BY nombre ASC"; 
         $stmt = $this->conn->prepare($query);
         $stmt->execute();
         return $stmt->fetchAll();
    }
}
?>