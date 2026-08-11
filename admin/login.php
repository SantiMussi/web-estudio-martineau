<?php
/**
 * login.php — Formulario de Login del Panel Admin
 * 
 * @security 
 * - Protección CSRF en formulario POST
 * - password_verify() contra bcrypt hash
 * - session_regenerate_id(true) tras autenticación exitosa
 * - No expone si el email existe o no (mensaje genérico)
 */

require_once __DIR__ . '/config.php';

// Si ya está autenticado, redirigir al panel
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// ─── Procesar Login ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Complete todos los campos.';
    } else {
        // Buscar usuario por username con prepared statement
        $stmt = $pdo->prepare('SELECT id, password_hash FROM usuarios WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Autenticación exitosa: regenerar ID de sesión (previene session fixation)
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            header('Location: index.php');
            exit();
        } else {
            // Mensaje genérico para no revelar si el email existe
            $error = 'Credenciales incorrectas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MartinEau Admin</title>

    <!-- Fuentes (mismas del sitio) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            background-image: radial-gradient(circle at top right, #1a1a1a 0%, #0a0a0a 100%);
            color: #F4F1ED;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }

        .login-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 2.5rem;
            text-align: center;
            letter-spacing: 0.15em;
            margin-bottom: 0.5rem;
            color: #F4F1ED;
        }

        .login-subtitle {
            text-align: center;
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #C28B55;
            margin-bottom: 3rem;
        }

        .login-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid #2B2A28;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            backdrop-filter: blur(20px);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9E9890;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: #0a0a0a;
            border: 1px solid #2B2A28;
            border-radius: 8px;
            color: #F4F1ED;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #C28B55;
        }

        .form-group input::placeholder {
            color: #524E48;
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #C28B55 0%, #916135 100%);
            border: none;
            border-radius: 8px;
            color: #0a0a0a;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.3s ease, transform 0.2s ease;
        }

        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-msg {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b7a;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #524E48;
        }

        .login-footer a {
            color: #C28B55;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">MartinEau</div>
        <p class="login-subtitle">Panel de Administración</p>

        <div class="login-card">
            <?php if ($error): ?>
                <div class="error-msg"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" placeholder="admin" 
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-login">Ingresar</button>
            </form>
        </div>

        <p class="login-footer">
            <a href="../">← Volver al sitio</a>
        </p>
    </div>
</body>
</html>
