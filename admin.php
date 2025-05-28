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
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Erro de CSRF: Token inválido. Por favor, tente novamente.";
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate after use
        $current_page_csrf_token = $_SESSION['csrf_token']; // Use this for forms on this page load

        $action = $_POST['action'] ?? '';
        $pdo_crud = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo_crud->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            if ($action === 'add_user') {
                $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
                $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
                $senha = $_POST['senha'];
                $perfil = $_POST['perfil'] === 'admin' ? 'admin' : 'usuario';

                if (empty($nome)) $errors[] = "O nome é obrigatório.";
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido ou ausente.";
                if (empty($senha) || strlen($senha) < 8) $errors[] = "A senha deve ter no mínimo 8 caracteres.";

                if (empty($errors)) {
                    $stmt_check_email = $pdo_crud->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                    $stmt_check_email->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt_check_email->execute();
                    if ($stmt_check_email->fetchColumn() > 0) {
                        $errors[] = "Este email já está cadastrado.";
                    } else {
                        $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt_insert = $pdo_crud->prepare("INSERT INTO users (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)");
                        $stmt_insert->execute([':nome' => $nome, ':email' => $email, ':senha' => $hashed_password, ':perfil' => $perfil]);
                        $success_message = "Usuário adicionado com sucesso!";
                    }
                }
            } elseif ($action === 'edit_user') {
                $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
                $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
                $perfil = $_POST['perfil'] === 'admin' ? 'admin' : 'usuario';
                $senha = $_POST['senha'];

                if (!$user_id) $errors[] = "ID de usuário inválido.";
                if (empty($nome)) $errors[] = "O nome é obrigatório.";
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido ou ausente.";
                if (!empty($senha) && strlen($senha) < 8) $errors[] = "A nova senha deve ter no mínimo 8 caracteres.";
                
                if ($user_id === $_SESSION['user_id'] && $perfil === 'usuario') {
                    $stmt_count_admins = $pdo_crud->query("SELECT COUNT(*) FROM users WHERE perfil = 'admin'");
                    if ($stmt_count_admins->fetchColumn() <= 1) {
                        $errors[] = "Não é possível alterar seu próprio perfil para 'usuário' pois você é o único administrador.";
                    }
                }

                if (empty($errors)) {
                    $stmt_check_email = $pdo_crud->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
                    $stmt_check_email->execute([':email' => $email, ':user_id' => $user_id]);
                    if ($stmt_check_email->fetch()) {
                        $errors[] = "O novo email fornecido já está em uso por outro usuário.";
                    } else {
                        $sql_update = "UPDATE users SET nome = :nome, email = :email, perfil = :perfil";
                        $params = [':nome' => $nome, ':email' => $email, ':perfil' => $perfil, ':user_id' => $user_id];
                        if (!empty($senha)) {
                            $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
                            $sql_update .= ", senha = :senha";
                            $params[':senha'] = $hashed_password;
                        }
                        $sql_update .= " WHERE id = :user_id";
                        $stmt_update = $pdo_crud->prepare($sql_update);
                        $stmt_update->execute($params);
                        $success_message = "Usuário (ID: $user_id) atualizado com sucesso!";
                        if ($user_id === $_SESSION['user_id']) $_SESSION['user_nome'] = $nome;
                    }
                }
            } elseif ($action === 'delete_user') {
                $user_id_to_delete = filter_input(INPUT_POST, 'delete_user_id', FILTER_VALIDATE_INT);
                if (!$user_id_to_delete) {
                    $errors[] = "ID de usuário inválido para deleção.";
                } elseif ($user_id_to_delete === $_SESSION['user_id']) {
                    $errors[] = "Você não pode deletar sua própria conta.";
                } else {
                    $stmt_delete = $pdo_crud->prepare("DELETE FROM users WHERE id = :user_id");
                    $stmt_delete->bindParam(':user_id', $user_id_to_delete, PDO::PARAM_INT);
                    $stmt_delete->execute();
                    if ($stmt_delete->rowCount() > 0) {
                        $success_message = "Usuário (ID: $user_id_to_delete) deletado com sucesso!";
                    } else {
                        $errors[] = "Usuário (ID: $user_id_to_delete) não encontrado ou já deletado.";
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Admin CRUD Error: " . $e->getMessage());
            $errors[] = "Ocorreu um erro no processamento do banco de dados. Tente novamente.";
        }
        // Update $csrf_token to the newly generated one for the current page view
        $csrf_token = $current_page_csrf_token;
    }
}


