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

// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = ['pratos', 'filme', 'logo', 'slider', 'perfis', 'avatares', 'img/perfis', 'img/avatares'];
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

// BUSCAS DAS RECEITAS
$stmtDoces = $pdo->query("SELECT * FROM receitas WHERE tipo = 'DOCE'");
$receitasDoces = $stmtDoces->fetchAll(PDO::FETCH_ASSOC);

$stmtSalgados = $pdo->query("SELECT * FROM receitas WHERE tipo = 'SALGADO'");
$receitasSalgados = $stmtSalgados->fetchAll(PDO::FETCH_ASSOC);

$stmtBebidas = $pdo->query("SELECT * FROM receitas WHERE tipo = 'BEBIDA'");
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
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        html {
            scroll-behavior: smooth;
        }

        body {
            background: #f3f1ff;
            color: #333;
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
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

        .menu a:hover, .menu a.ativo {
            color: #fff6a9ff;
        }

        .menu a i {
            font-size: 18px;
        }

        .nav-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9ff;
        }

        /* BANNER PRINCIPAL (900PX) */
        .banner-destaque {
            width: 100% !important;
            height: 900px !important;
            background-color: #fffcef !important;
            background-image: url('img/slider/bg-destaque.webp') !important;
            background-size: auto 100% !important;
            background-position: right center !important;
            background-repeat: no-repeat !important;
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            overflow: hidden !important;
        }

        button.btn-seta-destaque,
        .btn-seta-destaque {
            position: absolute !important;
            left: 210px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 10 !important;
            width: 42px !important;
            height: 42px !important;
            border-radius: 50% !important;
            background: #ab232a !important;
            background-color: #fffcef !important;
            color: #ab232a !important;
            border: none !important;
            outline: none !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25) !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 15px !important;
            transition: transform 0.2s ease, background-color 0.2s ease !important;
        }

        button.btn-seta-destaque i,
        .btn-seta-destaque i {
            color: #ab232a !important;
        }

        button.btn-seta-destaque:hover,
        .btn-seta-destaque:hover {
            transform: translateY(-50%) scale(1.1) !important;
            background: #8e1c22 !important;
            background-color: #8e1c22 !important;
        }

        button.btn-seta-destaque:hover i,
        .btn-seta-destaque:hover i {
            color: #fffcef !important;
        }

        .destaque-pratos-wrapper {
            display: flex !important;
            align-items: center !important;
            gap: 30px !important;
            position: absolute !important;
            left: -100px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 2 !important;
        }

        .card-prato {
            height: 650px !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
            position: relative !important;
            transition: all 0.45s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }

        .card-prato img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        .card-prato.card-peek {
            width: 320px !important;
            border-radius: 40px !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15) !important;
            opacity: 1 !important;
        }

        .card-prato.card-ativo {
            width: 850px !important;
            border-radius: 45px !important;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.2) !important;
            opacity: 1 !important;
        }

        .card-prato.card-escondido {
            width: 0px !important;
            opacity: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            pointer-events: none !important;
        }

        #containerReceitas {
            scroll-margin-top: 80px;
        }

        /* --- HERO & SLIDER --- */
        .hero {
            width: 100%;
            height: calc(100vh - 70px);
            position: relative;
            background-size: cover;
            background-position: center 65%;
            transition: background-image 0.6s ease-in-out;
            image-rendering: -webkit-optimize-contrast;
            transform: translateZ(0);
        }

        .hero-content {
            position: absolute;
            left: 200px;
            bottom: 110px;
            z-index: 5;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 18px 70px;
            font-size: 18px;
            background: #245089;
            backdrop-filter: blur(8px);
            color: #87a5bf;
            border: 1px solid #245089;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            text-transform: uppercase;
        }

        .hero-btn:hover {
            background: #305e9bff;
            color: #a8c3daff;
            border-color: #245089;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px #214575ff;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.3);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .slider-btn:hover {
            background: #fff6a9ff;
            color: #245089;
            transform: translateY(-50%) scale(1.1);
        }

        .slider-btn.prev { left: 30px; }
        .slider-btn.next { right: 30px; }

        .slider-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }

        .dot {
            width: 14px;
            height: 14px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .dot.active {
            background: #fff6a9ff;
            transform: scale(1.3);
        }

        /* --- BARRA DE PESQUISA --- */
        .search-wrapper {
            display: flex;
            justify-content: center;
            margin: 40px auto 10px;
            padding: 0 20px;
            width: 100%;
        }

        .search-container {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 30px;
            padding: 12px 25px;
            width: 100%;
            max-width: 650px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .search-container:hover,
        .search-container:focus-within {
            box-shadow: 0 15px 35px rgba(31, 67, 150, 0.15);
            border: 2px solid #1f4396;
            transform: translateY(-2px);
        }

        .search-container i {
            color: #1f4396;
            margin-right: 15px;
            font-size: 20px;
        }

        .search-container input {
            background: transparent;
            border: none;
            color: #333;
            outline: none;
            width: 100%;
            font-size: 16px;
        }

        /* --- RECEITAS --- */
        .container {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .titulo {
            margin: 30px 0 30px;
            font-size: 30px;
            font-weight: 700;
            color: #1f4396;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lista {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 35px;
            justify-content: center;
            margin-bottom: 60px;
        }

        .card {
            background: white;
            color: black;
            border-radius: 22px;
            overflow: hidden;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.20);
        }

        .card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .tag {
            width: fit-content;
            margin: 20px auto 10px;
            padding: 6px 16px;
            border-radius: 20px;
            color: white;
            font-size: 11px;
            font-weight: 700;
        }

        .doce { background: #ff69b4; }
        .salgado { background: #4CAF50; }
        .bebida { background: #00bcd4; }

        .card p {
            padding: 0 20px 25px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        #msgSemResultado {
            display: none;
            text-align: center;
            font-size: 18px;
            color: #666;
            margin: 40px 0;
        }

        /* =========================================================================
           NOVO ESTILO DE MODAIS BASEADO NO DESIGN EDITORIAL DISNEY
           ========================================================================= */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(18, 24, 41, 0.65);
            backdrop-filter: blur(8px);
            z-index: 999;
        }

        .card-flutuante {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 380px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 36px;
            padding: 35px 28px;
            z-index: 1000;
            animation: popUpModal 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-flutuante::-webkit-scrollbar { width: 5px; }
        .card-flutuante::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }

        @keyframes popUpModal {
            from { opacity: 0; transform: translate(-50%, -45%) scale(0.92); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        /* --- TEMA VERMELHO-CARMIM (CADASTRO, LOGIN E PERFIL) --- */
        .card-vermelho {
            background: #ab232a;
            color: #ffffff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .card-vermelho .castelo-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .card-vermelho .castelo-header i {
            font-size: 38px;
            color: #ffffff;
        }

        .card-vermelho h2.titulo-cursivo {
            font-family: 'Caveat', cursive;
            font-size: 38px;
            color: #ffffff;
            text-align: center;
            line-height: 1;
            margin-bottom: 2px;
        }

        .card-vermelho p.subtitulo-modal {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            margin-bottom: 22px;
            font-weight: 500;
        }

        .card-vermelho .input-group-modal {
            position: relative;
            margin-bottom: 14px;
        }

        .card-vermelho .input-group-modal i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffffff;
            font-size: 15px;
        }

        .card-vermelho .input-group-modal input {
            width: 100%;
            padding: 12px 18px 12px 48px;
            border: 2px solid #ffffff;
            background: transparent;
            border-radius: 50px;
            outline: none;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
        }

        .card-vermelho .input-group-modal input::placeholder {
            color: rgba(255, 255, 255, 0.85);
        }

        .card-vermelho .input-group-modal input:focus {
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.3);
        }

        .card-vermelho .btn-acao-creme {
            width: 100%;
            padding: 13px;
            background: #fff8bd;
            color: #ab232a;
            border: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-vermelho .btn-acao-creme:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .card-vermelho .footer-modal {
            margin-top: 18px;
            font-size: 13px;
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .card-vermelho .footer-modal a {
            color: #ffffff;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
        }

        /* --- TEMA AMARELO-CREME (MODAIS DE STATUS / AVISO) --- */
        .card-amarelo {
            background: #fff8bd;
            color: #ab232a;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            text-align: center;
        }

        .card-amarelo .castelo-header i {
            font-size: 38px;
            color: #ab232a;
            margin-bottom: 8px;
        }

        .card-amarelo h2.titulo-aviso {
            font-size: 22px;
            font-weight: 900;
            color: #ab232a;
            text-transform: uppercase;
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .card-amarelo h2.titulo-cursivo {
            font-family: 'Caveat', cursive;
            font-size: 42px;
            color: #1f4396;
            line-height: 1;
            margin-bottom: 6px;
        }

        .card-amarelo p.subtitulo-aviso {
            font-size: 14px;
            color: #701a1d;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 22px;
        }

        .card-amarelo .btn-acao-vermelho {
            width: 100%;
            padding: 13px;
            background: #ab232a;
            color: #ffffff;
            border: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(171, 35, 42, 0.3);
        }

        .card-amarelo .btn-acao-vermelho:hover {
            background: #8e1c22;
            transform: translateY(-2px);
        }

        .card-amarelo .btn-outline-vermelho {
            width: 100%;
            padding: 11px;
            background: transparent;
            color: #ab232a;
            border: 2px solid #ab232a;
            border-radius: 50px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .card-amarelo .btn-outline-vermelho:hover {
            background: rgba(171, 35, 42, 0.1);
        }

        /* --- ESTILOS ESPECÍFICOS DO PERFIL NO MODAL VERMELHO --- */
        .perfil-avatar-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .perfil-avatar-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .grid-avatares-modal {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            max-height: 160px;
            overflow-y: auto;
            padding: 6px;
            margin: 12px 0;
        }

        .avatar-item {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            overflow: hidden;
            transition: 0.2s;
        }

        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-item:hover, .avatar-item.selected {
            border-color: #fff8bd;
            transform: scale(1.12);
        }

        .btn-fechar-absoluto {
            position: absolute;
            top: 18px;
            right: 22px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: rgba(255,255,255,0.8);
            transition: 0.2s;
        }
        .btn-fechar-absoluto:hover { color: #ffffff; transform: scale(1.1); }

        .card-amarelo .btn-fechar-absoluto { color: rgba(171,35,42,0.7); }
        .card-amarelo .btn-fechar-absoluto:hover { color: #ab232a; }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <div class="navbar">
      <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.jpg') ?>" alt="Magical Bites"></a></div>
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

    <!-- 1. MODAL CADASTRO (DESIGN REDESIGN DISNEY) -->
    <div id="modalCadastro" class="card-flutuante card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        
        <div class="castelo-header">
            <i class="fa-solid fa-chess-castle"></i>
        </div>
        <h2 class="titulo-cursivo">Crie a sua conta</h2>
        <p class="subtitulo-modal">Junte-se a nossa família!</p>
        
        <form action="cadastrar.php" method="POST">
            <div class="input-group-modal">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="nome" placeholder="Nome Completo" required>
            </div>
            <div class="input-group-modal">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="E-mail" required>
            </div>
            <div class="input-group-modal">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn-acao-creme">Criar</button>
        </form>
        <div class="footer-modal">Já tem uma conta? <a onclick="alternarModais('login')">Faça login</a></div>
    </div>

    <!-- 2. MODAL LOGIN (DESIGN REDESIGN DISNEY) -->
    <div id="modalLogin" class="card-flutuante card-vermelho">
        <button class="btn-fechar-absoluto" onclick="fecharTodosModais()">&times;</button>
        
        <div class="castelo-header">
            <i class="fa-solid fa-chess-castle"></i>
        </div>
        <h2 class="titulo-cursivo">Bem - vindo</h2>
        <p class="subtitulo-modal">Acesse sua conta mágica:</p>
        
        <form action="index.php" method="POST">
            <div class="input-group-modal">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="E-mail" required>
            </div>
            <div class="input-group-modal">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn-acao-creme">Entrar</button>
        </form>
        <div class="footer-modal">Está chegando agora? <a onclick="alternarModais('cadastro')">Crie uma conta</a></div>
    </div>

    <!-- 3. MODAL PERFIL (DESIGN REDESIGN DISNEY) -->
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

    <!-- 4. MODAL STATUS / MENSAGENS E ERROS (DESIGN AMARELO-CREME) -->
    <?php if ($status != ""): ?>
    <div id="modalStatus" class="card-flutuante card-amarelo" style="display: block; z-index: 1001;">
        <button class="btn-fechar-absoluto" onclick="fecharStatus()">&times;</button>

        <div class="castelo-header">
            <i class="fa-solid fa-chess-castle"></i>
        </div>

        <?php if ($status == 'sucesso'): ?>
            <h2 class="titulo-cursivo">Bem - vindo</h2>
            <p class="subtitulo-aviso">
                <?= htmlspecialchars($msg ?: "Sua conta está registrada, agora é só aproveitar!") ?>
            </p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Aproveitar agora</button>

        <?php elseif ($status == 'erro'): ?>
            <h2 class="titulo-aviso">OOPS!</h2>
            <p class="subtitulo-aviso">
                <?= htmlspecialchars($msg ?: "Senha ou E-mail incorreto") ?>
            </p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Tentar de novo</button>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('modalOverlay').style.display = 'block';
    });
    </script>
    <?php endif; ?>

    <!-- HERO / BANNER -->
    <div class="hero">
        <button class="slider-btn next" onclick="mudarSlide(1)"><i class="fa fa-chevron-right"></i></button>
        
        <div class="slider-dots">
            <span class="dot" onclick="irParaSlide(0)"></span>
            <span class="dot" onclick="irParaSlide(1)"></span>
        </div>
        
        <div class="hero-content">
            <a href="#containerReceitas" class="hero-btn">Explorar</a>
        </div>
    </div>

    <!-- BANNER DESTAQUE -->
    <div class="banner-destaque">
        <div class="destaque-pratos-wrapper" id="sliderPratos">
            <div class="card-prato card-peek" style="order: 1;">
                <img src="img/pratos/beignet_princesa_sapo.webp" alt="Beignets">
            </div>
            <div class="card-prato card-ativo" style="order: 2;">
                <img src="img/pratos/espaguete_almondegas.webp" alt="Espaguete">
            </div>
            <div class="card-prato card-escondido" style="order: 3;">
                <img src="img/pratos/ratatouille_prato.webp" alt="Ratatouille">
            </div>
            <div class="card-prato card-escondido" style="order: 4;">
                <img src="img/pratos/torta_maca_branca_neve.webp" alt="Torta de Maçã">
            </div>
        </div>

        <button class="btn-seta-destaque" onclick="mudarPratoDestaque()" title="Próximo prato">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <!-- PESQUISA -->
    <div class="search-wrapper">
        <div class="search-container">
            <i class="fa fa-search"></i>
            <input type="text" id="inputPesquisa" placeholder="Procurar por receita ou filme..." onkeyup="pesquisarReceitas()">
        </div>
    </div>

    <!-- LISTA DE RECEITAS -->
    <div class="container" id="containerReceitas">

        <h2 class="titulo">Receitas Doces</h2>
        <div class="lista">
            <?php foreach($receitasDoces as $r): ?>
            <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card">
                <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                <div class="tag doce"><?= htmlspecialchars($r['tipo']) ?></div>
                <p><?= htmlspecialchars($r['nome_receita']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <h2 class="titulo">Receitas Salgadas</h2>
        <div class="lista">
            <?php foreach($receitasSalgados as $r): ?>
            <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card">
                <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                <div class="tag salgado"><?= htmlspecialchars($r['tipo']) ?></div>
                <p><?= htmlspecialchars($r['nome_receita']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if(count($receitasBebidas) > 0): ?>
        <h2 class="titulo">Bebidas Mágicas</h2>
        <div class="lista">
            <?php foreach($receitasBebidas as $r): ?>
            <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card">
                <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                <div class="tag bebida"><?= htmlspecialchars($r['tipo']) ?></div>
                <p><?= htmlspecialchars($r['nome_receita']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <p id="msgSemResultado">Não encontrámos nenhuma magia com esse nome... 🪄</p>
    </div>

    <script>
    function pesquisarReceitas() {
        let input = document.getElementById('inputPesquisa').value.toLowerCase();
        let cards = document.getElementsByClassName('card');
        let encontrouAlguma = false;

        for (let i = 0; i < cards.length; i++) {
            let tituloReceita = cards[i].querySelector('p').innerText.toLowerCase();

            if (tituloReceita.includes(input)) {
                cards[i].style.display = "flex";
                encontrouAlguma = true;
            } else {
                cards[i].style.display = "none";
            }
        }

        let msgSemResultado = document.getElementById('msgSemResultado');
        if (!encontrouAlguma && input !== "") {
            msgSemResultado.style.display = "block";
        } else {
            msgSemResultado.style.display = "none";
        }
    }

    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'none';
        if(document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
        if(document.getElementById('modalStatus')) document.getElementById('modalStatus').style.display = 'none';
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

    const hero = document.querySelector(".hero");
    const dots = document.querySelectorAll(".dot");
    const banners = [
        "<?= buscarImagem('cookies_banner.png') ?>",
        "<?= buscarImagem('beignet_banner.png') ?>"
    ];
    let slideAtual = 0;

    function atualizarSlide() {
        if (banners.length > 0) {
            hero.style.backgroundImage = `url('${banners[slideAtual]}')`;
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === slideAtual);
            });
        }
    }

    function mudarSlide(direcao) {
        slideAtual += direcao;
        if (slideAtual >= banners.length) slideAtual = 0;
        if (slideAtual < 0) slideAtual = banners.length - 1;
        atualizarSlide();
    }

    function irParaSlide(index) {
        slideAtual = index;
        atualizarSlide();
    }

    atualizarSlide();

    let indexAtivo = 1;

    function mudarPratoDestaque() {
        const cards = document.querySelectorAll('#sliderPratos .card-prato');
        if (!cards.length) return;

        const total = cards.length;
        indexAtivo = (indexAtivo + 1) % total;
        const indexPeek = (indexAtivo - 1 + total) % total;

        cards.forEach((card, i) => {
            card.classList.remove('card-peek', 'card-ativo', 'card-escondido');

            if (i === indexPeek) {
                card.classList.add('card-peek');
                card.style.order = "1";
            } else if (i === indexAtivo) {
                card.classList.add('card-ativo');
                card.style.order = "2";
            } else {
                card.classList.add('card-escondido');
                card.style.order = "3";
            }
        });
    }
    </script>
</body>
</html>