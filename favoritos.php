<?php
session_start();

// Exige utilizador logado
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php?status=erro&msg=" . urlencode("Inicie sessão para ver as suas receitas favoritas!"));
    exit;
}

$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
$pass = ""; 
//$pass = "Home@spSENAI2025!"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;
    if (file_exists("img/" . $nomeImagem)) return "img/" . $nomeImagem;

    $pastas = ['', 'fundo', 'slider', 'pratos', 'filme', 'logo', 'perfis', 'avatares', 'img/fundo', 'img/slider', 'img/perfis', 'img/avatares', 'img/logo'];
    $extensoes = ['', '.png', '.jpg', '.jpeg', '.webp', '.jfif'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminho = empty($pasta) ? "img/" . $nomeImagem . $ext : "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminho)) return $caminho;

            $caminhoRaiz = empty($pasta) ? $nomeImagem . $ext : $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoRaiz)) return $caminhoRaiz;
        }
    }

    return "img/logo/logo_menu.png";
}

// Suporte flexível para a chave de ID na sessão
$id_usuario = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['id_usuario'] ?? null;

// Busca as receitas favoritadas do utilizador junto com o filme e média real de avaliações
$receitasFavoritas = [];
if ($id_usuario) {
    $stmt = $pdo->prepare("
        SELECT r.*, fl.nome_filme,
               COUNT(a.id_avaliacao) AS total_avaliacoes,
               AVG(a.estrelas) AS media_estrelas
        FROM receitas r
        INNER JOIN favoritar f ON r.id_receita = f.id_receita
        LEFT JOIN filme fl ON r.id_filme = fl.id_filme
        LEFT JOIN avaliacoes a ON r.id_receita = a.id_receita
        WHERE f.id_usuario = :id_usuario
        GROUP BY r.id_receita, f.id_favoritos
        ORDER BY f.id_favoritos DESC
    ");
    $stmt->execute([':id_usuario' => $id_usuario]);
    $receitasFavoritas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
    <title>Minhas Receitas Favoritas | Magical Bites</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        html { scroll-behavior: smooth; }

        body { 
            background-color: #fffef2;
            color: #1f4396; 
            min-height: 100vh;
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

        .nav-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9ff;
        }

        /* --- HERO BANNER --- */
        .secao-hero {
            width: 100%;
            position: relative;
            background: #0b1329;
            overflow: hidden;
        }

        .hero-banner-wrapper {
            position: relative;
            width: 100%;
            height: 620px;
            max-height: 750px;
            min-height: 450px;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }

        /* --- WRAPPER PRINCIPAL & MARCA D'ÁGUA DE FUNDO --- */
        .main-wrapper {
            position: relative;
            width: 100%;
            background-color: #fffef2;
            overflow: hidden;
        }

        /* IMAGEM DE ENFEITE BEM GRANDE NO FUNDO ESQUERDO */
        .enfeite-fundo-esquerdo {
            position: absolute;
            top: -40px;
            left: -50px;
            width: 1200px;
            max-width: 85vw;
            height: auto;
            pointer-events: none;
            z-index: 0;
            opacity: 0.45;
            mix-blend-mode: multiply;
        }

        .container { 
            width: 100%; 
            max-width: 1050px; 
            margin: 45px auto 80px; 
            padding: 0 25px; 
            scroll-margin-top: 90px;
            position: relative;
            z-index: 2;
        }
        
        /* --- CABEÇALHO DA PÁGINA (ALINHADO À ESQUERDA) --- */
        .header-favoritos {
            margin-bottom: 45px;
            text-align: left;
            position: relative;
            z-index: 2;
        }

        .titulo-editorial { 
            font-size: 40px; 
            font-weight: 900; 
            color: #1f4396; 
            text-transform: uppercase;
            letter-spacing: -0.5px;
            line-height: 1.1;
            position: relative;
            display: inline-block;
            padding-right: 42px;
        }

        .titulo-editorial .icone-coroa {
            position: absolute;
            top: -14px;
            right: 0;
            font-size: 38px;
            color: #a62428;
            transform: rotate(20deg);
        }

        .subtitulo-editorial {
            color: #1f4396;
            font-size: 13.5px;
            margin-top: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.45;
            max-width: 720px;
        }

        /* --- LISTA DE RECEITAS --- */
        .lista-favoritos { 
            display: flex;
            flex-direction: column;
            gap: 36px;
            position: relative;
            z-index: 2;
        }

        .card-favorito-item { 
            display: flex;
            align-items: center;
            gap: 30px;
            text-decoration: none;
            transition: transform 0.3s ease;
            background: transparent;
            padding: 0;
            border: none;
            box-shadow: none;
        }

        .card-favorito-item:hover {
            transform: translateY(-4px);
        }

        .card-fav-img-wrapper {
            position: relative;
            width: 360px;
            height: 220px;
            flex-shrink: 0;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card-fav-img-wrapper img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            transition: transform 0.4s ease;
        }

        .card-favorito-item:hover .card-fav-img-wrapper img { 
            transform: scale(1.05); 
        }

        /* Botão do Coração no canto da foto */
        .btn-fav-icon-overlay {
            position: absolute; 
            top: 14px; 
            left: 14px; 
            background: #ffffff;
            width: 44px; 
            height: 44px; 
            border-radius: 50%; 
            color: #1f4396;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0,0,0,0.18); 
            transition: all 0.25s ease; 
            z-index: 5;
            text-decoration: none;
            font-size: 19px;
            border: none;
        }

        .btn-fav-icon-overlay:hover { 
            transform: scale(1.12); 
            background: #a62428; 
            color: #ffffff;
        }

        .card-fav-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .card-fav-titulo { 
            font-weight: 900; 
            font-size: 24px; 
            color: #1f4396; 
            line-height: 1.25;
            text-decoration: none;
        }

        .card-fav-titulo:hover {
            color: #a62428;
        }

        .card-fav-desc {
            font-size: 14.5px;
            color: #1f4396;
            line-height: 1.5;
            font-weight: 500;
            opacity: 0.9;
        }

        /* --- ESTRELA E MÉDIA NUMÉRICA --- */
        .card-fav-stars {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #ffca28;
            font-size: 16px;
            margin-top: 4px;
        }

        .card-fav-stars .nota-numero {
            color: #1f4396;
            font-weight: 800;
            font-size: 15px;
            margin-left: 6px;
        }

        /* ESTADO VAZIO (SEM FAVORITOS) */
        .sem-favoritos { 
            text-align: center; 
            padding: 70px 25px; 
            background: #ffffff;
            border-radius: 32px;
            border: 2px dashed #1f4396;
            max-width: 620px;
            margin: 20px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: relative;
            z-index: 2;
        }
        .sem-favoritos i { font-size: 65px; color: #1f4396; margin-bottom: 20px; }
        .sem-favoritos h3 { font-size: 24px; color: #1f4396; margin-bottom: 12px; font-weight: 800; }
        .sem-favoritos p { color: #1f4396; font-size: 14.5px; margin-bottom: 28px; line-height: 1.6; opacity: 0.85; }

        .btn-explorar {
            display: inline-block;
            padding: 14px 40px;
            background: #a62428;
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 15px;
            transition: 0.3s;
            box-shadow: 0 6px 20px rgba(166, 36, 40, 0.4);
        }
        .btn-explorar:hover {
            background: #881c20;
            color: #fff8bd;
            transform: translateY(-3px);
        }

        /* --- MODAIS FLUTUANTES --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(18, 24, 41, 0.75);
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
            background: #a62428;
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
            width: 100%; padding: 13px; background: #fff8bd; color: #a62428; border: none; border-radius: 50px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s ease; margin-top: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-vermelho .footer-modal { margin-top: 18px; font-size: 13px; text-align: center; color: rgba(255, 255, 255, 0.9); font-weight: 500; }
        .card-vermelho .footer-modal a { color: #ffffff; text-decoration: underline; font-weight: 700; cursor: pointer; }

        .btn-fechar-absoluto { position: absolute; top: 18px; right: 22px; background: none; border: none; font-size: 24px; cursor: pointer; color: rgba(255,255,255,0.8); transition: 0.2s; }
        .btn-fechar-absoluto:hover { color: #ffffff; transform: scale(1.1); }

        .perfil-avatar-header { text-align: center; margin-bottom: 12px; }
        .perfil-avatar-header img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

        .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; max-height: 160px; overflow-y: auto; padding: 6px; margin: 12px 0; }
        .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; overflow: hidden; transition: 0.2s; }
        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-item:hover, .avatar-item.selected { border-color: #fff8bd; transform: scale(1.12); }

        /* RESPONSIVIDADE */
        @media (max-width: 1024px) {
            .hero-banner-wrapper {
                height: 480px;
                min-height: 350px;
            }
            .enfeite-fundo-esquerdo {
                width: 900px;
                max-width: 80vw;
            }
        }

        @media (max-width: 850px) {
            .card-favorito-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-fav-img-wrapper {
                width: 100%;
                height: 230px;
            }
        }

        @media (max-width: 768px) {
            .hero-banner-wrapper {
                height: 320px;
                min-height: 240px;
            }
            .titulo-editorial { font-size: 30px; padding-right: 35px; }
            .titulo-editorial .icone-coroa { font-size: 30px; top: -10px; }
            .enfeite-fundo-esquerdo { opacity: 0.3; width: 650px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR PADRONIZADA -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="favoritos.php" class="ativo">Favoritos</a>
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

    <!-- BANNER PRINCIPAL -->
    <section class="secao-hero">
        <div class="hero-banner-wrapper" style="background-image: url('<?= buscarImagem('favoritos_banner') ?>');"></div>
    </section>

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

    <!-- ÁREA PRINCIPAL DA PÁGINA -->
    <div class="main-wrapper">
        <!-- MARCA D'ÁGUA FIXA BEM GRANDE NO CANTO ESQUERDO -->
        <img src="<?= buscarImagem('enfeite_favoritos1.webp') ?>" class="enfeite-fundo-esquerdo" alt="" onerror="this.src='img/enfeite_favoritos1.webp';">

        <div class="container" id="containerFavoritos">
            <div class="header-favoritos">
                <h1 class="titulo-editorial">
                    MINHAS RECEITAS FAVORITAS
                    <i class="fa-solid fa-crown icone-coroa"></i>
                </h1>
                <p class="subtitulo-editorial">
                    GUARDE OS SEUS PRATOS MÁGICOS PREFERIDOS PARA CONSULTAR SEMPRE QUE QUISER PREPARAR UM BANQUETE.
                </p>
            </div>

            <?php if (count($receitasFavoritas) > 0): ?>
                <div class="lista-favoritos">
                    <?php foreach($receitasFavoritas as $r): ?>
                    <div class="card-favorito-item">
                        
                        <div class="card-fav-img-wrapper">
                            <a href="favoritar.php?id=<?= $r['id_receita'] ?>" class="btn-fav-icon-overlay" title="Remover dos Favoritos">
                                <i class="fa-solid fa-heart"></i>
                            </a>
                            <a href="receita.php?id=<?= $r['id_receita'] ?>">
                                <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'" alt="<?= htmlspecialchars($r['nome_receita']) ?>">
                            </a>
                        </div>

                        <div class="card-fav-info">
                            <a href="receita.php?id=<?= $r['id_receita'] ?>" style="text-decoration:none;">
                                <h3 class="card-fav-titulo">
                                    <?= htmlspecialchars($r['nome_receita']) ?>
                                    <?php if (!empty($r['nome_filme'])): ?>
                                        – <?= htmlspecialchars($r['nome_filme']) ?>.
                                    <?php endif; ?>
                                </h3>
                            </a>
                            
                            <p class="card-fav-desc"><?= htmlspecialchars($r['descricao'] ?? 'Saborosa receita mágica inspirada no cinema.') ?></p>
                            
                            <?php if (($r['total_avaliacoes'] ?? 0) > 0): 
                                $media = (float)($r['media_estrelas'] ?? $r['avaliacao'] ?? 0);
                                $mediaArredondada = (int)round($media);
                            ?>
                                <div class="card-fav-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-<?= $i <= $mediaArredondada ? 'solid' : 'regular' ?> fa-star"></i>
                                    <?php endfor; ?>
                                    <span class="nota-numero"><?= number_format($media, 1, '.', '') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sem-favoritos">
                    <i class="fa-regular fa-heart"></i>
                    <h3>Sua lista de favoritos está vazia!</h3>
                    <p>Explore o nosso menu de receitas encantadoras e clique no ícone de coração para guardar aqui os seus pratos prediletos.</p>
                    <a href="index.php#containerReceitas" class="btn-explorar">EXPLORAR RECEITAS</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- JAVASCRIPT DOS MODAIS -->
    <script>
    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'none';
        if(document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
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