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

// Suporte flexível para a chave de ID na sessão
$id_usuario = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['id_usuario'] ?? null;

// Busca as receitas favoritadas do utilizador na tabela 'favoritar'
$receitasFavoritas = [];
if ($id_usuario) {
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM receitas r
        INNER JOIN favoritar f ON r.id_receita = f.id_receita
        WHERE f.id_usuario = :id_usuario
        ORDER BY f.id_favoritos DESC
    ");
    $stmt->execute([':id_usuario' => $id_usuario]);
    $receitasFavoritas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        html { scroll-behavior: smooth; }

        /* FUNDO CLARO DINÂMICO E RESPONSIVO COM FUNDO_FAVORITOS1 */
        body { 
            background-color: #ffffff;
            background-image: url('<?= buscarImagem("fundo_favoritos1") ?>');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #1f4396; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        @media (max-width: 768px) {
            body {
                background-attachment: scroll;
                background-size: cover;
            }
        }

        /* --- NAVBAR ORIGINAL PADRONIZADA --- */
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

        /* --- HERO BANNER 100% RESPONSIVO --- */
        .hero {
            width: 100%;
            aspect-ratio: 16 / 9;
            max-height: calc(100vh - 70px);
            position: relative;
            background-image: url('<?= buscarImagem("favoritos_banner") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #121829;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border-bottom: 2px solid rgba(41, 79, 136, 0.2);
            image-rendering: -webkit-optimize-contrast;
            overflow: hidden;
        }

        .hero-content {
            position: absolute;
            left: 10.5%;
            bottom: 11%;
            z-index: 5;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8vw 3.5vw;
            font-size: clamp(12px, 1.1vw, 18px);
            background: #245089;
            backdrop-filter: blur(8px);
            color: #ffffff;
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
            color: #fff6a9;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(33, 69, 117, 0.4);
        }

        @media (max-width: 600px) {
            .hero-content {
                left: 8%;
                bottom: 8%;
            }
            .hero-btn {
                padding: 6px 22px;
                font-size: 11px;
            }
        }

        /* CONTAINER PRINCIPAL */
        .container { 
            width: 100%; 
            max-width: 1250px; 
            margin: 50px auto 70px; 
            padding: 0 30px; 
            scroll-margin-top: 90px;
        }
        
        /* --- ESTILO DO TÍTULO EDITORIAL EM AZUL --- */
        .header-favoritos {
            margin-bottom: 45px;
            text-align: center;
        }

        .titulo-editorial { 
            font-size: 42px; 
            font-weight: 900; 
            color: #1f4396; 
            text-transform: uppercase;
            letter-spacing: 1.5px;
            line-height: 1.15;
            position: relative;
            display: inline-block;
            padding-right: 38px;
        }

        /* COROA VAZADA EM AZUL */
        .titulo-editorial .icone-destaque {
            position: absolute;
            top: -16px;
            right: -8px;
            font-size: 38px;
            color: transparent;
            -webkit-text-stroke: 2px #1f4396;
            transform: rotate(22deg);
            filter: drop-shadow(0 4px 12px rgba(31, 67, 150, 0.25));
        }

        .subtitulo-editorial {
            color: #294f88;
            font-size: 15px;
            margin-top: 12px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .titulo-editorial {
                font-size: 26px;
                padding-right: 28px;
            }
            .titulo-editorial .icone-destaque {
                font-size: 28px;
                top: -12px;
                right: -4px;
                -webkit-text-stroke: 1.5px #1f4396;
            }
        }

        /* GRID DE CARDS */
        .lista { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 35px; 
        }

        /* CARD DE RECEITA */
        .card-receita { 
            background: transparent;
            text-decoration: none; 
            transition: transform 0.35s ease, box-shadow 0.35s ease; 
            display: flex; 
            flex-direction: column; 
            position: relative; 
        }

        .card-receita:hover { 
            transform: translateY(-8px); 
        }

        /* CONTAINER DA IMAGEM */
        .card-img-wrapper {
            position: relative;
            width: 100%;
            height: 240px;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            margin-bottom: 16px;
        }

        .card-img-wrapper img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            transition: transform 0.5s ease;
        }

        .card-receita:hover .card-img-wrapper img {
            transform: scale(1.05);
        }

        /* BOTÃO DE REMOÇÃO RÁPIDA */
        .btn-remover-fav {
            position: absolute; 
            top: 14px; 
            right: 14px; 
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(31, 67, 150, 0.2); 
            width: 38px; 
            height: 38px; 
            border-radius: 50%; 
            color: #ff528a;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
            transition: 0.2s ease; 
            z-index: 5;
        }
        .btn-remover-fav:hover { 
            transform: scale(1.15); 
            background: #ff528a; 
            color: #ffffff;
            border-color: #ff528a;
        }

        /* TEXTO DO CARD EM AZUL NÍTIDO */
        .card-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 0 4px;
        }

        .card-titulo { 
            font-weight: 700; 
            font-size: 17px; 
            color: #1f4396; 
            line-height: 1.35;
        }

        .card-descricao {
            font-size: 13px;
            color: #475569;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-avaliacao {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #f59e0b;
            font-size: 13px;
            margin-top: 4px;
        }

        .card-avaliacao span {
            color: #475569;
            font-size: 12px;
            margin-left: 6px;
            font-weight: 600;
        }

        /* TELA VAZIA COM CONTRASTE CLARO */
        .sem-favoritos { 
            text-align: center; 
            padding: 90px 20px; 
            background: rgba(41, 79, 136, 0.05);
            backdrop-filter: blur(8px);
            border-radius: 28px;
            border: 2px dashed rgba(31, 67, 150, 0.2);
            max-width: 600px;
            margin: 20px auto;
        }
        .sem-favoritos i { font-size: 60px; color: #1f4396; margin-bottom: 20px; }
        .sem-favoritos h3 { font-size: 22px; color: #1f4396; margin-bottom: 10px; font-weight: 700; }
        .sem-favoritos p { color: #475569; font-size: 14px; margin-bottom: 25px; }

        .btn-explorar {
            display: inline-block;
            padding: 12px 32px;
            background: #1f4396;
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(31, 67, 150, 0.3);
        }
        .btn-explorar:hover {
            background: #162f6b;
            transform: translateY(-2px);
        }

        /* MODAIS */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); z-index: 999; }
        .card-flutuante { display: none; position: fixed; top: 80px; right: 25px; width: 380px; max-height: calc(100vh - 100px); overflow-y: auto; background: #ffffff; border-radius: 28px; box-shadow: 0 15px 50px rgba(0,0,0,0.3); padding: 28px; z-index: 1000; color: #333; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-fechar { background: none; border: none; font-size: 26px; cursor: pointer; color: #999; }
        .input-group-modal { position: relative; margin-bottom: 15px; }
        .input-group-modal i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #1f4396; }
        .input-group-modal input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 12px; outline: none; }
        .btn-acao { width: 100%; padding: 14px; background: #1f4396; color: white; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .perfil-avatar-header { text-align: center; margin-bottom: 15px; }
        .perfil-avatar-header img { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 3px solid #1f4396; }
        .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; max-height: 140px; overflow-y: auto; padding: 5px; margin: 10px 0; }
        .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; overflow: hidden; }
        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .btn-logout-modal { display: block; text-align: center; margin-top: 15px; color: #f44336; text-decoration: none; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

    <!-- NAVBAR ORIGINAL -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.jpg') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="favoritos.php" class="ativo">Favoritos</a>
            <a href="filmes.php">Filmes</a>

            <a href="javascript:void(0)" onclick="alternarModais('perfil')">
                <img src="<?= buscarImagem($_SESSION['usuario']['foto'] ?? '') ?>" class="nav-user-avatar" onerror="this.src='img/logo/logo_menu.png'">
                <?= htmlspecialchars(explode(' ', $_SESSION['usuario']['nome'])[0]) ?>
            </a>
        </div>
    </div>

    <!-- BANNER PRINCIPAL (HERO) -->
    <div class="hero">
        <div class="hero-content">
            <a href="#containerFavoritos" class="hero-btn">Explorar</a>
        </div>
    </div>

    <!-- OVERLAY E MODAL PERFIL -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>
    <div id="modalPerfil" class="card-flutuante">
        <div class="card-header">
            <img src="<?= buscarImagem('logo.png') ?>" style="width: 90px;">
            <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
        </div>
        <div class="perfil-avatar-header">
            <img src="<?= buscarImagem($_SESSION['usuario']['foto'] ?? '') ?>" id="modalPreviewFoto" onerror="this.src='img/logo/logo_menu.png'">
            <h3 style="color:#1f4396; margin-top:8px; font-size:18px;"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></h3>
            <p style="font-size:12px; color:#777;"><?= htmlspecialchars($_SESSION['usuario']['email']) ?></p>
        </div>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="atualizar_perfil">
            <input type="hidden" name="avatar_predefinido" id="modalInputAvatar" value="">
            <div class="input-group-modal"><i class="fa fa-user"></i><input type="text" name="nome" value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" required placeholder="Nome"></div>
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

    <!-- CONTEÚDO DA PÁGINA -->
    <div class="container" id="containerFavoritos">
        
        <div class="header-favoritos">
            <h1 class="titulo-editorial">
                MINHAS RECEITAS FAVORITAS
                <i class="fa-solid fa-crown icone-destaque"></i>
            </h1>
            <p class="subtitulo-editorial">Guarde os seus pratos mágicos preferidos para consultar sempre que quiser preparar um banquete.</p>
        </div>

        <?php if (count($receitasFavoritas) > 0): ?>
            <div class="lista">
                <?php foreach($receitasFavoritas as $r): ?>
                <div class="card-receita">
                    
                    <div class="card-img-wrapper">
                        <a href="favoritar.php?id=<?= $r['id_receita'] ?>" class="btn-remover-fav" title="Remover dos Favoritos">
                            <i class="fa-solid fa-heart"></i>
                        </a>
                        <a href="receita.php?id=<?= $r['id_receita'] ?>">
                            <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'" alt="<?= htmlspecialchars($r['nome_receita']) ?>">
                        </a>
                    </div>

                    <div class="card-info">
                        <a href="receita.php?id=<?= $r['id_receita'] ?>" style="text-decoration:none;">
                            <h3 class="card-titulo"><?= htmlspecialchars($r['nome_receita']) ?></h3>
                        </a>
                        
                        <p class="card-descricao"><?= htmlspecialchars($r['descricao'] ?? 'Saborosa receita inspirada na magia do cinema.') ?></p>
                        
                        <div class="card-avaliacao">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <span><?= htmlspecialchars($r['avaliacao'] ?? '4.8') ?></span>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sem-favoritos">
                <i class="fa-regular fa-heart"></i>
                <h3>Sua lista de favoritos está vazia!</h3>
                <p>Explore o nosso menu de receitas e clique no ícone de coração para guardar aqui os seus pratos prediletos.</p>
                <a href="index.php#containerReceitas" class="btn-explorar">Explorar Receitas</a>
            </div>
        <?php endif; ?>

    </div>

    <script>
    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
    }
    function alternarModais(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
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