// --- Fetch users from database (Read operation) ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt_users = $pdo->query("SELECT id, nome, email, perfil, data_criacao FROM users ORDER BY id ASC");
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin Fetch Users Error: " . $e->getMessage());
    $errors[] = "Erro ao buscar usuários. Verifique os logs do servidor.";
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
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Specific styles for admin page can go here if needed, or be added to admin_style.css */
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px; /* For smaller screens */
        }
        .table-actions form { /* For delete button form */
            display: inline-block;
        }
         .main-content-wrapper {
             padding-top: 70px; /* Adjust based on fixed navbar height */
        }
        .main-header.navbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); 
            z-index: 1030; 
            transition: left 0.3s ease, width 0.3s ease;
        }
        body.sidebar-toggled .main-header.navbar {
            left: 0;
            width: 100%;
        }
         @media (max-width: 991.98px) {
            .main-header.navbar {
                left: 0;
                width: 100%;
            }
             body.sidebar-toggled .main-header.navbar {
                left: var(--sidebar-width); 
             }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php">DocMD Painel</a>
        </div>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link sidebar-link" href="dashboard.php"><i class="fas fa-cogs fa-fw me-2"></i> <span>Gerador</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link" href="#"><i class="fas fa-file-alt fa-fw me-2"></i> <span>Templates</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link active" href="admin.php"><i class="fas fa-users fa-fw me-2"></i> <span>Usuários</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link" href="#"><i class="fas fa-sliders-h fa-fw me-2"></i> <span>Configurações</span></a>
            </li>
        </ul>
    </div>

    <div class="main-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light main-header">
          <div class="container-fluid">
            <button class="btn btn-link" id="sidebarToggle" type="button">
              <i class="fas fa-bars"></i>
            </button>
            <div class="ms-auto">
                <ul class="navbar-nav">
                    <li class="nav-item"><span class="navbar-text me-3">Usuário: <?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Admin'); ?> (Admin)</span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
          </div>
        </nav>

        <div class="container-fluid mt-3"> <!-- Changed to container-fluid for better use of space -->
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

            <div class="mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Adicionar Novo Usuário
                </button>
            </div>

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
                        <?php if (!empty($errors) && empty($users) && strpos(end($errors), "Erro ao buscar usuários") !== false): ?>
                            <tr>
                                 <td colspan="6" class="text-center text-danger">Erro ao carregar usuários. Verifique os logs.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> <!-- End of container-fluid for main admin content -->

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
                            <div class="mb-3">
                                <label for="add_nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="add_nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="add_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="add_senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="add_senha" name="senha" required minlength="8">
                                <small class="form-text text-muted">Mínimo 8 caracteres.</small>
                            </div>
                            <div class="mb-3">
                                <label for="add_perfil" class="form-label">Perfil</label>
                                <select class="form-select" id="add_perfil" name="perfil" required>
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
                            <div class="mb-3">
                                <label for="edit_nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="edit_nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_senha" class="form-label">Nova Senha (opcional)</label>
                                <input type="password" class="form-control" id="edit_senha" name="senha" minlength="8">
                                <small class="form-text text-muted">Deixe em branco para não alterar. Mínimo 8 caracteres se preenchido.</small>
                            </div>
                            <div class="mb-3">
                                <label for="edit_perfil" class="form-label">Perfil</label>
                                <select class="form-select" id="edit_perfil" name="perfil" required>
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
    </div> <!-- End of .main-content-wrapper -->

    <footer class="footer">
        <div class="container">
            <span>&copy; <?php echo date("Y"); ?> DocMD Admin Panel - Todos os direitos reservados.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-toggled');
                });
            }

            // Pre-fill edit user modal
            const editUserModal = document.getElementById('editUserModal');
            if (editUserModal) {
                editUserModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget; 
                    const userId = button.getAttribute('data-id');
                    const nome = button.getAttribute('data-nome');
                    const email = button.getAttribute('data-email');
                    const perfil = button.getAttribute('data-perfil');

                    const modalUserId = editUserModal.querySelector('#edit_user_id');
                    const modalNome = editUserModal.querySelector('#edit_nome');
                    const modalEmail = editUserModal.querySelector('#edit_email');
                    const modalPerfil = editUserModal.querySelector('#edit_perfil');
                    const modalSenha = editUserModal.querySelector('#edit_senha');

                    if(modalUserId) modalUserId.value = userId;
                    if(modalNome) modalNome.value = nome;
                    if(modalEmail) modalEmail.value = email;
                    if(modalPerfil) modalPerfil.value = perfil;
                    if(modalSenha) modalSenha.value = ''; 
                });

                editUserModal.addEventListener('hidden.bs.modal', event => {
                    const form = editUserModal.querySelector('form');
                    if(form) form.reset();
                    const modalSenha = editUserModal.querySelector('#edit_senha');
                    if(modalSenha) modalSenha.value = '';
                });
            }

            // Clear add user modal on hide
            const addUserModal = document.getElementById('addUserModal');
            if (addUserModal) {
                addUserModal.addEventListener('hidden.bs.modal', event => {
                    const form = addUserModal.querySelector('form');
                    if(form) form.reset();
                });
            }
        });
        
        function confirmDelete(userId, currentAdminId) {
            if (String(userId) === String(currentAdminId)) { 
                alert('Você não pode deletar sua própria conta de administrador.');
                return false;
            }
            return confirm('Tem certeza que deseja deletar este usuário (ID: ' + userId + ')? Esta ação não pode ser desfeita.');
        }
    </script>
</body>
</html>
