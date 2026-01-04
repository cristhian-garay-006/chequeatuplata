<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();
$page_title = 'Premium';
include __DIR__ . '/../views/layouts/header.php';
?>
<!-- Styles for blur and overlay -->
<style>
    .premium-container {
        position: relative;
        min-height: 80vh;
        overflow: hidden;
    }
    .blurred-content {
        filter: blur(8px);
        opacity: 0.6;
        pointer-events: none;
        user-select: none;
    }
    .premium-overlay {
        position: absolute;
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        display: flex; 
        flex-direction: column;
        justify-content: center; 
        align-items: center; 
        z-index: 10;
        background: rgba(0,0,0,0.1); /* Slight tint */
    }
    .plan-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        max-width: 400px;
        width: 100%;
        text-align: center;
        border: 2px solid var(--primary-color);
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
</style>

<div class="premium-container">
    <!-- Blurred Dummy Content -->
    <div class="blurred-content">
        <div class="page-header">
            <h1>Panel de Inversiones IA</h1>
        </div>
        <div class="dashboard-grid">
            <!-- Fake Charts -->
            <div class="dashboard-section" style="height: 300px; background: #fff;">
                <h3>Predicción de Mercado (IA)</h3>
                <div style="height: 200px; background: #eee; margin-top: 20px;"></div>
            </div>
            <div class="dashboard-section" style="height: 300px; background: #fff;">
                 <h3>Cripto Analítica Avanzada</h3>
                 <div style="height: 200px; background: #eee; margin-top: 20px;"></div>
            </div>
             <div class="dashboard-section" style="height: 300px; background: #fff;">
                 <h3>Arbitraje de Divisas</h3>
                 <div style="height: 200px; background: #eee; margin-top: 20px;"></div>
            </div>
        </div>
    </div>

    <!-- Overlay & CTA -->
    <div class="premium-overlay">
        <div class="plan-card">
            <i class="fas fa-crown" style="font-size: 4em; color: gold; margin-bottom: 20px;"></i>
            <h2 style="color: var(--text-primary); margin-bottom: 10px;">¡Desbloquea las Opciones Premium!</h2>
            <p class="text-muted">Accede a herramientas avanzadas y reportes exclusivos.</p>
            
            <button class="btn btn-primary btn-lg btn-block" onclick="openModal('plansModal')" style="margin-top: 20px; font-weight: bold; font-size: 1.2em;">
                DESBLOQUEAR AHORA
            </button>
        </div>
    </div>
</div>

