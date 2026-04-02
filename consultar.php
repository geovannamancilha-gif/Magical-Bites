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
    <title>Magical Bites | Usuários</title>

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
    }

    /* TOPO */
    .header {
        background: linear-gradient(135deg, #060220, #1f4396);
        padding: 20px;
        text-align: center;
        font-size: 28px;
        font-weight: 600;
    }

    /* TABELA */
    .container {
        width: 80%;
        margin: 40px auto;
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
        text-align: center;
    }

    th {
        background: #1c3f94;
    }

    tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.05);
    }

    /* BOTÃO EXCLUIR */
    .btn-delete {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        background: #ff4d4d;
        color: white;
        cursor: pointer;
        text-decoration: none;
        font-weight: 600;
    }

    .btn-delete:hover {
        background: #cc0000;
    }
    </style>
</head>

<body>

    <div class="header">
        Usuários Cadastrados ✨
    </div>

    <div class="container">

        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Ação</th>
            </tr>

            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id_usuario']; ?></td>
                <td><?php echo $row['nome_usuario']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td>
                    <a class="btn-delete" href="?excluir=<?php echo $row['id_usuario']; ?>">
                        Excluir
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

        </table>

    </div>

</body>

</html>