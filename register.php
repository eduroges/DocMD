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
$success_message = '';
$nome = '';
$email = '';

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Erro de CSRF: Token inválido. Por favor, tente novamente.";
    } else {
        // Regenerate CSRF token after successful validation to prevent reuse
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token']; // Update for the form if it's re-displayed

        // 2. Sanitize and Validate inputs
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $senha = $_POST['senha']; // Will be hashed, not sanitized as string
        $confirmar_senha = $_POST['confirmar_senha'];

        if (empty($nome)) {
            $errors[] = "O nome é obrigatório.";
        }
        if (empty($email)) {
            $errors[] = "O email é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de email inválido.";
        }
        if (empty($senha)) {
            $errors[] = "A senha é obrigatória.";
        } elseif (strlen($senha) < 8) {
            $errors[] = "A senha deve ter no mínimo 8 caracteres.";
        }
        if ($senha !== $confirmar_senha) {
            $errors[] = "As senhas não coincidem.";
        }

        // 3. Check if email already exists (if no other errors)
        if (empty($errors)) {
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();
                $email_count = $stmt->fetchColumn();

                if ($email_count > 0) {
                    $errors[] = "Este email já está cadastrado. Tente fazer login.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erro no banco de dados: " . $e->getMessage(); // Consider logging this instead of showing to user in production
            }
        }

        // 4. If all validations pass, hash password and insert user
        if (empty($errors)) {
            $hashed_password = password_hash($senha, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (nome, email, senha) VALUES (:nome, :email, :senha)");
                $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':senha', $hashed_password, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    $success_message = "Cadastro realizado com sucesso!";
                    // Clear form fields after successful registration
                    $nome = '';
                    $email = '';
                } else {
                    $errors[] = "Erro ao registrar usuário. Por favor, tente novamente.";
                }
            } catch (PDOException $e) {
                // Log the detailed error for the admin/developer
                error_log("PDOException in register.php: " . $e->getMessage());
                // Show a generic error to the user
                $errors[] = "Ocorreu um erro no servidor ao tentar registrar o usuário. Por favor, tente novamente mais tarde.";
                // Log the detailed error for the admin/developer
                error_log("PDOException in register.php (email check): " . $e->getMessage());
                // Show a generic error to the user
                $errors[] = "Ocorreu um erro no servidor ao verificar o email. Por favor, tente novamente mais tarde.";
            }
        }
    }
    // If there were errors, the form will be re-displayed with $errors and input values ($nome, $email)
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - DocMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin-top: 50px;
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
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Criar Conta</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <p><?php echo htmlspecialchars($success_message); ?></p>
                <p><a href="login.php">Clique aqui para fazer login</a></p>
            </div>
        <?php else: ?>
            <form id="registrationForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                    <div class="invalid-feedback">Por favor, insira seu nome.</div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback">Por favor, insira um email válido.</div>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                    <div class="invalid-feedback">A senha deve ter no mínimo 8 caracteres.</div>
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                    <div class="invalid-feedback">As senhas não coincidem.</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('registrationForm');
            const nomeInput = document.getElementById('nome');
            const emailInput = document.getElementById('email');
            const senhaInput = document.getElementById('senha');
            const confirmarSenhaInput = document.getElementById('confirmar_senha');

            form.addEventListener('submit', function (event) {
                let isValid = true;

                // Reset invalid states
                nomeInput.classList.remove('is-invalid');
                emailInput.classList.remove('is-invalid');
                senhaInput.classList.remove('is-invalid');
                confirmarSenhaInput.classList.remove('is-invalid');

                // Nome validation
                if (nomeInput.value.trim() === '') {
                    nomeInput.classList.add('is-invalid');
                    isValid = false;
                }

                // Email validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailInput.value.trim() === '' || !emailPattern.test(emailInput.value.trim())) {
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                }

                // Senha validation (min 8 characters)
                if (senhaInput.value.length < 8) {
                    senhaInput.classList.add('is-invalid');
                    document.querySelector('#senha + .invalid-feedback').textContent = 'A senha deve ter no mínimo 8 caracteres.';
                    isValid = false;
                }

                // Confirmar Senha validation
                if (confirmarSenhaInput.value === '') {
                    confirmarSenhaInput.classList.add('is-invalid');
                     document.querySelector('#confirmar_senha + .invalid-feedback').textContent = 'Por favor, confirme sua senha.';
                    isValid = false;
                } else if (senhaInput.value !== confirmarSenhaInput.value) {
                    confirmarSenhaInput.classList.add('is-invalid');
                    document.querySelector('#confirmar_senha + .invalid-feedback').textContent = 'As senhas não coincidem.';
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                // Bootstrap 5 needs this for form validation styles
                form.classList.add('was-validated');
            }, false);

            // Add real-time validation feedback for better UX (optional, but good)
            [nomeInput, emailInput, senhaInput, confirmarSenhaInput].forEach(input => {
                input.addEventListener('input', function() {
                    if (input.classList.contains('is-invalid')) {
                        // Basic check to remove error state on input, specific checks are on submit
                        if (input.value.trim() !== '') {
                             // For confirm password, also check if it matches password
                            if (input.id === 'confirmar_senha') {
                                if (input.value === senhaInput.value) {
                                    input.classList.remove('is-invalid');
                                }
                            } else if (input.id === 'senha') {
                                if (input.value.length >= 8) {
                                   input.classList.remove('is-invalid');
                                }
                                // also check confirm password if it was marked as mismatch
                                if(confirmarSenhaInput.classList.contains('is-invalid') && confirmarSenhaInput.value === input.value){
                                    confirmarSenhaInput.classList.remove('is-invalid');
                                }
                            }
                            else if (input.id === 'email') {
                                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                if (emailPattern.test(input.value.trim())) {
                                    input.classList.remove('is-invalid');
                                }
                            }
                             else {
                                input.classList.remove('is-invalid');
                            }
                        }
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
