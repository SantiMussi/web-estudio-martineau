<?php

require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

const LOGIN_MAX_INTENTOS = 8;
const LOGIN_BLOQUEO_MINUTOS = 10;

// Combina IP + usuario para no poder bloquear a otra persona solo con su nombre de usuario.
function rate_limit_clave(string $username): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    return $ip . '|' . mb_strtolower(trim($username));
}

function rate_limit_asegurar_tabla(PDO $pdo): void {
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS intentos_login (
            clave VARCHAR(191) NOT NULL PRIMARY KEY,
            intentos INT NOT NULL DEFAULT 0,
            bloqueado_hasta DATETIME NULL,
            actualizado_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');
}

// Segundos restantes de bloqueo, o 0 si se puede intentar. Si algo falla acá, no bloqueamos el login real.
function rate_limit_bloqueado(PDO $pdo, string $clave): int {
    try {
        rate_limit_asegurar_tabla($pdo);
        $stmt = $pdo->prepare('SELECT bloqueado_hasta FROM intentos_login WHERE clave = :clave');
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch();
        if ($row && $row['bloqueado_hasta']) {
            $restante = strtotime($row['bloqueado_hasta']) - time();
            return $restante > 0 ? $restante : 0;
        }
    } catch (Exception $e) {
        error_log('[rate_limit] Error verificando bloqueo: ' . $e->getMessage());
    }
    return 0;
}

function rate_limit_registrar_fallo(PDO $pdo, string $clave): void {
    try {
        rate_limit_asegurar_tabla($pdo);
        $stmt = $pdo->prepare('SELECT intentos FROM intentos_login WHERE clave = :clave');
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch();
        $intentos = ($row['intentos'] ?? 0) + 1;

        $bloqueado_hasta = $intentos >= LOGIN_MAX_INTENTOS
            ? date('Y-m-d H:i:s', time() + LOGIN_BLOQUEO_MINUTOS * 60)
            : null;

        $stmt = $pdo->prepare('
            INSERT INTO intentos_login (clave, intentos, bloqueado_hasta, actualizado_at)
            VALUES (:clave, :intentos, :bloqueado_hasta, NOW())
            ON DUPLICATE KEY UPDATE intentos = :intentos2, bloqueado_hasta = :bloqueado_hasta2, actualizado_at = NOW()
        ');
        $stmt->execute([
            'clave' => $clave,
            'intentos' => $intentos,
            'bloqueado_hasta' => $bloqueado_hasta,
            'intentos2' => $intentos,
            'bloqueado_hasta2' => $bloqueado_hasta,
        ]);
    } catch (Exception $e) {
        error_log('[rate_limit] Error registrando fallo: ' . $e->getMessage());
    }
}

function rate_limit_resetear(PDO $pdo, string $clave): void {
    try {
        $stmt = $pdo->prepare('DELETE FROM intentos_login WHERE clave = :clave');
        $stmt->execute(['clave' => $clave]);
    } catch (Exception $e) {
        error_log('[rate_limit] Error reseteando: ' . $e->getMessage());
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rate_clave = rate_limit_clave($username);
    $segundos_bloqueo = rate_limit_bloqueado($pdo, $rate_clave);

    if ($segundos_bloqueo > 0) {
        $minutos = (int) ceil($segundos_bloqueo / 60);
        $error = 'Demasiados intentos fallidos. Probá de nuevo en ' . $minutos . ' minuto' . ($minutos === 1 ? '' : 's') . '.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Complete todos los campos.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM usuarios WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            rate_limit_resetear($pdo, $rate_clave);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            header('Location: index.php');
            exit();
        } else {
            rate_limit_registrar_fallo($pdo, $rate_clave);
            // Mensaje genérico para no revelar si el email existe
            $error = 'Credenciales incorrectas.';
        }
    }
} elseif (isset($_GET['timeout'])) {
    $error = 'Tu sesión expiró por inactividad. Iniciá sesión de nuevo.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Martineau Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Bodoni Moda', 'Didot', 'Bodoni MT', Georgia, 'Times New Roman', serif;
            background: #E1DED7;
            background-image: radial-gradient(circle at top right, #F1EFEA 0%, #E1DED7 100%);
            color: #38322A;
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
            font-family: 'Bodoni Moda', 'Didot', 'Bodoni MT', Georgia, 'Times New Roman', serif;
            font-weight: 300;
            font-size: 2.5rem;
            text-align: center;
            letter-spacing: 0.15em;
            margin-bottom: 0.5rem;
            color: #38322A;
        }

        .login-subtitle {
            text-align: center;
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #6E6150;
            margin-bottom: 3rem;
        }

        .login-card {
            background: #F3F1EC;
            border: 1px solid #CBC4B6;
            border-radius: 12px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 50px rgba(56, 48, 38, 0.12);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #6E6150;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: #EFEDE8;
            border: 1px solid #CBC4B6;
            border-radius: 8px;
            color: #38322A;
            font-family: 'Bodoni Moda', 'Didot', 'Bodoni MT', Georgia, 'Times New Roman', serif;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #6E6150;
        }

        .form-group input::placeholder {
            color: #8A7C6C;
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #897B6B 0%, #574B3C 100%);
            border: none;
            border-radius: 8px;
            color: #F4F1ED;
            font-family: 'Bodoni Moda', 'Didot', 'Bodoni MT', Georgia, 'Times New Roman', serif;
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
            background: rgba(180, 69, 60, 0.12);
            border: 1px solid rgba(180, 69, 60, 0.3);
            color: #B4453C;
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
            color: #8A7C6C;
        }

        .login-footer a {
            color: #6E6150;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">Martineau</div>
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
