<!-- < ? php
// main.php — ponto de entrada; apenas redireciona para a página de usuários.

header('Location: pages/usuariosPage.php');
exit ; -->
<?php
// main.php — Painel principal / Dashboard administrativo

require_once __DIR__ . '/configs/authGuard.php';
require_once __DIR__ . '/configs/cors.php';
require_once __DIR__ . '/configs/bdConexao.php';

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
  <title>Painel Principal — Fito Clinic</title>
  <link rel="shortcut icon" href="media/img/faviconrhay.png" type="image/x-icon">
  <link rel="stylesheet" href="media/bootstrap/css/bootstrap.css" />
  <link rel="stylesheet" href="media/icons/bootstrap-icons.css" />
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/php.css" />
  <link rel="stylesheet" href="css/main.css">
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
        <a class="nav-item" href="pages/agendamentosPage.php"><span class="bi bi-calendar-event"></span> Agenda</a>
        <a class="nav-item" href="pages/pacientesPage.php"><span class="bi bi-people"></span> Pacientes</a>
        <a class="nav-item" href="pages/prontuariosPage.php"><span class="bi bi-file-medical"></span> Prontuários</a>
        <a class="nav-item" href="pages/prescricoesPage.php"><span class="bi bi-pencil-square"></span> Prescrições</a>
        <a class="nav-item" href="pages/itens_prescricaoPage.php"><span class="bi bi-list-check"></span> Itens de Prescrição</a>
        <a class="nav-item" href="pages/transacoes_financeirasPage.php"><span class="bi bi-currency-dollar"></span> Financeiro</a>
        <a class="nav-item" href="pages/itens_estoquePage.php"><span class="bi bi-box-seam"></span> Estoque</a>
        <a class="nav-item" href="pages/comunicacoesPage.php"><span class="bi bi-whatsapp"></span> Comunicações</a>
        <a class="nav-item" href="pages/documentosPage.php"><span class="bi bi-file-earmark-text"></span> Documentos</a>
        <a class="nav-item" href="pages/usuariosPage.php"><span class="bi bi-person-badge"></span> Usuários</a>
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
          <span class="eyebrow">PAINEL PRINCIPAL</span>
          <h1>Dashboard Administrativo</h1>
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
          <li class="breadcrumb-item"><a href="main.php">Início</a></li>
          <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
      </nav>

      <!-- ============ WELCOME BANNER ============ -->
      <section class="welcome-banner" id="inicio">
        <div>
          <span class="eyebrow gold">FITO CLINIC &amp; ESTÉTICA</span>
          <h2>Bem-vinda de volta, Rhayane.</h2>
          <p>
            Hoje é <strong><?= htmlspecialchars($diaSemana, ENT_QUOTES, 'UTF-8') ?>,
              <?= htmlspecialchars($dataHoje, ENT_QUOTES, 'UTF-8') ?></strong>.
            Você tem <strong><?= (int) $stats['agendamentosHoje'] ?>
              atendimento<?= $stats['agendamentosHoje'] === 1 ? '' : 's' ?></strong>
            programado<?= $stats['agendamentosHoje'] === 1 ? '' : 's' ?> para hoje e
            <strong><?= (int) $stats['comunicacoesPend'] ?></strong> comunicação<?= $stats['comunicacoesPend'] === 1 ? '' : 'ões' ?>
            pendente<?= $stats['comunicacoesPend'] === 1 ? '' : 's' ?>.
          </p>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="pages/agendamentosPage.php" class="btn btn-primary">
              <i class="bi bi-calendar-plus"></i> Novo atendimento
            </a>
            <a href="pages/pacientesPage.php" class="btn btn-primary">
              <i class="bi bi-person-plus"></i> Cadastrar paciente
            </a>
          </div>
        </div>

        <div class="banner-decoration" aria-hidden="true">
          <span class="orb"></span>
          <span class="orb orb-2"></span>
          <span class="line"></span>
          <span class="leaf leaf-c"></span>
        </div>
      </section>

      <!-- ============ ATALHOS RÁPIDOS ============ -->
      <section aria-label="Atalhos rápidos" class="mb-4 atalhos-margin">
        <div class="page-header">
          <div>
            <h1>Atalhos e Status.</h1>
            <span class="subtitle">Ações mais frequentes do dia a dia.</span>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6 col-lg-3">
            <a href="pages/pacientesPage.php" class="module-card">
              <div class="module-icon gold"><span class="bi bi-person-plus"></span></div>
              <strong>Novo paciente</strong>
              <small>Cadastrar dados completos.</small>
            </a>
          </div>
          <div class="col-md-6 col-lg-3">
            <a href="pages/agendamentosPage.php" class="module-card">
              <div class="module-icon"><span class="bi bi-calendar-plus"></span></div>
              <strong>Novo agendamento</strong>
              <small>Reservar horário na agenda.</small>
            </a>
          </div>
          <div class="col-md-6 col-lg-3">
            <a href="pages/prontuariosPage.php" class="module-card">
              <div class="module-icon gold"><span class="bi bi-file-medical"></span></div>
              <strong>Novo prontuário</strong>
              <small>Registrar atendimento clínico.</small>
            </a>
          </div>
          <div class="col-md-6 col-lg-3">
            <a href="pages/transacoes_financeirasPage.php" class="module-card">
              <div class="module-icon"><span class="bi bi-cash-coin"></span></div>
              <strong>Lançar financeiro</strong>
              <small>Registrar receita ou despesa.</small>
            </a>
          </div>
        </div>
      </section>

      <!-- ============ STATS GRID ============ -->
      <section class="stats-grid" aria-label="Resumo da clínica">
        <article class="stat-card">
          <div class="stat-icon"><span class="bi bi-people"></span></div>
          <div>
            <span>Pacientes</span>
            <strong><?= (int) $stats['pacientes'] ?></strong>
            <small>cadastro ativo</small>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon"><span class="bi bi-calendar-check"></span></div>
          <div>
            <span>Agendamentos hoje</span>
            <strong><?= (int) $stats['agendamentosHoje'] ?></strong>
            <small><?= (int) $stats['agendamentosFuturos'] ?> futuros</small>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon"><span class="bi bi-currency-dollar"></span></div>
          <div>
            <span>Receita do mês</span>
            <strong>R$ <?= number_format($receitaMes, 2, ',', '.') ?></strong>
            <small><?= (int) $progressoMeta ?>% da meta</small>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon"><span class="bi bi-file-medical"></span></div>
          <div>
            <span>Prontuários</span>
            <strong><?= (int) $stats['prontuarios'] ?></strong>
            <small><?= (int) $stats['prescricoes'] ?> prescrições</small>
          </div>
        </article>
      </section>


      <!-- ============ MÓDULOS ============ -->
      <section aria-label="Módulos do sistema" class="mb-4">
        <div class="page-header">
          <div>
            <h1>Módulos do Sistema</h1>
            <span class="subtitle">Acesse rapidamente qualquer área da clínica.</span>
          </div>
          <span class="badge rounded-pill badge-fito-prof">
            <?= (int) $totalModulos ?> módulos ativos
          </span>
        </div>

        <div class="row g-3">
          <?php foreach ($modulos as $i => $m): ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
              <a class="module-card" href="<?= htmlspecialchars($m['link'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="module-icon <?= $i % 2 === 0 ? '' : 'gold' ?>">
                  <span class="bi <?= htmlspecialchars($m['icone'], ENT_QUOTES, 'UTF-8') ?>"></span>
                </div>
                <strong><?= htmlspecialchars($m['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($m['desc'], ENT_QUOTES, 'UTF-8') ?></small>
                <span class="module-arrow">Acessar &rarr;</span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>


      <footer class="footer">
        <span>Fito Clinic &amp; Estética</span>
        <span>Sistema de gestão clínica &mdash; v1.0</span>
      </footer>
    </main>
  </div>

  <script src="media/bootstrap/js/bootstrap.js"></script>
  <script src="js/app.js"></script>
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