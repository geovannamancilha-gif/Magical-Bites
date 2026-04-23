<?php
include "conexao.php";

// PEGAR ID
if(isset($_GET['id'])){
    $id = $_GET['id'];

    $sql = "SELECT * FROM usuario WHERE id_usuario = $id";
    $result = $con->query($sql);
    $usuario = $result->fetch_assoc();
}

// ATUALIZAR
if(isset($_POST['atualizar'])){
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];

    $sql = "UPDATE usuario 
            SET nome_usuario='$nome', email='$email' 
            WHERE id_usuario=$id";

    if($con->query($sql)){
        header("Location: pagina_usuarios.php");
    } else {
        echo "Erro ao atualizar!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>

  <style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #3d4f8f;
    color: white;
    text-align: center;
}

/* HEADER */
.header {
    background: linear-gradient(135deg, #060220, #2d3f87);
    padding: 25px;
    font-size: 26px;
    font-weight: 600;
}

/* CONTAINER */
.container {
    width: 500px;
    margin: 60px auto;
    background: rgba(255, 255, 255, 0.08);
    padding: 35px;
    border-radius: 15px;
}

/* INPUTS */
input {
    width: 100%;
    padding: 14px;
    margin: 12px 0;
    border: none;
    border-radius: 10px;
    outline: none;
    font-size: 14px;
}

/* BOTÕES */
button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 30px;
    background: #2d4fd7;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #1f3bb3;
    transform: scale(1.05);
}

/* VOLTAR */
.voltar {
    display: inline-block;
    margin-top: 20px;
    color: white;
    text-decoration: none;
    font-size: 14px;
}
</style>
</head>
<body>

<h2>Editar Usuário</h2>

<form method="POST">
    <input type="hidden" name="id" value="<?php echo $usuario['id_usuario']; ?>">

    Nome:<br>
    <input type="text" name="nome" value="<?php echo $usuario['nome_usuario']; ?>"><br><br>

    Email:<br>
    <input type="email" name="email" value="<?php echo $usuario['email']; ?>"><br><br>

    <button type="submit" name="atualizar">Atualizar</button>
</form>

</body>
</html>