# pra rodar:

php -S localhost:8000

mexer em C:/php/php.ini sempre que vc puder pra ativar driver e libs do php.

## tanto na pasta "controller" quanto em "views", para so usuarios logados deve ter:

#### EM controller:

```php
<?php
    require_once __DIR__ . '/../configs/authGuard.php';
    // Bloqueia e redireciona se não estiver logado
?>
```

#### EM views:

```php
<?php
    require_once __DIR__ . '/../configs/authGuard.php';
    // Bloqueia e redireciona se não estiver logado
?>
```

## LISTAR PROJETO:

Usuario@DESKTOP-53VHBNN MINGW64 ~/Documents/centro_vscode/zzz SOFTWARE GERAL DIVERSOS/fitoclinic (main)
$ tree -I "media/bootstrap|media/icons|media/fonts"
.
├── MATERIAIS do SISTEMA
│ ├── Others
│ │ ├── oi rhay.txt
│ │ └── zdocs.md
│ ├── WhatsApp Image 2026-09-02 at 13.42.24.jpeg
│ ├── WhatsApp Image 2026-09-02 at 13.42.52.jpeg
│ ├── WhatsApp Image 2026-09-02 at 13.42.53.jpeg
│ ├── cores paleta rhay.png
│ ├── gitbash terminal.txt
│ ├── grade de atendimentos.jpeg
│ ├── paleta q a rhay quer.jpeg
│ ├── rhay promptt.txt
│ ├── rhay sis.txt
│ └── sql
│ ├── fito_clinc_bd.txt
│ └── fito_clinic.sql
├── README.md
├── account
│ ├── cadastro.php
│ ├── conta.php
│ └── login.php
├── configs
│ ├── authGuard.php
│ ├── bdConexao.php
│ └── cors.php
├── controller
│ ├── agendamentosDAO.php
│ ├── comunicacoesDAO.php
│ ├── documentosDAO.php
│ ├── itens_estoqueDAO.php
│ ├── itens_prescricaoDAO.php
│ ├── pacientesDAO.php
│ ├── prescricoesDAO.php
│ ├── prontuariosDAO.php
│ ├── transacoes_financeirasDAO.php
│ └── usuariosDAO.php
├── css
│ ├── php.css
│ └── styles.css
├── docs.md
├── index.php
├── js
│ └── app.js
├── main.php
├── media
│ └── img
│ ├── faviconrhay.png
│ └── logo.png
├── models
│ ├── agendamentos.php
│ ├── comunicacoes.php
│ ├── documentos.php
│ ├── itens_estoque.php
│ ├── itens_prescricao.php
│ ├── pacientes.php
│ ├── prescricoes.php
│ ├── prontuarios.php
│ ├── transacoes_financeiras.php
│ └── usuarios.php
├── pages
│ ├── agendamentosPage.php
│ ├── comunicacoesPage.php
│ ├── documentosPage.php
│ ├── itens_estoquePage.php
│ ├── itens_prescricaoPage.php
│ ├── pacientesPage.php
│ ├── prescricoesPage.php
│ ├── prontuariosPage.php
│ ├── transacoes_financeirasPage.php
│ └── usuariosPage.php
└── views
├── agendamentosGUI.php
├── comunicacoesGUI.php
├── documentosGUI.php
├── itens_estoqueGUI.php
├── itens_prescricaoGUI.php
├── pacientesGUI.php
├── prescricoesGUI.php
├── prontuariosGUI.php
├── transacoes_financeirasGUI.php
└── usuariosGUI.php

14 directories, 68 files

## Do que se trata:

📅 Agenda Médica – Organização completa da agenda

🌐 Agendamento Online – Paciente agenda sozinho

👤 Cadastro de Pacientes – Histórico sempre salvo

📋 Prontuário Eletrônico – Registros clínicos digitais

🖊️ Prescrição Eletrônica – Receitas rápidas e seguras. Buscador de medicações no mercado, e dosador de vitaminas nutricionais para pacientes
🧾

✍️ Assinatura Digital – Documentos validados online

🏷️ Logo em documentos – Identidade visual profissional (para imprimir em talonarios encaminhamentos e receitas de prescrição

📲 Lembrete por WhatsApp – Reduz faltas
✅ Confirmação por WhatsApp
🧾 Faturamento TISS – Integração com convênios

Gestão financeira completa do consultório

📦 Controle de estoque – Medicamentos e insumos

🎥 Tele consultas – Atenda de qualquer lugar (acredito que dá pra fazer pelo Google met)

🔁 Sequência de e-mails de retorno – para aumentar o retorno de pacientes

---
