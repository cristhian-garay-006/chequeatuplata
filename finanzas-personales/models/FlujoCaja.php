<?php
require_once __DIR__ . '/../config/database.php';

class FlujoCaja {
    private $conn;
    private $table = "flujo_caja";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createOrUpdate($data) {

        $query = "INSERT INTO {$this->table} 
                (usuario_id, anio, mes, saldo_inicial, total_ingresos, total_egresos)
                VALUES 
                (:usuario_id, :anio, :mes, :saldo_inicial, :total_ingresos, :total_egresos)
                ON DUPLICATE KEY UPDATE 
                saldo_inicial = :upd_saldo_inicial,
                total_ingresos = :upd_total_ingresos,
                total_egresos = :upd_total_egresos";

        $stmt = $this->conn->prepare($query);

        // Reemplazar parámetros duplicados
        $params = [
            ":usuario_id" => $data[":usuario_id"],
            ":anio" => $data[":anio"],
            ":mes" => $data[":mes"],
            ":saldo_inicial" => $data[":saldo_inicial"],
            ":total_ingresos" => $data[":total_ingresos"],
            ":total_egresos" => $data[":total_egresos"],

            // Parámetros para UPDATE
            ":upd_saldo_inicial" => $data[":saldo_inicial"],
            ":upd_total_ingresos" => $data[":total_ingresos"],
            ":upd_total_egresos" => $data[":total_egresos"]
        ];

        $stmt->execute($params);
        return true;
    }

    
    public function getByPeriod($usuario_id, $anio) {
        $query = "SELECT * FROM {$this->table}
                  WHERE usuario_id = :usuario_id
                  AND anio = :anio
                  ORDER BY FIELD(mes, 'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':anio', $anio);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }


    public function getByMonth($usuario_id, $anio, $mes) {
        $query = "SELECT * FROM {$this->table}
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


    public function calculateFlujo($usuario_id, $anio, $mes) {
        require_once __DIR__ . '/Ingreso.php';
        require_once __DIR__ . '/Egreso.php';
        
        $ingresoModel = new Ingreso();
        $egresoModel = new Egreso();

        $total_ingresos = $ingresoModel->getTotalByPeriod($usuario_id, $anio, $mes);
        $total_egresos = $egresoModel->getTotalByPeriod($usuario_id, $anio, $mes);

        // Obtener saldo inicial
        $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $mes_index = array_search($mes, $meses);
        
        $saldo_inicial = 0;

        if ($mes_index > 0) {
            // Mes anterior
            $mes_anterior = $meses[$mes_index - 1];
            $flujo_anterior = $this->getByMonth($usuario_id, $anio, $mes_anterior);
            if ($flujo_anterior) {
                $saldo_inicial = $flujo_anterior['flujo_financiero'];
            }
        } else {
            // Enero → mirar diciembre del año anterior
            $flujo_anterior = $this->getByMonth($usuario_id, $anio - 1, 'Dic');
            if ($flujo_anterior) {
                $saldo_inicial = $flujo_anterior['flujo_financiero'];
            }
        }

        // Construir array de datos
        $data = [
            ":usuario_id" => $usuario_id,
            ":anio" => $anio,
            ":mes" => $mes,
            ":saldo_inicial" => $saldo_inicial,
            ":total_ingresos" => $total_ingresos,
            ":total_egresos" => $total_egresos
        ];

        return $this->createOrUpdate($data);
    }
}
?>
