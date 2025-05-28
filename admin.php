<?php
require_once 'auth.php'; // Ensures user is authenticated

// Authorization: Only admins can access this page
if ($_SESSION['user_perfil'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Include database configuration
require_once 'config.php';

// Initialize variables
$errors = [];
$success_message = '';
$users = []; // To store the list of users

// CSRF Token Generation (will be used for forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Handle POST requests for Add/Edit/Delete ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Erro de CSRF: Token inválido. Por favor, tente novamente.";
    } else {
        // Regenerate CSRF token after successful validation to prevent reuse for the next request
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token']; // Update for the current page view

        $action = $_POST['action'] ?? '';

        // --- Add User ---
        if ($action === 'add_user') {
            $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $senha = $_POST['senha']; // Will be hashed
            $perfil = $_POST['perfil'] === 'admin' ? 'admin' : 'usuario'; // Validate profile

            if (empty($nome)) $errors[] = "O nome é obrigatório.";
            if (empty($email)) $errors[] = "O email é obrigatório.";
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de email inválido.";
            if (empty($senha)) $errors[] = "A senha é obrigatória.";
            elseif (strlen($senha) < 8) $errors[] = "A senha deve ter no mínimo 8 caracteres.";

            if (empty($errors)) {
                try {
                    $pdo_crud = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $pdo_crud->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Check if email already exists
                    $stmt_check_email = $pdo_crud->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                    $stmt_check_email->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt_check_email->execute();
                    if ($stmt_check_email->fetchColumn() > 0) {
                        $errors[] = "Este email já está cadastrado.";
                    } else {
                        $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt_insert = $pdo_crud->prepare("INSERT INTO users (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)");
                        $stmt_insert->bindParam(':nome', $nome, PDO::PARAM_STR);
                        $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
                        $stmt_insert->bindParam(':senha', $hashed_password, PDO::PARAM_STR);
                        $stmt_insert->bindParam(':perfil', $perfil, PDO::PARAM_STR);

                        if ($stmt_insert->execute()) {
                            $success_message = "Usuário adicionado com sucesso!";
                            // Users list will be re-fetched below, so no need to manually add to $users array here
                        } else {
                            $errors[] = "Erro ao adicionar usuário.";
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erro no banco de dados (adição): " . $e->getMessage(); // Log this
                }
            }
        }
        // --- End Add User ---

        // --- Edit User ---
        elseif ($action === 'edit_user') {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $perfil = $_POST['perfil'] === 'admin' ? 'admin' : 'usuario';
            $senha = $_POST['senha']; // Optional, will be hashed if provided

            if (!$user_id) $errors[] = "ID de usuário inválido para edição.";
            if (empty($nome)) $errors[] = "O nome é obrigatório (edição).";
            if (empty($email)) $errors[] = "O email é obrigatório (edição).";
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de email inválido (edição).";
            if (!empty($senha) && strlen($senha) < 8) $errors[] = "A nova senha deve ter no mínimo 8 caracteres.";
            
            // Prevent admin from changing their own profile to 'usuario' if they are the only admin
            // This is a simplified check. A more robust check would count total admins.
            if ($user_id === $_SESSION['user_id'] && $perfil === 'usuario') {
                 try {
                    $pdo_check_admin = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $pdo_check_admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $stmt_count_admins = $pdo_check_admin->prepare("SELECT COUNT(*) FROM users WHERE perfil = 'admin'");
                    $stmt_count_admins->execute();
                    $admin_count = $stmt_count_admins->fetchColumn();
                    if ($admin_count <= 1) {
                        $errors[] = "Você não pode alterar seu próprio perfil para 'usuário' pois é o único administrador.";
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erro ao verificar contagem de administradores: " . $e->getMessage();
                }
            }


            if (empty($errors)) {
                try {
                    $pdo_crud = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $pdo_crud->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Check if new email conflicts with another user
                    $stmt_check_email = $pdo_crud->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
                    $stmt_check_email->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt_check_email->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt_check_email->execute();
                    if ($stmt_check_email->fetchColumn()) {
                        $errors[] = "O novo email fornecido já está em uso por outro usuário.";
                    } else {
                        $sql_update = "UPDATE users SET nome = :nome, email = :email, perfil = :perfil";
                        if (!empty($senha)) {
                            $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
                            $sql_update .= ", senha = :senha";
                        }
                        $sql_update .= " WHERE id = :user_id";

                        $stmt_update = $pdo_crud->prepare($sql_update);
                        $stmt_update->bindParam(':nome', $nome, PDO::PARAM_STR);
                        $stmt_update->bindParam(':email', $email, PDO::PARAM_STR);
                        $stmt_update->bindParam(':perfil', $perfil, PDO::PARAM_STR);
                        $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        if (!empty($senha)) {
                            $stmt_update->bindParam(':senha', $hashed_password, PDO::PARAM_STR);
                        }

                        if ($stmt_update->execute()) {
                            $success_message = "Usuário (ID: $user_id) atualizado com sucesso!";
                             // If admin updated their own name, update session
                            if ($user_id === $_SESSION['user_id']) {
                                $_SESSION['user_nome'] = $nome;
                            }
                        } else {
                            $errors[] = "Erro ao atualizar usuário (ID: $user_id).";
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erro no banco de dados (edição): " . $e->getMessage(); // Log this
                }
            }
        }
        // --- End Edit User ---

        // --- Delete User ---
        elseif ($action === 'delete_user') {
            $user_id_to_delete = filter_input(INPUT_POST, 'delete_user_id', FILTER_VALIDATE_INT);

            if (!$user_id_to_delete) {
                $errors[] = "ID de usuário inválido para deleção.";
            } elseif ($user_id_to_delete === $_SESSION['user_id']) {
                $errors[] = "Você não pode deletar sua própria conta de administrador.";
            } else {
                try {
                    $pdo_crud = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $pdo_crud->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $stmt_delete = $pdo_crud->prepare("DELETE FROM users WHERE id = :user_id");
                    $stmt_delete->bindParam(':user_id', $user_id_to_delete, PDO::PARAM_INT);

                    if ($stmt_delete->execute()) {
                        if ($stmt_delete->rowCount() > 0) {
                            $success_message = "Usuário (ID: $user_id_to_delete) deletado com sucesso!";
                        } else {
                            $errors[] = "Usuário (ID: $user_id_to_delete) não encontrado ou já deletado.";
                        }
                    } else {
                        $errors[] = "Erro ao deletar usuário (ID: $user_id_to_delete).";
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erro no banco de dados (deleção): " . $e->getMessage(); // Log this
                }
            }
        }
        // --- End Delete User ---
    }
}
// --- End Handle POST requests ---


// --- Fetch users from database (Read operation) ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_users = $pdo->query("SELECT id, nome, email, perfil, data_criacao FROM users ORDER BY id ASC");
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "Erro ao buscar usuários: " . $e->getMessage(); // Log this in production
    // For user display, might be better to show a generic error and log the detailed one.
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - DocMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
            padding-top: 20px; /* Space for navbar */
        }
        .navbar {
            margin-bottom: 20px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 10px 0;
            text-align: center;
            margin-top: auto;
        }
        .table-actions form {
            display: inline-block;
            margin-right: 5px;
        }
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px; /* For smaller screens */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">DocMD Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                        Usuário: <?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Admin'); ?> (Admin)
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container main-content">
        <h2 class="mb-4">Gerenciamento de Usuários</h2>

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
            </div>
        <?php endif; ?>

        <!-- Add New User Button/Link (triggers modal) -->
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Adicionar Novo Usuário
            </button>
        </div>

        <!-- User List Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Perfil</th>
                        <th>Data Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users) && empty($errors)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php elseif (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['perfil'])); ?></td>
                                <td><?php echo htmlspecialchars(date("d/m/Y H:i:s", strtotime($user['data_criacao']))); ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-warning edit-user-btn" 
                                            data-id="<?php echo $user['id']; ?>"
                                            data-nome="<?php echo htmlspecialchars($user['nome']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-perfil="<?php echo htmlspecialchars($user['perfil']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#editUserModal">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="action" value="delete_user" class="btn btn-sm btn-danger" 
                                                onclick="return confirmDelete('<?php echo $user['id']; ?>', '<?php echo $_SESSION['user_id']; ?>');">
                                            <i class="fas fa-trash-alt"></i> Deletar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($errors) && empty($users)): // Show error if users couldn't be fetched ?>
                        <tr>
                             <td colspan="6" class="text-center text-danger">Erro ao carregar usuários. Verifique os logs.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Adicionar Novo Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addUserForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="add_user">
                            <div class="form-group">
                                <label for="add_nome">Nome Completo</label>
                                <input type="text" class="form-control" id="add_nome" name="nome" required>
                            </div>
                            <div class="form-group">
                                <label for="add_email">Email</label>
                                <input type="email" class="form-control" id="add_email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="add_senha">Senha</label>
                                <input type="password" class="form-control" id="add_senha" name="senha" required minlength="8">
                                <small class="form-text text-muted">Mínimo 8 caracteres.</small>
                            </div>
                            <div class="form-group">
                                <label for="add_perfil">Perfil</label>
                                <select class="form-control" id="add_perfil" name="perfil" required>
                                    <option value="usuario" selected>Usuário</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" form="addUserForm" class="btn btn-primary">Salvar Usuário</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Editar Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editUserForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="edit_user">
                            <input type="hidden" id="edit_user_id" name="user_id">
                            <div class="form-group">
                                <label for="edit_nome">Nome Completo</label>
                                <input type="text" class="form-control" id="edit_nome" name="nome" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_senha">Nova Senha (opcional)</label>
                                <input type="password" class="form-control" id="edit_senha" name="senha" minlength="8">
                                <small class="form-text text-muted">Deixe em branco para não alterar. Mínimo 8 caracteres se preenchido.</small>
                            </div>
                            <div class="form-group">
                                <label for="edit_perfil">Perfil</label>
                                <select class="form-control" id="edit_perfil" name="perfil" required>
                                    <option value="usuario">Usuário</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" form="editUserForm" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <footer class="footer">
        <div class="container">
            <span>&copy; <?php echo date("Y"); ?> DocMD Admin Panel - Todos os direitos reservados.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript for handling modals, edit pre-fill, and delete confirmation.
        // No jQuery needed for Bootstrap 5 modals, but if other jQuery dependent code exists, it might need review.
        // The modal pre-fill can be done with vanilla JS.

        // Pre-fill edit user modal
        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget; // Button that triggered the modal
                const userId = button.getAttribute('data-id');
                const nome = button.getAttribute('data-nome');
                const email = button.getAttribute('data-email');
                const perfil = button.getAttribute('data-perfil');

                const modalUserId = editUserModal.querySelector('#edit_user_id');
                const modalNome = editUserModal.querySelector('#edit_nome');
                const modalEmail = editUserModal.querySelector('#edit_email');
                const modalPerfil = editUserModal.querySelector('#edit_perfil');
                const modalSenha = editUserModal.querySelector('#edit_senha');

                modalUserId.value = userId;
                modalNome.value = nome;
                modalEmail.value = email;
                modalPerfil.value = perfil;
                modalSenha.value = ''; // Clear password field
            });

            editUserModal.addEventListener('hidden.bs.modal', event => {
                editUserModal.querySelector('form').reset();
                editUserModal.querySelector('#edit_senha').value = '';
            });
        }

        // Clear add user modal on hide
        const addUserModal = document.getElementById('addUserModal');
        if (addUserModal) {
            addUserModal.addEventListener('hidden.bs.modal', event => {
                addUserModal.querySelector('form').reset();
            });
        }
        
        function confirmDelete(userId, currentAdminId) {
            // Convert to string for comparison, as session ID might be int and data attribute string
            if (String(userId) === String(currentAdminId)) { 
                alert('Você não pode deletar sua própria conta de administrador.');
                return false;
            }
            return confirm('Tem certeza que deseja deletar este usuário (ID: ' + userId + ')? Esta ação não pode ser desfeita.');
        }
    </script>
</body>
</html>
