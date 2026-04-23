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