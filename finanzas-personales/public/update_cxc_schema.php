<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Add categoria_id
    // Note: Linking to categorias_ingreso for receivables
    $sql1 = "ALTER TABLE cuentas_por_cobrar ADD COLUMN categoria_id INT NULL AFTER usuario_id";
    try {
        $db->exec($sql1);
        echo "Column 'categoria_id' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'categoria_id' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // FK to categories_ingreso
    // Assuming 'categorias_ingreso' table exists and has 'id'
    $sqlFK = "ALTER TABLE cuentas_por_cobrar ADD CONSTRAINT fk_cxc_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_ingreso(id) ON DELETE SET NULL";
    try {
        $db->exec($sqlFK);
        echo "Foreign key for 'categoria_id' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Foreign key might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 2. Add monto_cuota
    $sql2 = "ALTER TABLE cuentas_por_cobrar ADD COLUMN monto_cuota DECIMAL(10, 2) NULL AFTER deuda_total";
    try {
        $db->exec($sql2);
        echo "Column 'monto_cuota' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'monto_cuota' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 3. Add frecuencia
    $sql3 = "ALTER TABLE cuentas_por_cobrar ADD COLUMN frecuencia ENUM('diario', 'semanal', 'quincenal', 'mensual', 'trimestral', 'anual') DEFAULT 'mensual' AFTER monto_cuota";
    try {
        $db->exec($sql3);
        echo "Column 'frecuencia' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'frecuencia' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 4. Add proximo_pago
    $sql4 = "ALTER TABLE cuentas_por_cobrar ADD COLUMN proximo_pago DATE NULL AFTER frecuencia";
    try {
        $db->exec($sql4);
        echo "Column 'proximo_pago' added successfully.<br>";
    } catch (PDOException $e) {
        echo "Column 'proximo_pago' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 5. Rename/Adjust 'fecha' to 'fecha_inicio' logic? 
    // Existing 'fecha' is likely the start date. We can keep it or alias it.
    // 'saldo' exists. 'estado' exists.
    
    // Optional: Drop old quota columns if desired, but safer to keep for now.
    // echo "Migration finished.";

} catch(PDOException $e) {
    echo "General Error: " . $e->getMessage();
}
?>
