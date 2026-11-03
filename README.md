# Fito Clinic & Estética — Ecossistema Digital de Atendimento e Gestão

> **Sistema Integrado para Consultório Farmacêutico, Terapias Manuais e Vendas.**

Desenvolvido sob medida para **Rhayane Lopes** — farmacêutica, massagista e empreendedora —, este sistema monolítico em **PHP puro**, Javascript e uso de material em bootstrap, centraliza a gestão clínica, financeira, de estoque e de relacionamento com clientes em uma única plataforma robusta, elegante e intuitiva.

---

## Sobre o Sistema

O **Fito Clinic** foi concebido para atender à versatilidade da atuação profissional de Rhayane, unindo o rigor científico da prescrição farmacêutica e do prontuário eletrônico à sensibilidade e organização exigidas nos atendimentos estéticos e de massoterapia, além do controle comercial de múltiplos produtos e serviços.

### Principais Módulos e Funcionalidades

- 📅 **Agenda Médica & Estética:** Organização completa de horários, grade de atendimentos e status em tempo real (agendado, confirmado, em atendimento, concluído, cancelado).
- 🌐 **Agendamento Online:** Canal integrado para que os pacientes possam agendar horários de forma autônoma.
- 👤 **Cadastro de Pacientes:** Histórico clínico completo, dados de contato, documentos, alergias, preferências e contatos de emergência.
- 📋 **Prontuário Eletrônico:** Registros clínicos digitais estruturados com anamnese, evolução, queixas, sinais vitais e tratamentos.
- 🖊️ **Prescrição Eletrônica:** Emissão de receitas seguras, buscador de medicações no mercado e dosador avançado de vitaminas nutricionais.
- 🧾 **Assinatura & Identidade Visual:** Validação de documentos online e uso de logotipos personalizados em talonários e encaminhamentos.
- 📲 **Automação via WhatsApp:** Lembretes, confirmações de atendimento e sequências de e-mails/mensagens de retorno para aumentar a retenção de pacientes.
- 🩺 **Teleconsultas:** Suporte a atendimentos remotos integrados (via Google Meet ou links externos).
- 💵 **Gestão Financeira & TISS:** Controle completo de receitas e despesas, formas de pagamento e suporte a faturamento com convênios (TISS).
- 📦 **Controle de Estoque:** Gestão de medicamentos, insumos, cosméticos e vitaminas com alertas de estoque mínimo e prazos de validade.

---

## 📂 Arquitetura do Projeto (Monolito PHP)

O sistema segue uma arquitetura limpa inspirada em MVC (Model-View-Controller) adaptada para PHP puro, garantindo alta performance, facilidade de manutenção e independência de frameworks pesados.

```text
.
├── account/             # Autenticação e gestão de contas de usuários (login, cadastro)
├── configs/             # Configurações globais (conexão PDO, guarda de autenticação, CORS)
├── controller/          # Camada de Regra de Negócio / DAOs (Data Access Objects)
├── css/                 # Folhas de estilo customizadas e integração Bootstrap
├── docs.md              # Documentação técnica e guias de referência rápida
├── media/img/           # Ativos visuais (logos, favicons)
├── index.php            # Página inicial / Dashboard principal do painel
├── js/                  # Scripts de interatividade no frontend (app.js)
├── main.php             # Ponto de entrada centralizado do ecossistema
├── models/              # Entidades de Domínio (Classes PHP e mapeamento de dados)
├── pages/               # Controladores de página (processamento de requisições HTTP)
├── sql/                 # Scripts DDL e dumps do banco de dados MySQL
├── views/               # Camada de Apresentação / GUIs (Interfaces HTML/PHP)
└── MATERIAIS do SISTEMA # Materiais de referência, paleta de cores e rascunhos da cliente
```

---

## 🔮 Roadmap Tecnológico: Suporte a Mídia e Arquivos

No ecossistema em expansão da Rhayane, o monolito foi projetado para futuramente suportar o armazenamento e gerenciamento integrado de diversos formatos de arquivos:

- 🖼️ **Imagens (`.jpg`, `.png`):** Armazenamento de fotos de antes/depois de procedimentos estéticos, exames e identidade visual.
- 🎵 **Áudios (`.mp3`):** Gravações de orientações de relaxamento, meditação ou notas de voz clínicas.
- 🎬 **Vídeos (`.mp4`):** Demonstrações de automassagem, exercícios posturais ou videoaulas para pacientes.
- 📄 **Textos e Documentos (`.txt`, `.pdf`):** Relatórios, termos de consentimento livre e esclarecido (TCLE) e atestados.
- 📊 **Planilhas (`.xls`, `.xlsx`, `.csv`):** Importação/exportação de dados financeiros, controle de estoque avançado e relatórios contábeis.

---

## ⚙️ Configuração e Instalação do Ambiente

### Requisitos do Sistema

- **PHP:** Versão 8.1 ou superior.
- **Banco de Dados:** MySQL / MariaDB (rodando na porta padrão `3306`).
- **Servidor Web:** Servidor embutido do PHP ou Apache/Nginx.

### Extensões PHP Necessárias

Certifique-se de ativar as seguintes extensões no seu arquivo `C:/php/php.ini`:

- `extension=pdo_mysql`
- `extension=mbstring`
- `extension=fileinfo`
- `extension=gd` (para manipulação futura de imagens)

---

## Como Executar o Projeto Localmente

1. **Clone ou mova o projeto** para o diretório do seu servidor web local.

2. **Inicie o servidor embutido do PHP** abrindo o terminal na raiz do projeto e executando:

    ```bash
    php -S localhost:8000
    ```

3. **Acesse no navegador:**
   Abra `http://localhost:8000` para carregar a aplicação. O sistema criará automaticamente o banco de dados `fito_clinic` e as tabelas necessárias na primeira execução através da classe `BdConexao`.

---

## 🔒 Segurança e Controle de Acesso

Todas as páginas protegidas na pasta `controller/` e `views/` contam com o guardião de autenticação para bloquear acessos não autorizados:

```php
<?php
    require_once __DIR__ . '/../configs/authGuard.php';
    // Bloqueia e redireciona automaticamente se o usuário não estiver logado
?>
```

---

## 🎨 Identidade Visual & Paleta

O design do sistema foi desenvolvido com base na paleta de cores exclusiva escolhida por Rhayane Lopes, transmitindo profissionalismo, acolhimento, leveza e sofisticação (tons alinhados à estética natural e farmacêutica).

---

_Desenvolvido com dedicação para Fito Clinic & Estética._
