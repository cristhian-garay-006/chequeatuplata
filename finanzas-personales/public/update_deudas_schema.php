<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Add categoria_id
    $sql1 = "ALTER TABLE deudas ADD COLUMN categoria_id INT NULL AFTER usuario_id";
    try {
        $db->exec($sql1);
        echo "Column 'categoria_id' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'categoria_id' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 2. Add foreign key for categoria_id
    // Assuming 'categorias_egreso' table exists and has 'id'
    $sqlFK = "ALTER TABLE deudas ADD CONSTRAINT fk_deuda_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_egreso(id) ON DELETE SET NULL";
    try {
        $db->exec($sqlFK);
        echo "Foreign key for 'categoria_id' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Foreign key might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 3. Add monto_cuota
    $sql2 = "ALTER TABLE deudas ADD COLUMN monto_cuota DECIMAL(10, 2) NULL AFTER monto_total";
    try {
        $db->exec($sql2);
        echo "Column 'monto_cuota' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'monto_cuota' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 4. Modify frecuencia ENUM
    $sql3 = "ALTER TABLE deudas MODIFY COLUMN frecuencia ENUM('diario', 'semanal', 'quincenal', 'mensual', 'trimestral', 'anual') NOT NULL";
    try {
        $db->exec($sql3);
        echo "Column 'frecuencia' updated successfully.<br>";
    } catch (PDOException $e) {
        echo "Error updating 'frecuencia': " . $e->getMessage() . "<br>";
    }

} catch(PDOException $e) {
    echo "General Error: " . $e->getMessage();
}
?>
