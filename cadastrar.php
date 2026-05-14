<?php
include "conexao.php";

// 1. Recebe os dados do formulário
$nome = isset($_POST["nome"]) ? $_POST["nome"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$senha = isset($_POST["senha"]) ? $_POST["senha"] : "";

if(!empty($nome) && !empty($email) && !empty($senha)) {
    // 2. Criptografa a senha por segurança
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    
    // 3. Prepara a inserção usando Prepared Statements (evita SQL Injection)
    $stmt = $con->prepare("INSERT INTO usuario (nome_usuario, email, senha) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nome, $email, $senhaHash);

    if ($stmt->execute()) {
        // SUCESSO: Redireciona para a index com o parâmetro 'sucesso'
        header("Location: index.php?status=sucesso");
    } else {
        // ERRO NO BANCO: Redireciona com a mensagem de erro
        $erroMsg = urlencode($stmt->error);
        header("Location: index.php?status=erro&msg=$erroMsg");
    }
    
    $stmt->close();
} else {
    // ERRO DE CAMPOS VAZIOS
    header("Location: index.php?status=erro&msg=Preencha todos os campos.");
}

$con->close();
exit(); // Interrompe a execução para garantir o redirecionamento
?>