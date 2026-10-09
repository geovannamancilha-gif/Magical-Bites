<?php
session_start();

// Exige utilizador logado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php?status=erro&msg=" . urlencode("Precisa de iniciar sessão para guardar favoritos!"));
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_receita = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_receita <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se já é favorito na tabela 'favoritar'
    $stmtCheck = $pdo->prepare("SELECT * FROM favoritar WHERE id_usuario = :id_usuario AND id_receita = :id_receita");
    $stmtCheck->execute([
        ':id_usuario' => $id_usuario,
        ':id_receita' => $id_receita
    ]);

    if ($stmtCheck->fetch()) {
        // Se já for favorito, remove
        $stmtDelete = $pdo->prepare("DELETE FROM favoritar WHERE id_usuario = :id_usuario AND id_receita = :id_receita");
        $stmtDelete->execute([
            ':id_usuario' => $id_usuario,
            ':id_receita' => $id_receita
        ]);
    } else {
        // Se não for favorito, adiciona
        $stmtInsert = $pdo->prepare("INSERT INTO favoritar (id_usuario, id_receita) VALUES (:id_usuario, :id_receita)");
        $stmtInsert->execute([
            ':id_usuario' => $id_usuario,
            ':id_receita' => $id_receita
        ]);
    }

    // Redireciona de volta para a página onde estava
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "receita.php?id=" . $id_receita;
    header("Location: " . $redirect);
    exit;

} catch (PDOException $e) {
    die("Erro ao processar favorito: " . $e->getMessage());
}
?>