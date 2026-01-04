<?php
require_once __DIR__ . '/../config/database.php';

class Deuda {
    private $conn;
    private $table = "deudas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear nueva deuda
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (usuario_id, categoria_id, acreedor, descripcion, monto_total, monto_cuota, monto_pendiente, fecha_inicio, frecuencia, proximo_pago) 
                  VALUES (:usuario_id, :categoria_id, :acreedor, :descripcion, :monto_total, :monto_cuota, :monto_pendiente, :fecha_inicio, :frecuencia, :proximo_pago)";
        
        $stmt = $this->conn->prepare($query);
        
        $proximo_pago = $this->calculateNextPaymentDate($data['fecha_inicio'], $data['frecuencia']);
        
        $data['proximo_pago'] = $proximo_pago;

        $stmt->bindParam(':usuario_id', $data['usuario_id']);
        $stmt->bindParam(':categoria_id', $data['categoria_id']);
        $stmt->bindParam(':acreedor', $data['acreedor']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':monto_total', $data['monto_total']);
        $stmt->bindParam(':monto_cuota', $data['monto_cuota']);
        $stmt->bindParam(':monto_pendiente', $data['monto_total']);
        $stmt->bindParam(':fecha_inicio', $data['fecha_inicio']);
        $stmt->bindParam(':frecuencia', $data['frecuencia']);
        $stmt->bindParam(':proximo_pago', $data['proximo_pago']);
        
        return $stmt->execute();
    }

    // Obtener todas las deudas (pendientes y pagadas)
    public function getAll($usuario_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE usuario_id = :usuario_id ORDER BY proximo_pago ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Obtener deudas pendientes que vencen pronto (ej. hoy, pasadas, o próximos 5 días)
    public function getDueDebts($usuario_id, $dias_proximos = 5) {
        $today = date('Y-m-d');
        // Fecha límite = hoy + días próximos
        $limit_date = date('Y-m-d', strtotime("+$dias_proximos days"));
        
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE usuario_id = :usuario_id 
                  AND estado = 'pendiente' 
                  AND proximo_pago <= :limit_date 
                  ORDER BY proximo_pago ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':limit_date', $limit_date);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Registrar pago
    public function registerPayment($id, $usuario_id, $monto_pagado) {
        // 1. Obtener deuda actual
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND usuario_id = :usuario_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        $deuda = $stmt->fetch();

        if (!$deuda) return false;

        // 2. Actualizar monto pendiente
        $nuevo_pendiente = $deuda['monto_pendiente'] - $monto_pagado;
        $estado = $nuevo_pendiente <= 0 ? 'pagado' : 'pendiente';
        if ($nuevo_pendiente < 0) $nuevo_pendiente = 0;

        // 3. Calcular nuevo próximo pago si sigue pendiente
        $nuevo_proximo_pago = $deuda['proximo_pago'];
        if ($estado == 'pendiente') {
            $nuevo_proximo_pago = $this->calculateNextPaymentDate($deuda['proximo_pago'], $deuda['frecuencia']);
        }

        // 4. Actualizar registro
        $updateQuery = "UPDATE " . $this->table . " 
                        SET monto_pendiente = :nuevo_pendiente, 
                            estado = :estado, 
                            proximo_pago = :nuevo_proximo_pago 
                        WHERE id = :id";
        
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(':nuevo_pendiente', $nuevo_pendiente);
        $updateStmt->bindParam(':estado', $estado);
        $updateStmt->bindParam(':nuevo_proximo_pago', $nuevo_proximo_pago);
        $updateStmt->bindParam(':id', $id);
        
        return $updateStmt->execute();
    }

    private function calculateNextPaymentDate($currentDate, $frequency) {
        $date = new DateTime($currentDate);
        switch ($frequency) {
            case 'diario':
                $date->modify('+1 day');
                break;
            case 'semanal':
                $date->modify('+1 week');
                break;
            case 'quincenal':
                $date->modify('+15 days');
                break;
            case 'mensual':
                $date->modify('+1 month');
                break;
            case 'trimestral':
                $date->modify('+3 months');
                break;
            case 'anual':
                $date->modify('+1 year');
                break;
        }
        return $date->format('Y-m-d');
    }
    
    // Eliminar deuda
    public function delete($id, $usuario_id) {
         $query = "DELETE FROM " . $this->table . " WHERE id = :id AND usuario_id = :usuario_id";
         $stmt = $this->conn->prepare($query);
         $stmt->bindParam(':id', $id);
         $stmt->bindParam(':usuario_id', $usuario_id);
         return $stmt->execute();
    }
}
?>
