// ============================================
// FUNCIONES GLOBALES
// ============================================

// Abrir modal
// Abrir modal
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("active");
    modal.style.display = "flex";
    document.body.style.overflow = "hidden"; // Prevenir scroll del body
  }
}

// Cerrar modal
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
    modal.style.display = "none";
    document.body.style.overflow = ""; // Restaurar scroll
  }
}

// Cerrar modal al hacer clic fuera
window.onclick = function (event) {
  if (event.target.classList.contains("modal")) {
    event.target.classList.remove("active");
    event.target.style.display = "none";
    document.body.style.overflow = "";
  }
};

// Cerrar modal con tecla ESC
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    const modals = document.querySelectorAll(".modal.active");
    modals.forEach((modal) => {
      modal.classList.remove("active");
      modal.style.display = "none";
    });
    document.body.style.overflow = "";
  }
});

// Formatear moneda
function formatCurrency(amount) {
  return (
    "S/ " +
    parseFloat(amount)
      .toFixed(2)
      .replace(/\d(?=(\d{3})+\.)/g, "$&,")
  );
}

// Validar formulario
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return false;

  const inputs = form.querySelectorAll("[required]");
  let valid = true;

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      input.style.borderColor = "var(--danger-color)";
      valid = false;
    } else {
      input.style.borderColor = "#ddd";
    }
  });

  return valid;
}

// Confirmar eliminación
function confirmDelete(message = "¿Está seguro de eliminar este registro?") {
  return confirm(message);
}

// Mostrar alerta temporal
function showAlert(message, type = "success") {
  const alert = document.createElement("div");
  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  alert.style.position = "fixed";
  alert.style.top = window.innerWidth <= 768 ? "70px" : "20px";
  alert.style.right = "20px";
  alert.style.left = window.innerWidth <= 768 ? "20px" : "auto";
  alert.style.zIndex = "9999";
  alert.style.minWidth = window.innerWidth <= 768 ? "auto" : "300px";

  document.body.appendChild(alert);

  setTimeout(() => {
    alert.style.opacity = "0";
    setTimeout(() => alert.remove(), 300);
  }, 3000);
}

// ============================================
// MOBILE SIDEBAR TOGGLE
// ============================================
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.querySelector(".sidebar-overlay");

  if (sidebar && overlay) {
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
  }
}

function closeSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.querySelector(".sidebar-overlay");

  if (sidebar && overlay) {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
  }
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  // Crear overlay para sidebar móvil
  if (window.innerWidth <= 768) {
    const overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    overlay.addEventListener("click", closeSidebar);
    document.body.appendChild(overlay);
  }

  // Cerrar sidebar al hacer clic en un enlace (móvil)
  if (window.innerWidth <= 768) {
    const navItems = document.querySelectorAll(".nav-item");
    navItems.forEach((item) => {
      item.addEventListener("click", closeSidebar);
    });
  }

  // Agregar animación a las tarjetas
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = "0";
      card.style.transform = "translateY(20px)";
      setTimeout(() => {
        card.style.transition = "all 0.5s ease";
        card.style.opacity = "1";
        card.style.transform = "translateY(0)";
      }, 50);
    }, index * 100);
  });

  // Resaltar fila activa en tablas
  const tableRows = document.querySelectorAll("table tbody tr");
  tableRows.forEach((row) => {
    row.addEventListener("click", function (e) {
      if (!e.target.closest(".btn")) {
        tableRows.forEach((r) => (r.style.backgroundColor = ""));
        this.style.backgroundColor = "#e3f2fd";
      }
    });
  });

  // Auto-cerrar alertas
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });

  // Detectar cambios de orientación en móvil
  window.addEventListener("orientationchange", function () {
    setTimeout(() => {
      const charts = document.querySelectorAll("canvas");
      charts.forEach((chart) => {
        if (chart.chart) {
          chart.chart.resize();
        }
      });
    }, 100);
  });

  // Prevenir zoom en inputs en iOS
  if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
    const viewport = document.querySelector("meta[name=viewport]");
    if (viewport) {
      viewport.content = "width=device-width, initial-scale=1, maximum-scale=1";
    }
  }
});

