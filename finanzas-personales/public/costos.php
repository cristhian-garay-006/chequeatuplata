<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/Costo.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/CuentaPorCobrar.php';

$costoModel = new Costo();
$ingresoModel = new Ingreso();
$cuentaModel = new CuentaPorCobrar();
$usuario_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Obtener período
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('M');

// Crear costo fijo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_costo'])) {
    $data = [
        'usuario_id' => $usuario_id,
        'anio' => $anio,
        'mes' => $mes,
        'categoria' => $_POST['categoria'],
        'concepto' => $_POST['concepto'],
        'monto' => $_POST['monto'],
        'descripcion' => $_POST['descripcion'] ?? null
    ];
    
    if ($costoModel->createCostoFijo($data)) {
        $message = 'Costo fijo creado exitosamente';
    } else {
        $error = 'Error al crear el costo fijo';
    }
}

// Actualizar distribución
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_distribucion'])) {
    // Sanitizar y convertir
    $ingreso_total = isset($_POST['ingreso_total']) ? floatval($_POST['ingreso_total']) : 0.0;
    $necesidades_pct = isset($_POST['necesidades_porcentaje']) ? floatval($_POST['necesidades_porcentaje']) : null;
    $deseos_pct = isset($_POST['deseos_porcentaje']) ? floatval($_POST['deseos_porcentaje']) : null;
    $deudas_pct = isset($_POST['deudas_ahorro_porcentaje']) ? floatval($_POST['deudas_ahorro_porcentaje']) : null;

    // Validaciones básicas
    if ($necesidades_pct === null || $deseos_pct === null || $deudas_pct === null) {
        $error = 'Por favor complete todos los porcentajes antes de actualizar.';
    } else {
        $sum = $necesidades_pct + $deseos_pct + $deudas_pct;
        // permitir pequeña tolerancia decimal
        if (abs($sum - 100) > 0.5) {
            $error = "La suma de porcentajes debe ser 100%. Actualmente: {$sum}%";
        } else {
            // calcular montos con redondeo
            $necesidades_monto = round($ingreso_total * ($necesidades_pct / 100), 2);
            $deseos_monto      = round($ingreso_total * ($deseos_pct / 100), 2);
            $deudas_ahorro_monto = round($ingreso_total * ($deudas_pct / 100), 2);

            $data = [
                'usuario_id' => $usuario_id,
                'anio' => $anio,
                'mes' => $mes,
                'ingreso_total' => $ingreso_total,
                'necesidades_porcentaje' => $necesidades_pct,
                'deseos_porcentaje' => $deseos_pct,
                'deudas_ahorro_porcentaje' => $deudas_pct,
                'necesidades_monto' => $necesidades_monto,
                'deseos_monto' => $deseos_monto,
                'deudas_ahorro_monto' => $deudas_ahorro_monto
            ];

            try {
                // validación final server-side
                if (!is_numeric($ingreso_total) || $ingreso_total < 0) {
                    $error = 'Ingreso total inválido.';
                } else {
                    $ok = $costoModel->createOrUpdateDistribucion($data);
                    if ($ok) {
                        $message = 'Distribución actualizada exitosamente';
                        // refrescar $distribucion para la vista
                        $distribucion = $costoModel->getDistribucion($usuario_id, $anio, $mes);
                    } else {
                        $error = 'No se pudo guardar la distribución. Verifique los datos e intente de nuevo.';
                    }
                }
            } catch (Throwable $e) {
                // Registrar detalle en logs para diagnóstico (no mostrar detalle al usuario)
                $logMsg = sprintf(
                  "[%s] Error actualizar distribucion: %s in %s:%d\nStack: %s\n",
                  date('Y-m-d H:i:s'),
                  $e->getMessage(),
                  $e->getFile(),
                  $e->getLine(),
                  $e->getTraceAsString()
                );
                // log al error_log de PHP
                error_log($logMsg);
                // log a archivo específico (asegúrate que la carpeta storage/logs exista y tenga permisos)
                @file_put_contents(__DIR__ . '/../storage/logs/error_distribucion.log', $logMsg, FILE_APPEND | LOCK_EX);

                // Mensaje amigable al usuario
                $error = 'Ocurrió un error al guardar la distribución. Se registró en logs del servidor.';
            }
        }
    }
}

// Actualizar caja/bancos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_caja'])) {
    $bancos = ['BCP', 'INTERBANK', 'EFECTIVO'];
    $success = true;
    foreach ($bancos as $banco) {
        $data = [
            'usuario_id' => $usuario_id,
            'anio' => $anio,
            'mes' => $mes,
            'banco' => $banco,
            'saldo' => $_POST['saldo_' . strtolower($banco)] ?? 0
        ];
        if (!$costoModel->createOrUpdateCajaBanco($data)) {
            $success = false;
            break;
        }
    }
    if ($success) {
        $message = 'Saldos de caja actualizados exitosamente';
    } else {
        $error = 'Error al actualizar los saldos de caja';
    }
}

