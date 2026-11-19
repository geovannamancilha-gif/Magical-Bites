<?php
// Dados do banco
$servidor = "localhost";
$usuario = "root";
//$senha = "Home@spSENAI2025!";
$senha = "Home@spSENAI2025!"; //quando trocar de sala, reativar a senha

$banco = "magical_bites";

// Criar conexão
$con = new mysqli($servidor, $usuario, $senha, $banco);

// Verificar conexão
if ($con->connect_error) {
    die("Erro na conexão: " . $con->connect_error);
}




?>