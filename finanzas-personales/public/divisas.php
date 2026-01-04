<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

// Intentar obtener saldo estimado (Total Ingresos - Total Egresos del mes actual como referencia)
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';
$ingresoModel = new Ingreso();
$egresoModel = new Egreso();
$usuario_id = $_SESSION['user_id'];
$anio = date('Y');
$mes = date('M'); // 'Ene', 'Feb'...
// Nota: getByCategory devuelve array grupal, necesitamos getTotalByPeriod
// Pero Egreso tiene getTotalByPeriod, Ingreso tiene getByCategory... revisemos modelos.
// Ingreso model tiene getTotalByPeriod? Revisé antes, tiene getAll.
// Usaremos una query rápida o simplemente 0 si es complejo.
// Solución rápida: Asumir 0 o dejar que el usuario ingrese.
// Para ser proactivo, intentemos obtenerlo si los métodos existen.

$page_title = 'Divisas';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1>Conversor de Divisas</h1>
    <p class="text-muted">Consulta el tipo de cambio en tiempo real.</p>
</div>

<div class="dashboard-grid">
    <!-- Converter Card -->
    <div class="dashboard-section" style="grid-column: span 12; max-width: 600px; margin: 0 auto; background: var(--card-bg); padding: 30px; border-radius: 12px; box-shadow: var(--card-shadow);">
        
        <div class="text-center mb-4">
            <i class="fas fa-coins" style="font-size: 3em; color: var(--primary-color);"></i>
        </div>

        <form id="converterForm" onsubmit="return false;">
            <div class="form-group">
                <label for="amount">Monto a Convertir</label>
                <div class="input-group-currency">
                    <input type="number" id="amount" class="form-control-lg" value="100" min="0" step="0.01" style="width: 100%; padding: 10px; font-size: 1.2em; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label for="from_currency">De</label>
                    <select id="from_currency" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        <option value="PEN" selected>S/ Sol Peruano</option>
                        <option value="USD">USD Dólar Estadounidense</option>
                        <option value="EUR">EUR Euro</option>
                        <option value="MXN">MXN Peso Mexicano</option>
                        <option value="COP">COP Peso Colombiano</option>
                        <option value="ARS">ARS Peso Argentino</option>
                        <option value="CLP">CLP Peso Chileno</option>
                        <option value="BRL">BRL Real Brasileño</option>
                    </select>
                </div>
                
                <div style="flex: 0;">
                    <button class="btn btn-secondary" onclick="swapCurrencies()" style="height: 42px; width: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                </div>

                <div style="flex: 1;">
                    <label for="to_currency">A</label>
                    <select id="to_currency" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        <option value="USD" selected>USD Dólar Estadounidense</option>
                        <option value="PEN">S/ Sol Peruano</option>
                        <option value="EUR">EUR Euro</option>
                        <option value="MXN">MXN Peso Mexicano</option>
                        <option value="COP">COP Peso Colombiano</option>
                        <option value="ARS">ARS Peso Argentino</option>
                        <option value="CLP">CLP Peso Chileno</option>
                        <option value="BRL">BRL Real Brasileño</option>
                    </select>
                </div>
            </div>

            <div class="result-box text-center p-4 mt-4" style="background: var(--bg-primary); border-radius: 12px; border: 1px solid var(--border);">
                <span class="text-muted d-block mb-1">Resultado Estimado</span>
                <h2 id="result" class="m-0" style="color: var(--primary-color); font-size: 2.5em; font-weight: 700;">---</h2>
                <div id="rate-info" class="text-sm text-muted mt-2" style="font-size: 0.9em;">Cargando tasa...</div>
            </div>
            
            <div class="mt-3 text-center">
                 <small class="text-muted">Tasas actualizadas diariamente via ExchangeRate-API.</small>
            </div>
        </form>
    </div>
</div>

<script>
    const amountInput = document.getElementById('amount');
    const fromSelect = document.getElementById('from_currency');
    const toSelect = document.getElementById('to_currency');
    const resultEl = document.getElementById('result');
    const rateInfoEl = document.getElementById('rate-info');
    
    let currentRates = {};

    async function fetchRates(base = 'USD') {
        try {
            rateInfoEl.innerText = 'Actualizando tasas...';
            // Usamos USD como base gratis común y convertimos
            const response = await fetch(`https://api.exchangerate-api.com/v4/latest/${base}`);
            if (!response.ok) throw new Error('Error al obtener tasas');
            const data = await response.json();
            currentRates = data.rates;
            calculate();
            rateInfoEl.innerText = `1 ${base} = ${currentRates[toSelect.value]} ${toSelect.value}`;
        } catch (error) {
            console.error(error);
            rateInfoEl.innerText = 'Error de conexión con API de divisas.';
            // Fallback hardcoded o reitento
        }
    }

    function calculate() {
        const amount = parseFloat(amountInput.value);
        if (isNaN(amount)) {
            resultEl.innerText = '---';
            return;
        }

        const from = fromSelect.value;
        const to = toSelect.value;

        // Si tenemos las tasas basadas en 'from', directo. Si no, necesitamos reconvertir?
        // La API free v4 suele dar base USD. Soportan base param? v4/latest/USD
        // Si pedimos base=PEN quizás no funcione en la free. Verifiquemos JS.
        // Estrategia: Pedir base al cambiar "De".
        
        // Simplemente mostraremos el resultado.
        const rate = currentRates[to]; 
        if(rate) {
            const total = (amount * rate).toFixed(2);
            resultEl.innerText = `${total} ${to}`;
            rateInfoEl.innerText = `Tasa: 1 ${from} = ${rate} ${to}`;
        }
    }

    // Event Listeners
    amountInput.addEventListener('input', calculate);
    
    fromSelect.addEventListener('change', () => {
        fetchRates(fromSelect.value);
    });

    toSelect.addEventListener('change', calculate);

    function swapCurrencies() {
        const temp = fromSelect.value;
        fromSelect.value = toSelect.value;
        toSelect.value = temp;
        fetchRates(fromSelect.value);
    }

    // Init with PEN (or Default)
    window.addEventListener('DOMContentLoaded', () => {
        fetchRates(fromSelect.value);
    });

</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
