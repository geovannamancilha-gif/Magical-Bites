<?php
session_start();

// Conexão com a base de dados
try {
    $pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "Home@spSENAI2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
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
            'id'    => $usuario['id_usuario'],
            'nome'  => $usuario['nome_usuario'],
            'email' => $usuario['email'],
            'foto'  => !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'logo_menu.png'
        ];
        header("Location: index.php");
        exit;
    } else {
        header("Location: index.php?status=erro&msg=" . urlencode("E-mail ou senha incorretos!"));
        exit;
    }
}


// =========================================================================
// 2. PROCESSAMENTO DA ATUALIZAÇÃO DO PERFIL (MODAL)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_perfil' && isset($_SESSION['usuario'])) {
    $idUsuario = $_SESSION['usuario']['id'];
    $novoNome = trim($_POST['nome']);
    $fotoPerfil = $_SESSION['usuario']['foto']; // Mantém a antiga se não alterar

    // Se escolheu um avatar pré-definido
    if (!empty($_POST['avatar_predefinido'])) {
        $fotoPerfil = trim($_POST['avatar_predefinido']);
    }

    // Se fez upload de foto
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

    // Atualiza base de dados
    $stmtUpdate = $pdo->prepare("UPDATE usuario SET nome_usuario = :nome, foto_perfil = :foto WHERE id_usuario = :id");
    $stmtUpdate->execute([
        ':nome' => $novoNome,
        ':foto' => $fotoPerfil,
        ':id'   => $idUsuario
    ]);

    // Atualiza a sessão
    $_SESSION['usuario']['nome'] = $novoNome;
    $_SESSION['usuario']['foto'] = $fotoPerfil;

    header("Location: index.php?status=sucesso&msg=" . urlencode("Perfil atualizado com sucesso!"));
    exit;
}


// =========================================================================
// FUNÇÃO AUTOMÁTICA DE IMAGENS
// =========================================================================
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

$status = isset($_GET['status']) ? $_GET['status'] : "";
$msg = isset($_GET['msg']) ? $_GET['msg'] : (isset($_GET['erro']) ? $_GET['erro'] : "");

