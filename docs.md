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

- tree -I "bootstrap|icons|fonts"

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