// Obtener datos
$ingreso_total = $ingresoModel->getTotalByPeriod($usuario_id, $anio, $mes);
$distribucion = $costoModel->getDistribucion($usuario_id, $anio, $mes);
$costos_fijos = $costoModel->getCostosFijos($usuario_id, $anio, $mes);
$caja_bancos = $costoModel->getCajaBancos($usuario_id, $anio, $mes);
$total_deudas = $cuentaModel->getTotalDeudas($usuario_id);

// Si no existe distribución, crear una por defecto
if (!$distribucion) {
    $distribucion = [
        'ingreso_total' => $ingreso_total,
        'necesidades_porcentaje' => 77,
        'deseos_porcentaje' => 8,
        'deudas_ahorro_porcentaje' => 15,
        'necesidades_monto' => $ingreso_total * 0.77,
        'deseos_monto' => $ingreso_total * 0.08,
        'deudas_ahorro_monto' => $ingreso_total * 0.15
    ];
}

// Organizar costos por categoría
$costos_por_categoria = [];
foreach ($costos_fijos as $costo) {
    $costos_por_categoria[$costo['categoria']][] = $costo;
}

$page_title = 'Costos y Distribución';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calculator"></i> Costos y Distribución</h1>
</div>

<div class="dashboard-grid">
    <!-- DISTRIBUCIÓN DE INGRESO -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3>Distribución de Ingreso</h3>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="ingreso_total" value="<?php echo $ingreso_total; ?>">
            
            <div class="metric-value text-center mb-3">
                <?php echo formatMoney($ingreso_total); ?>
                <small class="d-block text-muted" style="font-size: 12px; font-weight: normal;">Ingreso Total</small>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th class="text-center">Porcentaje</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Necesidades</td>
                            <td class="text-center">
                                <input type="number" name="necesidades_porcentaje" 
                                       value="<?php echo $distribucion['necesidades_porcentaje']; ?>" 
                                       step="0.01" min="0" max="100" class="form-control-inline text-center" style="width: 60px; padding: 5px;">%
                            </td>
                            <td class="text-right"><?php echo formatMoney($distribucion['necesidades_monto']); ?></td>
                        </tr>
                        <tr>
                            <td>Deseos</td>
                            <td class="text-center">
                                <input type="number" name="deseos_porcentaje" 
                                       value="<?php echo $distribucion['deseos_porcentaje']; ?>" 
                                       step="0.01" min="0" max="100" class="form-control-inline text-center" style="width: 60px; padding: 5px;">%
                            </td>
                            <td class="text-right"><?php echo formatMoney($distribucion['deseos_monto']); ?></td>
                        </tr>
                        <tr>
                            <td>Deudas/Ahorro</td>
                            <td class="text-center">
                                <input type="number" name="deudas_ahorro_porcentaje" 
                                       value="<?php echo $distribucion['deudas_ahorro_porcentaje']; ?>" 
                                       step="0.01" min="0" max="100" class="form-control-inline text-center" style="width: 60px; padding: 5px;">%
                            </td>
                            <td class="text-right"><?php echo formatMoney($distribucion['deudas_ahorro_monto']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="submit" name="update_distribucion" class="btn btn-primary btn-block btn-sm mt-3">Actualizar</button>
        </form>
    </div>

    <!-- CAJA Y BANCOS -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3>Caja y Bancos</h3>
        </div>
        <form method="POST" action="">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Banco</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $bancos = ['BCP', 'INTERBANK', 'EFECTIVO'];
                        $saldos_actuales = [];
                        foreach ($caja_bancos as $cb) {
                            $saldos_actuales[$cb['banco']] = $cb['saldo'];
                        }
                        
                        foreach ($bancos as $banco): 
                        ?>
                        <tr>
                            <td><?php echo $banco; ?></td>
                            <td class="text-right">
                                <input type="number" name="saldo_<?php echo strtolower($banco); ?>" 
                                       value="<?php echo $saldos_actuales[$banco] ?? 0; ?>" 
                                       step="0.01" min="0" class="form-control-inline text-right" style="width: 100px; padding: 5px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" name="update_caja" class="btn btn-primary btn-block btn-sm mt-3">Actualizar Saldos</button>
        </form>

        <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 15px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Caja Total:</span>
                <strong><?php echo formatMoney($costoModel->getTotalCaja($usuario_id, $anio, $mes)); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Deudas Por Cobrar:</span>
                <span class="text-danger"><?php echo formatMoney($total_deudas); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; background: var(--bg-primary); padding: 10px; border-radius: 8px;">
                <strong>TOTAL ACTIVO:</strong>
                <strong><?php echo formatMoney($costoModel->getTotalCaja($usuario_id, $anio, $mes) + $total_deudas); ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="section-header mt-5 mb-3" style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Costos Fijos</h2>
    <div style="display: flex; gap: 10px; align-items: center;">
        <span style="font-size: 1.2em; font-weight: bold; color: var(--expense-color);">
             Total: <?php echo formatMoney($costoModel->getTotalCostosFijos($usuario_id, $anio, $mes)); ?>
        </span>
        <button class="btn btn-primary" onclick="openModal('createCostoModal')">
            <i class="fas fa-plus"></i> Nuevo Costo
        </button>
    </div>
</div>

<!-- Grid de Categorías de Costos -->
<div class="dashboard-grid">
<?php 
$categorias_costos = ['Deudas/Ahorro', 'Deseos', 'Necesidades', 'Alquiler', 'Servicios'];
foreach ($categorias_costos as $cat): 
    $safe_id = str_replace(['/', ' '], '_', $cat);
    $total_categoria = 0;
    if (isset($costos_por_categoria[$cat])) {
        foreach ($costos_por_categoria[$cat] as $c) {
            $total_categoria += $c['monto'];
        }
    }
?>
    <div class="dashboard-section" style="text-align: center; padding: 20px; transition: transform 0.2s;">
        <h4 style="color: var(--accent-blue); margin-bottom: 15px;"><?php echo $cat; ?></h4>
        <h2 style="font-weight: 800; margin-bottom: 20px; color: var(--text-primary);"><?php echo formatMoney($total_categoria); ?></h2>
        
        <button class="btn btn-secondary btn-sm" onclick="openModal('modal_<?php echo $safe_id; ?>')">
            Ver Detalle <i class="fas fa-search-plus" style="margin-left: 5px;"></i>
        </button>
    </div>

    <!-- Modal Detalle Categoría -->
    <div id="modal_<?php echo $safe_id; ?>" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalle: <?php echo $cat; ?></h2>
                <span class="close" onclick="closeModal('modal_<?php echo $safe_id; ?>')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-right">Monto</th>
                                <th>Descripción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($costos_por_categoria[$cat]) && count($costos_por_categoria[$cat]) > 0): 
                                foreach ($costos_por_categoria[$cat] as $costo): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($costo['concepto']); ?></td>
                                <td class="text-right"><?php echo formatMoney($costo['monto']); ?></td>
                                <td><small><?php echo htmlspecialchars($costo['descripcion'] ?? ''); ?></small></td>
                                <td class="text-center">
                                    <a href="?delete_costo=<?php echo $costo['id']; ?>&anio=<?php echo $anio; ?>&mes=<?php echo $mes; ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('¿Eliminar este costo?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay costos registrados en esta categoría</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                             <tr class="total-row">
                                <td><strong>TOTAL</strong></td>
                                <td class="text-right"><strong><?php echo formatMoney($total_categoria); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                 <button class="btn btn-primary" onclick="closeModal('modal_<?php echo $safe_id; ?>'); openModal('createCostoModal')">
                    <i class="fas fa-plus"></i> Agregar Nuevo
                 </button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Modal Crear Costo Fijo Global -->
