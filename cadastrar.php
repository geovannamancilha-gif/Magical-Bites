<?php
include "conexao.php";

$nome = $_POST["nome"];
$email = $_POST["email"];
$senha = $_POST["senha"];

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuario (nome_usuario, email, senha)
        VALUES ('$nome', '$email', '$senhaHash')";

$sucesso = false;

if ($con->query($sql) === TRUE) {
    $sucesso = true;
} else {
    $erro = $con->error;
}

$con->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Magical Bites | Cadastro</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    .container {
        display: flex;
        height: 100vh;
    }

    /* LADO ESQUERDO COM LOGO */
    .left {
        width: 50%;
        background: linear-gradient(135deg, #060220, #1f4396);
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding-left: 250px;
    }

    .logo {
        max-width: 85%;
    }

    /* LADO DIREITO */
    .right {
        width: 50%;
        background: #2e4a92;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .box {
        width: 75%;
        max-width: 420px;
        color: white;
        text-align: center;
    }

    h1 {
        font-size: 32px;
        margin-bottom: 15px;
    }

    p {
        margin-bottom: 30px;
        opacity: 0.9;
    }

    /* BOTÃO */
    button {
        width: 100%;
        padding: 16px;
        border: none;
        border-radius: 40px;
        background: #1c3f94;
        color: white;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: #162f6b;
        transform: scale(1.04);
    }

    /* RESPONSIVO */
    @media(max-width:900px) {
        .container {
            flex-direction: column;
        }

        .left,
        .right {
            width: 100%;
            height: 50vh;
        }

        .left {
            justify-content: center;
            padding-left: 0;
        }

        .logo {
            max-width: 60%;
        }
    }
    </style>
</head>

<body>

    <div class="container">

        <!-- ESQUERDA COM LOGO -->
        <div class="left">
            <img src="logo.png" alt="Magical Bites Logo" class="logo">
        </div>

        <!-- DIREITA COM MENSAGEM -->
        <div class="right">
            <div class="box">

                <?php if ($sucesso): ?>
                <h1>Cadastro realizado! </h1>
                <p>Bem-vindo ao Magical Bites </p>
                <?php else: ?>
                <h1>Erro no cadastro ❌</h1>
                <p><?php echo $erro; ?></p>
                <?php endif; ?>

                <a href="index.html">
                    <button>IR PARA A PÁGINA INICIAL</button>
                </a>

            </div>
        </div>

    </div>

</body>

</html>