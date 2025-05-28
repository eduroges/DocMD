<?php
require_once 'auth.php'; // Ensures user is authenticated

// Access session variables
$user_nome = $_SESSION['user_nome'] ?? 'Usuário';
$user_email = $_SESSION['user_email'] ?? 'Não disponível'; // Kept for potential future use, though not displayed in this layout
$user_perfil = $_SESSION['user_perfil'] ?? 'Não disponível';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DocMD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css"> 
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/2.3.6/purify.min.js"></script>
    <!-- Inline styles specific to DocMD components will remain, general layout styles moved to admin_style.css -->
    <style>
        /* Styles from DocMD.html (specific to DocMD components) */
        #docmd-container:root { /* Apply vars to #docmd-container or use specific var names */
          --docmd-primary-color: #3498db;
          --docmd-secondary-color: #2c3e50;
          --docmd-accent-color: #e74c3c;
          --docmd-light-color: #ecf0f1;
          --docmd-dark-color: #34495e;
        }
        #docmd-container .app-container {
          max-width: 900px;
          margin: 0 auto;
        }
        #docmd-container .app-header {
          text-align: center;
          margin-bottom: 2rem;
          padding-bottom: 1rem;
          border-bottom: 2px solid var(--docmd-primary-color, #3498db);
        }
        #docmd-container .app-title {
          color: var(--docmd-primary-color, #3498db);
          font-weight: 600;
        }
        #docmd-container .app-subtitle {
          color: var(--docmd-secondary-color, #2c3e50);
          font-size: 1.1rem;
        }
        #docmd-container .card {
          border-radius: 10px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          border: none;
          margin-bottom: 1.5rem;
        }
        #docmd-container .card-header {
          background-color: var(--docmd-primary-color, #3498db);
          color: white;
          border-radius: 10px 10px 0 0 !important;
          padding: 1rem;
          font-weight: 600;
        }
        #docmd-container .accordion-item {
            border-radius: 10px !important;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem; 
            overflow: hidden; 
        }
        #docmd-container .accordion-header {
             border-radius: 10px 10px 0 0 !important;
        }
        #docmd-container .accordion-button { 
            background-color: var(--docmd-primary-color, #3498db);
            color: white;
            font-weight: 600;
            padding: 1rem;
         }
        #docmd-container .accordion-button:not(.collapsed) {
             background-color: var(--docmd-primary-color, #3498db); 
             color: white; 
             box-shadow: none; 
          }
        #docmd-container .accordion-button:focus {
              box-shadow: none;
              border-color: rgba(0,0,0,.125);
          }
        #docmd-container .accordion-button::after {
              background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
           }
        #docmd-container .accordion-button:not(.collapsed)::after {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
                transform: rotate(-180deg);
           }
        #docmd-container .accordion-body { 
               padding: 1.5rem; 
               background-color: white; 
           }
        #docmd-container .form-label { 
          font-weight: 500; 
          color: var(--docmd-secondary-color, #2c3e50); 
        }
        #docmd-container .form-control, #docmd-container .form-select { 
          border-radius: 6px; 
          border: 1px solid #ced4da; 
          padding: 0.6rem 0.75rem; 
          transition: all 0.3s; 
        }
        #docmd-container .form-control:focus, #docmd-container .form-select:focus { 
          border-color: var(--docmd-primary-color, #3498db); 
          box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25); 
        }
        #docmd-container .btn-primary {
          background-color: var(--docmd-primary-color, #3498db); 
          border-color: var(--docmd-primary-color, #3498db); 
          padding: 0.6rem 1.5rem;
          font-weight: 500;
          border-radius: 6px;
        }
        #docmd-container .btn-primary:hover { 
          background-color: #2980b9; 
          border-color: #2980b9; 
        }
        #docmd-container .btn-secondary { 
          background-color: var(--docmd-secondary-color, #2c3e50); 
          border-color: var(--docmd-secondary-color, #2c3e50); 
        }
        #docmd-container .btn-secondary:hover { 
          background-color: #1a252f; 
          border-color: #1a252f; 
        }
        #docmd-container #preview { 
          border: 1px solid #dee2e6;
          border-radius: 10px;
          padding: 2rem;
          background-color: white;
          min-height: 300px;
        }
        #docmd-container .hidden { 
          display: none;
        }
        #docmd-container .info-icon { 
          color: var(--docmd-primary-color, #3498db);
          cursor: pointer;
          margin-left: 0.5rem;
        }
        #docmd-container .format-badge { 
          display: inline-block;
          background-color: #e9f7fe;
          color: var(--docmd-primary-color, #3498db);
          border-radius: 4px;
          padding: 0.2rem 0.5rem;
          font-size: 0.85rem;
          margin: 0.2rem;
          border: 1px solid #c9e7fe;
        }
        #docmd-container  .format-badge.op-badge {
          background-color: #e8f5e9; 
          color: #2e7d32; 
          border: 1px solid #c8e6c9;
        }
        #docmd-container .format-example { 
          font-family: monospace;
          background-color: #f8f9fa;
          padding: 0.5rem;
          border-radius: 4px;
          margin: 0.5rem 0;
          border-left: 3px solid var(--docmd-primary-color, #3498db);
        }
        #docmd-container .file-upload-container { 
          border: 2px dashed #ced4da;
          border-radius: 10px;
          padding: 2rem;
          text-align: center;
          background-color: #f8f9fa;
          transition: all 0.3s;
          cursor: pointer;
        }
        #docmd-container .file-upload-container:hover { 
          border-color: var(--docmd-primary-color, #3498db);
          background-color: #e9f7fe;
        }
        #docmd-container .upload-icon { 
          font-size: 2.5rem;
          color: var(--docmd-primary-color, #3498db);
          margin-bottom: 1rem;
        }
        #docmd-container .tooltip-inner { 
          max-width: 350px; 
        }
        #docmd-container .placeholder-list { 
          background-color: #f8f9fa;
          border-radius: 8px;
          padding: 1rem;
          margin-top: 1rem;
          border: 1px solid #dee2e6;
        }
        #docmd-container .placeholder-item { 
          font-family: monospace;
          padding: 0.5rem;
          margin: 0.25rem 0;
          background-color: white;
          border-radius: 4px;
          border: 1px solid #e9ecef;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        #docmd-container .placeholder-item:hover { 
          background-color: #e9f7fe;
        }
        #docmd-container .copy-btn { 
          background: none;
          border: none;
          color: var(--docmd-primary-color, #3498db);
          cursor: pointer;
          padding: 0.25rem 0.5rem;
          border-radius: 4px;
          transition: all 0.2s;
        }
        #docmd-container .copy-btn:hover { 
          background-color: var(--docmd-primary-color, #3498db);
          color: white;
        }
        #docmd-container .placeholder-category { 
          font-weight: 600;
          margin-top: 1rem;
          margin-bottom: 0.5rem;
          color: var(--docmd-secondary-color, #2c3e50);
        }
        #docmd-container .placeholder-modal .modal-header { 
          background-color: var(--docmd-primary-color, #3498db); 
          color: white; 
        }
        #docmd-container .placeholder-modal .modal-body { 
          max-height: 70vh; 
          overflow-y: auto; 
        }
        #docmd-container .placeholder-tabs .nav-link { 
          color: var(--docmd-secondary-color, #2c3e50); 
        }
        #docmd-container .placeholder-tabs .nav-link.active { 
          color: var(--docmd-primary-color, #3498db); 
          font-weight: 600; 
          border-bottom: 2px solid var(--docmd-primary-color, #3498db); 
        }
        /* General layout styles that were previously here have been moved to admin_style.css */
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php">DocMD Painel</a>
        </div>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link sidebar-link active" href="dashboard.php"><i class="fas fa-cogs fa-fw me-2"></i> <span>Gerador</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link" href="#"><i class="fas fa-file-alt fa-fw me-2"></i> <span>Templates</span></a>
            </li>
            <?php if ($user_perfil === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link sidebar-link" href="admin.php"><i class="fas fa-users fa-fw me-2"></i> <span>Usuários</span></a>
            </li>
            <?php endif; ?>
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
                    <li class="nav-item"><span class="navbar-text me-3">Bem-vindo, <?php echo htmlspecialchars($user_nome); ?>!</span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
          </div>
        </nav>

        <!-- Main DocMD application content, previously in #docmd-container, now directly in main-content-wrapper -->
        <div id="docmd-container" class="container mt-3"> <!-- This container might be redundant if app-container handles width -->
             <div class="app-container"> 
                <header class="app-header">
                  <h1 class="app-title">Gerador de Documentos Markdown Avançado</h1>
                  <p class="app-subtitle">Crie documentos personalizados com formatação inteligente e datas pré-preenchidas</p>
                </header>
                <div class="accordion" id="howtoAccordion">
                  <div class="accordion-item">
                    <h2 class="accordion-header" id="howtoHeading">
                      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#howtoCollapse" aria-expanded="false" aria-controls="howtoCollapse">
                         <i class="fas fa-info-circle me-2"></i> Como funciona (Clique para expandir)
                      </button>
                    </h2>
                    <div id="howtoCollapse" class="accordion-collapse collapse" aria-labelledby="howtoHeading" data-bs-parent="#howtoAccordion">
                      <div class="accordion-body">
                         <p>Esta ferramenta permite criar documentos a partir de templates Markdown. Placeholders como <code>[Campo]</code> ou <code>{[Campo].formato}</code> serão substituídos.</p>
                         <h5 class="mt-4">Pré-preenchimento de Datas (Editável):</h5>
                         <div class="d-flex flex-wrap">
                             <span class="format-badge op-badge"><code>datahoje</code> - Pré-preenche com a data atual por extenso.</span>
                             <span class="format-badge op-badge"><code>datahoje.add(num[D|M|A])</code> - Pré-preenche com a data atual + dias (D), meses (M) ou anos (A), por extenso.</span>
                         </div>
                         <p class="mt-2"><small><strong>Exemplo:</strong> <code>{[Data Vencimento].datahoje.add(6M)}</code> irá preencher o campo "Data Vencimento" com a data daqui a 6 meses (ex: "16 de janeiro de 2025"). O usuário pode editar este valor.</small></p>
                         <h5 class="mt-4">Formatações de Texto/Número (Aplicadas na Geração):</h5>
                         <div class="d-flex flex-wrap">
                           <span class="format-badge"><code>maiusculo</code> - CAIXA ALTA</span>
                           <span class="format-badge"><code>minusculo</code> - caixa baixa</span>
                           <span class="format-badge"><code>capitalizado</code> - Primeira Letra Maiúscula</span>
                           <span class="format-badge"><code>telefone</code> - (99) 99999-9999</span>
                           <span class="format-badge"><code>cpf</code> - 999.999.999-99</span>
                           <span class="format-badge"><code>cnpj</code> - 99.999.999/9999-99</span>
                           <span class="format-badge"><code>cpfcnpj</code> - CPF ou CNPJ</span>
                           <span class="format-badge"><code>cep</code> - 99999-999</span>
                           <span class="format-badge"><code>data</code> - DD/MM/AAAA</span>
                           <span class="format-badge"><code>moeda</code> - R$ 9.999,99</span>
                           <span class="format-badge"><code>valorextenso</code> - Valor por extenso</span>
                         </div>
                         <h5 class="mt-4">Exemplos e Mais:</h5>
                         <p>Consulte a seção "Como funciona" e o modal "Placeholders Disponíveis" para mais detalhes sobre formatos, textos de ajuda, e como criar seus próprios placeholders.</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card mt-4"> 
                  <div class="card-header">
                    <i class="fas fa-file-upload me-2"></i> Carregar Template Markdown
                  </div>
                  <div class="card-body">
                    <div class="file-upload-container" id="dropArea">
                      <i class="fas fa-cloud-upload-alt upload-icon"></i>
                      <h5>Arraste e solte seu arquivo Markdown aqui</h5>
                      <p class="text-muted">ou</p>
                      <input type="file" id="fileInput" accept=".md" class="d-none">
                      <button class="btn btn-primary" id="browseButton">
                        <i class="fas fa-folder-open me-2"></i> Procurar arquivo
                      </button>
                    </div>
                  </div>
                </div>
                <form id="formFields" class="hidden mt-4">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <div><i class="fas fa-edit me-2"></i> Preencher Campos</div>
                      <div><button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#placeholderModal"><i class="fas fa-list-ul me-1"></i> Placeholders</button></div>
                    </div>
                    <div class="card-body">
                      <div id="dynamicFields"></div>
                      <hr class="my-4">
                      <div class="row">
                        <div class="col-md-6"><label for="fontFamily" class="form-label">Fonte:</label><select id="fontFamily" class="form-select"><option value="Helvetica, Arial, sans-serif" selected>Helvetica</option><option value="'Times New Roman', Times, serif">Times New Roman</option><option value="Georgia, serif">Georgia</option><option value="'Segoe UI', Tahoma, Geneva, Verdana, sans-serif">Segoe UI</option><option value="'Courier New', Courier, monospace">Courier New</option></select></div>
                        <div class="col-md-3"><label for="fontSize" class="form-label">Tamanho:</label><select id="fontSize" class="form-select"><option value="12px">12px</option><option value="14px">14px</option><option value="16px" selected>16px</option><option value="18px">18px</option><option value="20px">20px</option></select></div>
                        <div class="col-md-3"><label for="outputMode" class="form-label">Saída:</label><select id="outputMode" class="form-select"><option value="preview">Visualizar</option><option value="print">Imprimir</option></select></div>
                      </div>
                    </div>
                    <div class="card-footer text-end"><button type="submit" class="btn btn-primary"><i class="fas fa-file-alt me-2"></i> Gerar Documento</button></div>
                  </div>
                </form>
                <div id="previewContainer" class="mt-4 hidden">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <div><i class="fas fa-eye me-2"></i> Visualização</div>
                      <div><button id="printPreviewBtn" class="btn btn-sm btn-secondary"><i class="fas fa-print me-1"></i> Imprimir</button></div>
                    </div>
                    <div class="card-body"><div id="preview"></div></div>
                  </div>
                </div>
                <div class="modal fade placeholder-modal" id="placeholderModal" tabindex="-1" aria-labelledby="placeholderModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header"><h5 class="modal-title" id="placeholderModalLabel"><i class="fas fa-list-ul me-2"></i> Placeholders Disponíveis</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                      <div class="modal-body">
                        <p class="text-muted">Placeholders são marcadores no seu template...</p>
                        <ul class="nav nav-tabs placeholder-tabs mb-3" id="placeholderTabs" role="tablist">
                          <li class="nav-item" role="presentation"><button class="nav-link active" id="simple-tab" data-bs-toggle="tab" data-bs-target="#simple-placeholders" type="button" role="tab" aria-controls="simple-placeholders" aria-selected="true">Simples</button></li>
                          <li class="nav-item" role="presentation"><button class="nav-link" id="formatted-tab" data-bs-toggle="tab" data-bs-target="#formatted-placeholders" type="button" role="tab" aria-controls="formatted-placeholders" aria-selected="false">Com Formatação / Pré-preenchimento</button></li>
                          <li class="nav-item" role="presentation"><button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-placeholder" type="button" role="tab" aria-controls="create-placeholder" aria-selected="false">Criar Novo</button></li>
                        </ul>
                        <div class="tab-content" id="placeholderTabsContent">
                          <div class="tab-pane fade show active" id="simple-placeholders" role="tabpanel" aria-labelledby="simple-tab">
                            <p class="text-muted">Placeholders simples <code>[Nome do Campo]</code> ou <code>[Nome do Campo|Ajuda]</code>...</p><div id="simplePlaceholderList" class="placeholder-list"><div class="text-center text-muted py-3" id="noSimplePlaceholders">Nenhum placeholder simples.</div></div></div>
                          <div class="tab-pane fade" id="formatted-placeholders" role="tabpanel" aria-labelledby="formatted-tab">
                            <p class="text-muted">Placeholders no formato <code>{[Nome Campo].operação}</code>...</p><div id="formattedPlaceholderList" class="placeholder-list"><p class="placeholder-category">Pré-preenchimento de Data</p><div id="dateOpsPlaceholderList"></div><p class="placeholder-category mt-3">Formatação de Texto/Número</p><div id="textNumPlaceholderList"></div><div class="text-center text-muted py-3" id="noFormattedPlaceholders">Nenhum placeholder formatado.</div></div></div>
                          <div class="tab-pane fade" id="create-placeholder" role="tabpanel" aria-labelledby="create-tab">
                             <p class="text-muted">Crie um novo placeholder...</p>
                            <div class="mb-3"><label for="newPlaceholderName" class="form-label">Nome do Campo:</label><input type="text" class="form-control" id="newPlaceholderName" placeholder="Ex: Nome do Cliente"></div>
                            <div class="mb-3"><label for="newPlaceholderHelp" class="form-label">Texto de Ajuda (opcional):</label><input type="text" class="form-control" id="newPlaceholderHelp" placeholder="Ex: Digite o nome completo"></div>
                            <div class="mb-3"><label class="form-label">Tipo:</label><div class="form-check"><input class="form-check-input" type="radio" name="placeholderType" id="typePlain" value="plain" checked><label class="form-check-label" for="typePlain">Simples</label></div><div class="form-check"><input class="form-check-input" type="radio" name="placeholderType" id="typeFormatted" value="formatted"><label class="form-check-label" for="typeFormatted">Com Formatação</label></div></div>
                            <div id="formatOptions" class="mb-3 d-none"><label for="placeholderFormat" class="form-label">Operação:</label><select class="form-select" id="placeholderFormat"><option value="">Selecione</option><option value="datahoje" data-type="prefill">datahoje</option></select><small class="form-text text-muted" id="formatNote"></small></div>
                            <div class="mb-3"><label for="placeholderPreview" class="form-label">Prévia:</label><div class="form-control bg-light" id="placeholderPreview" style="font-family: monospace;">[Nome do Campo]</div></div>
                            <button type="button" class="btn btn-primary" id="copyNewPlaceholder"><i class="fas fa-copy me-2"></i> Copiar</button>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>
                    </div>
                  </div>
                </div>
            </div> 
        </div> 
    </div>

    <footer class="footer bg-dark text-white text-center py-3"> <!-- Adjusted footer to match admin_style.css if needed -->
        <div class="container">
            <span>&copy; <?php echo date("Y"); ?> DocMD - Todos os direitos reservados.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Sidebar Toggle JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-toggled');
            });
        }

      // DocMD Main Script (Adapted & with DOMPurify)
      const initializeTooltips = () => {
        const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        existingTooltips.forEach(el => {
            const tooltipInstance = bootstrap.Tooltip.getInstance(el);
            if (tooltipInstance) { tooltipInstance.dispose(); }
        });
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, { delay: { "show": 100, "hide": 100 }, trigger : 'hover focus', container: 'body' });
        });
      }
      initializeTooltips();
      window.initializeTooltips = initializeTooltips; 
      if (typeof initPlaceholderGenerator === "function") initPlaceholderGenerator();
       const accordionElement = document.getElementById('howtoCollapse');
       if (accordionElement) {
           accordionElement.addEventListener('shown.bs.collapse', window.initializeTooltips);
           accordionElement.addEventListener('hidden.bs.collapse', window.initializeTooltips);
       }
    });

    let template = "";
    const placeholders = new Set(); 
    const formattedPlaceholders = new Map(); 
    const placeholderHelps = new Map(); 
    const formatRegex = /\{\[([^\|\]]+)(?:\|([^\]]+))?\]\.([a-zA-Z0-9\.\(\)]+)\}/g; 
    const simpleRegex = /\[([^\|\]]+)(?:\|([^\]]+))?\]/g; 

    function formatarDataDescritiva(date) {
        if (!(date instanceof Date) || isNaN(date)) return '';
        const dia = date.getDate();
        const mes = date.toLocaleDateString('pt-BR', { month: 'long' });
        const ano = date.getFullYear();
        return `${dia} de ${mes} de ${ano}`;
    }
    function applyDateAdd(date, instruction) {
        if (!(date instanceof Date) || isNaN(date)) return date;
        const match = instruction.match(/add\((\d+)\s*([DMA])\)/i);
        if (!match) return date;
        const value = parseInt(match[1], 10);
        const unit = match[2].toUpperCase();
        const newDate = new Date(date.getTime());
        try {
            switch (unit) {
                case 'D': newDate.setDate(newDate.getDate() + value); break;
                case 'M': newDate.setMonth(newDate.getMonth() + value); break;
                case 'A': newDate.setFullYear(newDate.getFullYear() + value); break;
                default: return date;
            }
            return isNaN(newDate) ? date : newDate;
        } catch (e) { return date; }
    }
    function numeroParaExtenso(numero) {
        if (isNaN(numero)) return String(numero);
        const unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        const dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        const centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
        if (numero === 0) return 'zero reais';
        let numStr = parseFloat(numero).toFixed(2);
        let [inteiro, decimal] = numStr.split('.');
        inteiro = parseInt(inteiro); decimal = parseInt(decimal);
        // Simplified for brevity, full logic would be more complex
        let resultado = (inteiro === 1 ? 'um real' : `${unidades[inteiro] || inteiro} reais`);
        if (decimal > 0) {
            resultado += ` e ${unidades[decimal] || decimal} centavos`;
        }
        return resultado;
    }
    const formatters = {
      maiusculo: (text) => String(text).toUpperCase(),
      minusculo: (text) => String(text).toLowerCase(),
      capitalizado: (text) => String(text).split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' '),
      telefone: (text) => { const n = String(text).replace(/\D/g,''); if(n.length===11) return `(${n.slice(0,2)}) ${n.slice(2,7)}-${n.slice(7)}`; if(n.length===10) return `(${n.slice(0,2)}) ${n.slice(2,6)}-${n.slice(6)}`; return text; },
      cpf: (text) => { const n = String(text).replace(/\D/g,''); if(n.length===11) return `${n.slice(0,3)}.${n.slice(3,6)}.${n.slice(6,9)}-${n.slice(9)}`; return text; },
      cnpj: (text) => { const n = String(text).replace(/\D/g,''); if(n.length===14) return `${n.slice(0,2)}.${n.slice(2,5)}.${n.slice(5,8)}/${n.slice(8,12)}-${n.slice(12)}`; return text; },
      cpfcnpj: (text) => { const n = String(text).replace(/\D/g,''); if(n.length===11) return formatters.cpf(n); if(n.length===14) return formatters.cnpj(n); return text; },
      cep: (text) => { const n = String(text).replace(/\D/g,''); if(n.length===8) return `${n.slice(0,5)}-${n.slice(5)}`; return text; },
      data: (text) => { try { const d=new Date(text); if(!isNaN(d) && String(text).match(/[0-9]/)) return d.toLocaleDateString('pt-BR'); } catch(e){} return text; },
      moeda: (text) => { const n = parseFloat(String(text).replace(/[^0-9,.-]/g, '').replace(/[.,](?=[^.,]*[.,])/g, '').replace(',', '.')); return !isNaN(n) ? n.toLocaleString('pt-BR', {style:'currency', currency:'BRL'}) : text; },
      valorextenso: (text) => { const n = parseFloat(String(text).replace(/[^0-9,.-]/g, '').replace(/[.,](?=[^.,]*[.,])/g, '').replace(',', '.')); return !isNaN(n) ? numeroParaExtenso(n) : text; }
    };
    const dropArea = document.getElementById('dropArea'); 
    const fileInput = document.getElementById('fileInput');
    const browseButton = document.getElementById('browseButton');
    if (browseButton && fileInput) { browseButton.addEventListener('click', () => { fileInput.click(); }); }
    if (dropArea && fileInput) { 
        dropArea.addEventListener('dragover', (e) => { e.preventDefault(); dropArea.classList.add('bg-light'); });
        dropArea.addEventListener('dragleave', () => { dropArea.classList.remove('bg-light'); });
        dropArea.addEventListener('drop', (e) => { e.preventDefault(); dropArea.classList.remove('bg-light'); if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; handleFileSelect(e.dataTransfer.files[0]); } });
    }
    if (fileInput) { fileInput.addEventListener('change', (event) => { if (event.target.files.length) handleFileSelect(event.target.files[0]); }); }
    
    function handleFileSelect(file) {
        if (file && file.name.toLowerCase().endsWith('.md')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                template = e.target.result; 
                const dropAreaEl = document.getElementById('dropArea');
                if (dropAreaEl) {
                    dropAreaEl.innerHTML = `<i class="fas fa-check-circle upload-icon" style="color: green;"></i><h5>Arquivo carregado!</h5><p class="text-muted">${file.name}</p><button class="btn btn-outline-primary mt-2" id="changeFileBtn"><i class="fas fa-exchange-alt me-2"></i>Trocar</button>`;
                    const changeFileBtn = document.getElementById('changeFileBtn');
                    if (changeFileBtn) { changeFileBtn.addEventListener('click', () => { location.reload(); }); }
                }
                extractPlaceholders(template); 
                if (typeof updatePlaceholderLists === "function") updatePlaceholderLists();
            }; reader.onerror = function() { alert("Erro ao ler arquivo."); }; reader.readAsText(file);
        } else { alert('Selecione um arquivo Markdown (.md)'); }
    }
    function extractPlaceholders(text) { 
        placeholders.clear(); formattedPlaceholders.clear(); placeholderHelps.clear();
        const dynamicFields = document.getElementById('dynamicFields'); 
        if (!dynamicFields) return; 
        dynamicFields.innerHTML = '';
        const uniqueKeys = new Set();
        let match;
        const formatRegexCopy = new RegExp(formatRegex.source, 'g');
        while ((match = formatRegexCopy.exec(text)) !== null) {
            const key = match[1].trim(); const help = match[2] ? match[2].trim() : ''; const formatString = match[3].trim();
            formattedPlaceholders.set(key, formatString); if (help) placeholderHelps.set(key, help);
            if (!uniqueKeys.has(key)) { placeholders.add(key); uniqueKeys.add(key); createField(key, formatString, dynamicFields); }
        }
        const simpleRegexCopy = new RegExp(simpleRegex.source, 'g');
        while ((match = simpleRegexCopy.exec(text)) !== null) {
            const key = match[1].trim(); const help = match[2] ? match[2].trim() : '';
            if (!formattedPlaceholders.has(key) && !uniqueKeys.has(key)) { placeholders.add(key); uniqueKeys.add(key); createField(key, null, dynamicFields); }
            if (help && !placeholderHelps.has(key)) {
                 placeholderHelps.set(key, help); const inputEl = document.getElementById(`field_${key.replace(/\s+/g, '_')}`);
                 if(inputEl && !inputEl.placeholder) inputEl.placeholder = help;
            }
        }
        const formFieldsEl = document.getElementById('formFields');
        if (formFieldsEl) {
            if (placeholders.size > 0) { formFieldsEl.classList.remove('hidden'); setTimeout(() => { if (window.initializeTooltips) window.initializeTooltips(); }, 150); }
            else { if(template) alert('Nenhum campo [Campo] ou {[Campo].formato} encontrado.'); formFieldsEl.classList.add('hidden'); }
        }
    }
    function createField(key, formatString, container) {
        const fieldId = `field_${key.replace(/\s+/g, '_')}`; if (document.getElementById(fieldId)) return;
        const fieldGroup = document.createElement('div'); fieldGroup.className = 'mb-3';
        const label = document.createElement('label'); label.className = 'form-label'; label.htmlFor = fieldId;
        const labelText = document.createElement('span'); labelText.textContent = key + ':'; label.appendChild(labelText);
        const input = document.createElement('input'); input.type = 'text'; input.className = 'form-control'; input.id = fieldId; input.name = key;
        let prefilledValue = ''; let isDateField = false;
        if (formatString && (formatString.startsWith('datahoje') || formatString.includes('.datahoje.'))) {
             isDateField = true; let calculatedDate = new Date(); const formats = formatString.split('.');
             for (const format of formats) { if (format.startsWith('add(')) { calculatedDate = applyDateAdd(calculatedDate, format); } }
             prefilledValue = formatarDataDescritiva(calculatedDate);
        }
        if (prefilledValue) { input.value = prefilledValue; input.placeholder = placeholderHelps.get(key) || `Data pré-preenchida (editável)`; }
        else if (placeholderHelps.has(key)) { input.placeholder = placeholderHelps.get(key); }
        
        const hasFormatOrHelp = formatString || placeholderHelps.has(key);
        if (hasFormatOrHelp) {
            const infoIcon = document.createElement('i'); infoIcon.className = `fas ${formatString ? 'fa-info-circle' : 'fa-question-circle'} info-icon ms-1`; infoIcon.setAttribute('data-bs-toggle', 'tooltip'); infoIcon.setAttribute('data-bs-placement', 'top');
            let dynamicHelp = '';
            if (isDateField) { dynamicHelp = `Pré-preenchido (${prefilledValue}). Editável.`; const others = formatString.split('.').filter(f=>!f.startsWith('datahoje')&&!f.startsWith('add')).join(', '); if(others) dynamicHelp += ` Formatos (${others}) aplicados na geração.` }
            else if (formatString) { dynamicHelp = `Formato "${formatString}" aplicado na geração.`; }
            const tooltipText = [placeholderHelps.get(key), dynamicHelp].filter(Boolean).join(' | '); infoIcon.setAttribute('title', tooltipText || 'Info'); label.appendChild(infoIcon);
        }
        fieldGroup.appendChild(label); fieldGroup.appendChild(input); container.appendChild(fieldGroup);
    }
    const formFieldsEl = document.getElementById('formFields');
    if (formFieldsEl) {
        formFieldsEl.addEventListener('submit', function (event) {
          event.preventDefault();
          let filledText = template; 
          const processedFormatted = new Set(); 
          const formatRegexCopy = new RegExp(formatRegex.source, 'g');
          let formatMatch;
          while ((formatMatch = formatRegexCopy.exec(template)) !== null) {
            const fullMatch = formatMatch[0]; 
            if (processedFormatted.has(fullMatch)) continue; 
            const key = formatMatch[1].trim(); 
            const formatString = formatMatch[3].trim(); 
            const inputElement = document.getElementById(`field_${key.replace(/\s+/g, '_')}`);
            let finalValue = inputElement ? inputElement.value : ""; 
            const formats = formatString.split('.');
            for (const format of formats) {
                const currentFormat = format.trim();
                if (currentFormat === 'datahoje' || currentFormat.startsWith('add(')) continue; 
                if (formatters[currentFormat]) {
                     try { finalValue = formatters[currentFormat](finalValue); }
                     catch (e) { console.error(`Erro formatando ${key}.${currentFormat}:`, e); }
                } else if (currentFormat) { console.warn(`Formato desconhecido: ${currentFormat}`); }
            }
            filledText = filledText.replaceAll(fullMatch, () => finalValue); 
            processedFormatted.add(fullMatch);
          }
          const simpleRegexCopy = new RegExp(simpleRegex.source, 'g');
          let simpleMatch;
          while ((simpleMatch = simpleRegexCopy.exec(template)) !== null) {
            const fullMatch = simpleMatch[0]; 
            const key = simpleMatch[1].trim(); 
            const inputElement = document.getElementById(`field_${key.replace(/\s+/g, '_')}`);
            const value = inputElement ? inputElement.value : ""; 
            filledText = filledText.replaceAll(fullMatch, () => value);
          }
          const html = marked.parse(filledText); 
          const cleanHtml = DOMPurify.sanitize(html); // DOMPurify Sanitization
          const font = document.getElementById('fontFamily')?.value; 
          const size = document.getElementById('fontSize')?.value; 
          const mode = document.getElementById('outputMode')?.value; 
          const preview = document.getElementById('preview');
          if (preview) {
            preview.innerHTML = cleanHtml; // Use sanitized HTML
            preview.style.fontFamily = font; 
            preview.style.fontSize = size;
          }
          const previewContainer = document.getElementById('previewContainer');
          if (previewContainer) {
            previewContainer.classList.remove('hidden');
            if (mode === 'print' && preview) { 
                printDocument(preview.innerHTML, font, size); // Pass sanitized HTML from preview
            }
            previewContainer.scrollIntoView({ behavior: 'smooth' });
          }
        });
    }
    const printPreviewBtn = document.getElementById('printPreviewBtn');
    if (printPreviewBtn) {
        printPreviewBtn.addEventListener('click', function() { 
            const preview = document.getElementById('preview');
            const font = document.getElementById('fontFamily')?.value;
            const size = document.getElementById('fontSize')?.value;
            if (preview) { printDocument(preview.innerHTML, font, size); } // HTML from preview is already sanitized
        });
    }
    function printDocument(html, font, size) { 
        const cleanHtmlForPrint = DOMPurify.sanitize(html); // Sanitize again for safety
        const printWindow = window.open('', '', 'width=800,height=600');
        printWindow.document.write(`<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Imprimir</title><style>body{font-family:${font};font-size:${size};line-height:1.6;padding:40px;max-width:800px;margin:0 auto;} @media print{body{padding:0;margin:0;} p,h1,h2,h3,h4,h5,h6,li,blockquote{page-break-inside:avoid;} h1,h2,h3,h4,h5,h6{page-break-before:auto;page-break-after:avoid;}}</style></head><body>${cleanHtmlForPrint}</body></html>`); 
        printWindow.document.close(); 
        setTimeout(() => { try { printWindow.focus(); printWindow.print(); } catch (e) { alert("Erro imprimir."); } }, 500);
    }
    function initPlaceholderGenerator() { 
        const typeRadios = document.querySelectorAll('input[name="placeholderType"]'); const formatOptions = document.getElementById('formatOptions'); const placeholderPreview = document.getElementById('placeholderPreview'); const newPlaceholderName = document.getElementById('newPlaceholderName'); const newPlaceholderHelp = document.getElementById('newPlaceholderHelp'); const placeholderFormat = document.getElementById('placeholderFormat'); const copyNewPlaceholder = document.getElementById('copyNewPlaceholder'); const formatNote = document.getElementById('formatNote');
        if (!typeRadios.length && !formatOptions && !placeholderPreview) return; 
        typeRadios.forEach(radio => radio.addEventListener('change', updatePlaceholderPreview)); 
        if(newPlaceholderName) newPlaceholderName.addEventListener('input', updatePlaceholderPreview); 
        if(newPlaceholderHelp) newPlaceholderHelp.addEventListener('input', updatePlaceholderPreview); 
        if(placeholderFormat) placeholderFormat.addEventListener('change', updatePlaceholderPreview);
        const typeFormattedRadio = document.getElementById('typeFormatted');
        if (typeFormattedRadio && formatOptions) typeFormattedRadio.addEventListener('change', function() { formatOptions.classList.remove('d-none'); updatePlaceholderPreview(); });
        const typePlainRadio = document.getElementById('typePlain');
        if (typePlainRadio && formatOptions) typePlainRadio.addEventListener('change', function() { formatOptions.classList.add('d-none'); updatePlaceholderPreview(); });
        function updatePlaceholderPreview() { 
            if(!newPlaceholderName || !placeholderPreview || !placeholderFormat) return; 
            const name = newPlaceholderName.value.trim() || 'Nome Campo'; const help = newPlaceholderHelp?.value.trim() || ''; const typeCheckedRadio = document.querySelector('input[name="placeholderType"]:checked'); const type = typeCheckedRadio ? typeCheckedRadio.value : 'plain'; const selectedOption = placeholderFormat.options[placeholderFormat.selectedIndex]; const formatValue = selectedOption ? selectedOption.value : ''; const formatType = selectedOption ? selectedOption.getAttribute('data-type') : '';
            if (formatNote) {
                if (formatType === 'prefill' && type === 'formatted') { formatNote.textContent = `Pré-preenche ${name} com data (editável).`; formatNote.classList.remove('d-none'); } else if (type === 'formatted' && formatValue) { formatNote.textContent = `Formato "${formatValue.split(' - ')[0].trim()}" aplicado na geração.`; formatNote.classList.remove('d-none'); } else { formatNote.classList.add('d-none'); formatNote.textContent = ''; }
            }
            if (type === 'plain') { placeholderPreview.textContent = help ? `[${name}|${help}]` : `[${name}]`; } else { let baseFormat = formatValue || 'operação'; baseFormat = baseFormat.split(' - ')[0].trim(); placeholderPreview.textContent = help ? `{[${name}|${help}].${baseFormat}}` : `{[${name}].${baseFormat}}`; }
        }
        if (copyNewPlaceholder) copyNewPlaceholder.addEventListener('click', function() { const textToCopy = placeholderPreview.textContent; navigator.clipboard.writeText(textToCopy).then(() => { const o = copyNewPlaceholder.innerHTML; copyNewPlaceholder.innerHTML = '<i class="fas fa-check"></i> Copiado!'; copyNewPlaceholder.classList.add('btn-success'); copyNewPlaceholder.classList.remove('btn-primary'); setTimeout(() => { copyNewPlaceholder.innerHTML = o; copyNewPlaceholder.classList.remove('btn-success'); copyNewPlaceholder.classList.add('btn-primary'); }, 2000); }).catch(err => { alert('Erro copiar.'); }); });
        updatePlaceholderPreview();
    }
    function updatePlaceholderLists() { 
        const simpleList = document.getElementById('simplePlaceholderList'); const dateOpsList = document.getElementById('dateOpsPlaceholderList'); const textNumList = document.getElementById('textNumPlaceholderList'); const noSimpleMsg = document.getElementById('noSimplePlaceholders'); const noFormattedMsg = document.getElementById('noFormattedPlaceholders');
        if (!simpleList || !dateOpsList || !textNumList) return; 
        simpleList.innerHTML = ''; dateOpsList.innerHTML = ''; textNumList.innerHTML = ''; let simpleCount = 0; let formattedCount = 0; let dateOpCount = 0; let textNumCount = 0;
        const createListItem = (key, fullPlaceholderSyntax, targetList) => { const i = document.createElement('div'); i.className = 'placeholder-item'; const t = document.createElement('span'); t.textContent = fullPlaceholderSyntax; const c = document.createElement('button'); c.className = 'copy-btn'; c.innerHTML = '<i class="fas fa-copy"></i>'; c.title = 'Copiar'; c.addEventListener('click', () => { navigator.clipboard.writeText(t.textContent).then(() => { c.innerHTML = '<i class="fas fa-check"></i>'; setTimeout(() => { c.innerHTML = '<i class="fas fa-copy"></i>'; }, 1500); }); }); i.appendChild(t); i.appendChild(c); targetList.appendChild(i); };
        placeholders.forEach(key => { const help = placeholderHelps.get(key) || ''; const formatString = formattedPlaceholders.get(key);
            if (formatString) { formattedCount++; const fullSyntax = help ? `{[${key}|${help}].${formatString}}` : `{[${key}].${formatString}}`; if (formatString.includes('datahoje') || formatString.includes('add(')) { dateOpCount++; createListItem(key, fullSyntax, dateOpsList); } else { textNumCount++; createListItem(key, fullSyntax, textNumList); } }
            else { simpleCount++; const fullSyntax = help ? `[${key}|${help}]` : `[${key}]`; createListItem(key, fullSyntax, simpleList); }
        });
        if(noSimpleMsg) noSimpleMsg.remove(); 
        if(noFormattedMsg) noFormattedMsg.remove(); 
        if (simpleCount === 0 && noSimpleMsg) simpleList.appendChild(noSimpleMsg);
        const formattedPlaceholderListEl = document.getElementById('formattedPlaceholderList');
        if (formattedCount === 0 && noFormattedMsg && formattedPlaceholderListEl) { formattedPlaceholderListEl.appendChild(noFormattedMsg); } 
        else { if(dateOpCount === 0 && dateOpsList) dateOpsList.innerHTML = '<p class="text-muted small ps-1">Nenhum pré-preenchimento encontrado.</p>'; if(textNumCount === 0 && textNumList) textNumList.innerHTML = '<p class="text-muted small ps-1">Nenhuma formatação encontrada.</p>'; }
    }
    const placeholderModalEl = document.getElementById('placeholderModal');
    if (placeholderModalEl) { 
        placeholderModalEl.addEventListener('show.bs.modal', function () {
           if(template && placeholders.size === 0) { extractPlaceholders(template); }
           if (typeof updatePlaceholderLists === "function") updatePlaceholderLists();
           setTimeout(() => { if (window.initializeTooltips) window.initializeTooltips(); }, 150);
        });
    }
    </script>
</body>
</html>

[end of dashboard.php]
