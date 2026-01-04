<?php
require_once __DIR__ . '/../config/database.php';

class Costo {
    private $conn;
    private $table_fijos = "costos_fijos";
    private $table_distribucion = "distribucion_ingreso";
    private $table_caja = "caja_bancos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // ========== COSTOS FIJOS ==========
    public function createCostoFijo($data) {
        $query = "INSERT INTO " . $this->table_fijos . " 
                  (usuario_id, anio, mes, categoria, concepto, monto, descripcion) 
                  VALUES (:usuario_id, :anio, :mes, :categoria, :concepto, :monto, :descripcion)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);
        return $stmt->rowCount() > 0;
    }

    public function getCostosFijos($usuario_id, $anio, $mes) {
        $query = "SELECT * FROM " . $this->table_fijos . " 
                  WHERE usuario_id = :usuario_id 
                  AND anio = :anio 
                  AND mes = :mes 
                  ORDER BY categoria, concepto";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTotalCostosFijos($usuario_id, $anio, $mes) {
        $query = "SELECT COALESCE(SUM(monto), 0) as total 
                  FROM " . $this->table_fijos . " 
                  WHERE usuario_id = :usuario_id 
                  AND anio = :anio 
                  AND mes = :mes";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function deleteCostoFijo($id, $usuario_id) {
        $query = "DELETE FROM " . $this->table_fijos . " WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // ========== DISTRIBUCIÓN DE INGRESO ==========
    public function createOrUpdateDistribucion($data) {
        $query = "INSERT INTO " . $this->table_distribucion . " 
                  (usuario_id, anio, mes, ingreso_total, necesidades_porcentaje, deseos_porcentaje, deudas_ahorro_porcentaje) 
                  VALUES (:usuario_id, :anio, :mes, :ingreso_total, :necesidades_porcentaje, :deseos_porcentaje, :deudas_ahorro_porcentaje)
                  ON DUPLICATE KEY UPDATE 
                  ingreso_total = :ingreso_total,
                  necesidades_porcentaje = :necesidades_porcentaje,
                  deseos_porcentaje = :deseos_porcentaje,
                  deudas_ahorro_porcentaje = :deudas_ahorro_porcentaje";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($data);
        return true;
    }

    public function getDistribucion($usuario_id, $anio, $mes) {
        $query = "SELECT * FROM " . $this->table_distribucion . " 
                  WHERE usuario_id = :usuario_id 
                  AND anio = :anio 
                  AND mes = :mes 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // ========== CAJA Y BANCOS ==========
    public function createOrUpdateCajaBanco($data) {
        try {
            $query = "INSERT INTO " . $this->table_caja . " 
                      (usuario_id, anio, mes, banco, saldo) 
                      VALUES (:usuario_id, :anio, :mes, :banco, :saldo)
                      ON DUPLICATE KEY UPDATE 
                      saldo = VALUES(saldo)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($data);
            return true;
        } catch (Exception $e) {
            error_log("Error updating caja banco: " . $e->getMessage());
            return false;
        }
    }

    public function getCajaBancos($usuario_id, $anio, $mes) {
        $query = "SELECT * FROM " . $this->table_caja . " 
                  WHERE usuario_id = :usuario_id 
                  AND anio = :anio 
                  AND mes = :mes 
                  ORDER BY banco";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTotalCaja($usuario_id, $anio, $mes) {
        $query = "SELECT COALESCE(SUM(saldo), 0) as total 
                  FROM " . $this->table_caja . " 
                  WHERE usuario_id = :usuario_id 
                  AND anio = :anio 
                  AND mes = :mes";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    public function hasRecords($usuario_id, $anio, $mes) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_fijos . " 
                  WHERE usuario_id = :usuario_id 
                  AND anio = :anio 
                  AND mes = :mes";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }
}
?>