<!-- < ? php
// main.php — ponto de entrada; apenas redireciona para a página de usuários.

header('Location: pages/usuariosPage.php');
exit ; -->

<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>main</title>
  </head>

  <body>
    <?php echo '
    <h1>Página Principal</h1>
    '; ?>
    <a href="pages/usuariosPage.php">Ir para a Página do Usuário</a>
    <hr />
    <a href="pages/agendamentosPage.php">Ir para a Página de Agendamentos</a>
    <hr />
    <a href="pages/comunicacoesPage.php">Ir para a Página de Comunicações</a>
    <hr />
  </body>
</html>
