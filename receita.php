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

    $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id_receita = :id");
    $stmt->bindParam(':id', $id_receita, PDO::PARAM_INT);
    $stmt->execute();
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receita) {
        die("<h1>Receita não encontrada na base de dados para o ID: $id_receita!</h1>");
    }

    // Processamento de listas
    $lista_ingredientes = array_filter(array_map('trim', explode(";", $receita['ingredientes'])));
    $passos_preparo = array_filter(array_map('trim', preg_split('/(?=\d+\.\s)/', $receita['modo_preparo'])));

    // Definição de cores por categoria
    $classe_tag = 'tag-doce';
    if ($receita['tipo'] == 'SALGADO') {
        $classe_tag = 'tag-salgado';
    } elseif ($receita['tipo'] == 'BEBIDA') {
        $classe_tag = 'tag-bebida';
    }

    // VERIFICAÇÃO SE A RECEITA ESTÁ FAVORITADA PELO USUÁRIO LOGADO
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

    // Busca de receitas relacionadas
    $stmtDoces = $pdo->prepare("SELECT * FROM receitas WHERE tipo = :tipo AND id_receita != :id LIMIT 4");
    $stmtDoces->bindParam(':tipo', $receita['tipo'], PDO::PARAM_STR);
    $stmtDoces->bindParam(':id', $id_receita, PDO::PARAM_INT);
    $stmtDoces->execute();
    $receitasRelacionadas = $stmtDoces->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

// BUSCA AVATARES CASO O UTILIZADOR ESTEJA LOGADO (PARA O MODAL DE PERFIL)
$avataresPadrao = [];
if (isset($_SESSION['usuario'])) {
    try {
        $stmtAvatares = $pdo->query("SELECT * FROM avatar_predefinido");
        $avataresPadrao = $stmtAvatares->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $avataresPadrao = [];
    }
}

