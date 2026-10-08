<?php
session_start();

// 1. Conexão com o banco de dados (ajuste a senha se necessário)
try {
    //$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "");
    $pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header("Location: index.php?status=erro&msg=" . urlencode("Erro na conexão com a base de dados."));
    exit;
}

// 2. Processar o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    // Verifica se os campos foram preenchidos
    if (empty($nome) || empty($email) || empty($senha)) {
        header("Location: index.php?status=erro&msg=" . urlencode("Por favor, preencha todos os campos!"));
        exit;
    }

    // 3. Verificar se o e-mail já existe na tabela usuario
    $stmtCheck = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
    $stmtCheck->execute([':email' => $email]);

    if ($stmtCheck->rowCount() > 0) {
        header("Location: index.php?status=erro&msg=" . urlencode("Este e-mail já está registado!"));
        exit;
    }

    // 4. Criptografar a senha para segurança
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // 5. Inserir o novo utilizador no banco de dados
    try {
        $stmtInsert = $pdo->prepare("INSERT INTO usuario (nome_usuario, email, senha) VALUES (:nome, :email, :senha)");
        $stmtInsert->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $senhaHash
        ]);

        // Redireciona de volta ao index acionando o seu modal de sucesso!
        header("Location: index.php?status=sucesso");
        exit;

    } catch (PDOException $e) {
        header("Location: index.php?status=erro&msg=" . urlencode("Erro ao guardar no banco de dados."));
        exit;
    }
} else {
    // Se tentarem acessar o arquivo diretamente via URL sem enviar o formulário
    header("Location: index.php");
    exit;
}