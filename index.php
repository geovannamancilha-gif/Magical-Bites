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
} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

// =========================================================================
// 1. PROCESSAMENTO DO LOGIN
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['senha']) && !isset($_POST['acao'])) {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmtUser = $pdo->prepare("SELECT * FROM usuario WHERE email = :email");
    $stmtUser->execute([':email' => $email]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($usuario && ($senha === $usuario['senha'] || password_verify($senha, $usuario['senha']))) {
        $_SESSION['usuario'] = [
            'id'    => $usuario['id_usuario'] ?? $usuario['id'] ?? null,
            'nome'  => $usuario['nome_usuario'] ?? $usuario['nome'] ?? 'Usuário',
            'email' => $usuario['email'],
            'foto'  => !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'logo_menu.png'
        ];
        header("Location: index.php");
        exit;
    } else {
        header("Location: index.php?status=erro&msg=" . urlencode("Senha ou E-mail incorreto"));
        exit;
    }
}

// =========================================================================
// 2. PROCESSAMENTO DA ATUALIZAÇÃO DO PERFIL (MODAL)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_perfil' && isset($_SESSION['usuario'])) {
    $idUsuario = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['id_usuario'] ?? null;
    $novoNome = trim($_POST['nome']);
    $fotoPerfil = $_SESSION['usuario']['foto'] ?? 'logo_menu.png'; 

    if (!empty($_POST['avatar_predefinido'])) {
        $fotoPerfil = trim($_POST['avatar_predefinido']);
    }

    if (isset($_FILES['foto_upload']) && $_FILES['foto_upload']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_upload']['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $extensoesPermitidas)) {
            $novoNomeFoto = "user_" . $idUsuario . "_" . time() . "." . $ext;
            $destino = "img/perfis/" . $novoNomeFoto;

            if (!is_dir('img/perfis')) {
                mkdir('img/perfis', 0777, true);
            }

            if (move_uploaded_file($_FILES['foto_upload']['tmp_name'], $destino)) {
                $fotoPerfil = $novoNomeFoto;
            }
        }
    }

    if ($idUsuario) {
        $stmtUpdate = $pdo->prepare("UPDATE usuario SET nome_usuario = :nome, foto_perfil = :foto WHERE id_usuario = :id");
        $stmtUpdate->execute([
            ':nome' => $novoNome,
            ':foto' => $fotoPerfil,
            ':id'   => $idUsuario
        ]);

        $_SESSION['usuario']['nome'] = $novoNome;
        $_SESSION['usuario']['foto'] = $fotoPerfil;
    }

    header("Location: index.php?status=sucesso&msg=" . urlencode("Perfil atualizado com sucesso!"));
    exit;
}

// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS (INCLUI AS PASTAS FUNDOS E FUNDO)
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = [
        'fundos', 'fundo', 'index', 'mouse', 'pratos', 'filme', 'logo', 'slider', 'perfis', 'avatares',
        'img/fundos', 'img/fundo', 'img/index', 'img/mouse', 'img/perfis', 'img/avatares', 'img/logo', 'img/slider'
    ];
    $extensoes = ['', '.png', '.jpg', '.jpeg', '.webp', '.jfif'];

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

// FUNÇÃO ESPECÍFICA PARA LOCALIZAR A IMAGEM DO CURSOR
function buscarImagemCursor($tipo) {
    $nomesPossiveis = ($tipo === 'aberto') 
        ? ['mause_aberto.webp', 'mouse_aberto.webp', 'mause_aberto.png', 'mouse_aberto.png']
        : ['mause_fechado.webp', 'mouse_fechado.webp', 'mause_fechado.png', 'mouse_fechado.png'];

    $pastas = ['', 'img/', 'mouse/', 'img/mouse/', 'fundos/', 'img/fundos/', 'img/logo/', 'img/fundo/'];

    foreach ($pastas as $pasta) {
        foreach ($nomesPossiveis as $nome) {
            $caminho = $pasta . $nome;
            if (file_exists($caminho)) return $caminho;
        }
    }

    return "img/mouse/" . $nomesPossiveis[0];
}

// =========================================================================
// 3. CONSULTAS DINÂMICAS DAS RECEITAS (DO BANCO DE DADOS + FILMES + AVALIAÇÕES)
// =========================================================================
$sqlReceitas = "
    SELECT r.*, f.nome_filme, 
           COUNT(a.id_avaliacao) AS total_avaliacoes, 
           AVG(a.estrelas) AS media_estrelas
    FROM receitas r 
    LEFT JOIN filme f ON r.id_filme = f.id_filme 
    LEFT JOIN avaliacoes a ON r.id_receita = a.id_receita 
    WHERE r.tipo = :tipo 
    GROUP BY r.id_receita 
    LIMIT 6
";

$stmtSalgados = $pdo->prepare($sqlReceitas);
$stmtSalgados->execute([':tipo' => 'SALGADO']);
$receitasSalgados = $stmtSalgados->fetchAll(PDO::FETCH_ASSOC);

$stmtDoces = $pdo->prepare($sqlReceitas);
$stmtDoces->execute([':tipo' => 'DOCE']);
$receitasDoces = $stmtDoces->fetchAll(PDO::FETCH_ASSOC);

$stmtBebidas = $pdo->prepare($sqlReceitas);
$stmtBebidas->execute([':tipo' => 'BEBIDA']);
$receitasBebidas = $stmtBebidas->fetchAll(PDO::FETCH_ASSOC);

$status = $_GET['status'] ?? "";
$msg = $_GET['msg'] ?? ($_GET['erro'] ?? "");

