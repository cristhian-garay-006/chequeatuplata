<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS deudas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        acreedor VARCHAR(100) NOT NULL,
        descripcion VARCHAR(255),
        monto_total DECIMAL(10, 2) NOT NULL,
        monto_pendiente DECIMAL(10, 2) NOT NULL,
        fecha_inicio DATE NOT NULL,
        frecuencia ENUM('mensual', 'quincenal', 'semanal', 'diario') NOT NULL,
        proximo_pago DATE NOT NULL,
        estado ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Table 'deudas' created successfully.";

} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
