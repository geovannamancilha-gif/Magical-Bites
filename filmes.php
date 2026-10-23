<?php
session_start();

// Conexão com a base de dados
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
    if (empty($nomeImagem)) {
        return "img/logo/logo_menu.png";
    }
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = ['slider', 'pratos', 'filme', 'logo', 'perfis', 'avatares', 'img/perfis', 'img/avatares', 'fundo'];
    $extensoes = ['', '.webp', '.png', '.jpg', '.jpeg'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminhoCompleto = "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoCompleto)) {
                return $caminhoCompleto;
            }
            $caminhoRaiz = $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoRaiz)) return $caminhoRaiz;
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

// Consulta de Filmes e Receitas Relacionadas
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
        'A Era do Gelo' => '#3a86ff',
        'A Espada Era a Lei' => '#d4a373',
        'A Nova Onda do Imperador' => '#ff006e',
        'Carros' => '#d62828',
        'Como Treinar o Seu Dragão' => '#212529',
        'Hércules' => '#ffb703',
        'Malévola' => '#3c096c',
        'O Rei Leão' => '#d97706',
        'Peter Pan' => '#2d6a4f',
        'Pocahontas' => '#9e2a2b',
        'Tinker Bell' => '#87c747ff',
        'Cinderela' => '#4cc9f0'
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
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background: #f3f1ff; color: #333; overflow-x: hidden; }

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

    .header-filmes { text-align: center; padding: 40px 20px 20px; }
    .header-filmes h1 { color: #1f4396; font-size: 38px; font-weight: 800; }
    .header-filmes p { color: #556987; font-size: 15px; margin-top: 6px; }

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

    /* =========================================================================
       MODAIS COM TEMA DISNEY (CARMIM & CREME)
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

    .perfil-avatar-header { text-align: center; margin-bottom: 12px; }
    .perfil-avatar-header img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

    .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; max-height: 160px; overflow-y: auto; padding: 6px; margin: 12px 0; }
    .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; overflow: hidden; transition: 0.2s; }
    .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-item:hover, .avatar-item.selected { border-color: #fff8bd; transform: scale(1.12); }

    /* --- GRID DE FILMES --- */
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

    /* MODAL DE RECEITAS DO FILME */
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
    </style>
</head>

<body>

    <!-- NAVBAR PADRONIZADA -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="favoritos.php">Favoritos</a>
            <a href="filmes.php" class="ativo">Filmes</a>

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

    <!-- BANNER DE ENTRADA -->
    <div class="banner-inicio">
        <img src="img/slider/filme.webp" alt="Banner Filmes">
    </div>

    <!-- OVERLAY DOS MODAIS -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>

    <!-- 1. MODAL CADASTRO (DESIGN DISNEY) -->
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

    <!-- 2. MODAL LOGIN (DESIGN DISNEY) -->
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

    <!-- 3. MODAL PERFIL (DESIGN DISNEY) -->
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

    <!-- 4. MODAL STATUS / AVISO -->
    <?php if ($status != ""): ?>
    <div id="modalStatus" class="card-flutuante card-amarelo" style="display: block; z-index: 1001;">
        <button class="btn-fechar-absoluto" onclick="fecharStatus()">&times;</button>

        <div class="castelo-header">
            <i class="fa-solid fa-chess-castle"></i>
        </div>

        <?php if ($status == 'sucesso'): ?>
            <h2 class="titulo-cursivo">Bem - vindo</h2>
            <p class="subtitulo-aviso">
                <?= htmlspecialchars($erro_msg ?: "Sua conta está registrada, agora é só aproveitar!") ?>
            </p>
            <button class="btn-acao-vermelho" onclick="fecharStatus();">Aproveitar agora</button>

        <?php elseif ($status == 'erro'): ?>
            <h2 class="titulo-aviso">OOPS!</h2>
            <p class="subtitulo-aviso">
                <?= htmlspecialchars($erro_msg ?: "Senha ou E-mail incorreto") ?>
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

    <!-- JAVASCRIPT DOS MODAIS -->
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
        if(document.getElementById('modalCadastro')) document.getElementById('modalCadastro').style.display = 'none';
        if(document.getElementById('modalLogin')) document.getElementById('modalLogin').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
        if(document.getElementById('modalStatus')) document.getElementById('modalStatus').style.display = 'none';
    }

    function fecharStatus() {
        fecharTodosModais();
        window.history.replaceState({}, document.title, "filmes.php");
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

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-filme-overlay')) {
            event.target.classList.remove('ativo');
            document.body.style.overflow = 'auto';
        }
    }
    </script>

</body>
</html>