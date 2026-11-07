<!-- < ? php
// main.php — ponto de entrada; apenas redireciona para a página de usuários.

header('Location: pages/usuariosPage.php');
exit ; -->
<?php
// main.php — Painel principal / Suporte administrativo

require_once __DIR__ . '/../configs/authGuard.php';
require_once __DIR__ . '/../configs/cors.php';
require_once __DIR__ . '/../configs/bdConexao.php';

// =========================================================
// Estatísticas (com fallback seguro — nunca quebra a página)
// =========================================================
function contarSeguro(PDO $pdo, string $tabela, string $where = ''): int
{
    try {
        $sql = "SELECT COUNT(*) FROM `{$tabela}`" . ($where !== '' ? " WHERE {$where}" : '');
        return (int) $pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function somarSeguro(PDO $pdo, string $sql): float
{
    try {
        return (float) $pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0.0;
    }
}

try {
    $pdo = BdConexao::getConexao();
} catch (Throwable $e) {
    $pdo = null;
}

$stats = [
    'pacientes'           => $pdo ? contarSeguro($pdo, 'pacientes') : 0,
    'usuariosAtivos'      => $pdo ? contarSeguro($pdo, 'usuarios', 'ativo = 1') : 0,
    'agendamentosHoje'    => $pdo ? contarSeguro($pdo, 'agendamentos', "data_agendamento = CURDATE()") : 0,
    'agendamentosFuturos' => $pdo ? contarSeguro($pdo, 'agendamentos', "data_agendamento >= CURDATE() AND situacao IN ('agendado','confirmado')") : 0,
    'prontuarios'         => $pdo ? contarSeguro($pdo, 'prontuarios') : 0,
    'prescricoes'         => $pdo ? contarSeguro($pdo, 'prescricoes') : 0,
    'itensEstoque'        => $pdo ? contarSeguro($pdo, 'itens_estoque') : 0,
    'comunicacoesPend'    => $pdo ? contarSeguro($pdo, 'comunicacoes', "status = 'pendente'") : 0,
];

$receitaMes = $pdo ? somarSeguro(
    $pdo,
    "SELECT COALESCE(SUM(valor), 0) FROM transacoes_financeiras
     WHERE tipo = 'receita'
       AND MONTH(data_transacao) = MONTH(CURDATE())
       AND YEAR(data_transacao)  = YEAR(CURDATE())"
) : 0.0;

$totalTabelas    = 10;
$totalModulos    = 10;
$pacientesAtivos = $stats['pacientes'];   // aproximação amigável
$metaMensal      = max(1, $receitaMes * 1.15); // usado só no progress
$progressoMeta   = min(100, (int) round(($receitaMes / $metaMensal) * 100));

// Cartões de módulos (todos os 10)
$modulos = [
    ['icone' => 'bi-people',           'nome' => 'Pacientes',           'desc' => 'Cadastro completo com histórico, CPF e contatos.',         'link' => 'pages/pacientesPage.php'],
    ['icone' => 'bi-calendar-event',   'nome' => 'Agenda',              'desc' => 'Grade de atendimentos, status e teleconsulta.',             'link' => 'pages/agendamentosPage.php'],
    ['icone' => 'bi-file-medical',     'nome' => 'Prontuários',         'desc' => 'Anamnese, evolução, sinais vitais e tratamentos.',         'link' => 'pages/prontuariosPage.php'],
    ['icone' => 'bi-pencil-square',    'nome' => 'Prescrições',         'desc' => 'Receitas eletrônicas com medicamentos e dosagens.',         'link' => 'pages/prescricoesPage.php'],
    ['icone' => 'bi-list-check',       'nome' => 'Itens de Prescrição', 'desc' => 'Detalhamento dos medicamentos prescritos.',                'link' => 'pages/itens_prescricaoPage.php'],
    ['icone' => 'bi-box-seam',         'nome' => 'Estoque',             'desc' => 'Medicamentos, insumos e alertas de reposição.',             'link' => 'pages/itens_estoquePage.php'],
    ['icone' => 'bi-currency-dollar',  'nome' => 'Financeiro',          'desc' => 'Receitas, despesas e fechamento mensal.',                   'link' => 'pages/transacoes_financeirasPage.php'],
    ['icone' => 'bi-whatsapp',         'nome' => 'Comunicações',        'desc' => 'Lembretes e mensagens automáticas via WhatsApp.',           'link' => 'pages/comunicacoesPage.php'],
    ['icone' => 'bi-file-earmark-text', 'nome' => 'Documentos',          'desc' => 'Receitas, atestados e termos de consentimento.',           'link' => 'pages/documentosPage.php'],
    ['icone' => 'bi-person-badge',     'nome' => 'Usuários',            'desc' => 'Administradores, profissionais e recepcionistas.',         'link' => 'pages/usuariosPage.php'],
];

$hoje = new DateTime();
$meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
$dataHoje = $hoje->format('d') . ' de ' . $meses[(int) $hoje->format('n') - 1] . ' de ' . $hoje->format('Y');
$diaSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'][(int) $hoje->format('w')];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Suporte — Fito Clinic</title>
    <link rel="shortcut icon" href="../media/img/faviconrhay.png" type="image/x-icon">
    <link rel="stylesheet" href="../media/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../media/icons/bootstrap-icons.css" />
    <link rel="stylesheet" href="../css/styles.css" />
    <link rel="stylesheet" href="../css/php.css" />
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>
    <div class="app-shell">

        <!-- ============ SIDEBAR ============ -->
        <aside class="sidebar" id="sidebar">
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Fechar menu">
                <span class="bi bi-x-lg"></span>
            </button>

            <a href="/" class="brand">
                <div class="brand-mark" aria-hidden="true">
                    <span class="brand-r"></span>
                </div>
                <div>
                    <strong>Fito Clinic</strong>
                    <small>& Estética</small>
                </div>
            </a>

            <a href="../account/login.php" class="profile-mini" style="text-decoration: none;">
                <div class="avatar"><i class="bi bi-person-circle"></i></div>
                <div>
                    <strong>Conta | Login</strong>
                    <small>Profissional</small>
                </div>
            </a>

            <nav class="nav">
                <a class="nav-item active" href="/"><span class="bi bi-house"></span> Início</a>
                <a class="nav-item" href="../main.php"><span class="bi bi-bar-chart-steps"></span> Painel</a>
                <a class="nav-item" href="../pages/agendamentosPage.php"><span class="bi bi-calendar-event"></span> Agenda</a>
                <a class="nav-item" href="../pages/pacientesPage.php"><span class="bi bi-people"></span> Pacientes</a>
                <a class="nav-item" href="../pages/prontuariosPage.php"><span class="bi bi-file-medical"></span> Prontuários</a>
                <a class="nav-item" href="../pages/prescricoesPage.php"><span class="bi bi-pencil-square"></span> Prescrições</a>
                <a class="nav-item" href="../pages/itens_prescricaoPage.php"><span class="bi bi-list-check"></span> Itens de Prescrição</a>
                <a class="nav-item" href="../pages/transacoes_financeirasPage.php"><span class="bi bi-currency-dollar"></span> Financeiro</a>
                <a class="nav-item" href="../pages/itens_estoquePage.php"><span class="bi bi-box-seam"></span> Estoque</a>
                <a class="nav-item" href="../pages/comunicacoesPage.php"><span class="bi bi-whatsapp"></span> Comunicações</a>
                <a class="nav-item" href="../pages/documentosPage.php"><span class="bi bi-file-earmark-text"></span> Documentos</a>
                <a class="nav-item" href="../pages/usuariosPage.php"><span class="bi bi-person-badge"></span> Usuários</a>
            </nav>

            <div class="sidebar-footer">
                <div class="help-card">
                    <span class="help-icon bi bi-heart"></span>
                    <div>
                        <strong>Precisa de ajuda?</strong>
                        <small>Central de suporte</small>
                    </div>
                </div>
                <small class="copyright">Fito Clinic & Estética</small>
            </div>
        </aside>


        <!-- ============ CONTEÚDO ============ -->
        <main class="main">

            <header class="topbar">
                <button class="menu-btn" type="button" id="menuBtn" aria-label="Abrir menu">
                    <span class="bi bi-list"></span>
                </button>

                <div>
                    <span class="eyebrow">PAINEL DOCS</span>
                    <h1>Suporte Administrativo</h1>
                </div>

                <div class="top-actions">
                    <button class="icon-btn" title="Notificações">
                        <span class="bi bi-bell"></span>
                    </button>
                    <button class="profile-btn">
                        <span class="avatar small"><i class="bi bi-person-circle"></i></span>
                        <span>Conta | Login</span>
                        <span class="bi bi-chevron-down"></span>
                    </button>
                </div>
            </header>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-fito">
                    <li class="breadcrumb-item"><a href="../main.php">Início</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Suporte</li>
                </ol>
            </nav>

            <!-- ============ SOBRE O PROJETO ============ -->
            <section aria-label="Sobre o projeto" class="mb-4">
                <div class="page-header">
                    <div>
                        <h1>Sobre o Fito Clinic</h1>
                        <span class="subtitle">
                            Entenda o propósito, a arquitetura e o roadmap deste ecossistema.
                        </span>
                    </div>
                </div>

                <div class="accordion" id="accordionProjeto">

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSobre">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseSobre" aria-expanded="true" aria-controls="collapseSobre">
                                <i class="bi bi-info-circle me-2"></i> Sobre o Sistema
                            </button>
                        </h2>
                        <div id="collapseSobre" class="accordion-collapse collapse show"
                            aria-labelledby="headingSobre" data-bs-parent="#accordionProjeto">
                            <div class="accordion-body">
                                <p>
                                    O <strong>Fito Clinic &amp; Estética</strong> foi concebido para atender à
                                    versatilidade da atuação profissional de <strong>Conta | Login</strong> —
                                    farmacêutica, massagista e empreendedora — unindo o rigor científico da
                                    prescrição farmacêutica e do prontuário eletrônico à sensibilidade e organização
                                    exigidas nos atendimentos estéticos e de massoterapia, além do controle
                                    comercial de múltiplos produtos e serviços.
                                </p>
                                <p class="mb-0">
                                    Sistema monolítico em <strong>PHP puro</strong>, JavaScript e Bootstrap,
                                    centralizando gestão clínica, financeira, de estoque e de relacionamento com
                                    clientes em uma única plataforma robusta, elegante e intuitiva.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingModulos">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseModulos" aria-expanded="false" aria-controls="collapseModulos">
                                <i class="bi bi-grid-3x3-gap me-2"></i> Principais Módulos
                            </button>
                        </h2>
                        <div id="collapseModulos" class="accordion-collapse collapse"
                            aria-labelledby="headingModulos" data-bs-parent="#accordionProjeto">
                            <div class="accordion-body">
                                <ul class="mb-0">
                                    <li>📅 <strong>Agenda Médica &amp; Estética</strong> — organização de horários, grade de atendimentos e status em tempo real.</li>
                                    <li>🌐 <strong>Agendamento Online</strong> — canal para o paciente agendar autonomamente.</li>
                                    <li>👤 <strong>Cadastro de Pacientes</strong> — histórico completo, dados de contato, alergias e emergência.</li>
                                    <li>📋 <strong>Prontuário Eletrônico</strong> — anamnese, evolução, sinais vitais e tratamentos.</li>
                                    <li>🖊️ <strong>Prescrição Eletrônica</strong> — receitas seguras com buscador de medicações.</li>
                                    <li>✍️ <strong>Assinatura Digital</strong> — validação de documentos online.</li>
                                    <li>🏷️ <strong>Identidade Visual</strong> — logo aplicada em talonários e encaminhamentos.</li>
                                    <li>📲 <strong>Automação WhatsApp</strong> — lembretes, confirmações e sequências de retorno.</li>
                                    <li>🎥 <strong>Teleconsultas</strong> — atendimentos remotos via Google Meet.</li>
                                    <li>💵 <strong>Gestão Financeira &amp; TISS</strong> — receitas, despesas e convênios.</li>
                                    <li>📦 <strong>Controle de Estoque</strong> — medicamentos, insumos e alertas de validade.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingArquitetura">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseArquitetura" aria-expanded="false"
                                aria-controls="collapseArquitetura">
                                <i class="bi bi-diagram-3 me-2"></i> Arquitetura
                            </button>
                        </h2>
                        <div id="collapseArquitetura" class="accordion-collapse collapse"
                            aria-labelledby="headingArquitetura" data-bs-parent="#accordionProjeto">
                            <div class="accordion-body">
                                <p>
                                    O sistema segue uma arquitetura limpa inspirada em
                                    <strong>MVC</strong> (Model–View–Controller) adaptada para PHP puro,
                                    garantindo alta performance, facilidade de manutenção e independência de
                                    frameworks pesados.
                                </p>
                                <pre class="mb-0" style="background:var(--cream-3);padding:14px;border-radius:12px;font-size:.82rem;line-height:1.5;color:var(--ink);white-space:pre-wrap;">.
├── account/       # Login, cadastro e gestão de contas
├── configs/       # Conexão PDO, guarda de autenticação, CORS
├── controller/    # DAOs — Data Access Objects
├── css/           # Estilos customizados (Bootstrap incluso)
├── js/            # Scripts de frontend
├── models/        # Entidades de domínio (classes PHP)
├── pages/         # Controladores de página (HTTP)
├── views/         # Camada de apresentação (HTML/PHP)
├── sql/           # Dumps e DDL
└── main.php       # Dashboard principal (este painel)</pre>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingInstalacao">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseInstalacao" aria-expanded="false"
                                aria-controls="collapseInstalacao">
                                <i class="bi bi-gear me-2"></i> Requisitos &amp; Instalação
                            </button>
                        </h2>
                        <div id="collapseInstalacao" class="accordion-collapse collapse"
                            aria-labelledby="headingInstalacao" data-bs-parent="#accordionProjeto">
                            <div class="accordion-body">
                                <p class="mb-2"><strong>Requisitos</strong></p>
                                <ul>
                                    <li>PHP <strong>8.1+</strong></li>
                                    <li>MySQL / MariaDB (porta padrão 3306)</li>
                                    <li>Servidor embutido do PHP ou Apache/Nginx</li>
                                </ul>
                                <p class="mb-2"><strong>Extensões PHP necessárias</strong></p>
                                <ul>
                                    <li><code>pdo_mysql</code></li>
                                    <li><code>mbstring</code></li>
                                    <li><code>fileinfo</code></li>
                                    <li><code>gd</code> (imagens)</li>
                                </ul>
                                <p class="mb-2"><strong>Como rodar</strong></p>
                                <pre class="mb-0" style="background:var(--cream-3);padding:14px;border-radius:12px;font-size:.85rem;color:var(--ink);">php -S localhost:8000</pre>
                                <p class="mt-3 mb-0">
                                    Acesse <code>http://localhost:8000</code>. O banco
                                    <code>fito_clinic</code> e as tabelas são criados
                                    automaticamente na primeira execução via <code>BdConexao</code>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingRoadmap">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseRoadmap" aria-expanded="false" aria-controls="collapseRoadmap">
                                <i class="bi bi-signpost-split me-2"></i> Roadmap — Mídia &amp; Arquivos
                            </button>
                        </h2>
                        <div id="collapseRoadmap" class="accordion-collapse collapse"
                            aria-labelledby="headingRoadmap" data-bs-parent="#accordionProjeto">
                            <div class="accordion-body">
                                <p>O monolito está preparado para, futuramente, suportar:</p>
                                <ul class="mb-0">
                                    <li>🖼️ <strong>Imagens</strong> (<code>.jpg</code>, <code>.png</code>) — antes/depois de procedimentos e exames.</li>
                                    <li>🎵 <strong>Áudios</strong> (<code>.mp3</code>) — orientações de relaxamento e notas clínicas.</li>
                                    <li>🎬 <strong>Vídeos</strong> (<code>.mp4</code>) — demonstrações de automassagem e exercícios.</li>
                                    <li>📄 <strong>Documentos</strong> (<code>.pdf</code>, <code>.txt</code>) — relatórios e termos de consentimento.</li>
                                    <li>📊 <strong>Planilhas</strong> (<code>.xls</code>, <code>.csv</code>) — importação e exportação financeira.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSeguranca">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseSeguranca" aria-expanded="false"
                                aria-controls="collapseSeguranca">
                                <i class="bi bi-shield-lock me-2"></i> Segurança &amp; Controle de Acesso
                            </button>
                        </h2>
                        <div id="collapseSeguranca" class="accordion-collapse collapse"
                            aria-labelledby="headingSeguranca" data-bs-parent="#accordionProjeto">
                            <div class="accordion-body">
                                <p>
                                    Todas as páginas protegidas exigem o guardião de autenticação:
                                </p>
                                <pre class="mb-3" style="background:var(--cream-3);padding:14px;border-radius:12px;font-size:.82rem;color:var(--ink);">&lt;?php
require_once __DIR__ . '/../configs/authGuard.php';
// Redireciona automaticamente se o usuário não estiver logado
?&gt;</pre>
                                <p class="mb-0">
                                    Tokens <strong>CSRF</strong> são gerados por sessão e validados em
                                    todos os POSTs, e o PDO usa <strong>prepared statements</strong> em
                                    100% das queries para prevenir SQL Injection.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ============ COMPONENTES BOOTSTRAP ============ -->
            <section aria-label="Componentes de interface" class="mb-4">
                <div class="page-header">
                    <div>
                        <h1>Biblioteca de Componentes</h1>
                        <span class="subtitle">
                            Referência rápida dos componentes Bootstrap 5.3 usados no projeto.
                        </span>
                    </div>
                    <span class="badge rounded-pill badge-fito-admin">
                        <i class="bi bi-palette"></i> Design System
                    </span>
                </div>

                <div class="row g-3">

                    <!-- Alerts -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">ALERTS</span>
                                <h2 class="m-0">Notificações</h2>
                            </div>
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="alert-fito alert-sucesso mb-0">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Paciente cadastrado com sucesso.</span>
                                </div>
                                <div class="alert-fito alert-erro mb-0">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Não foi possível agendar: horário ocupado.</span>
                                </div>
                                <div class="alert-fito mb-0">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Lembrete: 3 confirmações pendentes hoje.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">BADGES</span>
                                <h2 class="m-0">Etiquetas</h2>
                            </div>
                            <div class="card-body d-flex flex-wrap gap-2 align-items-start">
                                <span class="badge rounded-pill badge-fito-ativo">Ativo</span>
                                <span class="badge rounded-pill badge-fito-inativo">Inativo</span>
                                <span class="badge rounded-pill badge-fito-admin">Admin</span>
                                <span class="badge rounded-pill badge-fito-prof">Profissional</span>
                                <span class="badge rounded-pill badge-fito-recep">Recepção</span>
                                <span class="badge rounded-pill badge-agendado">Agendado</span>
                                <span class="badge rounded-pill badge-confirmado">Confirmado</span>
                                <span class="badge rounded-pill badge-em-atendimento">Em atendimento</span>
                                <span class="badge rounded-pill badge-concluido">Concluído</span>
                                <span class="badge rounded-pill badge-cancelado">Cancelado</span>
                                <span class="badge rounded-pill badge-faltou">Faltou</span>
                                <span class="badge rounded-pill badge-teleconsulta">
                                    <i class="bi bi-camera-video"></i> Teleconsulta
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">BUTTONS</span>
                                <h2 class="m-0">Botões</h2>
                            </div>
                            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                                <button class="btn-fito"><i class="bi bi-check-lg"></i> Salvar</button>
                                <button class="btn-fito-outline"><i class="bi bi-x-lg"></i> Cancelar</button>
                                <button class="btn-fito-icon" title="Editar"><i class="bi bi-pen"></i></button>
                                <button class="btn-fito-icon" title="Excluir"><i class="bi bi-trash"></i></button>
                                <button class="btn btn-warning"><i class="bi bi-pen"></i> Editar</button>
                                <button class="btn btn-danger"><i class="bi bi-trash"></i> Excluir</button>
                                <button class="btn btn-light">Secundário</button>
                                <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo</button>
                            </div>
                        </div>
                    </div>

                    <!-- Progresso e Spinners -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">PROGRESS &amp; SPINNERS</span>
                                <h2 class="m-0">Progresso</h2>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-fito-muted">Meta financeira do mês</small>
                                    <strong><?= (int) $progressoMeta ?>%</strong>
                                </div>
                                <div class="progress-fito mb-3">
                                    <div class="bar" style="width: <?= (int) $progressoMeta ?>%"></div>
                                </div>

                                <div class="d-flex gap-3 align-items-center">
                                    <div class="spinner-border text-success" role="status" style="width:1.5rem;height:1.5rem;">
                                        <span class="visually-hidden">Carregando…</span>
                                    </div>
                                    <div class="spinner-grow text-warning" role="status" style="width:1.5rem;height:1.5rem;">
                                        <span class="visually-hidden">Carregando…</span>
                                    </div>
                                    <div class="spinner-border text-primary" role="status" style="width:1.5rem;height:1.5rem;">
                                        <span class="visually-hidden">Carregando…</span>
                                    </div>
                                    <small class="text-fito-muted">Indicadores de carregamento</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tooltips + Popovers -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">TOOLTIPS &amp; POPOVERS</span>
                                <h2 class="m-0">Ajuda contextual</h2>
                            </div>
                            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                                <button type="button" class="btn btn-light"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Cadastra um novo paciente no sistema">
                                    <i class="bi bi-person-plus"></i> Tooltip
                                </button>
                                <button type="button" class="btn btn-light"
                                    data-bs-toggle="popover" data-bs-placement="bottom"
                                    data-bs-content="Uma receita pode ter múltiplos itens vinculados.">
                                    <i class="bi bi-info-circle"></i> Popover
                                </button>
                                <span class="tech-badge"><i class="bi bi-cursor"></i> Hover para ver</span>
                            </div>
                        </div>
                    </div>

                    <!-- Toasts -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">TOASTS</span>
                                <h2 class="m-0">Notificações rápidas</h2>
                            </div>
                            <div class="card-body">
                                <div class="toast show w-100 mb-2" role="alert">
                                    <div class="toast-header">
                                        <span class="bi bi-check-circle-fill text-success me-2"></span>
                                        <strong class="me-auto">Sucesso</strong>
                                        <small class="text-fito-muted">agora</small>
                                    </div>
                                    <div class="toast-body">
                                        Agendamento #12 confirmado.
                                    </div>
                                </div>
                                <div class="toast show w-100" role="alert">
                                    <div class="toast-header">
                                        <span class="bi bi-exclamation-triangle-fill text-warning me-2"></span>
                                        <strong class="me-auto">Atenção</strong>
                                        <small class="text-fito-muted">agora</small>
                                    </div>
                                    <div class="toast-body">
                                        2 itens de estoque abaixo do mínimo.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navs e Tabs -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">NAVS &amp; TABS</span>
                                <h2 class="m-0">Navegação</h2>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-3">
                                    <li class="nav-item">
                                        <a class="nav-link active" aria-current="page" href="../#">Hoje</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../#">Semana</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../#">Mês</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link disabled">Ano</a>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="../#">Todos</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../#">Confirmados</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../#">Pendentes</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- List group -->
                    <div class="col-lg-6">
                        <div class="card card-fito h-100">
                            <div class="card-header">
                                <span class="eyebrow gold">LIST GROUP</span>
                                <h2 class="m-0">Listas</h2>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Maria da Silva
                                        <span class="badge rounded-pill badge-confirmado">Confirmado</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Ana Paula Mendes
                                        <span class="badge rounded-pill badge-agendado">Agendado</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Carolina Ribeiro
                                        <span class="badge rounded-pill badge-em-atendimento">Em atendimento</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Paleta de cores -->
                    <div class="col-12">
                        <div class="card card-fito">
                            <div class="card-header">
                                <span class="eyebrow gold">PALETA DE CORES</span>
                                <h2 class="m-0">Identidade visual</h2>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="swatch" style="background:#274b31;">Verde</div>
                                    </div>
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="swatch" style="background:#365c3f;">Verde 2</div>
                                    </div>
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="swatch" style="background:#b8872f;">Dourado</div>
                                    </div>
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="swatch" style="background:#d8b36a;">Dourado 2</div>
                                    </div>
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="swatch light" style="background:#fbf4e8;">Creme</div>
                                    </div>
                                    <div class="col-6 col-md-3 col-lg-2">
                                        <div class="swatch light" style="background:#e6d9c4;">Linha</div>
                                    </div>
                                </div>
                                <p class="text-fito-muted mt-3 mb-0" style="font-size:.85rem;">
                                    As cores são definidas como variáveis CSS em <code>:root</code> e
                                    reutilizadas em todos os módulos.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ============ STACK TECNOLÓGICA ============ -->
            <section aria-label="Stack tecnológica" class="mb-4">
                <div class="page-header">
                    <div>
                        <h1>Stack Tecnológica</h1>
                        <span class="subtitle">Ferramentas e padrões usados em todo o sistema.</span>
                    </div>
                </div>
                <div class="card card-fito">
                    <div class="card-body">
                        <span class="tech-badge"><i class="bi bi-filetype-php"></i> PHP 8.1+</span>
                        <span class="tech-badge"><i class="bi bi-database"></i> MySQL / MariaDB</span>
                        <span class="tech-badge"><i class="bi bi-bootstrap"></i> Bootstrap 5.3</span>
                        <span class="tech-badge"><i class="bi bi-braces"></i> JavaScript (vanilla)</span>
                        <span class="tech-badge"><i class="bi bi-shield-check"></i> CSRF + Prepared Statements</span>
                        <span class="tech-badge"><i class="bi bi-arrow-repeat"></i> AJAX (fetch + debounce)</span>
                        <span class="tech-badge"><i class="bi bi-grid"></i> MVC adaptado</span>
                        <span class="tech-badge"><i class="bi bi-phone"></i> Design responsivo</span>
                        <span class="tech-badge"><i class="bi bi-people"></i> <?= (int) $stats['usuariosAtivos'] ?> usuários ativos</span>
                        <span class="tech-badge"><i class="bi bi-table"></i> <?= (int) $totalTabelas ?> tabelas</span>
                    </div>
                </div>
            </section>



            <footer class="footer">
                <span>Fito Clinic &amp; Estética</span>
                <span>Sistema de gestão clínica &mdash; v1.0</span>
            </footer>
        </main>
    </div>

    <script src="../media/bootstrap/js/bootstrap.js"></script>
    <script src="../js/app.js"></script>
    <script>
        // ---- Sidebar mobile (compatível com app.js) ----
        (function() {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.getElementById('menuBtn');
            const closeBtn = document.getElementById('sidebarClose');
            if (!sidebar || !menuBtn) return;

            menuBtn.addEventListener('click', () => {
                sidebar.classList.add('open');
                document.body.classList.add('sidebar-open');
            });
            closeBtn && closeBtn.addEventListener('click', () => {
                sidebar.classList.remove('open');
                document.body.classList.remove('sidebar-open');
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    document.body.classList.remove('sidebar-open');
                }
            });
        })();

        // ---- Bootstrap tooltips & popovers ----
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
            new bootstrap.Popover(el);
        });
    </script>
</body>

</html>