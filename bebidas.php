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
        header("Location: bebidas.php");
        exit;
    } else {
        header("Location: bebidas.php?status=erro&msg=" . urlencode("Senha ou E-mail incorreto"));
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

    header("Location: bebidas.php?status=sucesso&msg=" . urlencode("Perfil atualizado com sucesso!"));
    exit;
}

// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";

    $variacoes = [$nomeImagem];
    if (strpos($nomeImagem, 'beb') !== false) {
        $variacoes[] = 'bebidas-receitas.webp';
        $variacoes[] = 'bebitas-receitas.webp';
        $variacoes[] = 'bebidas-receitas.png';
        $variacoes[] = 'bebitas-receitas.png';
        $variacoes[] = 'bebidas_receitas.png';
        $variacoes[] = 'bebidas_receitas.webp';
    }

    $pastas = ['', 'logo', 'elementos', 'fundo', 'pratos', 'filme', 'slider', 'perfis', 'avatares'];
    $extensoes = ['', '.webp', '.png', '.jpg', '.jpeg'];

    foreach ($variacoes as $var) {
        if (file_exists($var)) return $var;
        if (file_exists("img/" . $var)) return "img/" . $var;

        $nomeBase = pathinfo($var, PATHINFO_FILENAME);

        foreach ($pastas as $pasta) {
            $prefixo = empty($pasta) ? "img/" : "img/" . trim($pasta, '/') . "/";
            foreach ($extensoes as $ext) {
                $caminho1 = $prefixo . $var . $ext;
                if (file_exists($caminho1)) return $caminho1;

                $caminho2 = $prefixo . $nomeBase . $ext;
                if (file_exists($caminho2)) return $caminho2;
            }
        }
    }

    return "img/" . (strpos($nomeImagem, '.') !== false ? $nomeImagem : $nomeImagem . ".png");
}

