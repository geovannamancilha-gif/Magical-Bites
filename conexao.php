<?php
// Dados do banco
$servidor = "localhost";
$usuario = "root";
$senha = "";
//$senha = ""; //quando trocar de sala, reativar a senha

$banco = "magical_bites";

// Criar conexão
$con = new mysqli($servidor, $usuario, $senha, $banco);

// Verificar conexão
if ($con->connect_error) {
    die("Erro na conexão: " . $con->connect_error);
}




?>