<?php
// admin/crear_admin.php
// Sube este archivo a Hostinger y visítalo en el navegador para crear el usuario.
require_once __DIR__ . '/config.php';

try {
    // 1. Cambiar la columna 'email' a 'username' si todavía existe
    try {
        $pdo->exec("ALTER TABLE usuarios CHANGE email username VARCHAR(255) NOT NULL UNIQUE");
        echo "Columna email cambiada a username exitosamente.<br>";
    } catch (PDOException $e) {
        // Ignorar si la columna ya fue cambiada
    }

    // 2. Crear el hash seguro de la contraseña
    $username = 'admin';
    $password = '2026#Martineau!Admin';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 3. Insertar o actualizar el usuario
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (username, password_hash) 
        VALUES (:username, :hash)
        ON DUPLICATE KEY UPDATE password_hash = :hash2
    ");
    
    $stmt->execute([
        'username' => $username,
        'hash' => $hash,
        'hash2' => $hash
    ]);

    echo "<h3>¡Usuario administrador configurado con éxito!</h3>";
    echo "<b>Usuario:</b> " . htmlspecialchars($username) . "<br>";
    echo "<b>Contraseña:</b> (la que elegiste)<br><br>";
    echo "<a href='login.php'>Ir al Login</a>";

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage();
}
