<?php
require_once __DIR__ . '/../config/database.php';

class CuentaPorCobrar {
    private $conn;
    private $table = "cuentas_por_cobrar";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear nueva cuenta
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, categoria_id, fecha, cliente, deuda_total, monto_cuota, saldo, frecuencia, proximo_pago, estado) 
                  VALUES (:usuario_id, :categoria_id, :fecha, :cliente, :deuda_total, :monto_cuota, :saldo, :frecuencia, :proximo_pago, :estado)";
        
        $stmt = $this->conn->prepare($query);

        // Calculate next payment if not set
        if (empty($data['proximo_pago'])) {
            $data['proximo_pago'] = $this->calculateNextPaymentDate($data['fecha'], $data['frecuencia']);
        }
        
        $stmt->bindParam(':usuario_id', $data['usuario_id']);
        $stmt->bindParam(':categoria_id', $data['categoria_id']);
        $stmt->bindParam(':fecha', $data['fecha']);
        $stmt->bindParam(':cliente', $data['cliente']);
        $stmt->bindParam(':deuda_total', $data['deuda_total']);
        $stmt->bindParam(':monto_cuota', $data['monto_cuota']);
        $stmt->bindParam(':saldo', $data['deuda_total']); // Initially balance = total
        $stmt->bindParam(':frecuencia', $data['frecuencia']);
        $stmt->bindParam(':proximo_pago', $data['proximo_pago']);
        $stmt->bindParam(':estado', $data['estado']);
        
        return $stmt->execute();
    }

    public function getAll($usuario_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE usuario_id = :usuario_id ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
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
    
    // Registrar pago (similar a Deudas)
    public function registerPayment($id, $usuario_id, $monto_pagado) {
        $cuenta = $this->getById($id, $usuario_id);
        if (!$cuenta) return false;

        $nuevo_saldo = $cuenta['saldo'] - $monto_pagado;
        $estado = $nuevo_saldo <= 0 ? 'Pagado' : 'Pendiente';
        if ($nuevo_saldo < 0) $nuevo_saldo = 0;

        $nuevo_proximo_pago = $cuenta['proximo_pago'];
        if ($estado == 'Pendiente') {
            $nuevo_proximo_pago = $this->calculateNextPaymentDate($cuenta['proximo_pago'], $cuenta['frecuencia']);
        }

        $query = "UPDATE " . $this->table . " 
                  SET saldo = :nuevo_saldo, estado = :estado, proximo_pago = :nuevo_proximo_pago 
                  WHERE id = :id AND usuario_id = :usuario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nuevo_saldo', $nuevo_saldo);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':nuevo_proximo_pago', $nuevo_proximo_pago);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        return $stmt->execute();
    }

    public function delete($id, $usuario_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getTotalDeudas($usuario_id) {
        $query = "SELECT COALESCE(SUM(saldo), 0) as total 
                  FROM " . $this->table . " 
                  WHERE usuario_id = :usuario_id 
                  AND estado != 'Pagado'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    // Helper para fechas
    private function calculateNextPaymentDate($currentDate, $frequency) {
        $date = new DateTime($currentDate);
        switch ($frequency) {
            case 'diario': $date->modify('+1 day'); break;
            case 'semanal': $date->modify('+1 week'); break;
            case 'quincenal': $date->modify('+15 days'); break;
            case 'mensual': $date->modify('+1 month'); break;
            case 'trimestral': $date->modify('+3 months'); break;
            case 'anual': $date->modify('+1 year'); break;
            default: $date->modify('+1 month'); break;
        }
        return $date->format('Y-m-d');
    }

    // Mantener compatibilidad si es necesario, pero actualizamos logica principal.
    // ...
}
?>