// BUSCA OS 30 AVATARES DIRECTO DA BASE DE DADOS
$stmtAvatares = $pdo->query("SELECT * FROM avatar_predefinido");
$avataresPadrao = $stmtAvatares->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magical Bites | Receitas Disney</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            }

            body {
                background: #f3f1ff;
                color: #333;
                overflow-x: hidden;
            }

            /* --- NAVBAR --- */
             .navbar {
                width: 100%;
                background: #294f88; /* Cor exata do fundo da foto (elimina a caixinha) */
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
                font-weight: 700; /* Aplica o negrito nas opções do menu */
                transition: 0.3s;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
            }

             .menu a:hover {
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

            /* --- HERO & SLIDER --- */
.hero {
  width: 100%;
  height: calc(100vh - 70px);
  position: relative;
  background-size: cover;
  background-position: center 60%; /* Centraliza a imagem perfeitamente */
  transition: background-image 0.6s ease-in-out;
}

.hero-content {
  position: absolute;
  left: 200px; /* Aumentado para empurrar o botão para a direita */
  bottom: 110px;
  z-index: 5;
}

.hero-btn {
display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 18px 70px; /* Aumenta a altura e a largura do botão */
  font-size: 18px;    /* Aumenta o tamanho do texto EXPLORAR */
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
                color: #1e0061;
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

            /* --- LAYOUT RECEITAS --- */
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

            /* --- MODAIS FLUTUANTES --- */
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(4px);
                z-index: 999;
            }

            .card-flutuante {
                display: none;
                position: fixed;
                top: 80px;
                right: 25px;
                width: 380px;
                max-height: calc(100vh - 100px);
                overflow-y: auto;
                background: white;
                border-radius: 28px;
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
                padding: 28px;
                z-index: 1000;
                color: #333;
                animation: fadeInSlide 0.3s ease-out;
            }

            .card-flutuante::-webkit-scrollbar { width: 6px; }
            .card-flutuante::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }

            @keyframes fadeInSlide {
                from { opacity: 0; transform: translateX(30px); }
                to { opacity: 1; transform: translateX(0); }
            }

            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .btn-fechar {
                background: none;
                border: none;
                font-size: 26px;
                cursor: pointer;
                color: #999;
            }

            .input-group-modal {
                position: relative;
                margin-bottom: 15px;
            }

            .input-group-modal i {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #1f4396;
            }

            .input-group-modal input {
                width: 100%;
                padding: 12px 15px 12px 45px;
                border: 1px solid #ddd;
                border-radius: 12px;
                outline: none;
            }

            .btn-acao {
                width: 100%;
                padding: 14px;
                background: #1f4396;
                color: white;
                border: none;
                border-radius: 50px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.3s;
                margin-top: 10px;
            }

            .btn-acao:hover {
                background: #162f6b;
                transform: scale(1.02);
            }

            .status-icon {
                font-size: 50px;
                margin-bottom: 15px;
                text-align: center;
            }

            .footer-modal {
                margin-top: 20px;
                font-size: 13px;
                text-align: center;
                color: #666;
            }

            .footer-modal a {
                color: #1f4396;
                text-decoration: none;
                font-weight: 600;
                cursor: pointer;
            }

            /* ESTILOS ESPECÍFICOS DO PERFIL NO MODAL */
            .perfil-avatar-header {
                text-align: center;
                margin-bottom: 15px;
            }
            .perfil-avatar-header img {
                width: 85px;
                height: 85px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid #1f4396;
                box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            }
            .grid-avatares-modal {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                justify-content: center;
                max-height: 140px;
                overflow-y: auto;
                padding: 5px;
                margin: 10px 0;
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
                border-color: #ffe45c;
                transform: scale(1.1);
            }
            .btn-logout-modal {
                display: block;
                text-align: center;
                margin-top: 15px;
                color: #f44336;
                text-decoration: none;
                font-weight: 600;
                font-size: 14px;
            }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <div class="navbar">
      <div class="logo"><img src="<?= buscarImagem('logo_menu.jpg') ?>" alt="Magical Bites"></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="#containerReceitas">Receitas</a>
            <a href="#">Favoritos</a>
            <a href="filmes.php">Filmes</a>

            <!-- ABRE O MODAL DO PERFIL SE LOGADO, SE NÃO ABRE O LOGIN -->
            <?php if (isset($_SESSION['usuario'])): ?>
                <a href="javascript:void(0)" onclick="alternarModais('perfil')">
                    <img src="<?= buscarImagem($_SESSION['usuario']['foto']) ?>" class="nav-user-avatar" onerror="this.src='img/logo/logo_menu.png'">
                    <?= htmlspecialchars(explode(' ', $_SESSION['usuario']['nome'])[0]) ?>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="alternarModais('login')"><i class="fa-solid fa-user"></i> Perfil</a>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>

    <!-- 1. MODAL CADASTRO -->
    <div id="modalCadastro" class="card-flutuante">
        <div class="card-header">
            <img src="<?= buscarImagem('logo.png') ?>" style="width: 100px;">
            <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
        </div>
        <h2 style="color:#1f4396">Crie a sua conta</h2>
        <p style="font-size:14px; color:#666; margin-bottom:20px">Junte-se à nossa comunidade!</p>
        <form action="cadastrar.php" method="POST">
            <div class="input-group-modal"><i class="fa fa-user"></i><input type="text" name="nome" placeholder="Nome completo" required></div>
            <div class="input-group-modal"><i class="fa fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
            <div class="input-group-modal"><i class="fa fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
            <button type="submit" class="btn-acao">Registar</button>
        </form>
        <div class="footer-modal">Já tem conta? <a onclick="alternarModais('login')">Faça login</a></div>
    </div>

    <!-- 2. MODAL LOGIN -->
    <div id="modalLogin" class="card-flutuante">
        <div class="card-header">
            <img src="<?= buscarImagem('logo.png') ?>" style="width: 100px;">
            <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
        </div>
        <h2 style="color:#1f4396">Bem-vindo!</h2>
        <p style="font-size:14px; color:#666; margin-bottom:20px">Acesse sua conta mágica.</p>
        <form action="index.php" method="POST">
            <div class="input-group-modal"><i class="fa fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
            <div class="input-group-modal"><i class="fa fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
            <button type="submit" class="btn-acao">Entrar</button>
        </form>
        <div class="footer-modal">Novo aqui? <a onclick="alternarModais('cadastro')">Crie uma conta</a></div>
    </div>

    <!-- 3. MODAL PERFIL -->
    <?php if (isset($_SESSION['usuario'])): ?>
    <div id="modalPerfil" class="card-flutuante">
        <div class="card-header">
            <img src="<?= buscarImagem('logo.png') ?>" style="width: 90px;">
            <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
        </div>

        <div class="perfil-avatar-header">
            <img src="<?= buscarImagem($_SESSION['usuario']['foto']) ?>" id="modalPreviewFoto" onerror="this.src='img/logo/logo_menu.png'">
            <h3 style="color:#1f4396; margin-top:8px; font-size:18px;"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></h3>
            <p style="font-size:12px; color:#777;"><?= htmlspecialchars($_SESSION['usuario']['email']) ?></p>
        </div>

        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="atualizar_perfil">
            <input type="hidden" name="avatar_predefinido" id="modalInputAvatar" value="">

            <div class="input-group-modal" style="margin-bottom: 10px;">
                <i class="fa fa-user"></i>
                <input type="text" name="nome" value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" required placeholder="Nome">
            </div>

            <label style="font-size:12px; font-weight:600; color:#1f4396;">Avatar Disney</label>
            <div class="grid-avatares-modal">
                <?php foreach ($avataresPadrao as $avatar): ?>
                    <div class="avatar-item" onclick="selecionarAvatarModal('<?= $avatar['caminho_imagem'] ?>', this)">
                        <img src="<?= buscarImagem($avatar['caminho_imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>
                <?php endforeach; ?>
            </div>

            <label style="font-size:12px; font-weight:600; color:#1f4396; display:block; margin-top:10px;">Ou foto própria:</label>
            <input type="file" name="foto_upload" accept="image/*" style="font-size:12px; margin-top:5px; width:100%;" onchange="limparAvatarModal()">

            <button type="submit" class="btn-acao" style="margin-top:18px;">Salvar Alterações</button>
        </form>

        <a href="logout.php" class="btn-logout-modal"><i class="fa-solid fa-right-from-bracket"></i> Sair da Conta</a>
    </div>
    <?php endif; ?>

    <!-- 4. MODAL STATUS / MENSAGENS -->
    <?php if ($status != ""): ?>
    <div id="modalStatus" class="card-flutuante" style="display: block; z-index: 1001;">
        <div class="card-header">
            <button class="btn-fechar" onclick="fecharStatus()">&times;</button>
        </div>

        <?php if ($status == 'sucesso'): ?>
            <div class="status-icon" style="color: #4CAF50;">✅</div>
            <h2 style="color:#1f4396; text-align:center;">Prontinho!</h2>
            <p style="font-size:14px; color:#666; text-align:center; margin-bottom:20px;">
                <?= htmlspecialchars($msg) ?>
            </p>
            <button class="btn-acao" onclick="fecharStatus();">Continuar</button>
        <?php elseif ($status == 'erro'): ?>
            <div class="status-icon" style="color: #f44336;">❌</div>
            <h2 style="color:#1f4396; text-align:center;">Oops!</h2>
            <p style="font-size:14px; color:#666; text-align:center; margin-bottom:20px;">
                <?= htmlspecialchars($msg) ?>
            </p>
            <button class="btn-acao" onclick="fecharStatus();">Tentar Novamente</button>
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
    // PESQUISA EM TEMPO REAL
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

    // MODAIS
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

    // SLIDER
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

    // Inicializa slider no carregamento
    atualizarSlide();
    </script>
</body>
</html>