$avataresPadrao = [];
try {
    $stmtAvatares = $pdo->query("SELECT * FROM avatar_predefinido");
    $avataresPadrao = $stmtAvatares->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magical Bites | Receitas Disney</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* --- OCULTA O CURSOR PADRÃO DO SISTEMA --- */
        html, body, *, a, button, input, select, textarea, label {
            cursor: none !important;
        }

        /* --- ESTILO DO CURSOR PERSONALIZADO DO MICKEY --- */
        #mickeyCursor {
            position: fixed;
            top: 0;
            left: 0;
            width: 38px;
            height: auto;
            pointer-events: none;
            z-index: 999999;
            display: none;
            transform: translate(-30%, -10%);
            transition: transform 0.05s ease-out;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #0b1329;
            color: #ffffff;
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
        .navbar {
            width: 100%;
            background: #294f88;
            padding: 4px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.20);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo img {
            height: 620px;
            max-height: 62px;
            width: auto;
            display: block;
            margin: -4px 0;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu a:hover, .menu a.ativo {
            color: #fff6a9ff;
        }

        .nav-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9ff;
        }

        /* --- 1. HERO BANNER SLIDER --- */
        .secao-hero {
            width: 100%;
            position: relative;
            background: #0b1329;
            overflow: hidden;
        }

        .hero-slider-wrapper {
            position: relative;
            width: 100%;
            height: 620px;
            max-height: 750px;
            min-height: 450px;
        }

        .hero-slide {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
            background-size: cover;
            background-position: center center;
        }

        .hero-slide.active {
            opacity: 1;
            visibility: visible;
        }

        .hero-slider-btn {
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            color: #ab232a;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .hero-slider-btn:hover {
            background: #ffffff;
            color: #8e1c22;
            transform: translateY(-50%) scale(1.12);
        }

        .hero-slider-dots {
            position: absolute;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }

        .hero-slider-dots .dot {
            width: 11px;
            height: 11px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .hero-slider-dots .dot.active {
            background: #fff6a9;
            transform: scale(1.3);
        }

        /* --- 2. SECÇÃO: A MAGIA DE CADA PEDAÇO --- */
        .secao-magia {
            background: #fffcef;
            padding: 50px 0 60px 0;
            color: #1e293b;
            position: relative;
            overflow: hidden;
        }

        .container-magia-layout {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0 40px 0 0;
            display: grid;
            grid-template-columns: 580px 1fr;
            gap: 40px;
            align-items: center;
            position: relative;
        }

        .magia-carousel-side {
            position: relative;
            display: flex;
            align-items: center;
        }

        .magia-cards-wrapper {
            display: flex;
            align-items: center;
            position: relative;
            width: 100%;
        }

        .magia-card-peek {
            width: 140px;
            height: 380px;
            border-radius: 0 28px 28px 0;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
            margin-right: 15px;
            box-shadow: 4px 8px 20px rgba(0,0,0,0.15);
        }

        .magia-card-peek img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-seta-magia-left {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #ffffff;
            color: #a62428;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            z-index: 10;
            transition: transform 0.2s;
        }

        .btn-seta-magia-left:hover {
            transform: translateY(-50%) scale(1.1);
            background: #fff8bd;
        }

        .magia-card-main {
            width: 440px;
            height: 410px;
            border-radius: 32px;
            overflow: visible;
            position: relative;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            z-index: 2;
            flex-shrink: 0;
        }

        .magia-card-main img.prato-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 32px;
        }

        .mickey-glove-img {
            position: absolute;
            bottom: -400px;
            left: -65px;
            width: 1000px;
            height: auto;
            z-index: 10;
            filter: drop-shadow(0 8px 12px rgba(0,0,0,0.3));
            pointer-events: none;
        }

        .magia-text-side {
            position: relative;
            max-width: 520px;
            padding-right: 0;
            z-index: 3;
        }

        .magia-header-title {
            display: flex;
            align-items: center;
            gap: 14px;
            justify-content: flex-start;
            margin-bottom: 8px;
        }

        .magia-header-title h2 {
            font-size: 40px;
            font-weight: 900;
            color: #1e3a6a;
            letter-spacing: -0.5px;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .magia-rating-line {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }

        .magia-stars {
            color: #a62428;
            font-size: 18px;
            letter-spacing: 2px;
        }

        .magia-rating-text {
            color: #a62428;
            font-size: 14.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .magia-p-main, .magia-p-sub {
            font-size: 14.5px;
            color: #2c3e50;
            line-height: 1.65;
            font-weight: 500;
            margin-bottom: 14px;
        }

        .mickey-feiticeiro-img {
            position: absolute;
            right: -1200px;
            bottom: -198px;
            width: 1300px;
            height: auto;
            pointer-events: none;
            z-index: 5;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.25));
        }

        /* --- 3. SECÇÃO: PESQUISA E CATEGORIAS --- */
        .secao-busca-categorias {
            background-color: #24487a;
            padding: 50px 0 65px;
        }

        .container-secao {
            max-width: 1150px;
            margin: 0 auto;
            padding: 0 25px;
        }

        .search-home-box {
            position: relative;
            width: 100%;
            max-width: 620px;
            margin: 0 auto 45px;
        }

        .search-home-box input {
            width: 100%;
            padding: 14px 25px 14px 50px;
            border-radius: 50px;
            border: none;
            background: #fffdf5;
            font-size: 15px;
            color: #1e293b;
            outline: none;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .search-home-box input:focus {
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        .search-home-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #24487a;
            font-size: 18px;
        }

        .grid-3-categorias {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            max-width: 1050px;
            margin: 0 auto;
        }

        .card-categoria-item {
            position: relative;
            height: 380px;
            border-radius: 28px;
            overflow: hidden;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 30px 20px;
            text-align: center;
        }

        .card-categoria-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
        }

        .card-categoria-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            transition: transform 0.5s ease;
        }

        .card-categoria-item:hover .card-categoria-bg {
            transform: scale(1.06);
        }

        .card-categoria-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.25) 50%, rgba(0,0,0,0.7) 100%);
            z-index: 2;
        }

        .card-cat-tag {
            position: relative;
            z-index: 3;
            font-size: 13px;
            font-weight: 800;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .card-cat-titulo {
            position: relative;
            z-index: 3;
            font-size: 20px;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.25;
            text-shadow: 0 3px 10px rgba(0,0,0,0.6);
            margin: auto 0;
        }

        /* --- 4. SECÇÃO: RECEITAS SALGADAS (FUNDO DE FUNDOS) --- */
        .secao-salgados {
            position: relative;
            background-image: url('<?= buscarImagem("fundo2_index") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 60px 0 50px;
            overflow: hidden;
        }

        /* --- 6. SECÇÃO: RECEITAS DOCES (FUNDO DE FUNDOS) --- */
        .secao-doces {
            position: relative;
            background-image: url('<?= buscarImagem("fundo1_index") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 60px 0 50px;
            overflow: hidden;
        }

        /* --- 8. SECÇÃO: RECEITAS BEBIDAS (FUNDO DE FUNDOS) --- */
        .secao-bebidas {
            position: relative;
            background-image: url('<?= buscarImagem("fundo3_index") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 60px 0 50px;
            overflow: hidden;
        }

        .secao-salgados::before, .secao-doces::before, .secao-bebidas::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 40px 70px, #fff8bd, rgba(0,0,0,0)),
                radial-gradient(1.5px 1.5px at 150px 120px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 280px 40px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(2.5px 2.5px at 400px 180px, #fff8bd, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 600px 90px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(1.5px 1.5px at 750px 220px, #ffffff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 900px 50px, #fff8bd, rgba(0,0,0,0)),
                radial-gradient(2.5px 2.5px at 1100px 150px, #ffffff, rgba(0,0,0,0));
            background-repeat: repeat;
            background-size: 1200px 400px;
            opacity: 0.85;
            pointer-events: none;
        }

        .grid-home-receitas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 35px 28px;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .item-receita-clean {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-decoration: none;
            color: #ffffff;
            transition: transform 0.3s ease;
            background: transparent !important;
            border: none;
            box-shadow: none;
            padding: 12px;
        }

        .item-receita-clean:hover {
            transform: translateY(-6px);
        }

        .item-receita-img {
            width: 100%;
            height: 270px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .item-receita-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-filme {
            display: inline-block;
            background-color: #ff9800;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            align-self: flex-start;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        .item-receita-titulo {
            font-size: 16px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.3;
            margin-top: 2px;
            text-shadow: 0 2px 6px rgba(0,0,0,0.8);
        }

        .item-receita-desc {
            font-size: 12.5px;
            color: #e2e8f0;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-receita-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #ffc107;
            font-size: 14px;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .btn-center-wrapper {
            text-align: center;
            margin: 10px 0 20px;
            position: relative;
            z-index: 2;
        }

        .btn-ver-mais-vermelho {
            display: inline-block;
            padding: 14px 60px;
            background: #a62428;
            color: #ffffff;
            border-radius: 50px;
            font-weight: 800;
            font-size: 15px;
            text-decoration: none;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(166, 36, 40, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-ver-mais-vermelho:hover {
            background: #881c20;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(166, 36, 40, 0.7);
        }

        .btn-ver-mais-creme {
            display: inline-block;
            padding: 14px 60px;
            background: #fff8bd;
            color: #a62428;
            border-radius: 50px;
            font-weight: 800;
            font-size: 15px;
            text-decoration: none;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-ver-mais-creme:hover {
            background: #ffffff;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        }

        /* --- 5. & 7. & 9. SECÇÕES FULL-WIDTH --- */
        .secao-full-vermelha, .secao-full-creme {
            width: 100%;
            position: relative;
            padding: 65px 0;
            overflow: hidden;
            min-height: 480px;
            display: flex;
            align-items: center;
        }

        .secao-full-vermelha {
            background: linear-gradient(135deg, #a62428 0%, #881c20 100%);
            box-shadow: inset 0 10px 20px -10px rgba(0,0,0,0.3), inset 0 -10px 20px -10px rgba(0,0,0,0.3);
        }

        .secao-full-creme {
            background: linear-gradient(135deg, #fff8bd 0%, #f7f0aa 100%);
            color: #a62428;
            box-shadow: inset 0 10px 20px -10px rgba(0,0,0,0.15), inset 0 -10px 20px -10px rgba(0,0,0,0.15);
        }

        .container-full-vermelho, .container-full-creme {
            max-width: 100%;
            margin: 0;
            padding: 0 0 0 5%;
            width: 100%;
            display: grid;
            grid-template-columns: minmax(500px, 620px) 1fr;
            gap: 20px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .conteudo-banner-texto {
            max-width: 600px;
        }

        .titulo-banner-red {
            font-size: 46px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.1;
            margin-bottom: 16px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
            letter-spacing: -0.8px;
        }

        .script-banquete {
            font-family: 'Caveat', cursive;
            font-size: 98px;
            font-weight: 700;
            display: inline-block;
            margin-right: 6px;
            line-height: 0.7;
            vertical-align: middle;
            color: #fff8bd;
            text-shadow: 0 4px 10px rgba(0,0,0,0.4);
        }

        .combo-sub {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: #fff8bd;
            margin-bottom: 16px;
            line-height: 1.4;
            letter-spacing: 0.3px;
        }

        .combo-desc {
            font-size: 16px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .titulo-banner-creme {
            font-size: 46px;
            font-weight: 900;
            color: #a62428;
            line-height: 1.1;
            margin-bottom: 16px;
            letter-spacing: -0.8px;
        }

        .script-festival {
            font-family: 'Caveat', cursive;
            font-size: 98px;
            font-weight: 700;
            display: inline-block;
            margin-right: 6px;
            line-height: 0.7;
            vertical-align: middle;
            color: #a62428;
        }

        .combo-sub-creme {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: #a62428;
            margin-bottom: 16px;
            line-height: 1.4;
            letter-spacing: 0.3px;
        }

        .combo-desc-creme {
            font-size: 16px;
            font-weight: 400;
            color: #4a1012;
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .banner-red-img, .banner-creme-img {
            position: absolute;
            top: 0;
            right: 0;
            width: 55%;
            height: 100%;
            border-radius: 0;
            overflow: hidden;
            z-index: 1;
        }

        .banner-red-img img, .banner-creme-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 35%);
            mask-image: linear-gradient(to right, transparent 0%, black 35%);
        }

        /* --- BOTÃO FLUTUANTE DE VOLTAR AO TOPO --- */
        .btn-flutuante-voltar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 52px;
            height: 52px;
            background-color: #a62428;
            color: #fff3bc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 22px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
            transition: opacity 0.35s ease, visibility 0.35s ease, transform 0.3s ease, background-color 0.3s ease;
            cursor: pointer;
            border: 2.5px solid #fff3bc;
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .btn-flutuante-voltar.visivel {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .btn-flutuante-voltar:hover {
            transform: scale(1.12);
            background-color: #831a1e;
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.65);
        }

        /* --- RODAPÉ MÁGICO DO SITE --- */
        .site-footer {
            position: relative;
            background-image: linear-gradient(180deg, rgba(11, 19, 41, 0.40) 0%, rgba(11, 19, 41, 0.85) 100%), url('<?= buscarImagem("dama_vagabundo") ?>');
            background-size: cover;
            background-position: right 80%;
            background-repeat: no-repeat;
            color: #ffffff;
            padding: 60px 20px 30px;
            border-top: 4px solid #fff8bd;
            z-index: 10;
        }

        .footer-card-wrapper {
            max-width: 800px;
            margin: 0 0 0 3%;
            padding: 10px 15px;
            background: transparent;
        }

        .footer-container {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr 1.4fr;
            gap: 25px;
            text-align: left;
        }

        .footer-col h3 {
            font-size: 18px;
            font-weight: 800;
            color: #fff8bd;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            position: relative;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.9);
        }

        .footer-col h3::after {
            content: '';
            display: block;
            width: 35px;
            height: 3px;
            background: #fff8bd;
            margin-top: 8px;
            border-radius: 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
        }

        .footer-about img {
            height: 85px;
            width: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.8));
        }

        .footer-about p {
            font-size: 13.5px;
            color: #ffffff;
            line-height: 1.6;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.9);
            font-weight: 500;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s ease, padding-left 0.3s ease;
            display: inline-block;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.9);
        }

        .footer-links a:hover {
            color: #fff8bd;
            padding-left: 5px;
        }

        .footer-social {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            background: rgba(11, 19, 41, 0.65);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 248, 189, 0.4);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.4);
        }

        .footer-social a:hover {
            background: #a62428;
            color: #fff8bd;
            border-color: #fff8bd;
            transform: translateY(-4px);
        }

        .footer-bottom {
            max-width: 800px;
            margin: 35px 0 0 0;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            text-align: left;
            font-size: 13px;
            color: #ffffff;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.9);
            font-weight: 500;
        }

        .footer-bottom i {
            color: #fff8bd;
        }

        /* --- MODAIS FLUTUANTES --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(18, 24, 41, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
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

        .card-combo-modal {
            display: none;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 740px;
            max-width: 92vw;
            max-height: 88vh;
            overflow-y: auto;
            border-radius: 36px;
            padding: 35px 30px;
            z-index: 1000;
            animation: popUpModal 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popUpModal {
            from { opacity: 0; transform: translate(-50%, -45%) scale(0.92); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .card-vermelho {
            background: #a62428;
            color: #ffffff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .card-amarelo {
            background: #fff8bd;
            color: #a62428;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            border: 2px solid rgba(166, 36, 40, 0.2);
        }

        .combo-modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .combo-modal-header h2 {
            font-family: 'Caveat', cursive;
            font-size: 46px;
            line-height: 1;
            margin-bottom: 6px;
        }

        .combo-modal-header p {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.92;
        }

        .grid-combo-items {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 15px;
        }

        .item-combo-card {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 22px;
            padding: 14px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.15);
        }

        .card-amarelo .item-combo-card {
            background: rgba(166, 36, 40, 0.08);
            border: 1px solid rgba(166, 36, 40, 0.15);
        }

        .item-combo-card img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        .item-combo-card h4 {
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .item-combo-card p {
            font-size: 12px;
            line-height: 1.4;
            opacity: 0.9;
            margin-bottom: 12px;
        }

        .btn-ver-receita-combo {
            display: inline-block;
            margin-top: auto;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 800;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        .card-vermelho .btn-ver-receita-combo {
            background: #fff8bd;
            color: #a62428;
        }

        .card-vermelho .btn-ver-receita-combo:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0,0,0,0.3);
        }

        .card-amarelo .btn-ver-receita-combo {
            background: #a62428;
            color: #ffffff;
        }

        .card-amarelo .btn-ver-receita-combo:hover {
            background: #881c20;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(166,36,40,0.4);
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
            width: 100%; padding: 13px; background: #fff8bd; color: #a62428; border: none; border-radius: 50px; font-weight: 800; font-size: 16px; transition: 0.3s ease; margin-top: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-vermelho .footer-modal { margin-top: 18px; font-size: 13px; text-align: center; color: rgba(255, 255, 255, 0.9); font-weight: 500; }
        .card-vermelho .footer-modal a { color: #ffffff; text-decoration: underline; font-weight: 700; }

        .btn-fechar-absoluto { position: absolute; top: 18px; right: 22px; background: none; border: none; font-size: 24px; color: rgba(255,255,255,0.8); transition: 0.2s; }
        .btn-fechar-absoluto:hover { color: #ffffff; transform: scale(1.1); }
        .card-amarelo .btn-fechar-absoluto { color: rgba(166,36,40,0.7); }
        .card-amarelo .btn-fechar-absoluto:hover { color: #a62428; }

        .perfil-avatar-header { text-align: center; margin-bottom: 12px; }
        .perfil-avatar-header img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; max-height: 160px; overflow-y: auto; padding: 6px; margin: 12px 0; }
        .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; overflow: hidden; transition: 0.2s; }
        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-item:hover, .avatar-item.selected { border-color: #fff8bd; transform: scale(1.12); }

        @media (max-width: 1024px) {
            .hero-slider-wrapper {
                height: 480px;
                min-height: 350px;
            }
        }

        @media (max-width: 900px) {
            .container-magia-layout { grid-template-columns: 1fr; text-align: center; padding: 0 25px; }
            .magia-text-side { max-width: 100%; padding-right: 0; }
            .mickey-feiticeiro-img { position: relative; right: 0; bottom: 0; margin-top: 15px; }
            .grid-3-categorias { grid-template-columns: 1fr; }
            .container-full-vermelho, .container-full-creme { grid-template-columns: 1fr; text-align: center; padding: 0 20px; }
            .conteudo-banner-texto { max-width: 100%; }
            .grid-home-receitas { grid-template-columns: 1fr; }
            .footer-container { grid-template-columns: 1fr 1fr; }
            .footer-card-wrapper { margin: 0 auto; }

            .banner-red-img, .banner-creme-img {
                position: relative;
                width: 100%;
                height: 280px;
                margin-top: 25px;
            }

            .banner-red-img img, .banner-creme-img img {
                -webkit-mask-image: none;
                mask-image: none;
                border-radius: 24px;
            }
        }

        @media (max-width: 700px) {
            .grid-combo-items { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .hero-slider-wrapper {
                height: 320px;
                min-height: 240px;
            }
            .hero-slider-btn {
                right: 12px;
                width: 38px;
                height: 38px;
                font-size: 15px;
            }
            .btn-flutuante-voltar {
                width: 46px;
                height: 46px;
                font-size: 18px;
                bottom: 20px;
                right: 20px;
            }
        }

        @media (max-width: 600px) {
            .footer-container { grid-template-columns: 1fr; }
            .footer-card-wrapper { padding: 10px 0; }
        }
    </style>
</head>

<body>

    <!-- ELEMENTO DO CURSOR DO MICKEY -->
    <img id="mickeyCursor" src="<?= buscarImagemCursor('aberto') ?>" alt="Cursor Mickey">

    <!-- NAVBAR -->
    <div class="navbar">
      <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php" class="ativo">Início</a>
            <a href="#containerReceitas">Receitas</a>
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

    <!-- 1. MODAL CADASTRO -->
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

    <!-- 2. MODAL LOGIN -->
    <div id="modalLogin" class="card-flutuante card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="castelo-header"><i class="fa-solid fa-chess-castle"></i></div>
        <h2 class="titulo-cursivo">Bem - vindo</h2>
        <p class="subtitulo-modal">Acesse sua conta mágica:</p>
        <form action="index.php" method="POST">
            <div class="input-group-modal"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
            <div class="input-group-modal"><i class="fa-solid fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
            <button type="submit" class="btn-acao-creme">Entrar</button>
        </form>
        <div class="footer-modal">Está chegando agora? <a onclick="alternarModais('cadastro')">Crie uma conta</a></div>
    </div>

    <!-- 3. MODAL PERFIL -->
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
            <label style="font-size:12px; font-weight:600; color:#ffffff; display:block; text-align:center;" onclick="document.getElementById('fotoUploadInput').click()">
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

    <!-- 4. MODAL STATUS / AVISO -->
    <?php if ($status != ""): ?>
    <div id="modalStatus" class="card-flutuante card-amarelo" style="display: block; z-index: 1001;">
        <button class="btn-fechar-absoluto" onclick="fecharStatus()">&times;</button>
        <div class="castelo-header"><i class="fa-solid fa-chess-castle"></i></div>
        <?php if ($status == 'sucesso'): ?>
            <h2 class="titulo-cursivo">Bem - vindo</h2>
            <p class="subtitulo-aviso"><?= htmlspecialchars($msg ?: "Sua conta está registrada, agora é só aproveitar!") ?></p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Aproveitar agora</button>
        <?php elseif ($status == 'erro'): ?>
            <h2 class="titulo-aviso">OOPS!</h2>
            <p class="subtitulo-aviso"><?= htmlspecialchars($msg ?: "Senha ou E-mail incorreto") ?></p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Tentar de novo</button>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('modalOverlay').style.display = 'block';
    });
    </script>
    <?php endif; ?>

    <!-- 5. MODAL COMBO: BANQUETE REAL NA FLORESTA ENCANTADA -->
    <div id="modalComboBanquete" class="card-combo-modal card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="combo-modal-header">
            <h2>Banquete Real na Floresta Encantada</h2>
            <p>Desperte em um verdadeiro conto de fadas! Saboreie aromas envolventes e acompanhamentos crocantes perfeitos para começar o dia com espírito de aventura.</p>
        </div>
        <div class="grid-combo-items">
            <div class="item-combo-card">
                <img src="<?= buscarImagem('pocao_da_bruxa') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Poção da Bruxa">
                <h4>Poção da Bruxa</h4>
                <p>Bebida quente, aromática e aconchegante com especiarias.</p>
                <a href="receita.php?id=57" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="item-combo-card">
                <img src="<?= buscarImagem('petisco_pongo') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Petiscos do Pongo">
                <h4>Petiscos do Pongo</h4>
                <p>Rolinhos de carne moída recheados com queijo e bacon.</p>
                <a href="receita.php?id=33" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="item-combo-card">
                <img src="<?= buscarImagem('torta_maca_branca_neve') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Torta de Maçã">
                <h4>Torta de Maçã</h4>
                <p>A clássica torta de maçã da Branca de Neve sem feitiço.</p>
                <a href="receita.php?id=1" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
        <button class="btn-acao-creme" onclick="fecharTodosModais()" style="margin-top:22px;">Fechar Combo</button>
    </div>

    <!-- 6. MODAL COMBO: FESTIVAL DAS LANTERNAS DOURADAS -->
    <div id="modalComboLanternas" class="card-combo-modal card-amarelo">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="combo-modal-header">
            <h2>Festival das Lanternas Douradas</h2>
            <p>Ilumine sua tarde com uma combinação brilhante de chás aromáticos, biscoitos quentinhos e o toque inconfundível de uma celebração mágica ao pôr do sol.</p>
        </div>
        <div class="grid-combo-items">
            <div class="item-combo-card">
                <img src="<?= buscarImagem('tody_de_lava') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Toddy de Lava do Wu">
                <h4>Toddy de Lava do Wu</h4>
                <p>Uma bebida quente, aconchegante e aromática.</p>
                <a href="receita.php?id=61" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="item-combo-card">
                <img src="<?= buscarImagem('cookies_enrolados') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Cookies de Enrolados">
                <h4>Cookies de Enrolados</h4>
                <p>Cookies macios e coloridos com confeitos lilás e amarelos.</p>
                <a href="receita.php?id=4" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="item-combo-card">
                <img src="<?= buscarImagem('bolo_de_cafe') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Bolo de Café com Espiral de Canela">
                <h4>Bolo de Café com Espiral de Canela</h4>
                <p>Bolo fofinho com espiral aconchegante de canela.</p>
                <a href="receita.php?id=6" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
        <button class="btn-ver-mais-vermelho" onclick="fecharTodosModais()" style="margin-top:22px; width: 100%;">Fechar Combo</button>
    </div>

    <!-- 7. MODAL COMBO: SESSÃO MÁGICA DE CINEMA -->
    <div id="modalComboSessao" class="card-combo-modal card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        <div class="combo-modal-header">
            <h2>Sessão Mágica de Cinema</h2>
            <p>O combo definitivo para dar play na sua maratona! Petiscos crocantes irresistíveis, pipoca doce quentinha e bebida borbulhante bem gelada para acompanhar seus filmes favoritos.</p>
        </div>
        <div class="grid-combo-items">
            <div class="item-combo-card">
                <img src="<?= buscarImagem('pipoca_da_bela') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Pipoca Rosa da Aurora">
                <h4>Pipoca Rosa da Aurora</h4>
                <p>Pipoca doce rosa e crocante com calda de marshmallow.</p>
                <a href="receita.php?id=10" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="item-combo-card">
                <img src="<?= buscarImagem('refrigeranteazul_wei') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Refrigerante Azul Gelado do Wei">
                <h4>Refrigerante Azul Gelado do Wei</h4>
                <p>Uma bebida leve, efervescente e super refrescante.</p>
                <a href="receita.php?id=62" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="item-combo-card">
                <img src="<?= buscarImagem('chips_mulan') ?>" onerror="this.src='img/logo/logo_menu.png'" alt="Chips Crocantes da Mulan">
                <h4>Chips Crocantes da Mulan</h4>
                <p>Chips crocantes e temperadas de batata-doce assadas.</p>
                <a href="receita.php?id=37" class="btn-ver-receita-combo">Ver Receita <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
        <button class="btn-acao-creme" onclick="fecharTodosModais()" style="margin-top:22px;">Fechar Combo</button>
    </div>

    <!-- 1. BANNER PRINCIPAL (HERO SLIDER) -->
    <section class="secao-hero">
        <div class="hero-slider-wrapper">
            <div class="hero-slide active" style="background-image: url('<?= buscarImagem('cookies_banner') ?>');"></div>
            <div class="hero-slide" style="background-image: url('<?= buscarImagem('beignet_banner') ?>');"></div>

            <button class="hero-slider-btn" onclick="mudarSlide(1)" title="Próximo Slide">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <div class="hero-slider-dots">
                <span class="dot active" onclick="irParaSlide(0)"></span>
                <span class="dot" onclick="irParaSlide(1)"></span>
            </div>
        </div>
    </section>

    <!-- 2. SECÇÃO: A MAGIA DE CADA PEDAÇO -->
    <section class="secao-magia" id="bloco-magia">
        <div class="container-magia-layout">
            <div class="magia-carousel-side">
                <div class="magia-cards-wrapper">
                    <div class="magia-card-peek">
                        <img id="imgPratoPeek" src="<?= buscarImagem('torta_maca_branca_neve') ?>" alt="Torta de Maçã">
                        <button class="btn-seta-magia-left" onclick="mudarPratoMagia(-1)" title="Anterior">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                    </div>

                    <div class="magia-card-main">
                        <img id="imgPratoMain" class="prato-img" src="<?= buscarImagem('espaguete_almondegas') ?>" alt="Espaguete com Almôndegas">
                        <img src="<?= buscarImagem('luva_mickey') ?>" class="mickey-glove-img" alt="Luva Mickey" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>

            <div class="magia-text-side">
                <div class="magia-header-title">
                    <h2>A MAGIA DE<br>CADA PEDAÇO</h2>
                </div>

                <div class="magia-rating-line">
                    <span class="magia-stars">★★★★★</span>
                    <span class="magia-rating-text">(4.9 - MAIS ACLAMADA)</span>
                </div>

                <p class="magia-p-main">
                    Se existisse um feitiço capaz de reunir todo mundo ao redor da mesa com um sorriso no rosto, com certeza seriam estas receitas. Elas conquistarão o topo do nosso ranking porque entregam muito mais do que um sabor impecável:
                </p>

                <p class="magia-p-sub">
                    entregam nostalgia, aconchego e aquela pitada de encantamento que faz qualquer dia comum parecer uma celebração especial. Clássicos amados e testados por milhares de pessoas que não abrem mão de uma experiência verdadeiramente mágica.
                </p>

                <img src="<?= buscarImagem('mickey_feiticeiro') ?>" class="mickey-feiticeiro-img" alt="Mickey Feiticeiro" onerror="this.style.display='none'">
            </div>
        </div>
    </section>

    <!-- 3. SECÇÃO: PESQUISA E CATEGORIAS -->
    <section class="secao-busca-categorias">
        <div class="container-secao">
            <div class="search-home-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="inputPesquisa" placeholder="Procurar por receitas ou filmes..." onkeyup="pesquisarReceitas()">
            </div>

            <div class="grid-3-categorias">
                <a href="salgados.php" class="card-categoria-item">
                    <img src="<?= buscarImagem('ratatouille_prato') ?>" class="card-categoria-bg" alt="Receitas Salgadas" onerror="this.src='img/pratos/ratatouille_prato.webp'">
                    <div class="card-categoria-overlay"></div>
                    <span class="card-cat-tag">RECEITAS SALGADAS</span>
                    <h3 class="card-cat-titulo">"QUALQUER UM PODE COZINHAR"</h3>
                </a>

                <a href="doces.php" class="card-categoria-item">
                    <img src="<?= buscarImagem('torta_maca_branca_neve') ?>" class="card-categoria-bg" alt="Receitas Doces" onerror="this.src='img/pratos/torta_maca_branca_neve.webp'">
                    <div class="card-categoria-overlay"></div>
                    <span class="card-cat-tag">RECEITAS DOCES</span>
                    <h3 class="card-cat-titulo">UM DOCE FINAL FELIZ</h3>
                </a>

                <a href="bebidas.php" class="card-categoria-item">
                    <img src="<?= buscarImagem('suco_ilha') ?>" class="card-categoria-bg" alt="Bebidas" onerror="this.src='img/pratos/beignet_banner.png'">
                    <div class="card-categoria-overlay"></div>
                    <span class="card-cat-tag">BEBIDAS</span>
                    <h3 class="card-cat-titulo">GOLES DE PURA MAGIA</h3>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. SECÇÃO: RECEITAS SALGADAS -->
    <section class="secao-salgados" id="containerReceitas">
        <div class="container-secao">
            
            <div class="grid-home-receitas">
                <?php foreach($receitasSalgados as $r): ?>
                <a href="receita.php?id=<?= $r['id_receita'] ?>" class="item-receita-clean item-busca">
                    <div class="item-receita-img">
                        <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>
                    <?php if (!empty($r['nome_filme'])): ?>
                        <span class="badge-filme"><?= htmlspecialchars($r['nome_filme']) ?></span>
                    <?php endif; ?>
                    <div class="item-receita-titulo"><?= htmlspecialchars($r['nome_receita']) ?></div>
                    <div class="item-receita-desc"><?= htmlspecialchars($r['descricao'] ?? 'Saborosa receita salgada mágica inspirada no cinema.') ?></div>
                    
                    <?php if (($r['total_avaliacoes'] ?? 0) > 0): 
                        $media = round((float)($r['media_estrelas'] ?? $r['avaliacao'] ?? 0), 1);
                    ?>
                        <div class="item-receita-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($media >= $i): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif ($media >= $i - 0.5): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span style="font-size: 12px; color: #ffffff; font-weight: 700; margin-left: 4px;"><?= number_format($media, 1) ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="btn-center-wrapper">
                <a href="salgados.php" class="btn-ver-mais-vermelho">VER MAIS</a>
            </div>

        </div>
    </section>

    <!-- 5. BANNER FULL-WIDTH VERMELHO: BANQUETE REAL NA FLORESTA ENCANTADA -->
    <section class="secao-full-vermelha">
        <div class="container-full-vermelho">
            <div class="conteudo-banner-texto">
                <h2 class="titulo-banner-red">
                    <span class="script-banquete">Banquete</span> Real na<br>Floresta Encantada
                </h2>
                <span class="combo-sub">
                    <i class="fa-solid fa-utensils"></i> Combo: Poção da Bruxa + Petiscos do Pongo + Torta de maçã
                </span>
                <p class="combo-desc">
                    Desperte em um verdadeiro conto de fadas! Saboreie aromas envolventes e acompanhamentos crocantes perfeitos para começar o dia com espírito de aventura.
                </p>
                <a href="javascript:void(0)" onclick="abrirModalCombo('banquete')" class="btn-ver-mais-creme">VER COMBO COMPLETO</a>
            </div>
        </div>
        <div class="banner-red-img">
            <img src="<?= buscarImagem('banquete_real') ?>" onerror="this.src='<?= buscarImagem('pocao_da_bruxa') ?>'" alt="Banquete Real na Floresta Encantada">
        </div>
    </section>

    <!-- 6. SECÇÃO: RECEITAS DOCES -->
    <section class="secao-doces">
        <div class="container-secao">
            <div class="grid-home-receitas">
                <?php foreach($receitasDoces as $r): ?>
                <a href="receita.php?id=<?= $r['id_receita'] ?>" class="item-receita-clean item-busca">
                    <div class="item-receita-img">
                        <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>
                    <?php if (!empty($r['nome_filme'])): ?>
                        <span class="badge-filme"><?= htmlspecialchars($r['nome_filme']) ?></span>
                    <?php endif; ?>
                    <div class="item-receita-titulo"><?= htmlspecialchars($r['nome_receita']) ?></div>
                    <div class="item-receita-desc"><?= htmlspecialchars($r['descricao'] ?? 'Deliciosa receita doce mágica inspirada no cinema.') ?></div>
                    
                    <?php if (($r['total_avaliacoes'] ?? 0) > 0): 
                        $media = round((float)($r['media_estrelas'] ?? $r['avaliacao'] ?? 0), 1);
                    ?>
                        <div class="item-receita-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($media >= $i): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif ($media >= $i - 0.5): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span style="font-size: 12px; color: #ffffff; font-weight: 700; margin-left: 4px;"><?= number_format($media, 1) ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="btn-center-wrapper">
                <a href="doces.php" class="btn-ver-mais-creme">VER MAIS</a>
            </div>
        </div>
    </section>

    <!-- 7. BANNER FULL-WIDTH CREME: FESTIVAL DAS LANTERNAS DOURADAS -->
    <section class="secao-full-creme">
        <div class="container-full-creme">
            <div class="conteudo-banner-texto">
                <h2 class="titulo-banner-creme">
                    <span class="script-festival">Festival</span> das Lanternas<br>Douradas
                </h2>
                <span class="combo-sub-creme">
                    <i class="fa-solid fa-mug-hot"></i> Combo: Toddy de lava do wu + Cookies de enrolados + Bolo de café
                </span>
                <p class="combo-desc-creme">
                    Ilumine sua tarde com uma combinação brilhante de chás aromáticos, biscoitos quentinhos e o toque inconfundível de uma celebração mágica ao pôr do sol.
                </p>
                <a href="javascript:void(0)" onclick="abrirModalCombo('lanternas')" class="btn-ver-mais-vermelho">VER COMBO COMPLETO</a>
            </div>
        </div>
        <div class="banner-creme-img">
            <img src="<?= buscarImagem('festival_lanternas') ?>" onerror="this.src='<?= buscarImagem('tody_de_lava') ?>'" alt="Festival das Lanternas Douradas">
        </div>
    </section>

    <!-- 8. SECÇÃO: RECEITAS BEBIDAS -->
    <section class="secao-bebidas">
        <div class="container-secao">
            <div class="grid-home-receitas">
                <?php foreach($receitasBebidas as $r): ?>
                <a href="receita.php?id=<?= $r['id_receita'] ?>" class="item-receita-clean item-busca">
                    <div class="item-receita-img">
                        <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>
                    <?php if (!empty($r['nome_filme'])): ?>
                        <span class="badge-filme"><?= htmlspecialchars($r['nome_filme']) ?></span>
                    <?php endif; ?>
                    <div class="item-receita-titulo"><?= htmlspecialchars($r['nome_receita']) ?></div>
                    <div class="item-receita-desc"><?= htmlspecialchars($r['descricao'] ?? 'Refrescante bebida mágica inspirada no cinema.') ?></div>
                    
                    <?php if (($r['total_avaliacoes'] ?? 0) > 0): 
                        $media = round((float)($r['media_estrelas'] ?? $r['avaliacao'] ?? 0), 1);
                    ?>
                        <div class="item-receita-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($media >= $i): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif ($media >= $i - 0.5): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span style="font-size: 12px; color: #ffffff; font-weight: 700; margin-left: 4px;"><?= number_format($media, 1) ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="btn-center-wrapper">
                <a href="bebidas.php" class="btn-ver-mais-vermelho">VER MAIS</a>
            </div>
        </div>
    </section>

    <!-- 9. BANNER FULL-WIDTH VERMELHO: SESSÃO MÁGICA DE CINEMA -->
    <section class="secao-full-vermelha">
        <div class="container-full-vermelho">
            <div class="conteudo-banner-texto">
                <h2 class="titulo-banner-red">
                    <span class="script-banquete">Sessão</span> Mágica de<br>Cinema
                </h2>
                <span class="combo-sub">
                    <i class="fa-solid fa-film"></i> Combo: Pipoca Rosa + Refrigerante Azul + Chips Crocantes
                </span>
                <p class="combo-desc">
                    O combo definitivo para dar play na sua maratona! Petiscos crocantes irresistíveis, pipoca doce quentinha e bebida borbulhante bem gelada para acompanhar seus filmes favoritos.
                </p>
                <a href="javascript:void(0)" onclick="abrirModalCombo('sessao')" class="btn-ver-mais-creme">VER COMBO COMPLETO</a>
            </div>
        </div>
        <div class="banner-red-img">
            <img src="<?= buscarImagem('sessao_cinema') ?>" onerror="this.src='<?= buscarImagem('pipoca_da_bela') ?>'" alt="Sessão Mágica de Cinema">
        </div>
    </section>

    <!-- BOTÃO FLUTUANTE DE VOLTAR AO TOPO -->
    <a href="javascript:void(0)" onclick="voltarAoTopo(event)" class="btn-flutuante-voltar" id="btnVoltarTopo" title="Voltar ao topo">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

    <!-- 10. RODAPÉ MÁGICO DO SITE (IMAGEM À DIREITA & TEXTO À ESQUERDA) -->
    <footer class="site-footer">
        <div class="footer-card-wrapper">
            <div class="footer-container">
                <div class="footer-col footer-about">
                    <img src="<?= buscarImagem('logo_menu') ?>" alt="Magical Bites" onerror="this.src='img/logo/logo_menu.png'">
                    <p>Traga a magia dos filmes Disney diretamente para a sua cozinha! No Magical Bites encontra as receitas mais encantadoras do cinema.</p>
                    <div class="footer-social">
                        <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="#" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#" title="Pinterest"><i class="fa-brands fa-pinterest"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h3>Navegação</h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Início</a></li>
                        <li><a href="#containerReceitas"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Receitas</a></li>
                        <li><a href="favoritos.php"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Favoritos</a></li>
                        <li><a href="filmes.php"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Filmes</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>Categorias</h3>
                    <ul class="footer-links">
                        <li><a href="salgados.php"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Salgados</a></li>
                        <li><a href="doces.php"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Doces</a></li>
                        <li><a href="bebidas.php"><i class="fa-solid fa-chevron-right" style="font-size: 10px; margin-right: 6px;"></i> Bebidas</a></li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Magical Bites. Feito com <i class="fa-solid fa-heart"></i> e magia.</p>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT DO CURSOR DO MICKEY, SLIDERS, MODAIS E SETA AO TOPO -->
    <script>
    // --- EXIBIR A SETA FLUTUANTE AO ROLAR A PÁGINA ---
    window.addEventListener('scroll', function() {
        let btn = document.getElementById('btnVoltarTopo');
        if (btn) {
            if (window.scrollY >= 250) {
                btn.classList.add('visivel');
            } else {
                btn.classList.remove('visivel');
            }
        }
    });

    // FUNÇÃO PARA ROLAR ATÉ O TOPO DE FORMA SUAVE
    function voltarAoTopo(event) {
        if (event) event.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // --- LÓGICA DO CURSOR PERSONALIZADO DO MICKEY ---
    const mickeyCursor = document.getElementById('mickeyCursor');
    const imgAberto = "<?= buscarImagemCursor('aberto') ?>";
    const imgFechado = "<?= buscarImagemCursor('fechado') ?>";

    if (mickeyCursor) {
        document.addEventListener('mousemove', (e) => {
            mickeyCursor.style.display = 'block';
            mickeyCursor.style.left = e.clientX + 'px';
            mickeyCursor.style.top = e.clientY + 'px';
        });

        document.addEventListener('mouseleave', () => {
            mickeyCursor.style.display = 'none';
        });

        const seletorInterativo = 'a, button, input, select, textarea, label, [onclick], .dot, .avatar-item, .hero-slider-btn, .btn-seta-magia-left, .btn-flutuante-voltar';

        document.addEventListener('mouseover', (e) => {
            if (e.target.closest(seletorInterativo)) {
                mickeyCursor.src = imgFechado;
            }
        });

        document.addEventListener('mouseout', (e) => {
            if (e.target.closest(seletorInterativo)) {
                mickeyCursor.src = imgAberto;
            }
        });

        document.addEventListener('mousedown', () => {
            mickeyCursor.src = imgFechado;
        });

        document.addEventListener('mouseup', (e) => {
            if (!e.target.closest(seletorInterativo)) {
                mickeyCursor.src = imgAberto;
            }
        });
    }

    // 1. Hero Slider Principal (transição suave de opacidade 0.8s)
    let slideAtual = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-slider-dots .dot');
    let timerSlide;

    function atualizarSlide() {
        slides.forEach((slide, idx) => {
            slide.classList.toggle('active', idx === slideAtual);
        });
        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === slideAtual);
        });
    }

    function mudarSlide(direcao) {
        slideAtual += direcao;
        if (slideAtual >= slides.length) slideAtual = 0;
        if (slideAtual < 0) slideAtual = slides.length - 1;
        atualizarSlide();
        reiniciarTimerSlide();
    }

    function irParaSlide(index) {
        slideAtual = index;
        atualizarSlide();
        reiniciarTimerSlide();
    }

    function iniciarTimerSlide() {
        timerSlide = setInterval(() => {
            slideAtual++;
            if (slideAtual >= slides.length) slideAtual = 0;
            atualizarSlide();
        }, 5000);
    }

    function reiniciarTimerSlide() {
        clearInterval(timerSlide);
        iniciarTimerSlide();
    }

    iniciarTimerSlide();

    // 2. Carousel de Fotos da secção de receitas (troca direta sem animação)
    const listaPratosMagia = [
        "<?= buscarImagem('espaguete_almondegas') ?>",
        "<?= buscarImagem('torta_maca_branca_neve') ?>",
        "<?= buscarImagem('beignet_princesa_sapo') ?>",
        "<?= buscarImagem('ratatouille_prato') ?>"
    ];
    let idxPratoMagia = 0;

    function mudarPratoMagia(dir = 1) {
        const imgMain = document.getElementById('imgPratoMain');
        const imgPeek = document.getElementById('imgPratoPeek');

        idxPratoMagia = (idxPratoMagia + dir + listaPratosMagia.length) % listaPratosMagia.length;
        let idxPeek = (idxPratoMagia - 1 + listaPratosMagia.length) % listaPratosMagia.length;

        imgMain.src = listaPratosMagia[idxPratoMagia];
        imgPeek.src = listaPratosMagia[idxPeek];
    }

    // 3. Pesquisa em Tempo Real
    function pesquisarReceitas() {
        let input = document.getElementById('inputPesquisa').value.toLowerCase();
        let items = document.querySelectorAll('.item-busca');

        items.forEach(item => {
            let titulo = item.querySelector('.item-receita-titulo').innerText.toLowerCase();
            if (titulo.includes(input)) {
                item.style.display = "flex";
            } else {
                item.style.display = "none";
            }
        });
    }

    // 4. Gestão de Modais
    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'none';
        if(document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
        if(document.getElementById('modalStatus')) document.getElementById('modalStatus').style.display = 'none';
        if(document.getElementById('modalComboBanquete')) document.getElementById('modalComboBanquete').style.display = 'none';
        if(document.getElementById('modalComboLanternas')) document.getElementById('modalComboLanternas').style.display = 'none';
        if(document.getElementById('modalComboSessao')) document.getElementById('modalComboSessao').style.display = 'none';
    }

    function fecharStatus() {
        fecharTodosModais();
        window.history.replaceState({}, document.title, "index.php");
    }

    function alternarModais(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
        if (tipo === 'cadastro') document.getElementById('modalCadastro').style.display = 'block';
        if (tipo === 'login') document.getElementById('modalLogin').style.display = 'block';
        if (tipo === 'perfil') document.getElementById('modalPerfil').style.display = 'block';
    }

    function abrirModalCombo(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
        if (tipo === 'banquete') document.getElementById('modalComboBanquete').style.display = 'block';
        if (tipo === 'lanternas') document.getElementById('modalComboLanternas').style.display = 'block';
        if (tipo === 'sessao') document.getElementById('modalComboSessao').style.display = 'block';
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