<?php
// Conexão com a base de dados
$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "");
//$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "Home@spSENAI2025!");

// ==========================================
// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS
// ==========================================
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) {
        return "img/logo/logo_menu.png";
    }

    $pastas = ['slider', 'pratos', 'filme', 'logo', 'perfis', 'avatares', 'img/perfis', 'img/avatares'];
    $extensoes = ['', '.webp', '.png', '.jpg', '.jpeg'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminhoCompleto = "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoCompleto)) {
                return $caminhoCompleto;
            }
        }
    }

    if (file_exists("img/" . $nomeImagem)) {
        return "img/" . $nomeImagem;
    }

    return "img/logo/logo_menu.png";
}

// Mapeamento automático de categorias via PHP
function obterCategoriaFilme($nomeFilme) {
    $categorias = [
        'Princesas & Reinos' => ['Branca de Neve', 'Frozen', 'Enrolados', 'A Princesa e o Sapo', 'A Bela Adormecida', 'Cinderela', 'A Pequena Sereia', 'Mulan', 'Aladdin'],
        'Aventuras & Fantasia' => ['Up: Altas Aventuras', 'Ratatouille', 'Lilo & Stitch', 'Mogli', 'Turma do Mickey', 'Valente', 'Ursinho Pooh', 'Encanto', 'Luca', 'A Dama e o Vagabundo', 'Zootopia', 'Viva', 'Aristogatas', 'Moana', 'Pinóquio', '101 Dálmatas', 'Os Incríveis']
    ];

    foreach ($categorias as $categoria => $filmes) {
        foreach ($filmes as $f) {
            if (stripos($nomeFilme, $f) !== false) {
                return $categoria;
            }
        }
    }
    return 'Clássicos & Aventuras';
}

// SQL corrigida (sem a coluna f.categoria)
$sql = "
    SELECT 
        f.id_filme, 
        f.nome_filme, 
        f.imagem AS img_filme, 
        r.id_receita, 
        r.nome_receita, 
        r.imagem AS img_receita, 
        r.tipo 
    FROM filme f
    INNER JOIN receitas r ON f.id_filme = r.id_filme
    ORDER BY f.nome_filme ASC, r.nome_receita ASC
";
$stmt = $pdo->query($sql);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupa os filmes por categoria
$categorias = [];

foreach ($resultados as $row) {
    $id_filme = $row['id_filme'];
    $nome_filme = $row['nome_filme'];
    $categoria_atual = obterCategoriaFilme($nome_filme);

    if (!isset($categorias[$categoria_atual])) {
        $categorias[$categoria_atual] = [];
    }

    if (!isset($categorias[$categoria_atual][$id_filme])) {
        $categorias[$categoria_atual][$id_filme] = [
            'nome_filme' => $nome_filme,
            'imagem' => $row['img_filme'],
            'receitas' => []
        ];
    }
    
    $categorias[$categoria_atual][$id_filme]['receitas'][] = [
        'id_receita' => $row['id_receita'],
        'nome_receita' => $row['nome_receita'],
        'imagem' => $row['img_receita'],
        'tipo' => $row['tipo']
    ];
}

function obterCorFilme($nomeFilme) {
    $cores = [
        'Branca de Neve' => '#d62828',
        'Frozen' => '#90e0ef',
        'Enrolados' => '#e0aaff',
        'A Princesa e o Sapo' => '#2a9d8f',
        'Alice no País das Maravirhas' => '#0077b6',
        'Ratatouille' => '#f77f00',
        'Lilo & Stitch' => '#0077b6',
        'A Bela Adormecida' => '#ff006e',
        'Up: Altas Aventuras' => '#4ea8de',
        'Mogli' => '#2d6a4f',
        'Turma do Mickey' => '#d00000',
        'Valente' => '#e85d04',
        'Ursinho Pooh' => '#ffb703',
        'Encanto' => '#8338ec',
        'Luca' => '#00b4d8',
        'A Dama e o Vagabundo' => '#780000',
        'Zootopia' => '#fb8500',
        'Viva' => '#f48c06',
        'Aristogatas' => '#ffb5a7',
        'Moana' => '#00f5d4',
        'Pinóquio' => '#9c6644',
        '101 Dálmatas' => '#343a40',
        'Aladdin' => '#3c096c',
        'Os Incríveis' => '#e63946',
        'Mulan' => '#bc4749',
        // Cores personalizadas para os filmes que faltavam:
        'A Era do Gelo' => '#3a86ff',             // Azul glacial
        'A Espada Era a Lei' => '#d4a373',         // Ouro medieval
        'A Nova Onda do Imperador' => '#ff006e',   // Púrpura/Rosa vibrante
        'Carros' => '#d62828',                     // Vermelho Relâmpago McQueen
        'Como Treinar o Seu Dragão' => '#212529',  // Cinza escuro/Banguela
        'Hércules' => '#ffb703',                   // Dourado Olimpo
        'Malévola' => '#3c096c',                   // Roxo vilã
        'O Rei Leão' => '#d97706',                 // Laranja Savana
        'Peter Pan' => '#2d6a4f',                  // Verde Terra do Nunca
        'Pocahontas' => '#9e2a2b',                 // Vermelho terra/outono
        'Tinker Bell' => '#87c747ff',                // Verde Sininho/Pó mágico
        'Cinderela' => '#4cc9f0'                   // Azul vestido de baile
    ];

    foreach ($cores as $chave => $cor) {
        if (stripos($nomeFilme, $chave) !== false) {
            return $cor;
        }
    }
    return '#1f4396'; 
}

