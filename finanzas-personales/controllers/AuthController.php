<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    
    public function login($email, $password) {
        $usuario = new Usuario();
        $result = $usuario->login($email, $password);
        
        if ($result) {
            setUserSession($result['id'], $result['nombre'], $result['email']);
            return ['success' => true, 'message' => 'Inicio de sesión exitoso'];
        }
        
        return ['success' => false, 'message' => 'Credenciales incorrectas'];
    }
    
    public function register($nombre, $email, $password, $password_confirm) {
        // Validaciones
        if (empty($nombre) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        if ($password !== $password_confirm) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }
        
        $usuario = new Usuario();
        
        // Verificar si el email ya existe
        if ($usuario->emailExists($email)) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Registrar usuario
        $usuario->nombre = $nombre;
        $usuario->email = $email;
        $usuario->password = $password;
        
        $user_id = $usuario->register();
        
        if ($user_id) {
            // Crear categorías por defecto
            $this->createDefaultCategories($user_id);
            
            setUserSession($user_id, $nombre, $email);
            return ['success' => true, 'message' => 'Registro exitoso'];
        }
        
        return ['success' => false, 'message' => 'Error al registrar usuario'];
    }
    
    public function logout() {
        destroySession();
        redirect('public/login.php');
    }
    
    private function createDefaultCategories($usuario_id) {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        // Categorías de ingreso
        $categorias_ingreso = ['Sueldo', 'Otros Ingresos', 'Bonos', 'Freelance'];
        foreach ($categorias_ingreso as $cat) {
            $query = "INSERT INTO categorias_ingreso (usuario_id, nombre) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$usuario_id, $cat]);
        }
        
        // Categorías de egreso
        $categorias_egreso = [
            ['Ahorro', 'fijo'],
            ['Cuota', 'fijo'],
            ['Salidas/Caprichos', 'variable'],
            ['Alquiler', 'fijo'],
            ['Peajes', 'variable'],
            ['Alimentación', 'variable'],
            ['Servicios', 'fijo'],
            ['Internet', 'fijo'],
            ['Celular', 'fijo'],
            ['Streaming', 'fijo']
        ];
        
        foreach ($categorias_egreso as $cat) {
            $query = "INSERT INTO categorias_egreso (usuario_id, nombre, tipo) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$usuario_id, $cat[0], $cat[1]]);
        }
    }
}
?>