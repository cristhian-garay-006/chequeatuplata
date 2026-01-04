<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$page_title = 'Soporte Técnico';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-headset" style="color: var(--primary-color);"></i> Soporte & Ayuda</h1>
</div>

<div class="dashboard-section text-center" style="margin-bottom: 30px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
    <h2 style="color: var(--primary-color);">¿Necesitas ayuda con el sistema?</h2>
    <p style="color: var(--text-secondary); max-width: 600px; margin: 10px auto;">
        Estamos aquí para asistirte. Si tienes algún problema técnico, sugerencia o consulta, no dudes en comunicarte con nuestro equipo de desarrollo a través de cualquiera de estos canales.
    </p>
</div>

<div class="dashboard-grid">
    
    <!-- Teléfono / WhatsApp -->
    <div class="dashboard-section text-center metric-card" style="border-top: 4px solid #25D366;">
        <div style="width: 60px; height: 60px; background: rgba(37, 211, 102, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #25D366; font-size: 1.8em;">
            <i class="fab fa-whatsapp"></i>
        </div>
        <h3>WhatsApp / Celular</h3>
        <p style="font-size: 1.2em; font-weight: bold; margin: 10px 0;">901 115 993</p>
        <a href="https://wa.me/51901115993" target="_blank" class="btn btn-sm btn-outline-success" style="border:1px solid #25d366; color:#25d366; background:transparent;">
            <i class="fab fa-whatsapp"></i> Enviar Mensaje
        </a>
    </div>

    <!-- Correo -->
    <div class="dashboard-section text-center metric-card" style="border-top: 4px solid #EA4335;">
        <div style="width: 60px; height: 60px; background: rgba(234, 67, 53, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #EA4335; font-size: 1.8em;">
            <i class="fas fa-envelope"></i>
        </div>
        <h3>Correo Electrónico</h3>
        <p style="font-size: 0.9em; font-weight: bold; margin: 10px 0; word-break: break-all;">cristhiangarayubillus2006@gmail.com</p>
        <a href="mailto:cristhiangarayubillus2006@gmail.com" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-envelope-open-text"></i> Enviar Correo
        </a>
    </div>

    <!-- Facebook -->
    <div class="dashboard-section text-center metric-card" style="border-top: 4px solid #1877F2;">
        <div style="width: 60px; height: 60px; background: rgba(24, 119, 242, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #1877F2; font-size: 1.8em;">
            <i class="fab fa-facebook-f"></i>
        </div>
        <h3>Facebook</h3>
        <p style="font-size: 1.1em; font-weight: bold; margin: 10px 0;">Cristhian Garay Ubillus</p>
        <a href="https://www.facebook.com/public/Cristhian-Garay-Ubillus" target="_blank" class="btn btn-sm btn-primary" style="background:#1877F2;">
            <i class="fab fa-facebook"></i> Ver Perfil
        </a>
    </div>

    <!-- Instagram -->
    <div class="dashboard-section text-center metric-card" style="border-top: 4px solid #E1306C;">
        <div style="width: 60px; height: 60px; background: rgba(225, 48, 108, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #E1306C; font-size: 1.8em;">
            <i class="fab fa-instagram"></i>
        </div>
        <h3>Instagram</h3>
        <p style="font-size: 1.1em; font-weight: bold; margin: 10px 0;">@cristhian_garay20_26</p>
        <a href="https://www.instagram.com/cristhian_garay20_26" target="_blank" class="btn btn-sm" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white;">
            <i class="fab fa-instagram"></i> Seguir
        </a>
    </div>

</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
