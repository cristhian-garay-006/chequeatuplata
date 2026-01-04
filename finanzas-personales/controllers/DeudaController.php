<?php
require_once __DIR__ . '/../models/Deuda.php';

class DeudaController {
    private $deudaModel;

    public function __construct() {
        $this->deudaModel = new Deuda();
    }

    public function create($usuario_id, $data) {
        $data['usuario_id'] = $usuario_id;
        return $this->deudaModel->create($data);
    }

    public function getCategorias($usuario_id) {
         require_once __DIR__ . '/../models/Egreso.php';
         $egresoModel = new Egreso();
         return $egresoModel->getAllCategorias($usuario_id);
    }

    public function getAll($usuario_id) {
        return $this->deudaModel->getAll($usuario_id);
    }
    
    public function getDueDebts($usuario_id) {
        return $this->deudaModel->getDueDebts($usuario_id);
    }

    public function registerPayment($id, $usuario_id, $monto) {
        return $this->deudaModel->registerPayment($id, $usuario_id, $monto);
    }

    public function delete($id, $usuario_id) {
        return $this->deudaModel->delete($id, $usuario_id);
    }
}
?>