// CONSULTA DINÂMICA DAS RECEITAS DE BEBIDAS COM NOME DO FILME E MÉDIA DE AVALIAÇÕES
$stmtBebidas = $pdo->query("
    SELECT r.*, f.nome_filme, 
           COUNT(a.id_avaliacao) AS total_avaliacoes, 
           AVG(a.estrelas) AS media_estrelas
    FROM receitas r 
    LEFT JOIN filme f ON r.id_filme = f.id_filme 
    LEFT JOIN avaliacoes a ON r.id_receita = a.id_receita
    WHERE r.tipo = 'BEBIDA'
    GROUP BY r.id_receita
    ORDER BY r.nome_receita ASC
");
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
    <title>Bebidas Mágicas | Magical Bites</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            background-color: #0a1733;
            color: #ffffff;
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

        /* --- PAGE HEADER BANNER --- */
        .page-header-banner {
            background-color: #fff4b8;
            padding: 25px 20px 30px;
            text-align: center;
            position: relative;
            border-bottom: 4px solid #a62428;
        }

        .banner-titulo-box {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }

        .img-titulo-banner {
            width: 100%;
            max-width: 330px;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .search-page-box {
            position: relative;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        .search-page-box input {
            width: 100%;
            padding: 10px 20px 10px 48px;
            border-radius: 50px;
            border: 2px solid #a62428;
            background: #fff4b8;
            font-size: 15px;
            color: #a62428;
            outline: none;
            box-shadow: none;
        }

        .search-page-box input::placeholder {
            color: #a62428;
            opacity: 0.8;
        }

        .search-page-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #a62428;
            font-size: 17px;
        }

        /* --- SEÇÃO BEBIDAS --- */
        .secao-bebidas-page {
            position: relative;
            background-image: linear-gradient(rgba(10, 25, 55, 0.45), rgba(6, 16, 38, 0.55)), url('<?= buscarImagem("fundo2_index") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            padding: 50px 0 70px;
            min-height: 70vh;
        }

        .container-secao {
            max-width: 1150px;
            margin: 0 auto;
            padding: 0 25px;
        }

        .grid-receitas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .card-receita-item {
            background: rgba(10, 20, 42, 0.65);
            border-radius: 22px;
            overflow: hidden;
            text-decoration: none;
            color: #ffffff;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(6px);
        }

        .card-receita-item:hover {
            transform: translateY(-7px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
            border-color: rgba(255, 246, 169, 0.5);
            background: rgba(14, 28, 58, 0.75);
        }

        .card-receita-img {
            width: 100%;
            height: 210px;
            overflow: hidden;
            position: relative;
        }

        .card-receita-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .card-receita-item:hover .card-receita-img img {
            transform: scale(1.06);
        }

        .badge-filme {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(166, 36, 40, 0.9);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .card-receita-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-receita-titulo {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #ffffff;
            line-height: 1.3;
        }

        .card-receita-desc {
            font-size: 12.5px;
            color: #e2e8f0;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .card-receita-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: #fff8bd;
            font-weight: 600;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .card-receita-rating {
            color: #ffca28;
            letter-spacing: 1px;
            font-size: 13px;
        }

        /* MODAIS */
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

        @media (max-width: 900px) {
            .grid-receitas { grid-template-columns: repeat(2, 1fr); }
            .img-titulo-banner { max-width: 260px; }
        }
        @media (max-width: 600px) {
            .grid-receitas { grid-template-columns: 1fr; }
            .img-titulo-banner { max-width: 210px; }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="salgados.php">Salgados</a>
            <a href="doces.php">Doces</a>
            <a href="bebidas.php" class="ativo">Bebidas</a>
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
        <form action="bebidas.php" method="POST">
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
        <form action="bebidas.php" method="POST" enctype="multipart/form-data">
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

    <!-- HEADER DA PÁGINA -->
    <header class="page-header-banner">
        <div class="banner-titulo-box">
            <img src="<?= buscarImagem('bebitas-receitas.webp') ?>" alt="Receitas Bebidas" class="img-titulo-banner">
        </div>
        
        <div class="search-page-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="inputPesquisa" placeholder="Buscar receita..." onkeyup="pesquisarReceitas()">
        </div>
    </header>

    <!-- LISTAGEM DE RECEITAS -->
    <section class="secao-bebidas-page">
        <div class="container-secao">
            <div class="grid-receitas">
                <?php foreach($receitasBebidas as $r): ?>
                <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card-receita-item item-busca">
                    <div class="card-receita-img">
                        <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'" alt="<?= htmlspecialchars($r['nome_receita']) ?>">
                        <?php if (!empty($r['nome_filme'])): ?>
                            <span class="badge-filme"><?= htmlspecialchars($r['nome_filme']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-receita-body">
                        <h3 class="card-receita-titulo"><?= htmlspecialchars($r['nome_receita']) ?></h3>
                        <p class="card-receita-desc"><?= htmlspecialchars($r['descricao'] ?? 'Saborosa receita de bebida mágica inspirada no cinema.') ?></p>
                        
                        <div class="card-receita-meta">
                            <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($r['tempo_preparo'] ?? '30min') ?></span>
                            <?php if (($r['total_avaliacoes'] ?? 0) > 0): 
                                $media = round((float)($r['media_estrelas'] ?? $r['avaliacao'] ?? 0), 1);
                            ?>
                                <span class="card-receita-rating">★ <?= number_format($media, 1) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- JAVASCRIPT -->
    <script>
    function pesquisarReceitas() {
        let input = document.getElementById('inputPesquisa').value.toLowerCase();
        let items = document.querySelectorAll('.item-busca');

        items.forEach(item => {
            let titulo = item.querySelector('.card-receita-titulo').innerText.toLowerCase();
            if (titulo.includes(input)) {
                item.style.display = "flex";
            } else {
                item.style.display = "none";
            }
        });
    }

    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'none';
        if(document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
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
    </script>
</body>
</html>