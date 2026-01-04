    <!-- End Content Wrapper -->
    </div>
    
    <footer class="main-footer">
        <small>Desarrollado por <strong>Cristhian Garay</strong></small>
    </footer>

    </main>
    </div>

    <!-- Floating Action Button for Support -->
    <div class="fab-container">
        <div class="fab-actions" id="fabActions">
            <!-- WhatsApp -->
            <a href="https://wa.me/51901115993" target="_blank" class="fab-action fab-whatsapp" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <!-- Email -->
            <a href="mailto:cristhiangarayubillus2006@gmail.com" class="fab-action fab-email" title="Correo">
                <i class="fas fa-envelope"></i>
            </a>
            <!-- Facebook -->
            <a href="https://www.facebook.com/public/Cristhian-Garay-Ubillus" target="_blank" class="fab-action fab-facebook" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <!-- Instagram -->
            <a href="https://www.instagram.com/cristhian_garay20_26" target="_blank" class="fab-action fab-instagram" title="Instagram">
                <i class="fab fa-instagram"></i>
            </a>
        </div>
        <button class="fab-main" onclick="toggleFab()" id="fabMainBtn">
            <i class="fas fa-headset"></i>
        </button>
    </div>

    <script>
        function toggleFab() {
            const actions = document.getElementById('fabActions');
            
            if (actions.classList.contains('active')) {
                actions.classList.remove('active');
            } else {
                actions.classList.add('active');
            }
        }
    </script>
    
    <script src="js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>