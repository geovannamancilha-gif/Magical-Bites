<?php
include "conexao.php";

/* EXCLUSÃO */
if(isset($_GET['excluir'])){
    $id = $_GET['excluir'];
    $con->query("DELETE FROM usuario WHERE id_usuario = $id");
}

/* CONSULTA */
$sql = "SELECT * FROM usuario";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Magical Bites | Painel</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: #2e4a92;
        color: white;
        text-align: center;
    }

    /* CABEÇALHO */
    .header {
        background: linear-gradient(135deg, #060220, #1f4396);
        padding: 25px;
        font-size: 28px;
        font-weight: 600;
    }

    /* BOTÃO MOSTRAR */
    .show-btn {
        margin: 40px;
        padding: 18px 30px;
        font-size: 18px;
        border: none;
        border-radius: 40px;
        background: #1c3f94;
        color: white;
        cursor: pointer;
        transition: 0.3s;
    }

    .show-btn:hover {
        background: #162f6b;
        transform: scale(1.05);
    }

    /* TABELA */
    .container {
        width: 80%;
        margin: 0 auto;
        display: none;
        /* começa escondido */
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }

    th,
    td {
        padding: 15px;
    }

    th {
        background: #1c3f94;
    }

    tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.05);
    }

    /* BOTÕES */
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        color: white;
        text-decoration: none;
        font-weight: 600;
    }

    .edit {
        background: #4CAF50;
    }

    .delete {
        background: #ff4d4d;
    }
    </style>

    <script>
    function mostrarUsuarios() {
        document.getElementById("tabelaUsuarios").style.display = "block";
    }
    </script>

</head>

<body>

    <div class="header">
        Painel Magical Bites ✨
    </div>

    <button class="show-btn" onclick="mostrarUsuarios()">
        Mostrar usuários cadastrados
    </button>

    <div id="tabelaUsuarios" class="container">

        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Ações</th>
            </tr>

            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id_usuario']; ?></td>
                <td><?php echo $row['nome_usuario']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td>
                    <a class="btn edit" href="editar.php?id=<?php echo $row['id_usuario']; ?>">
                        Editar
                    </a>

                    <a class="btn delete" href="?excluir=<?php echo $row['id_usuario']; ?>">
                        Excluir
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

        </table>

    </div>

</body>

</html>