<div id="createCostoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nuevo Costo Fijo</h2>
            <span class="close" onclick="closeModal('createCostoModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="categoria" required class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Deudas/Ahorro">Deudas/Ahorro</option>
                        <option value="Deseos">Deseos</option>
                        <option value="Necesidades">Necesidades</option>
                        <option value="Alquiler">Alquiler</option>
                        <option value="Servicios">Servicios</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="concepto">Concepto *</label>
                    <input type="text" id="concepto" name="concepto" required class="form-control" placeholder="Ej: Luz, Agua, Netflix">
                </div>
                
                <div class="form-group">
                    <label for="monto">Monto *</label>
                    <input type="number" id="monto" name="monto" step="0.01" min="0" required class="form-control" placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="form-control" placeholder="Opcional"></textarea>
                </div>

                <!-- Placeholder Comprobante -->
                <div class="form-group">
                    <label>Comprobante / Recibo</label>
                    <div style="border: 2px dashed var(--border); padding: 10px; text-align: center; border-radius: 8px; background: var(--bg-primary);">
                        <i class="fas fa-file-invoice-dollar" style="font-size: 1.5em; color: var(--muted); margin-bottom: 5px;"></i>
                        <p style="margin:0; font-size: 0.8em; color: var(--text-secondary);">Adjuntar recibo</p>
                        <input type="file" disabled style="display:none">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createCostoModal')">Cancelar</button>
                <button type="submit" name="create_costo" class="btn btn-primary">Guardar Costo</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>