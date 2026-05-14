<?php
include "conexao.php";

// Sanitização básica (importante para evitar erros de SQL simples)
$nome = isset($_POST["nome"]) ? $_POST["nome"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$senha = isset($_POST["senha"]) ? $_POST["senha"] : "";

$sucesso = false;
$erro = "";

if(!empty($nome) && !empty($email) && !empty($senha)) {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Usando Prepared Statements para segurança
    $stmt = $con->prepare("INSERT INTO usuario (nome_usuario, email, senha) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nome, $email, $senhaHash);

    if ($stmt->execute()) {
        $sucesso = true;
    } else {
        $erro = $stmt->error;
    }
    $stmt->close();
} else {
    $erro = "Todos os campos são obrigatórios.";
}

$con->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Magical Bites | Status do Cadastro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #060220, #1f4396);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .logo {
            width: 120px;
            margin-bottom: 20px;
        }

        .icon {
            font-size: 50px;
            margin-bottom: 20px;
        }

        .success { color: #28a745; }
        .error { color: #dc3545; }

        h2 {
            margin-bottom: 15px;
            color: #333;
        }

        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1f4396;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn:hover {
            background: #162f6b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="logo.png" alt="Magical Bites" class="logo">
        
        <?php if ($sucesso): ?>
            <div class="icon success">✅</div>
            <h2>Cadastro realizado!</h2>
            <p>Seja bem-vindo(a) à nossa cozinha mágica. Sua conta foi criada com sucesso e você já pode explorar todas as receitas.</p>
            <a href="login.html" class="btn">Ir para Login</a>
        <?php else: ?>
            <div class="icon error">❌</div>
            <h2>Ops! Algo deu errado.</h2>
            <p>Não foi possível concluir seu cadastro. <br> <strong>Erro:</strong> <?php echo $erro; ?></p>
            <a href="cadastro.html" class="btn">Tentar novamente</a>
        <?php endif; ?>
    </div>
</body>
</html>