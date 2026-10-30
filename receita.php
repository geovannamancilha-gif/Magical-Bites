<?php
session_start();

// Configurações de conexão com a base de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
$pass = "";     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_receita = isset($_GET['id']) ? (int)$_GET['id'] : 1;

    // =========================================================================
    // 1. PROCESSAMENTO DE LOGIN NA PRÓPRIA PÁGINA
    // =========================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['senha']) && !isset($_POST['acao'])) {
        $email = trim($_POST['email']);
        $senha = trim($_POST['senha']);

        $stmtUser = $pdo->prepare("SELECT * FROM usuario WHERE email = :email");
        $stmtUser->execute([':email' => $email]);
        $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($usuario && ($senha === $usuario['senha'] || password_verify($senha, $usuario['senha']))) {
            $_SESSION['usuario'] = [
                'id'    => $usuario['id_usuario'],
                'nome'  => $usuario['nome_usuario'],
                'email' => $usuario['email'],
                'foto'  => !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'avatar1.webp'
            ];
            header("Location: receita.php?id=$id_receita&status=sucesso&msg=" . urlencode("Login efetuado com sucesso!"));
            exit;
        } else {
            header("Location: receita.php?id=$id_receita&status=erro&msg=" . urlencode("Senha ou E-mail incorreto."));
            exit;
        }
    }

    // =========================================================================
    // 2. PROCESSAMENTO DE ENVIO DE AVALIAÇÃO / COMENTÁRIO
    // =========================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'avaliar') {
        if (!isset($_SESSION['usuario'])) {
            header("Location: receita.php?id=$id_receita&status=erro&msg=" . urlencode("Você precisa estar logado para enviar uma avaliação!"));
            exit;
        }

        $sessaoUser = $_SESSION['usuario'];
        $idUsuarioSessao = $sessaoUser['id'] ?? $sessaoUser['id_usuario'] ?? null;
        $emailSessao = $sessaoUser['email'] ?? '';

        $usuarioBD = null;

        if ($idUsuarioSessao) {
            $stmtUserCheck = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = :id");
            $stmtUserCheck->execute([':id' => $idUsuarioSessao]);
            $usuarioBD = $stmtUserCheck->fetch(PDO::FETCH_ASSOC);
        }

        if (!$usuarioBD && !empty($emailSessao)) {
            $stmtUserCheck = $pdo->prepare("SELECT * FROM usuario WHERE email = :email");
            $stmtUserCheck->execute([':email' => $emailSessao]);
            $usuarioBD = $stmtUserCheck->fetch(PDO::FETCH_ASSOC);
        }

        if (!$usuarioBD) {
            unset($_SESSION['usuario']);
            header("Location: receita.php?id=$id_receita&status=erro&msg=" . urlencode("Sua sessão é inválida. Por favor, faça login novamente."));
            exit;
        }

        $idUsuarioValido = $usuarioBD['id_usuario'];

        $_SESSION['usuario']['id'] = $idUsuarioValido;
        $_SESSION['usuario']['nome'] = $usuarioBD['nome_usuario'];
        $_SESSION['usuario']['email'] = $usuarioBD['email'];
        $_SESSION['usuario']['foto'] = $usuarioBD['foto_perfil'];

        $estrelas = isset($_POST['estrelas']) ? (int)$_POST['estrelas'] : 0;
        $comentario = trim($_POST['comentario'] ?? '');

        if ($estrelas < 1 || $estrelas > 5) {
            header("Location: receita.php?id=$id_receita&status=erro&msg=" . urlencode("Por favor, selecione uma nota de 1 a 5 estrelas."));
            exit;
        }

        if (empty($comentario)) {
            header("Location: receita.php?id=$id_receita&status=erro&msg=" . urlencode("Por favor, escreva um comentário sobre a receita."));
            exit;
        }

        try {
            $stmtAvaliar = $pdo->prepare("
                INSERT INTO avaliacoes (id_receita, id_usuario, estrelas, comentario) 
                VALUES (:id_receita, :id_usuario, :estrelas, :comentario)
                ON DUPLICATE KEY UPDATE estrelas = :estrelas2, comentario = :comentario2, data_criacao = CURRENT_TIMESTAMP
            ");
            $stmtAvaliar->execute([
                ':id_receita' => $id_receita,
                ':id_usuario' => $idUsuarioValido,
                ':estrelas'   => $estrelas,
                ':comentario' => $comentario,
                ':estrelas2'  => $estrelas,
                ':comentario2'=> $comentario
            ]);

            $stmtMedia = $pdo->prepare("SELECT AVG(estrelas) as media FROM avaliacoes WHERE id_receita = :id_receita");
            $stmtMedia->execute([':id_receita' => $id_receita]);
            $mediaRow = $stmtMedia->fetch(PDO::FETCH_ASSOC);
            
            if ($mediaRow && $mediaRow['media']) {
                $novaMedia = round($mediaRow['media'], 1);
                $stmtUpdateRec = $pdo->prepare("UPDATE receitas SET avaliacao = :media WHERE id_receita = :id_receita");
                $stmtUpdateRec->execute([
                    ':media' => $novaMedia,
                    ':id_receita' => $id_receita
                ]);
            }

            header("Location: receita.php?id=$id_receita&status=sucesso&msg=" . urlencode("Sua avaliação foi registrada com sucesso!"));
            exit;

        } catch (PDOException $e) {
            header("Location: receita.php?id=$id_receita&status=erro&msg=" . urlencode("Erro ao registrar avaliação na base de dados."));
            exit;
        }
    }

    // BUSCA A RECEITA ATUAL
    $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id_receita = :id");
    $stmt->bindParam(':id', $id_receita, PDO::PARAM_INT);
    $stmt->execute();
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receita) {
        die("<h1 style='color:#fff; text-align:center; margin-top:50px;'>Receita não encontrada na base de dados para o ID: $id_receita!</h1>");
    }

    // BUSCA RECEITAS RECOMENDADAS CONTANDO SUAS AVALIAÇÕES REAIS
    $stmtRecomendadas = $pdo->prepare("
        SELECT r.*, COUNT(a.id_avaliacao) as total_aval 
        FROM receitas r
        LEFT JOIN avaliacoes a ON r.id_receita = a.id_receita
        WHERE r.id_receita != :id_atual AND r.tipo = :tipo
        GROUP BY r.id_receita
        ORDER BY RAND() 
        LIMIT 4
    ");
    $stmtRecomendadas->execute([
        ':id_atual' => $id_receita,
        ':tipo'     => $receita['tipo'] ?? 'Salgado'
    ]);
    $receitasRecomendadas = $stmtRecomendadas->fetchAll(PDO::FETCH_ASSOC);

    if (count($receitasRecomendadas) < 2) {
        $stmtRecomendadas = $pdo->prepare("
            SELECT r.*, COUNT(a.id_avaliacao) as total_aval 
            FROM receitas r
            LEFT JOIN avaliacoes a ON r.id_receita = a.id_receita
            WHERE r.id_receita != :id_atual 
            GROUP BY r.id_receita
            ORDER BY RAND() 
            LIMIT 4
        ");
        $stmtRecomendadas->execute([':id_atual' => $id_receita]);
        $receitasRecomendadas = $stmtRecomendadas->fetchAll(PDO::FETCH_ASSOC);
    }

    // BUSCA AVALIAÇÕES DE CLIENTES DA RECEITA ATUAL
    $stmtAvaliacoesList = $pdo->prepare("
        SELECT a.*, u.nome_usuario, u.foto_perfil 
        FROM avaliacoes a 
        JOIN usuario u ON a.id_usuario = u.id_usuario 
        WHERE a.id_receita = :id_receita 
        ORDER BY a.data_criacao DESC
    ");
    $stmtAvaliacoesList->execute([':id_receita' => $id_receita]);
    $avaliacoesClientes = $stmtAvaliacoesList->fetchAll(PDO::FETCH_ASSOC);
    $totalAvaliacoes = count($avaliacoesClientes);

    // Processamento de ingredientes e preparo
    $lista_ingredientes = array_filter(array_map('trim', explode(";", $receita['ingredientes'])));
    $passos_preparo = array_filter(array_map('trim', preg_split('/(?=\d+\.\s)/', $receita['modo_preparo'])));

    $tipo_formatado = ucfirst(strtolower($receita['tipo'] ?? 'Salgado'));

    // VERIFICAÇÃO SE A RECEITA ESTÁ FAVORITADA
    $isFavorito = false;
    if (isset($_SESSION['usuario'])) {
        $idUsuario = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['id_usuario'] ?? null;
        if ($idUsuario) {
            $stmtFav = $pdo->prepare("SELECT id_favoritos FROM favoritar WHERE id_usuario = :user AND id_receita = :receita");
            $stmtFav->execute([
                ':user' => $idUsuario,
                ':receita' => $id_receita
            ]);
            $isFavorito = (bool)$stmtFav->fetch();
        }
    }

} catch(PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

$status = $_GET['status'] ?? "";
$msg = $_GET['msg'] ?? ($_GET['erro'] ?? "");

// BUSCA AVATARES CASO O UTILIZADOR ESTEJA LOGADO
$avataresPadrao = [];
if (isset($_SESSION['usuario'])) {
    try {
        $stmtAvatares = $pdo->query("SELECT * FROM avatar_predefinido");
        $avataresPadrao = $stmtAvatares->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $avataresPadrao = [];
    }
}

// FUNÇÃO DE CARREGAMENTO DE IMAGENS
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = ['pratos', 'filme', 'logo', 'slider', 'perfis', 'avatares', 'img/perfis', 'img/avatares', 'fundo'];
    $extensoes = ['', '.png', '.jpg', '.jpeg', '.webp'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminhoCompleto = "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoCompleto)) return $caminhoCompleto;

            $caminhoRaiz = $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoRaiz)) return $caminhoRaiz;
        }
    }

    if (file_exists("img/" . $nomeImagem)) return "img/" . $nomeImagem;
    return "img/logo/logo_menu.png";
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($receita['nome_receita']) ?> | Magical Bites</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body { 
            background-color: #0b1329;
            background-image: url('<?= buscarImagem("fundo_receitas") ?>');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #ffffff; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        @media (max-width: 768px) {
            body {
                background-attachment: scroll;
                background-size: cover;
            }
        }

        /* --- NAVBAR PADRONIZADA --- */
        .navbar {
            width: 100%;
            background: #294f88;
            padding: 10px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.20);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo img {
            height: 50px; 
            width: auto;
            display: block;
            margin-top: 0;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .menu a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            transition: 0.3s;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu a:hover, .menu a.ativo, .menu a.active {
            color: #fff6a9ff;
        }

        .menu a i { font-size: 18px; }

        .nav-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9ff;
        }

        /* --- CONTAINER PRINCIPAL CENTRALIZADO --- */
        .container {
            max-width: 1240px;
            margin: 40px auto 70px;
            padding: 0 25px;
        }

        /* LAYOUT CENTRALIZADO COM SIDEBAR */
        .layout-com-sidebar {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 40px;
            align-items: start;
            justify-content: center;
        }

        .conteudo-principal {
            min-width: 0;
            width: 100%;
        }

        /* BARRA LATERAL - RECOMENDAÇÕES */
        .sidebar-recomendados {
            background: rgba(10, 20, 42, 0.7);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            padding: 20px;
            position: sticky;
            top: 90px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .sidebar-titulo {
            font-size: 17px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #fff6a9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-titulo i {
            color: #fff6a9;
        }

        .lista-recomendadas {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card-recomendada {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .card-recomendada:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateX(4px);
            border-color: #fff6a9;
        }

        .card-recomendada img {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .info-recomendada h4 {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .info-recomendada .meta-rec {
            font-size: 11.5px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-recomendada .meta-rec i {
            color: #fbbf24;
        }

        /* HERO RECEITA */
        .hero-receita {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 35px;
            align-items: center;
            margin-bottom: 50px;
        }

        .hero-imagem {
            width: 100%;
            height: 330px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
        }

        .hero-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hero-info .titulo {
            font-size: 34px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            text-shadow: 0 3px 10px rgba(0,0,0,0.6);
        }

        .meta-info {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            color: #ffffff;
            font-weight: 700;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i.fa-clock { color: #ffffff; }
        .meta-item i.fa-star { color: #fbbf24; }

        .tag-categoria {
            background: #fff6a9;
            color: #0b1329;
            padding: 3px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12.5px;
        }

        .descricao {
            font-size: 14px;
            color: #e2e8f0;
            line-height: 1.55;
            margin-top: 4px;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .acoes {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .btn-acao-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: 0.3s ease;
        }

        .icone-circulo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #ffffff;
            transition: 0.3s ease;
        }

        .btn-acao-pill:hover .icone-circulo {
            background: #ffffff;
            color: #0b1329;
            transform: scale(1.08);
        }

        /* FAVORITADO NO TOM AMARELO DO SITE */
        .btn-acao-pill.favoritado {
            color: #fff6a9;
        }

        .btn-acao-pill.favoritado .icone-circulo {
            background: #fff6a9;
            border-color: #fff6a9;
            color: #0b1329;
        }

        /* GRID PREPARO E INGREDIENTES */
        .conteudo-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        .secao-titulo {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 18px;
            text-shadow: 0 3px 8px rgba(0,0,0,0.6);
        }

        .lista-preparo {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .lista-preparo li {
            color: #f1f5f9;
            font-size: 14px;
            line-height: 1.6;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .lista-preparo li strong {
            font-weight: 800;
            color: #ffffff;
        }

        .lista-ingredientes {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .lista-ingredientes li {
            position: relative;
            padding-left: 20px;
            color: #f1f5f9;
            font-size: 14px;
            line-height: 1.5;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .lista-ingredientes li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #ffffff;
            font-size: 18px;
            top: -2px;
        }

        /* --- SEÇÃO DE AVALIAÇÃO --- */
        .secao-avaliacao {
            text-align: center;
            margin-top: 40px;
            padding: 30px 20px;
            background: rgba(10, 20, 42, 0.65);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .secao-avaliacao h3 {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            text-shadow: 0 3px 8px rgba(0,0,0,0.6);
        }

        .estrelas-avaliacao {
            display: flex;
            justify-content: center;
            gap: 10px;
            font-size: 32px;
            color: rgba(255, 255, 255, 0.3);
            margin: 12px 0 8px;
            cursor: pointer;
        }

        .estrelas-avaliacao i {
            transition: all 0.2s ease;
        }

        .estrelas-avaliacao i.ativa {
            color: #fbbf24;
            transform: scale(1.15);
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }

        .subtitulo-avaliacao {
            color: #cbd5e1;
            font-size: 13px;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .campo-comentario textarea {
            width: 100%;
            max-width: 600px;
            padding: 12px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(15, 23, 42, 0.8);
            color: #ffffff;
            font-size: 13.5px;
            outline: none;
            resize: vertical;
            margin-top: 8px;
            transition: 0.3s;
        }

        .campo-comentario textarea:focus {
            border-color: #fff6a9;
            box-shadow: 0 0 12px rgba(255, 246, 169, 0.3);
        }

        .btn-enviar-avaliacao {
            background: #fff8bd;
            color: #ab232a;
            border: none;
            padding: 10px 28px;
            font-weight: 800;
            font-size: 14.5px;
            border-radius: 50px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .btn-enviar-avaliacao:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        /* --- LISTA DE AVALIAÇÕES DOS CLIENTES --- */
        .secao-lista-avaliacoes {
            margin-top: 45px;
        }

        .grid-avaliacoes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 18px;
        }

        .card-avaliacao-cliente {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 18px;
            backdrop-filter: blur(6px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .header-avaliacao-cliente {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar-avaliacao-cliente {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9;
        }

        .info-usuario-aval .nome {
            font-weight: 700;
            font-size: 14px;
            color: #ffffff;
        }

        .info-usuario-aval .data {
            font-size: 10.5px;
            color: #94a3b8;
        }

        .estrelas-cliente {
            color: #fbbf24;
            font-size: 13px;
            display: flex;
            gap: 3px;
        }

        .comentario-cliente {
            font-size: 13px;
            color: #e2e8f0;
            line-height: 1.5;
        }

        /* MODAIS DISNEY */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(18, 24, 41, 0.65);
            backdrop-filter: blur(8px);
            z-index: 999;
        }

        .card-flutuante {
            display: none;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 380px; max-width: 90vw; max-height: 90vh;
            overflow-y: auto;
            border-radius: 36px;
            padding: 35px 28px;
            z-index: 1000;
            animation: popUpModal 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popUpModal {
            from { opacity: 0; transform: translate(-50%, -45%) scale(0.92); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .card-vermelho {
            background: #ab232a;
            color: #ffffff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .card-vermelho .castelo-header { text-align: center; margin-bottom: 12px; }
        .card-vermelho .castelo-header i { font-size: 38px; color: #ffffff; }

        .card-vermelho h2.titulo-cursivo {
            font-family: 'Caveat', cursive;
            font-size: 38px; color: #ffffff; text-align: center; line-height: 1; margin-bottom: 2px;
        }

        .card-vermelho p.subtitulo-modal {
            font-size: 13px; color: rgba(255, 255, 255, 0.9); text-align: center; margin-bottom: 22px; font-weight: 500;
        }

        .card-vermelho .input-group-modal { position: relative; margin-bottom: 14px; }
        .card-vermelho .input-group-modal i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #ffffff; font-size: 15px; }
        .card-vermelho .input-group-modal input {
            width: 100%; padding: 12px 18px 12px 48px; border: 2px solid #ffffff; background: transparent; border-radius: 50px; outline: none; color: #ffffff; font-size: 14px; font-weight: 500; transition: 0.3s;
        }
        .card-vermelho .input-group-modal input::placeholder { color: rgba(255, 255, 255, 0.85); }

        .card-vermelho .btn-acao-creme {
            width: 100%; padding: 13px; background: #fff8bd; color: #ab232a; border: none; border-radius: 50px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s ease; margin-top: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-vermelho .footer-modal { margin-top: 18px; font-size: 13px; text-align: center; color: rgba(255, 255, 255, 0.9); font-weight: 500; }
        .card-vermelho .footer-modal a { color: #ffffff; text-decoration: underline; font-weight: 700; cursor: pointer; }

        .card-amarelo {
            background: #fff8bd; color: #ab232a; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35); text-align: center;
        }
        .card-amarelo .castelo-header i { font-size: 38px; color: #ab232a; margin-bottom: 8px; }
        .card-amarelo h2.titulo-aviso { font-size: 22px; font-weight: 900; color: #ab232a; text-transform: uppercase; line-height: 1.25; margin-bottom: 10px; }
        .card-amarelo h2.titulo-cursivo { font-family: 'Caveat', cursive; font-size: 42px; color: #1f4396; line-height: 1; margin-bottom: 6px; }
        .card-amarelo p.subtitulo-aviso { font-size: 14px; color: #701a1d; font-weight: 600; line-height: 1.4; margin-bottom: 22px; }
        .card-amarelo .btn-acao-vermelho {
            width: 100%; padding: 13px; background: #ab232a; color: #ffffff; border: none; border-radius: 50px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 8px; box-shadow: 0 4px 12px rgba(171, 35, 42, 0.3);
        }

        .btn-fechar-absoluto { position: absolute; top: 18px; right: 22px; background: none; border: none; font-size: 24px; cursor: pointer; color: rgba(255,255,255,0.8); transition: 0.2s; }
        .btn-fechar-absoluto:hover { color: #ffffff; transform: scale(1.1); }
        .card-amarelo .btn-fechar-absoluto { color: rgba(171,35,42,0.7); }

        .perfil-avatar-header { text-align: center; margin-bottom: 12px; }
        .perfil-avatar-header img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

        .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; max-height: 160px; overflow-y: auto; padding: 6px; margin: 12px 0; }
        .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; overflow: hidden; transition: 0.2s; }
        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-item:hover, .avatar-item.selected { border-color: #fff8bd; transform: scale(1.12); }

        @media (max-width: 992px) {
            .layout-com-sidebar { grid-template-columns: 1fr; }
            .sidebar-recomendados { position: static; margin-top: 40px; }
            .hero-receita { grid-template-columns: 1fr; gap: 25px; }
            .hero-imagem { height: 280px; }
            .conteudo-grid { grid-template-columns: 1fr; gap: 30px; }
            .grid-avaliacoes { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <!-- NAVBAR PADRONIZADA -->
    <div class="navbar">
        <div class="logo">
            <a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a>
        </div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas" class="active">Receitas</a>
            <a href="favoritos.php">Favoritos</a>
            <a href="filmes.php">Filmes</a>

            <?php if (isset($_SESSION['usuario'])): ?>
                <a href="javascript:void(0)" onclick="alternarModais('perfil')">
                    <img src="<?= buscarImagem($_SESSION['usuario']['foto'] ?? '') ?>" class="nav-user-avatar" onerror="this.src='img/logo/logo_menu.png'">
                    <?= htmlspecialchars(explode(' ', $_SESSION['usuario']['nome'])[0]) ?>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="alternarModais('login')"><i class="fa-solid fa-user"></i> Perfil</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- OVERLAY DOS MODAIS -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>

    <!-- MODAL CADASTRO -->
    <div id="modalCadastro" class="card-flutuante card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="castelo-header"><i class="fa-solid fa-chess-castle"></i></div>
        <h2 class="titulo-cursivo">Crie a sua conta</h2>
        <p class="subtitulo-modal">Junte-se a nossa família!</p>
        <form action="cadastrar.php" method="POST">
            <div class="input-group-modal"><i class="fa-solid fa-user"></i><input type="text" name="nome" placeholder="Nome Completo" required></div>
            <div class="input-group-modal"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
            <div class="input-group-modal"><i class="fa-solid fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
            <button type="submit" class="btn-acao-creme">Criar</button>
        </form>
        <div class="footer-modal">Já tem uma conta? <a onclick="alternarModais('login')">Faça login</a></div>
    </div>

    <!-- MODAL LOGIN -->
    <div id="modalLogin" class="card-flutuante card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="castelo-header"><i class="fa-solid fa-chess-castle"></i></div>
        <h2 class="titulo-cursivo">Bem - vindo</h2>
        <p class="subtitulo-modal">Acesse sua conta mágica:</p>
        <form action="receita.php?id=<?= $id_receita ?>" method="POST">
            <div class="input-group-modal"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
            <div class="input-group-modal"><i class="fa-solid fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
            <button type="submit" class="btn-acao-creme">Entrar</button>
        </form>
        <div class="footer-modal">Está chegando agora? <a onclick="alternarModais('cadastro')">Crie uma conta</a></div>
    </div>

    <!-- MODAL PERFIL -->
    <?php if (isset($_SESSION['usuario'])): ?>
    <div id="modalPerfil" class="card-flutuante card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="perfil-avatar-header">
            <img src="<?= buscarImagem($_SESSION['usuario']['foto'] ?? '') ?>" id="modalPreviewFoto" onerror="this.src='img/logo/logo_menu.png'">
            <h3 style="color:#ffffff; margin-top:6px; font-size:18px; font-weight:700;"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></h3>
            <p style="font-size:12px; color:rgba(255,255,255,0.8);"><?= htmlspecialchars($_SESSION['usuario']['email']) ?></p>
        </div>
        <h2 class="titulo-cursivo" style="font-size:32px; margin-top:8px;">Escolha seu avatar</h2>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="atualizar_perfil">
            <input type="hidden" name="avatar_predefinido" id="modalInputAvatar" value="">
            <div class="input-group-modal" style="margin-bottom: 8px;">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="nome" value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" required placeholder="Seu Nome">
            </div>
            <div class="grid-avatares-modal">
                <?php foreach ($avataresPadrao as $avatar): ?>
                    <div class="avatar-item" onclick="selecionarAvatarModal('<?= $avatar['caminho_imagem'] ?>', this)">
                        <img src="<?= buscarImagem($avatar['caminho_imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>
                <?php endforeach; ?>
            </div>
            <label style="font-size:12px; font-weight:600; color:#ffffff; display:block; text-align:center; cursor:pointer;" onclick="document.getElementById('fotoUploadInput').click()">
                <i class="fa-solid fa-image"></i> Escolher da Galeria
            </label>
            <input type="file" id="fotoUploadInput" name="foto_upload" accept="image/*" style="display:none;" onchange="limparAvatarModal()">
            <button type="submit" class="btn-acao-creme" style="margin-top:14px;">Salvar alteração</button>
        </form>
        <a href="logout.php" style="display:block; text-align:center; margin-top:12px; color:#ffffff; text-decoration:none; font-weight:600; font-size:13px;">
            <i class="fa-solid fa-right-from-bracket"></i> Sair da conta
        </a>
    </div>
    <?php endif; ?>

    <!-- MODAL STATUS / AVISO -->
    <?php if ($status != ""): ?>
    <div id="modalStatus" class="card-flutuante card-amarelo" style="display: block; z-index: 1001;">
        <button class="btn-fechar-absoluto" onclick="fecharStatus()">&times;</button>
        <div class="castelo-header"><i class="fa-solid fa-chess-castle"></i></div>
        <?php if ($status == 'sucesso'): ?>
            <h2 class="titulo-cursivo">Muito Obrigado!</h2>
            <p class="subtitulo-aviso"><?= htmlspecialchars($msg ?: "Ação realizada com sucesso!") ?></p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Continuar</button>
        <?php elseif ($status == 'erro'): ?>
            <h2 class="titulo-aviso">OOPS!</h2>
            <p class="subtitulo-aviso"><?= htmlspecialchars($msg ?: "Ocorreu um erro ao processar seu pedido") ?></p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Tentar de novo</button>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('modalOverlay').style.display = 'block';
    });
    </script>
    <?php endif; ?>

    <!-- CONTEÚDO PRINCIPAL DA RECEITA COM SIDEBAR -->
    <div class="container">
        <div class="layout-com-sidebar">
            
            <!-- COLUNA PRINCIPAL DA RECEITA -->
            <div class="conteudo-principal">

                <!-- SEÇÃO HERO -->
                <div class="hero-receita">
                    <div class="hero-imagem">
                        <img src="<?= buscarImagem($receita['imagem']) ?>" alt="<?= htmlspecialchars($receita['nome_receita']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>

                    <div class="hero-info">
                        <h1 class="titulo"><?= htmlspecialchars($receita['nome_receita']) ?></h1>

                        <div class="meta-info">
                            <div class="meta-item">
                                <i class="fa-solid fa-clock"></i>
                                <span><?= htmlspecialchars($receita['tempo_preparo']) ?></span>
                            </div>

                            <!-- EXIBIÇÃO CONDICIONAL DA AVALIAÇÃO DA RECEITA PRINCIPAL -->
                            <?php if ($totalAvaliacoes > 0 && !empty($receita['avaliacao'])): ?>
                                <div class="meta-item">
                                    <i class="fa-solid fa-star"></i>
                                    <span><?= number_format((float)$receita['avaliacao'], 1, ',', '.') ?> (<?= $totalAvaliacoes ?> <?= $totalAvaliacoes == 1 ? 'avaliação' : 'avaliações' ?>)</span>
                                </div>
                            <?php else: ?>
                                <div class="meta-item">
                                    <i class="fa-regular fa-star"></i>
                                    <span>Sem avaliações</span>
                                </div>
                            <?php endif; ?>

                            <span class="tag-categoria"><?= htmlspecialchars($tipo_formatado) ?></span>
                        </div>

                        <p class="descricao"><?= htmlspecialchars($receita['descricao']) ?></p>

                        <div class="acoes">
                            <a href="favoritar.php?id=<?= $receita['id_receita'] ?>" class="btn-acao-pill <?= $isFavorito ? 'favoritado' : '' ?>">
                                <div class="icone-circulo"><i class="fa-solid fa-heart"></i></div>
                                <span><?= $isFavorito ? 'Favoritado' : 'Favoritar' ?></span>
                            </a>

                            <a href="#modo-preparo" class="btn-acao-pill">
                                <div class="icone-circulo"><i class="fa-solid fa-utensils"></i></div>
                                <span>Fazer agora</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- CONTEÚDO EM DUAS COLUNAS (MODO PREPARO E INGREDIENTES) -->
                <div class="conteudo-grid" id="modo-preparo">
                    
                    <!-- MODO DE PREPARO -->
                    <div>
                        <h2 class="secao-titulo">Modo de preparo:</h2>
                        <ul class="lista-preparo">
                            <?php 
                            $num = 1;
                            foreach($passos_preparo as $passo): 
                                $textoLimpo = preg_replace('/^\d+\.\s*/', '', $passo);
                            ?>
                                <li><strong><?= $num ?>.</strong> <?= htmlspecialchars($textoLimpo) ?></li>
                            <?php 
                                $num++;
                            endforeach; 
                            ?>
                        </ul>
                    </div>

                    <!-- INGREDIENTES -->
                    <div>
                        <h2 class="secao-titulo">Ingredientes:</h2>
                        <ul class="lista-ingredientes">
                            <?php foreach($lista_ingredientes as $ingrediente): ?>
                                <li><?= htmlspecialchars($ingrediente) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div>

                <!-- SEÇÃO DE AVALIAÇÃO DA RECEITA -->
                <div class="secao-avaliacao">
                    <h3>COMO FICOU ESSA RECEITA?</h3>

                    <?php if (isset($_SESSION['usuario'])): ?>
                        <form action="receita.php?id=<?= $id_receita ?>" method="POST" id="formAvaliacao">
                            <input type="hidden" name="acao" value="avaliar">
                            <input type="hidden" name="estrelas" id="inputEstrelas" value="0">

                            <div class="estrelas-avaliacao" id="estrelasBox">
                                <i class="fa-solid fa-star" data-value="1"></i>
                                <i class="fa-solid fa-star" data-value="2"></i>
                                <i class="fa-solid fa-star" data-value="3"></i>
                                <i class="fa-solid fa-star" data-value="4"></i>
                                <i class="fa-solid fa-star" data-value="5"></i>
                            </div>
                            <p class="subtitulo-avaliacao" id="textoEstrelas">Clique nas estrelas para selecionar uma nota</p>

                            <div class="campo-comentario">
                                <textarea name="comentario" placeholder="Escreva aqui o que achou dessa receita para ajudar outros cozinheiros..." rows="3" required></textarea>
                            </div>

                            <button type="submit" class="btn-enviar-avaliacao">Enviar Avaliação</button>
                        </form>
                    <?php else: ?>
                        <div class="estrelas-avaliacao" style="cursor: default;">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <p class="subtitulo-avaliacao">Faça login para avaliar e deixar seu comentário sobre esta receita!</p>
                        <button type="button" class="btn-enviar-avaliacao" onclick="alternarModais('login')">Fazer Login para Avaliar</button>
                    <?php endif; ?>
                </div>

                <!-- SEÇÃO DE AVALIAÇÕES DOS CLIENTES -->
                <div class="secao-lista-avaliacoes">
                    <h2 class="secao-titulo">Avaliações dos Clientes (<?= $totalAvaliacoes ?>)</h2>

                    <?php if ($totalAvaliacoes > 0): ?>
                        <div class="grid-avaliacoes">
                            <?php foreach ($avaliacoesClientes as $aval): ?>
                                <div class="card-avaliacao-cliente">
                                    <div class="header-avaliacao-cliente">
                                        <img src="<?= buscarImagem($aval['foto_perfil'] ?? 'avatar1.webp') ?>" class="avatar-avaliacao-cliente" onerror="this.src='img/logo/logo_menu.png'">
                                        <div class="info-usuario-aval">
                                            <div class="nome"><?= htmlspecialchars($aval['nome_usuario']) ?></div>
                                            <div class="data"><?= date('d/m/Y \à\s H:i', strtotime($aval['data_criacao'])) ?></div>
                                        </div>
                                    </div>
                                    <div class="estrelas-cliente">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa-<?= $i <= $aval['estrelas'] ? 'solid' : 'regular' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="comentario-cliente"><?= nl2br(htmlspecialchars($aval['comentario'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #cbd5e1; font-size: 14.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Esta receita ainda não possui avaliações. Seja o primeiro a avaliar!</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- BARRA LATERAL COM RECEITAS RECOMENDADAS -->
            <aside class="sidebar-recomendados">
                <h3 class="sidebar-titulo"><i class="fa-solid fa-utensils"></i> Recomendadas</h3>
                <div class="lista-recomendadas">
                    <?php if (!empty($receitasRecomendadas)): ?>
                        <?php foreach ($receitasRecomendadas as $rec): ?>
                            <a href="receita.php?id=<?= $rec['id_receita'] ?>" class="card-recomendada">
                                <img src="<?= buscarImagem($rec['imagem']) ?>" alt="<?= htmlspecialchars($rec['nome_receita']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                                <div class="info-recomendada">
                                    <h4><?= htmlspecialchars($rec['nome_receita']) ?></h4>
                                    <div class="meta-rec">
                                        <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($rec['tempo_preparo']) ?></span>
                                        
                                        <!-- EXIBE NOTA APENAS SE A RECEITA RECOMENDADA TIVER AVALIAÇÃO REAL -->
                                        <?php if (!empty($rec['total_aval']) && $rec['total_aval'] > 0 && !empty($rec['avaliacao'])): ?>
                                            <span><i class="fa-solid fa-star"></i> <?= number_format((float)$rec['avaliacao'], 1, ',', '.') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size: 13px; color: #cbd5e1;">Nenhuma recomendação no momento.</p>
                    <?php endif; ?>
                </div>
            </aside>

        </div>
    </div>

    <!-- JAVASCRIPT DOS MODAIS E AVALIAÇÃO -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const estrelas = document.querySelectorAll('#estrelasBox i');
        const inputEstrelas = document.getElementById('inputEstrelas');
        const textoEstrelas = document.getElementById('textoEstrelas');
        let ratingSelecionado = 0;

        if (estrelas.length > 0) {
            estrelas.forEach((estrela) => {
                estrela.addEventListener('mouseover', function() {
                    const val = parseInt(this.getAttribute('data-value'));
                    destacarEstrelas(val);
                });

                estrela.addEventListener('mouseout', function() {
                    destacarEstrelas(ratingSelecionado);
                });

                estrela.addEventListener('click', function() {
                    ratingSelecionado = parseInt(this.getAttribute('data-value'));
                    inputEstrelas.value = ratingSelecionado;
                    destacarEstrelas(ratingSelecionado);
                    textoEstrelas.innerText = `Você selecionou ${ratingSelecionado} de 5 estrelas`;
                    textoEstrelas.style.color = "#fff6a9";
                });
            });

            function destacarEstrelas(qtd) {
                estrelas.forEach((s) => {
                    const v = parseInt(s.getAttribute('data-value'));
                    if (v <= qtd) {
                        s.classList.add('ativa');
                    } else {
                        s.classList.remove('ativa');
                    }
                });
            }
        }

        const formAvaliacao = document.getElementById('formAvaliacao');
        if (formAvaliacao) {
            formAvaliacao.addEventListener('submit', function(e) {
                if (parseInt(inputEstrelas.value) === 0) {
                    e.preventDefault();
                    alert('Por favor, clique em uma das estrelas para definir sua nota antes de enviar!');
                }
            });
        }
    });

    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'none';
        if(document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
        if(document.getElementById('modalStatus')) document.getElementById('modalStatus').style.display = 'none';
    }

    function fecharStatus() {
        fecharTodosModais();
        window.history.replaceState({}, document.title, "receita.php?id=<?= $id_receita ?>");
    }

    function alternarModais(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
        if (tipo === 'cadastro' && document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'block';
        if (tipo === 'login' && document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'block';
        if (tipo === 'perfil' && document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'block';
    }

    function selecionarAvatarModal(nomeAvatar, elemento) {
        document.getElementById('modalInputAvatar').value = nomeAvatar;
        let avatares = document.querySelectorAll('.avatar-item');
        avatares.forEach(el => el.classList.remove('selected'));
        elemento.classList.add('selected');
        document.getElementById('modalPreviewFoto').src = elemento.querySelector('img').src;
    }

    function limparAvatarModal() {
        document.getElementById('modalInputAvatar').value = '';
        let avatares = document.querySelectorAll('.avatar-item');
        avatares.forEach(el => el.classList.remove('selected'));
    }
    </script>

</body>
</html>