<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Fito Clinic — Painel</title>
    <link rel="shortcut icon" href="media/img/faviconrhay.png" type="image/x-icon">
    <link rel="stylesheet" href="media/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="media/icons/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/styles.css" />
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

            <div class="profile-mini">
                <div class="avatar">RL</div>
                <div>
                    <strong>Rhayane Lopes</strong>
                    <small>Profissional</small>
                </div>
            </div>

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

        <main class="main">
            <header class="topbar">
                <button class="menu-btn" id="menuBtn" aria-label="Abrir menu">
                    <span class="bi bi-list"></span>
                </button>
                <div>
                    <h1>Ecossistema Digital de Atendimento</h1>
                    <span id="currentDate" class="eyebrow">Data atual</span>
                </div>
                <div class="top-actions">
                    <button class="icon-btn" title="Notificações">
                        <span class="bi bi-bell"></span>
                    </button>
                    <a class="profile-btn anti-links" href="/pages/usuariosPage.php">
                        <span class="avatar small">RL</span>
                        <span>Rhayane Lopes</span>
                        <span class="bi bi-chevron-down"></span>
                    </a>
                </div>
            </header>

            <section class="welcome-banner" id="inicio">
                <div>
                    <span class="eyebrow gold">FITO CLINIC & ESTÉTICA</span>
                    <h2>Cuidado que vai além do toque.</h2>
                    <p>
                        Seu consultório organizado em um só lugar, com atendimento, agenda
                        e gestão.
                    </p>
                    <button id="newAppointment" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#appointmentModal">
                        <i class="bi bi-clipboard-plus-fill"></i> Novo atendimento
                    </button>
                </div>
                <div class="banner-decoration" aria-hidden="true">
                    <span class="orb"></span>
                    <span class="orb orb-2"></span>
                    <span class="line"></span>
                    <span class="leaf leaf-c"></span>
                </div>
            </section>

            <section class="stats-grid" aria-label="Resumo">
                <article class="stat-card">
                    <div class="stat-icon"><span class="bi bi-clock"></span></div>
                    <div>
                        <span>Atendimentos hoje</span>
                        <strong>08</strong>
                        <small>+2 em relação a ontem</small>
                    </div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon"><span class="bi bi-people"></span></div>
                    <div>
                        <span>Pacientes cadastrados</span>
                        <strong>248</strong>
                        <small>12 novos este mês</small>
                    </div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon">
                        <span class="bi bi-currency-dollar"></span>
                    </div>
                    <div>
                        <span>Receita do mês</span>
                        <strong>R$ 8.450</strong>
                        <small>Dentro da meta mensal</small>
                    </div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon">
                        <span class="bi bi-check-circle-fill"></span>
                    </div>
                    <div>
                        <span>Confirmações</span>
                        <strong>92%</strong>
                        <small>Via WhatsApp</small>
                    </div>
                </article>
            </section>

            <section class="content-grid">
                <article class="panel agenda-panel" id="agenda">
                    <div class="panel-heading">
                        <div>
                            <span class="eyebrow">AGENDA</span>
                            <h2>Próximos atendimentos</h2>
                        </div>
                        <button class="btn-light">Ver agenda completa →</button>
                    </div>

                    <div class="date-strip">
                        <button class="date-chip">
                            <small>SEG</small><strong>31</strong>
                        </button>
                        <button class="date-chip">
                            <small>TER</small><strong>01</strong>
                        </button>
                        <button class="date-chip">
                            <small>QUA</small><strong>02</strong>
                        </button>
                        <button class="date-chip selected">
                            <small>QUI</small><strong>03</strong>
                        </button>
                        <button class="date-chip">
                            <small>SEX</small><strong>04</strong>
                        </button>
                        <button class="date-chip">
                            <small>SÁB</small><strong>05</strong>
                        </button>
                    </div>

                    <div class="appointments">
                        <div class="appointment">
                            <div class="time">09:00</div>
                            <div class="timeline-dot"></div>
                            <div class="appointment-main">
                                <strong>Maria da Silva</strong>
                                <span>Consulta farmacêutica</span>
                            </div>
                            <span class="status confirmed">Confirmado</span>
                            <button class="more">•••</button>
                        </div>
                        <div class="appointment">
                            <div class="time">10:30</div>
                            <div class="timeline-dot"></div>
                            <div class="appointment-main">
                                <strong>Ana Paula Mendes</strong>
                                <span>Massagem modeladora</span>
                            </div>
                            <span class="status pending">A confirmar</span>
                            <button class="more">•••</button>
                        </div>
                        <div class="appointment">
                            <div class="time">14:00</div>
                            <div class="timeline-dot"></div>
                            <div class="appointment-main">
                                <strong>Carolina Ribeiro</strong>
                                <span>Massagem lifting facial</span>
                            </div>
                            <span class="status confirmed">Confirmado</span>
                            <button class="more">•••</button>
                        </div>
                        <div class="appointment">
                            <div class="time">16:30</div>
                            <div class="timeline-dot"></div>
                            <div class="appointment-main">
                                <strong>Juliana Alves</strong>
                                <span>Combo completo</span>
                            </div>
                            <span class="status pending">A confirmar</span>
                            <button class="more">•••</button>
                        </div>
                    </div>
                </article>

                <article class="panel quick-panel">
                    <div class="panel-heading">
                        <div>
                            <span class="eyebrow">ACESSO RÁPIDO</span>
                            <h2>Ações frequentes</h2>
                        </div>
                    </div>
                    <div class="quick-actions">
                        <button class="quick-action">
                            <span class="bi bi-person-plus"></span>
                            <div>
                                <strong>Novo paciente</strong><small>Cadastrar dados</small>
                            </div>
                            <b>→</b>
                        </button>
                        <button class="quick-action">
                            <span class="bi bi-pencil"></span>
                            <div>
                                <strong>Nova prescrição</strong><small>Receita e orientação</small>
                            </div>
                            <b>→</b>
                        </button>
                        <button class="quick-action">
                            <span class="bi bi-whatsapp"></span>
                            <div>
                                <strong>Enviar WhatsApp</strong><small>Confirmar atendimento</small>
                            </div>
                            <b>→</b>
                        </button>
                        <button class="quick-action">
                            <span class="bi bi-currency-dollar"></span>
                            <div>
                                <strong>Lançar financeiro</strong><small>Registrar pagamento</small>
                            </div>
                            <b>→</b>
                        </button>
                    </div>
                </article>
            </section>

            <section class="bottom-grid">
                <article class="panel" id="pacientes">
                    <div class="panel-heading">
                        <div>
                            <span class="eyebrow">PACIENTES</span>
                            <h2>Últimos cadastros</h2>
                        </div>
                        <button class="btn-light">Ver todos →</button>
                    </div>
                    <div class="patient-list">
                        <div class="patient">
                            <div class="avatar">MS</div>
                            <div>
                                <strong>Maria da Silva</strong><small>Último atendimento hoje</small>
                            </div>
                            <span class="tag">Ativa</span>
                        </div>
                        <div class="patient">
                            <div class="avatar">AP</div>
                            <div>
                                <strong>Ana Paula Mendes</strong><small>Último atendimento 28/08</small>
                            </div>
                            <span class="tag">Ativa</span>
                        </div>
                        <div class="patient">
                            <div class="avatar">CR</div>
                            <div>
                                <strong>Carolina Ribeiro</strong><small>Último atendimento 26/08</small>
                            </div>
                            <span class="tag">Ativa</span>
                        </div>
                    </div>
                </article>

                <article class="panel reminder-panel">
                    <div class="panel-heading">
                        <div>
                            <span class="eyebrow">LEMBRETES</span>
                            <h2>Hoje</h2>
                        </div>
                    </div>
                    <div class="reminder">
                        <span class="reminder-icon bi bi-check"></span>
                        <div>
                            <strong>3 confirmações pendentes</strong><small>Enviar lembrete pelo WhatsApp</small>
                        </div>
                    </div>
                    <div class="reminder">
                        <span class="reminder-icon bi bi-box"></span>
                        <div>
                            <strong>Estoque em atenção</strong><small>2 itens abaixo do mínimo</small>
                        </div>
                    </div>
                    <div class="reminder">
                        <span class="reminder-icon bi bi-currency-dollar"></span>
                        <div>
                            <strong>1 pagamento pendente</strong><small>Conferir financeiro</small>
                        </div>
                    </div>
                </article>
            </section>

            <footer class="footer">
                <span>Fito Clinic & Estética</span>
                <span>Sistema de gestão clínica</span>
            </footer>
        </main>
    </div>

    <div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalLabel">Agendar atendimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="appointmentForm">
                        <div class="mb-3">
                            <label class="form-label">Paciente</label>
                            <input type="text" class="form-control" placeholder="Nome do paciente" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serviço</label>
                            <select class="form-select" required>
                                <option value="">Selecione</option>
                                <option>Consulta farmacêutica</option>
                                <option>Massagem modeladora</option>
                                <option>Massagem relaxante</option>
                                <option>Massagem lifting facial</option>
                                <option>Combo completo</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col">
                                <label class="form-label">Data</label>
                                <input type="date" class="form-control" required />
                            </div>
                            <div class="col">
                                <label class="form-label">Horário</label>
                                <input type="time" class="form-control" required />
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary" type="submit">Salvar agendamento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="media/bootstrap/js/bootstrap.js"></script>
    <script src="js/app.js"></script>
</body>

</html>