<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/CuentaPorCobrar.php';
require_once __DIR__ . '/../models/FlujoCaja.php';

class DashboardController {
    
    public function getResumenMensual($usuario_id, $anio, $mes, $dia = null) {
        $ingresoModel = new Ingreso();
        $egresoModel = new Egreso();
        $cuentaPorCobrarModel = new CuentaPorCobrar();
        $flujoCajaModel = new FlujoCaja();
        
        // Calcular totales
        // Si hay día, filtrar por día.
        $total_ingresos = $ingresoModel->getTotalByPeriod($usuario_id, $anio, $mes, $dia);
        $total_egresos = $egresoModel->getTotalByPeriod($usuario_id, $anio, $mes, $dia);
        
        // Deudas es snapshot, Flujo es mensual.
        // Si es día especifico, el flujo sigue siendo el del mes, o 0?
        // El usuario quiere filtro. Mantenemos flujo del mes para contexto o mostramos nada?
        // Dashboard generalmente muestra contexto.
        $total_deudas = $cuentaPorCobrarModel->getTotalDeudas($usuario_id);
        
        // Obtener flujo de caja (siempre mensual)
        $flujo = $flujoCajaModel->getByMonth($usuario_id, $anio, $mes);
        
        $balance = $total_ingresos - $total_egresos;
        
        return [
            'total_ingresos' => $total_ingresos,
            'total_egresos' => $total_egresos,
            'balance' => $balance,
            'total_deudas' => $total_deudas,
            'flujo_caja' => $flujo,
            'saldo_disponible' => $flujo ? $flujo['flujo_financiero'] : 0
        ];
    }
    
    public function getEgresosPorCategoria($usuario_id, $anio, $mes, $dia = null) {
        $egresoModel = new Egreso();
        return $egresoModel->getByCategory($usuario_id, $anio, $mes, $dia);
    }
    
    public function getIngresosRecientes($usuario_id, $limit = 5) {
        $ingresoModel = new Ingreso();
        $ingresos = $ingresoModel->getAll($usuario_id);
        return array_slice($ingresos, 0, $limit);
    }
    
    public function getEgresosRecientes($usuario_id, $limit = 5) {
        $egresoModel = new Egreso();
        $egresos = $egresoModel->getAll($usuario_id);
        return array_slice($egresos, 0, $limit);
    }
    public function getDualboardStatus($usuario_id, $anio, $mes, $dia = null) {
        $ingresoModel = new Ingreso();
        $egresoModel = new Egreso();
        $cuentaPorCobrarModel = new CuentaPorCobrar();
        $flujoCajaModel = new FlujoCaja();
        $costoModel = new Costo();
        
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        // Verificar Categorías (Siempre checkea general)
        $stmtCat = $conn->prepare("SELECT COUNT(*) as total FROM categorias_ingreso WHERE usuario_id = ?");
        $stmtCat->execute([$usuario_id]);
        $hasCategorias = $stmtCat->fetch(PDO::FETCH_ASSOC)['total'] > 0;
        
        // Verificar Reportes (Simulamos que siempre si hay datos generales)
        $hasReportes = true; 

        // Verificar los demas con filtro de fecha
        $hasIngresos = $ingresoModel->hasRecords($usuario_id, $anio, $mes, $dia);
        $hasEgresos = $egresoModel->hasRecords($usuario_id, $anio, $mes, $dia);
        $hasCuentas = $cuentaPorCobrarModel->hasRecords($usuario_id, $anio, $mes, $dia);
        
        // Flujo y Costos son mensuales
        $flujo = $flujoCajaModel->getByMonth($usuario_id, $anio, $mes);
        $hasFlujo = $flujo ? true : false;
        
        $hasCostos = $costoModel->hasRecords($usuario_id, $anio, $mes);
        
        return [
            'ingresos' => $hasIngresos,
            'egresos' => $hasEgresos,
            'cuentas_cobrar' => $hasCuentas,
            'flujo_caja' => $hasFlujo,
            'costos' => $hasCostos,
            'categorias' => $hasCategorias,
            'reportes' => $hasReportes
        ];
    }
}
?>