// ============================================
// MANEJO DE RESIZE
// ============================================
let resizeTimer;
window.addEventListener("resize", function () {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function () {
    // Cerrar sidebar si cambiamos a desktop
    if (window.innerWidth > 768) {
      closeSidebar();
      const overlay = document.querySelector(".sidebar-overlay");
      if (overlay) {
        overlay.remove();
      }
    } else {
      // Recrear overlay si no existe
      if (!document.querySelector(".sidebar-overlay")) {
        const overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        overlay.addEventListener("click", closeSidebar);
        document.body.appendChild(overlay);
      }
    }
  }, 250);
});

// ============================================
// FUNCIONES ESPECÍFICAS
// ============================================

// Calcular saldo automáticamente en cuentas por cobrar
function calculateSaldo() {
  const deudaTotal = parseFloat(
    document.getElementById("deuda_total")?.value || 0
  );
  const cuota1 = parseFloat(document.getElementById("cuota_1")?.value || 0);
  const cuota2 = parseFloat(document.getElementById("cuota_2")?.value || 0);
  const cuota3 = parseFloat(document.getElementById("cuota_3")?.value || 0);

  const saldo = deudaTotal - (cuota1 + cuota2 + cuota3);

  const saldoElement = document.getElementById("saldo_display");
  if (saldoElement) {
    saldoElement.textContent = formatCurrency(saldo);
    saldoElement.style.color =
      saldo <= 0 ? "var(--success-color)" : "var(--danger-color)";
  }
}

// Validar porcentajes de distribución
function validatePercentages() {
  const necesidades = parseFloat(
    document.querySelector('[name="necesidades_porcentaje"]')?.value || 0
  );
  const deseos = parseFloat(
    document.querySelector('[name="deseos_porcentaje"]')?.value || 0
  );
  const deudas = parseFloat(
    document.querySelector('[name="deudas_ahorro_porcentaje"]')?.value || 0
  );

  const total = necesidades + deseos + deudas;

  if (Math.abs(total - 100) > 0.01) {
    alert("La suma de los porcentajes debe ser 100%");
    return false;
  }

  return true;
}

// Exportar tabla a CSV
function exportToCSV(tableId, filename = "export.csv") {
  const table = document.getElementById(tableId);
  if (!table) return;

  let csv = [];
  const rows = table.querySelectorAll("tr");

  rows.forEach((row) => {
    const cols = row.querySelectorAll("td, th");
    const csvRow = [];
    cols.forEach((col) => csvRow.push(col.textContent));
    csv.push(csvRow.join(","));
  });

  const csvContent = csv.join("\n");
  const blob = new Blob([csvContent], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.setAttribute("href", url);
  a.setAttribute("download", filename);
  a.click();
}

// Imprimir reporte
function printReport() {
  window.print();
}

// ============================================
// TOUCH GESTURES PARA MÓVIL
// ============================================
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener(
  "touchstart",
  function (e) {
    touchStartX = e.changedTouches[0].screenX;
  },
  false
);

document.addEventListener(
  "touchend",
  function (e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
  },
  false
);

function handleSwipe() {
  const sidebar = document.querySelector(".sidebar");
  if (!sidebar || window.innerWidth > 768) return;

  const swipeDistance = touchEndX - touchStartX;

  // Swipe right to open (desde el borde izquierdo)
  if (swipeDistance > 100 && touchStartX < 50) {
    toggleSidebar();
  }

  // Swipe left to close (si está abierto)
  if (swipeDistance < -100 && sidebar.classList.contains("active")) {
    closeSidebar();
  }
}

// ============================================
// SMOOTH SCROLL
// ============================================
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

console.log("Sistema de Finanzas Personales v1.0.0 - Mobile Ready ✓");
