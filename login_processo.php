<?php
session_start();
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Verifica se o usuário existe e se a senha bate (usando password_verify para segurança)
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nome'] = $usuario['nome_usuario'];
        
        // REDIRECIONA PARA O INDEX COM O AVISO
        header("Location: index.php?status=login_sucesso");
        exit();
    } else {
        // Se errar, volta com erro
        header("Location: index.php?status=erro&msg=Dados incorretos");
        exit();
    }
}
?>