$status = isset($_GET['status']) ? $_GET['status'] : "";
$erro_msg = isset($_GET['erro']) ? $_GET['erro'] : (isset($_GET['msg']) ? $_GET['msg'] : "");

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
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filmes | Magical Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background: #f3f1ff; color: #333; overflow-x: hidden; }

    .navbar {
        width: 100%;
        background: #1f4396;
        padding: 14px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.20);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    }
    .logo img { height: 60px; width: auto; margin-top: -10px; }
    .menu { display: flex; align-items: center; gap: 35px; }
    .menu a { color: white; text-decoration: none; font-size: 15px; font-weight: 500; transition: 0.3s; cursor: pointer; display: flex; align-items: center; gap: 8px; }
    .menu a:hover, .menu a.ativo { color: #ffe45c; }

    .header-filmes { text-align: center; padding: 40px 20px 20px; }
    .header-filmes h1 { color: #1f4396; font-size: 38px; }

    /* --- IMAGEM DE TOPO (BANNER SEM CORTE) --- */
    .banner-inicio {
        width: 100%;
        display: block;
    }
    .banner-inicio img {
        width: 100%;
        height: auto;
        display: block;
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

    /* --- FILMES --- */
    .container { width: 100%; max-width: 1250px; margin: 0 auto; padding: 0 30px 50px; }
    .categoria-titulo { width: 100%; margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 3px solid #1f4396; color: #1f4396; font-size: 26px; font-weight: 700; display: flex; align-items: center; gap: 10px; }

    .grid-filmes { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px; margin-bottom: 20px; }
    .box-filme { position: relative; height: 180px; border-radius: 20px; overflow: hidden; cursor: pointer; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15); transition: transform 0.3s ease, box-shadow 0.3s ease; background-color: var(--cor-tema); }
    .box-filme:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3); }

    .img-capa-filme { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; z-index: 1; }
    .box-filme:hover .img-capa-filme { transform: scale(1.1); }

    .box-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 100%; background: linear-gradient(to top, var(--cor-tema) 15%, rgba(0, 0, 0, 0.4) 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; color: white; transition: 0.3s; z-index: 2; }
    .box-filme:hover .box-overlay { background: linear-gradient(to top, var(--cor-tema) 30%, rgba(0, 0, 0, 0.6) 100%); }
    .box-overlay h3 { font-size: 20px; font-weight: 700; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8); margin-bottom: 5px; }
    .box-overlay span { font-size: 12px; font-weight: 600; background: rgba(255, 255, 255, 0.25); padding: 4px 10px; border-radius: 20px; width: fit-content; backdrop-filter: blur(5px); }

    .modal-filme-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); z-index: 999; justify-content: center; align-items: center; padding: 20px; opacity: 0; transition: opacity 0.3s; }
    .modal-filme-overlay.ativo { display: flex; opacity: 1; }
    .modal-filme-content { background: #f3f1ff; width: 100%; max-width: 900px; max-height: 90vh; border-radius: 25px; overflow: hidden; position: relative; display: flex; flex-direction: column; transform: scale(0.9); transition: transform 0.3s; }
    .modal-filme-overlay.ativo .modal-filme-content { transform: scale(1); }

    .modal-header { height: 180px; position: relative; display: flex; align-items: flex-end; padding: 30px; background-color: var(--cor-tema); overflow: hidden; }
    .modal-header img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; opacity: 0.8; }
    .modal-header-overlay { position: absolute; inset: 0; background: linear-gradient(to top, var(--cor-tema) 0%, rgba(0, 0, 0, 0.3) 100%); z-index: 2; }
    .modal-header h2 { position: relative; color: white; font-size: 32px; z-index: 3; text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.8); }

    .modal-filme-content .btn-fechar { position: absolute; top: 20px; right: 20px; background: rgba(0, 0, 0, 0.5); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; cursor: pointer; z-index: 10; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
    .modal-filme-content .btn-fechar:hover { background: white; color: black; transform: scale(1.1); }

    .modal-body { padding: 30px; overflow-y: auto; flex: 1; }
    .lista-receitas { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }

    .card { background: white; color: black; border-radius: 20px; overflow: hidden; text-decoration: none; transition: 0.3s; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); display: flex; flex-direction: column; }
    .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
    .card img { width: 100%; height: 140px; object-fit: cover; }
    .tag { width: fit-content; margin: 15px auto 10px; padding: 5px 12px; border-radius: 20px; color: white; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .doce { background: #ff69b4; } .salgado { background: #4CAF50; } .bebida { background: #00bcd4; }
    .card p { padding: 0 15px 20px; text-align: center; font-weight: 600; font-size: 14px; color: #333; }

    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px); z-index: 999; }
    .card-flutuante { display: none; position: fixed; top: 85px; right: 25px; width: 360px; background: white; border-radius: 28px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3); padding: 30px; z-index: 1000; color: #333; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .card-flutuante .btn-fechar { background: none; border: none; font-size: 26px; cursor: pointer; color: #999; }
    .input-group-modal { position: relative; margin-bottom: 15px; }
    .input-group-modal i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #1f4396; }
    .input-group-modal input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 12px; outline: none; }
    .btn-acao { width: 100%; padding: 14px; background: #1f4396; color: white; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .footer-modal { margin-top: 20px; font-size: 13px; text-align: center; color: #666; }
    .footer-modal a { color: #1f4396; text-decoration: none; font-weight: 600; cursor: pointer; }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="#">Favoritos</a>
            <a href="filmes.php" class="ativo">Filmes</a>

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

    <!-- BANNER DE ENTRADA (MANTÉM PROPORÇÃO TOTAL) -->
    <div class="banner-inicio">
        <img src="img/slider/filme.webp" alt="Banner Filmes">
    </div>

    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>

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

    <div class="header-filmes">
        <h1>Escolha o seu Universo</h1>
        <p>Selecione um filme e descubra todas as receitas mágicas dele!</p>
    </div>

    <!-- SEÇÃO PRINCIPAL FILMES -->
    <div class="container">
        <?php foreach($categorias as $nomeCategoria => $filmesCategoria): ?>
        <?php if(!empty($filmesCategoria)): ?>

        <h2 class="categoria-titulo"><?= htmlspecialchars($nomeCategoria) ?></h2>

        <div class="grid-filmes">
            <?php foreach($filmesCategoria as $id => $filme): 
                $corTema = obterCorFilme($filme['nome_filme']);
            ?>
            <div class="box-filme" style="--cor-tema: <?= $corTema ?>;" onclick="abrirModalFilme('modal-filme-<?= $id ?>')">
                <img src="<?= buscarImagem($filme['imagem']) ?>" alt="<?= htmlspecialchars($filme['nome_filme']) ?>" class="img-capa-filme" onerror="this.style.opacity='0';">
                <div class="box-overlay">
                    <h3><?= htmlspecialchars($filme['nome_filme']) ?></h3>
                    <span><i class="fa fa-utensils"></i> <?= count($filme['receitas']) ?> Receita(s)</span>
                </div>
            </div>

            <div id="modal-filme-<?= $id ?>" class="modal-filme-overlay">
                <div class="modal-filme-content">
                    <div class="modal-header" style="--cor-tema: <?= $corTema ?>;">
                        <img src="<?= buscarImagem($filme['imagem']) ?>" alt="<?= htmlspecialchars($filme['nome_filme']) ?>" onerror="this.style.opacity='0';">
                        <div class="modal-header-overlay"></div>
                        <button onclick="fecharModalFilme('modal-filme-<?= $id ?>', event)" class="btn-fechar">&times;</button>
                        <h2><?= htmlspecialchars($filme['nome_filme']) ?></h2>
                    </div>

                    <div class="modal-body">
                        <div class="lista-receitas">
                            <?php foreach($filme['receitas'] as $r): ?>
                            <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card">
                                <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='<?= buscarImagem('logo_menu.png') ?>'">
                                <div class="tag <?= strtolower(htmlspecialchars($r['tipo'])) ?>">
                                    <?= htmlspecialchars($r['tipo']) ?>
                                </div>
                                <p><?= htmlspecialchars($r['nome_receita']) ?></p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script>
    function abrirModalFilme(idModal) {
        document.getElementById(idModal).classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }

    function fecharModalFilme(idModal, event) {
        if (event) event.stopPropagation();
        document.getElementById(idModal).classList.remove('ativo');
        document.body.style.overflow = 'auto';
    }

    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        document.getElementById('modalCadastro').style.display = 'none';
        document.getElementById('modalLogin').style.display = 'none';
    }

    function alternarModais(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
        if (tipo === 'cadastro') document.getElementById('modalCadastro').style.display = 'block';
        if (tipo === 'login') document.getElementById('modalLogin').style.display = 'block';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-filme-overlay')) {
            event.target.classList.remove('ativo');
            document.body.style.overflow = 'auto';
        }
    }
    </script>

</body>
</html>