<?php
session_start();

// Configurações de conexão com a base de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
$pass = "Home@spSENAI2025!";
//$pass = "Home@spSENAI2025!";  

// Exige utilizador autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php?status=erro&msg=" . urlencode("Precisa de iniciar sessão para guardar favoritos!"));
    exit;
}

// Suporte flexível para a chave do ID do usuário na sessão
$id_usuario = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['id_usuario'] ?? null;
$id_receita = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if (!$id_usuario || $id_receita <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se a receita já está na tabela 'favoritar'
    $stmtCheck = $pdo->prepare("SELECT id_favoritos FROM favoritar WHERE id_usuario = :id_usuario AND id_receita = :id_receita");
    $stmtCheck->execute([
        ':id_usuario' => $id_usuario,
        ':id_receita' => $id_receita
    ]);

    if ($stmtCheck->fetch()) {
        // Se já for favorito, remove (Toggle OFF)
        $stmtDelete = $pdo->prepare("DELETE FROM favoritar WHERE id_usuario = :id_usuario AND id_receita = :id_receita");
        $stmtDelete->execute([
            ':id_usuario' => $id_usuario,
            ':id_receita' => $id_receita
        ]);
    } else {
        // Se não for favorito, adiciona (Toggle ON)
        $stmtInsert = $pdo->prepare("INSERT INTO favoritar (id_usuario, id_receita) VALUES (:id_usuario, :id_receita)");
        $stmtInsert->execute([
            ':id_usuario' => $id_usuario,
            ':id_receita' => $id_receita
        ]);
    }

    // Redirecionamento inteligente (volta para a página que chamou ou para a receita)
    $redirect = $_SERVER['HTTP_REFERER'] ?? "receita.php?id=" . $id_receita;
    header("Location: " . $redirect);
    exit;

} catch (PDOException $e) {
    die("Erro ao processar favorito no banco de dados: " . $e->getMessage());
}
?>