<!-- Modal Planes -->
<div id="plansModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Elige tu Plan</h2>
            <span class="close" onclick="closeModal('plansModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; margin-bottom: 30px;">
                
                <!-- Plan Pro -->
                <div style="border: 1px solid var(--border); border-radius: 12px; padding: 20px; flex: 1; min-width: 300px; text-align: center;">
                    <h3>Pro</h3>
                    <div style="font-size: 2em; font-weight: bold; color: var(--primary-color);">S/ 100 <small style="font-size: 0.5em; color: var(--text-secondary);">/mes</small></div>
                    <ul style="list-style: none; padding: 0; margin: 20px 0; text-align: left; max-height: 300px; overflow-y: auto; font-size: 0.9em;">
                        <li><i class="fas fa-check text-success"></i> Gráficos Avanzados</li>
                        <li><i class="fas fa-check text-success"></i> Soporte Prioritario 24/7</li>
                        <li><i class="fas fa-check text-success"></i> Alertas de Mercado en Vivo</li>
                        <li><i class="fas fa-check text-success"></i> Reportes Semanales PDF</li>
                        <li><i class="fas fa-check text-success"></i> Calculadora Interés Compuesto</li>
                        <li><i class="fas fa-check text-success"></i> Historial Ilimitado</li>
                        <li><i class="fas fa-check text-success"></i> Exportación a Excel/CSV</li>
                        <li><i class="fas fa-check text-success"></i> Seguimiento de Dividendos</li>
                        <li><i class="fas fa-check text-success"></i> Notificaciones vía WhatsApp</li>
                        <li><i class="fas fa-check text-success"></i> Análisis de Gastos con IA</li>
                        <li><i class="fas fa-check text-success"></i> Presupuestos Ilimitados</li>
                        <li><i class="fas fa-check text-success"></i> Categorías Personalizadas</li>
                        <li><i class="fas fa-check text-success"></i> Sin Publicidad</li>
                        <li><i class="fas fa-check text-success"></i> Acceso a Webinars Grabados</li>
                        <li><i class="fas fa-check text-success"></i> Comparador de Fondos Mutuos</li>
                        <li><i class="fas fa-check text-success"></i> Simulador de Préstamos</li>
                        <li><i class="fas fa-check text-success"></i> Integración Bancaria (Solo Lectura)</li>
                        <li><i class="fas fa-check text-success"></i> Plantillas de Portafolio</li>
                        <li><i class="fas fa-check text-success"></i> Descuentos en Cursos</li>
                        <li><i class="fas fa-check text-success"></i> Acceso Multi-dispositivo</li>
                    </ul>
                    <button class="btn btn-outline-primary btn-block" onclick="selectPlan('Pro', 100)">Elegir Pro</button>
                </div>

                <!-- Plan Super Pro -->
                <div style="border: 2px solid gold; border-radius: 12px; padding: 20px; flex: 1; min-width: 300px; text-align: center; position: relative;">
                    <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: gold; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8em;">RECOMENDADO</div>
                    <h3>Super Pro</h3>
                    <div style="font-size: 2em; font-weight: bold; color: gold;">S/ 1000</div>
                    <ul style="list-style: none; padding: 0; margin: 20px 0; text-align: left; max-height: 300px; overflow-y: auto; font-size: 0.9em;">
                        <li><i class="fas fa-check text-success"></i> <strong>Todo lo de Pro</strong> y además:</li>
                        <li><i class="fas fa-check text-success"></i> Asesoría Personalizada 1-a-1</li>
                        <li><i class="fas fa-check text-success"></i> Acceso a IA Predictiva (Beta)</li>
                        <li><i class="fas fa-check text-success"></i> Señales de Trading en Vivo</li>
                        <li><i class="fas fa-check text-success"></i> Grupo Privado de Inversores</li>
                        <li><i class="fas fa-check text-success"></i> Consultas Fiscales Mensuales</li>
                        <li><i class="fas fa-check text-success"></i> Gestor de Cuenta Dedicado</li>
                        <li><i class="fas fa-check text-success"></i> Análisis de Portafolio Experto</li>
                        <li><i class="fas fa-check text-success"></i> Alertas de Arbitraje</li>
                        <li><i class="fas fa-check text-success"></i> Acceso Anticipado a IPOs</li>
                        <li><i class="fas fa-check text-success"></i> Estrategias de Hedging</li>
                        <li><i class="fas fa-check text-success"></i> Reportes Macroeconómicos</li>
                        <li><i class="fas fa-check text-success"></i> Reuniones Zoom Trimestrales</li>
                        <li><i class="fas fa-check text-success"></i> Tarjeta Metálica Exclusiva</li>
                        <li><i class="fas fa-check text-success"></i> Seguros de Inversión</li>
                        <li><i class="fas fa-check text-success"></i> API de Acceso Completo</li>
                        <li><i class="fas fa-check text-success"></i> Límites de Transferencia Altos</li>
                        <li><i class="fas fa-check text-success"></i> Retiros Prioritarios</li>
                        <li><i class="fas fa-check text-success"></i> Invitaciones a Eventos VIP</li>
                        <li><i class="fas fa-check text-success"></i> Merchandising Exclusivo</li>
                    </ul>
                    <button class="btn btn-primary btn-block" onclick="selectPlan('Super Pro', 1000)">Elegir Super Pro</button>
                </div>
            </div>

            <!-- Fake Payment Form (Hidden initially) -->
            <div id="paymentForm" style="display: none; border-top: 1px solid var(--border); padding-top: 20px;">
                <h3 id="planTitle">Pagando Plan...</h3>
                <form onsubmit="event.preventDefault(); alert('¡Pago procesado (Simulación)! Bienvenido a Premium.'); window.location.href='dashboard.php';">
                    <div class="form-group">
                        <label>Nombre en la Tarjeta</label>
                        <input type="text" class="form-control" required placeholder="Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label>Número de Tarjeta</label>
                        <div style="position: relative;">
                            <input type="text" class="form-control" required placeholder="0000 0000 0000 0000" maxlength="19">
                            <i class="fas fa-credit-card" style="position: absolute; right: 10px; top: 12px; color: var(--muted);"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Expira (MM/YY)</label>
                            <input type="text" class="form-control" required placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>CVC</label>
                            <input type="text" class="form-control" required placeholder="123" maxlength="3">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-block btn-lg">Pagar Ahora</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function selectPlan(name, price) {
        document.getElementById('planTitle').innerText = `Pagando Plan ${name} - S/ ${price}`;
        document.getElementById('paymentForm').style.display = 'block';
        // Scroll to form
        document.getElementById('paymentForm').scrollIntoView({behavior: 'smooth'});
    }
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
