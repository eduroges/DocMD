<?php
session_start();
// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Include database configuration
require_once 'config.php';

$errors = [];
$email = ''; // To re-populate the email field on error

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Erro de CSRF: Token inválido. Por favor, tente novamente.";
    } else {
        // Regenerate CSRF token for the next request (even if login fails, good practice)
        // Store it in a new variable for the current form display if there's an error
        $new_csrf_token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $new_csrf_token;


        // 2. Sanitize and Validate inputs
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $senha = $_POST['senha']; // Password will be verified, not sanitized as string

        if (empty($email)) {
            $errors[] = "O email é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de email inválido.";
        }
        if (empty($senha)) {
            $errors[] = "A senha é obrigatória.";
        }

        // 3. If inputs are valid, attempt to log in user
        if (empty($errors)) {
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("SELECT id, nome, email, senha, perfil FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($senha, $user['senha'])) {
                    // Password is correct, start session
                    session_regenerate_id(true); // Regenerate session ID
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nome'] = $user['nome'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_perfil'] = $user['perfil'];
                    
                    // Regenerate CSRF token after successful login and session regeneration
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


                    header("Location: dashboard.php");
                    exit;
                } else {
                    // Invalid email or password
                    $errors[] = "Email ou senha inválidos.";
                }
            } catch (PDOException $e) {
                // $errors[] = "Erro no banco de dados: " . $e->getMessage(); // Log this in production
                $errors[] = "Ocorreu um erro. Por favor, tente novamente mais tarde."; // User-friendly message
                // Log the actual error: error_log("Database error during login: " . $e->getMessage());
            }
        }
    }
    // If there are errors, the $csrf_token for the form needs to be the newly generated one
    // if one was generated due to a POST request.
    if(isset($new_csrf_token)){
        $csrf_token = $new_csrf_token;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DocMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 450px;
            margin-top: 100px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .alert {
            margin-top: 20px;
        }
        .text-center a {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <div class="invalid-feedback">Por favor, insira um email válido.</div>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
                <div class="invalid-feedback">Por favor, insira sua senha.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
        <p class="text-center mt-3">
            Não tem uma conta? <a href="register.php">Cadastre-se aqui</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const senhaInput = document.getElementById('senha');

            form.addEventListener('submit', function (event) {
                let isValid = true;

                // Reset invalid states
                emailInput.classList.remove('is-invalid');
                senhaInput.classList.remove('is-invalid');

                // Email validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInput.value.trim() === '' || !emailPattern.test(emailInput.value.trim())) {
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                }

                // Senha validation (required)
                if (senhaInput.value.trim() === '') {
                    senhaInput.classList.add('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                // Bootstrap 5 needs this for form validation styles
                form.classList.add('was-validated');
            }, false);

            // Add real-time validation feedback (optional, but good for UX)
            [emailInput, senhaInput].forEach(input => {
                input.addEventListener('input', function() {
                    // This is a simplified version for real-time, actual validation happens on submit
                    if (input.value.trim() !== '') {
                        if (input.id === 'email') {
                            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (emailPattern.test(input.value.trim())) {
                                input.classList.remove('is-invalid');
                                input.classList.add('is-valid');
                            } else {
                                input.classList.add('is-invalid');
                                input.classList.remove('is-valid');
                            }
                        } else { // senha
                             input.classList.remove('is-invalid');
                             input.classList.add('is-valid');
                        }
                    } else {
                        input.classList.add('is-invalid');
                        input.classList.remove('is-valid');
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