// ==========================================
// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS
// ==========================================
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
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($receita['nome_receita']) ?> | Magical Bites</title>
    
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
            background: linear-gradient(135deg, #f3f5fd 0%, #e8ecf8 100%);
            color: #1e2448;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- NAVBAR (PADRONIZADA COM O INDEX/FILMES) --- */
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

        /* --- MODAIS FLUTUANTES (PADRONIZADOS) --- */
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

        .card-flutuante .btn-fechar {
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

        /* --- LAYOUT DA RECEITA --- */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 25px;
        }

        .voltar-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1f4396;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
            transition: 0.2s;
        }

        .voltar-link:hover {
            transform: translateX(-3px);
            color: #162f6b;
        }

        /* HERO SECTION (INFO + IMAGEM) */
        .hero-receita {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 40px;
            align-items: center;
            margin-bottom: 40px;
        }

        .hero-info .titulo {
            font-size: 38px;
            font-weight: 700;
            color: #172146;
            line-height: 1.2;
            margin-bottom: 15px;
        }

        .tag {
            display: inline-block;
            padding: 5px 16px;
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .tag-doce { background: #ff528a; }
        .tag-salgado { background: #22c55e; }
        .tag-bebida { background: #06b6d4; }

        .meta-info {
            display: flex;
            align-items: center;
            gap: 25px;
            font-size: 14px;
            color: #505878;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: #1f4396;
        }

        .meta-item i.fa-star {
            color: #ffb800;
        }

        .descricao {
            font-size: 15px;
            color: #636c8a;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 520px;
        }

        /* BOTOES DE AÇÃO */
        .acoes {
            display: flex;
            gap: 15px;
        }

        .btn-favoritar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 30px;
            border: 1.5px solid #1f4396;
            background: transparent;
            color: #1f4396;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-favoritar:hover {
            background: rgba(31, 67, 150, 0.08);
            transform: translateY(-2px);
        }

        /* ESTILO QUANDO A RECEITA JÁ ESTÁ FAVORITADA */
        .btn-favoritar.favoritado {
            background: #ff528a;
            border-color: #ff528a;
            color: white;
            box-shadow: 0 4px 14px rgba(255, 82, 138, 0.35);
        }

        .btn-favoritar.favoritado:hover {
            background: #e04377;
            border-color: #e04377;
            transform: translateY(-2px);
        }

        .btn-fazer {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 32px;
            border-radius: 30px;
            border: none;
            background: #1f4396;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(31, 67, 150, 0.35);
            transition: 0.3s;
        }

        .btn-fazer:hover {
            background: #162f6b;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(31, 67, 150, 0.45);
        }

        /* CONTAINER DA IMAGEM PRINCIPAL */
        .hero-imagem {
            position: relative;
            width: 100%;
            height: 380px;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .hero-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- DETALHES DA RECEITA --- */
        .detalhes-grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 30px;
            margin-top: 10px;
        }

        .card-painel {
            background: #ffffff;
            border-radius: 28px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-painel h2 {
            font-size: 22px;
            color: #172146;
            margin-bottom: 22px;
            font-weight: 700;
        }

        /* LISTA DE INGREDIENTES */
        .lista-ingredientes {
            list-style: none;
        }

        .lista-ingredientes li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 14px;
            color: #4b526d;
            font-size: 14.5px;
            line-height: 1.5;
        }

        .lista-ingredientes li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #1f4396;
            font-size: 18px;
            top: -2px;
        }

        /* LISTA DE PREPARO */
        .lista-preparo {
            list-style: none;
            counter-reset: passo-counter;
        }

        .lista-preparo li {
            counter-increment: passo-counter;
            position: relative;
            padding-left: 28px;
            margin-bottom: 16px;
            color: #4b526d;
            font-size: 14.5px;
            line-height: 1.6;
        }

        .lista-preparo li::before {
            content: counter(passo-counter) ".";
            position: absolute;
            left: 0;
            color: #1f4396;
            font-weight: 700;
        }

        /* DICA MÁGICA BOX */
        .dica-magica {
            background: #f0edff;
            border-radius: 20px;
            padding: 20px 25px;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .dica-conteudo h4 {
            color: #1f4396;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .dica-conteudo p {
            color: #43598d;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .dica-icone {
            font-size: 28px;
            color: #1f4396;
        }

        /* SEÇÃO RELACIONADAS */
        .secao-relacionadas {
            margin-top: 60px;
            margin-bottom: 40px;
        }

        .secao-relacionadas h3 {
            font-size: 22px;
            color: #172146;
            margin-bottom: 25px;
        }

        .grid-relacionadas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }

        .card-relacionada {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }

        .card-relacionada:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        .card-relacionada img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .card-relacionada .info-card {
            padding: 15px;
            text-align: center;
        }

        .card-relacionada p {
            font-size: 14px;
            font-weight: 600;
            color: #172146;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 900px) {
            .hero-receita {
                grid-template-columns: 1fr;
            }

            .hero-imagem {
                height: 300px;
            }

            .detalhes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="logo">
            <a href="index.php"><img src="<?= buscarImagem('logo_menu.jpg') ?>" alt="Magical Bites"></a>
        </div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas" class="active">Receitas</a>
            <a href="favoritos.php">Favoritos</a>
            <a href="filmes.php">Filmes</a>

            <!-- EXIBE FOTO E NOME DO PERFIL SE LOGADO, SE NÃO PERMITE FAZER LOGIN -->
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

    <!-- OVERLAY DOS MODAIS -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>

    <!-- MODAL CADASTRO -->
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

    <!-- MODAL LOGIN -->
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

    <!-- MODAL PERFIL -->
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

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="container">
        
        <!-- BOTÃO VOLTAR -->
        <a href="index.php#containerReceitas" class="voltar-link">
            <i class="fa-solid fa-chevron-left"></i> Voltar para receitas
        </a>

        <!-- SEÇÃO HERO (INFO + IMAGEM DA RECEITA) -->
        <div class="hero-receita">
            
            <div class="hero-info">
                <h1 class="titulo"><?= htmlspecialchars($receita['nome_receita']) ?></h1>
                
                <span class="tag <?= $classe_tag ?>"><?= htmlspecialchars($receita['tipo']) ?></span>

                <div class="meta-info">
                    <div class="meta-item">
                        <i class="fa-regular fa-clock"></i>
                        <span><?= htmlspecialchars($receita['tempo_preparo']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-gauge-simple-high"></i>
                        <span><?= htmlspecialchars($receita['dificuldade']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-star"></i>
                        <span><?= htmlspecialchars($receita['avaliacao'] ?? '4.8') ?> (128 avaliações)</span>
                    </div>
                </div>

                <p class="descricao"><?= htmlspecialchars($receita['descricao']) ?></p>

                <div class="acoes">
                    <a href="favoritar.php?id=<?= $receita['id_receita'] ?>" class="btn-favoritar <?= $isFavorito ? 'favoritado' : '' ?>">
                        <i class="<?= $isFavorito ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                        <?= $isFavorito ? 'Favoritado' : 'Favoritar' ?>
                    </a>
                    <a href="#modo-preparo" class="btn-fazer">
                        <i class="fa-solid fa-utensils"></i> Fazer agora
                    </a>
                </div>
            </div>

            <div class="hero-imagem">
                <img src="<?= buscarImagem($receita['imagem']) ?>" alt="<?= htmlspecialchars($receita['nome_receita']) ?>" onerror="this.src='img/logo/logo_menu.png'">
            </div>

        </div>

        <!-- SEÇÃO DE INGREDIENTES E MODO DE PREPARO -->
        <div class="detalhes-grid" id="modo-preparo">
            
            <!-- CARD INGREDIENTES -->
            <div class="card-painel">
                <div>
                    <h2>Ingredientes</h2>
                    <ul class="lista-ingredientes">
                        <?php foreach($lista_ingredientes as $ingrediente): ?>
                            <li><?= htmlspecialchars($ingrediente) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- CARD MODO DE PREPARO -->
            <div class="card-painel">
                <div>
                    <h2>Modo de preparo</h2>
                    <ol class="lista-preparo">
                        <?php foreach($passos_preparo as $passo): ?>
                            <li><?= htmlspecialchars(preg_replace('/^\d+\.\s*/', '', $passo)) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>

        </div>

        <!-- OUTRAS RECEITAS RELACIONADAS -->
        <?php if (!empty($receitasRelacionadas)): ?>
        <div class="secao-relacionadas">
            <h3>Mais receitas relacionadas</h3>
            <div class="grid-relacionadas">
                <?php foreach($receitasRelacionadas as $r): ?>
                <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card-relacionada">
                    <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    <div class="info-card">
                        <p><?= htmlspecialchars($r['nome_receita']) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- JAVASCRIPT DOS MODAIS DE CONTA/PERFIL -->
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