<?php
session_start();

// Configurações de conexão com a base de dados
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

// =========================================================================
// 1. PROCESSAMENTO DO LOGIN (COM REDIRECIONAMENTO AUTOMÁTICO PARA ADMIN)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['senha']) && !isset($_POST['acao'])) {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmtUser = $pdo->prepare("SELECT * FROM usuario WHERE email = :email");
    $stmtUser->execute([':email' => $email]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($usuario && ($senha === $usuario['senha'] || password_verify($senha, $usuario['senha']))) {
        $ehAdmin = (($usuario['is_admin'] ?? 0) == 1 || strtolower(trim($usuario['email'])) === 'admin@magicalbites.com') ? 1 : 0;

        $_SESSION['usuario'] = [
            'id'       => $usuario['id_usuario'] ?? $usuario['id'] ?? null,
            'nome'     => $usuario['nome_usuario'] ?? $usuario['nome'] ?? 'Usuário',
            'email'    => $usuario['email'],
            'foto'     => !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'logo_menu.png',
            'is_admin' => $ehAdmin
        ];

        if ($ehAdmin === 1) {
            header("Location: admin.php");
        } else {
            header("Location: filmes.php");
        }
        exit;
    } else {
        header("Location: filmes.php?status=erro&msg=" . urlencode("Senha ou E-mail incorreto"));
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

    header("Location: filmes.php?status=sucesso&msg=" . urlencode("Perfil atualizado com sucesso!"));
    exit;
}

// =========================================================================
// 3. FUNÇÕES INTELIGENTES DE IMAGEM E CONFIGURAÇÕES
// =========================================================================
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;
    if (file_exists("img/" . $nomeImagem)) return "img/" . $nomeImagem;

    $pastas = [
        'fundos', 'fundo', 'titulos', 'titulo', 'banners', 'banner', 'index', 'mouse', 
        'cardsfilme', 'slider', 'filme', 'pratos', 'logo', 'perfis', 'avatares', 'elementos',
        'img/fundos', 'img/fundo', 'img/titulos', 'img/titulo', 'img/banners', 'img/banner', 
        'img/index', 'img/mouse', 'img/cardsfilme', 'img/slider', 'img/filme', 'img/pratos', 
        'img/logo', 'img/perfis', 'img/avatares', 'img/elementos'
    ];
    $extensoes = ['', '.webp', '.png', '.jpg', '.jpeg', '.jfif'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminhoCompleto = "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoCompleto)) return $caminhoCompleto;

            $caminhoRaiz = $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoRaiz)) return $caminhoRaiz;
        }
    }

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

// Mapeamento automático de categorias
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

// COR PADRONIZADA PARA TODOS OS FILMES (VERMELHO VINHO - DAMA E O VAGABUNDO)
function obterCorFilme($nomeFilme) {
    return '#780000';
}

// Consulta de Filmes e Receitas no BD
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
    LEFT JOIN receitas r ON f.id_filme = r.id_filme
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
            'id_filme'   => $id_filme,
            'nome_filme' => $nome_filme,
            'imagem'     => $row['img_filme'],
            'receitas'   => []
        ];
    }
    
    if (!empty($row['id_receita'])) {
        $categorias[$categoria_atual][$id_filme]['receitas'][] = [
            'id_receita'   => $row['id_receita'],
            'nome_receita' => $row['nome_receita'],
            'imagem'       => $row['img_receita'],
            'tipo'         => $row['tipo']
        ];
    }
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
    <title>Filmes | Magical Bites</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

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

        html { scroll-behavior: smooth; }
        body { background: #0e2646; color: #ffffff; overflow-x: hidden; }

        /* --- NAVBAR --- */
        .navbar {
            width: 100%;
            height: 70px;
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

        .logo img { height: 50px; width: auto; display: block; }
        .menu { display: flex; align-items: center; gap: 30px; }
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
        .menu a:hover, .menu a.ativo { color: #fff6a9ff; }
        .nav-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9ff;
        }

        /* --- 1. BANNER PRINCIPAL DO TOPO --- */
        .banner-inicio {
            width: 100%;
            height: 610px;
            position: relative;
            overflow: hidden;
            background: #0b1329;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-inicio img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* --- 2. SEÇÃO DE BUSCA E CATEGORIAS (BLOCO CLARO) --- */
        .secao-busca-categorias {
            background-color: #fffdf5ff;
            padding: 50px 0 70px;
            position: relative;
            box-shadow: inset 0 15px 20px -10px rgba(0,0,0,0.12), inset 0 -15px 20px -10px rgba(0,0,0,0.12);
        }

        .container-secao {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 25px;
            position: relative;
            z-index: 2;
        }

        /* BARRA DE PESQUISA */
        .search-home-box {
            position: relative;
            width: 100%;
            max-width: 650px;
            margin: 0 auto 40px;
        }

        .search-home-box input {
            width: 100%;
            padding: 13px 25px 13px 52px;
            border-radius: 50px;
            border: 1px solid rgba(168, 26, 26, 0.08);
            background: #a62428;
            font-size: 15px;
            font-weight: 500;
            color: #ffffffff;
            outline: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
        }

        .search-home-box input::placeholder { color: #fffde6ff; }
        .search-home-box i { position: absolute; left: 22px; top: 50%; transform: translateY(-50%); color: #fffde6ff; font-size: 17px; }

        /* GRID DOS 3 CARDS DE CATEGORIA */
        .grid-3-categorias {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card-categoria-item {
            position: relative;
            height: 350px;
            border-radius: 30px;
            overflow: hidden;
            text-decoration: none;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 145px;
            isolation: isolate;
            transform: translateZ(0);
        }

        /* ESFUMADO PRETO NOS CARDS DE CATEGORIA */
        .card-categoria-item::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.25) 50%, rgba(0, 0, 0, 0.45) 100%);
            z-index: 2;
            transition: background 0.35s ease;
        }

        .card-categoria-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.38);
        }

        .card-categoria-item:hover::after {
            background: linear-gradient(to top, rgba(0, 0, 0, 0.92) 0%, rgba(0, 0, 0, 0.35) 50%, rgba(0, 0, 0, 0.55) 100%);
        }

        .card-categoria-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            transform: scale(1.02);
            transition: transform 0.5s ease;
        }

        .card-categoria-item:hover .card-categoria-bg { 
            transform: scale(1.08); 
        }

        .card-cat-titulo {
            position: relative;
            z-index: 3;
            font-size: 16px;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.25;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
            padding: 0 15px;
            letter-spacing: 0.5px;
        }

        /* --- 3. BLOCOS DE CATEGORIAS INDIVIDUALIZADOS (AMPLIADO LATERALMENTE) --- */
        .secao-bloco-categoria {
            padding: 65px 0 80px;
            position: relative;
            width: 100%;
            box-shadow: inset 0 10px 18px -8px rgba(0,0,0,0.3), inset 0 -10px 18px -8px rgba(0,0,0,0.3);
        }

        .container-filmes-grid {
            width: 100%;
            max-width: 1550px; /* Alargado para ocupar mais o ecrã */
            margin: 0 auto;
            padding: 0 25px; /* Margens laterais mais curtas */
            position: relative;
            z-index: 2;
        }

        /* TÍTULO DA CATEGORIA */
        .categoria-titulo {
            width: 100%;
            margin: 0 0 32px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ffffff;
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* GRELHA DE 3 EM 3 AMPLA */
        .grid-filmes {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Mantém 3 por linha */
            gap: 32px 36px;
        }

        .box-filme {
            position: relative;
            height: 295px; /* Cards maiores em altura (estava 240px) */
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.38);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            background-color: var(--cor-tema);
        }

        .box-filme:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.55);
        }

        .img-capa-filme {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            z-index: 1;
        }

        .box-filme:hover .img-capa-filme {
            transform: scale(1.06);
        }

        .box-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0, 0, 0, 0.05) 60%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 22px 26px;
            color: #ffffff;
            transition: 0.3s;
            z-index: 2;
            opacity: 0.9;
        }

        .box-filme:hover .box-overlay {
            opacity: 1;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0, 0, 0, 0.15) 70%);
        }

        .box-overlay h3 {
            font-size: 20px;
            font-weight: 800;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.9);
            margin-bottom: 8px;
            line-height: 1.25;
            color: #ffffff;
        }

        .box-overlay span {
            font-size: 13px;
            font-weight: 700;
            background: rgba(0, 0, 0, 0.45);
            color: #ffffff;
            padding: 5px 14px;
            border-radius: 20px;
            width: fit-content;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        /* BOTÃO FLUTUANTE ESTÁVEL */
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

        /* --- MODAIS FLUTUANTES DA CONTA --- */
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
            width: 100%; padding: 13px; background: #fff8bd; color: #a62428; border: none; border-radius: 50px; font-weight: 800; font-size: 16px; transition: 0.3s ease; margin-top: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-vermelho .footer-modal { margin-top: 18px; font-size: 13px; text-align: center; color: rgba(255, 255, 255, 0.9); font-weight: 500; }
        .card-vermelho .footer-modal a { color: #ffffff; text-decoration: underline; font-weight: 700; }

        .btn-fechar-absoluto { position: absolute; top: 18px; right: 22px; background: none; border: none; font-size: 24px; color: rgba(255,255,255,0.8); transition: 0.2s; }
        .btn-fechar-absoluto:hover { color: #ffffff; transform: scale(1.1); }

        .perfil-avatar-header { text-align: center; margin-bottom: 12px; }
        .perfil-avatar-header img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; max-height: 160px; overflow-y: auto; padding: 6px; margin: 12px 0; }
        .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; overflow: hidden; transition: 0.2s; }
        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-item:hover, .avatar-item.selected { border-color: #fff8bd; transform: scale(1.12); }

        /* --- MODAL DE RECEITAS DO FILME --- */
        .modal-filme-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(5px); z-index: 999; justify-content: center; align-items: center; padding: 20px; opacity: 0; transition: opacity 0.3s; }
        .modal-filme-overlay.ativo { display: flex; opacity: 1; }
        
        .modal-filme-content { 
            background-color: var(--cor-tema); 
            width: 100%; 
            max-width: 850px; 
            max-height: 90vh; 
            border-radius: 28px; 
            overflow: hidden; 
            position: relative; 
            display: flex; 
            flex-direction: column; 
            color: #ffffff; 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.55);
            border: 2px solid rgba(255, 255, 255, 0.25);
        }

        .modal-header { height: 180px; position: relative; display: flex; align-items: flex-end; padding: 30px; background-color: var(--cor-tema); overflow: hidden; }
        .modal-header img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.85; }
        .modal-header-overlay { position: absolute; inset: 0; background: linear-gradient(to top, var(--cor-tema) 10%, rgba(0, 0, 0, 0.15) 100%); }
        .modal-header h2 { position: relative; color: #ffffff; font-size: 32px; font-weight: 800; z-index: 3; text-shadow: 0 2px 10px rgba(0,0,0,0.7); }
        .modal-filme-content .btn-fechar { position: absolute; top: 20px; right: 20px; background: rgba(0, 0, 0, 0.5); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; z-index: 10; transition: 0.3s; }
        .modal-filme-content .btn-fechar:hover { background: rgba(0, 0, 0, 0.8); transform: scale(1.1); }

        .modal-body { 
            padding: 30px; 
            overflow-y: auto; 
            flex: 1; 
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.05), rgba(0, 0, 0, 0.25));
        }

        .lista-receitas { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }

        .card { 
            background: rgba(255, 255, 255, 0.18); 
            color: #ffffff; 
            border-radius: 20px; 
            overflow: hidden; 
            text-decoration: none; 
            transition: transform 0.3s ease, background 0.3s ease, box-shadow 0.3s ease; 
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25); 
            display: flex; 
            flex-direction: column; 
            border: 1px solid rgba(255, 255, 255, 0.28);
            backdrop-filter: blur(6px);
        }

        .card:hover {
            transform: translateY(-6px);
            background: rgba(255, 255, 255, 0.28);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
        }

        .card img { width: 100%; height: 140px; object-fit: cover; }
        .tag { width: fit-content; margin: 15px auto 10px; padding: 5px 14px; border-radius: 20px; color: white; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .doce { background: #ff69b4; } .salgado { background: #2e7d32; } .bebida { background: #00bcd4; }
        .card p { padding: 0 15px 20px; text-align: center; font-weight: 700; font-size: 14px; color: #ffffff; text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6); }

        @media (max-width: 1200px) {
            .container-filmes-grid { padding: 0 20px; }
            .grid-filmes { gap: 24px; }
            .box-filme { height: 260px; }
        }
        @media (max-width: 900px) {
            .grid-filmes { grid-template-columns: repeat(2, 1fr); }
            .box-filme { height: 230px; }
        }
        @media (max-width: 768px) {
            .grid-3-categorias { grid-template-columns: repeat(2, 1fr); }
            .banner-inicio { height: 450px; }
            .btn-flutuante-voltar {
                width: 46px;
                height: 46px;
                font-size: 18px;
                bottom: 20px;
                right: 20px;
            }
        }
        @media (max-width: 580px) {
            .grid-filmes, .grid-3-categorias { grid-template-columns: repeat(1, 1fr); }
            .box-filme { height: 200px; }
            .menu { gap: 12px; }
            .menu a { font-size: 13px; }
            .banner-inicio { height: 320px; }
        }
    </style>
</head>

<body>

    <!-- ELEMENTO DO CURSOR DO MICKEY -->
    <img id="mickeyCursor" src="<?= buscarImagemCursor('aberto') ?>" alt="Cursor Mickey">

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="favoritos.php">Favoritos</a>
            <a href="filmes.php" class="ativo">Filmes</a>

            <?php if (isset($_SESSION['usuario']) && (($_SESSION['usuario']['is_admin'] ?? 0) == 1 || strtolower(trim($_SESSION['usuario']['email'] ?? '')) === 'admin@magicalbites.com')): ?>
                <a href="admin.php" style="color: #fff6a9;"><i class="fa-solid fa-wand-magic-sparkles"></i> Admin</a>
            <?php endif; ?>

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

    <!-- 1. BANNER PRINCIPAL DO TOPO -->
    <div class="banner-inicio">
        <img src="<?= buscarImagem('filme.png') ?>" alt="Banner Filmes" onerror="this.src='img/slider/bg-destaque.webp'">
    </div>

    <!-- 2. SEÇÃO DE BUSCA E CATEGORIAS DA DISNEY (BLOCO CLARO) -->
    <section class="secao-busca-categorias" id="secaoCategorias">
        <div class="container-secao">
            
            <!-- BARRA DE PESQUISA -->
            <div class="search-home-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="inputPesquisaFilmes" placeholder="Procurar por filmes..." onkeyup="pesquisarFilmes()">
            </div>

            <!-- 3 CARDS DE CATEGORIA -->
            <div class="grid-3-categorias">
                <div class="card-categoria-item" onclick="filtrarCategoria('Aventuras & Fantasia')">
                    <img src="<?= buscarImagem('mickeyeamigos.webp') ?>" class="card-categoria-bg" alt="Aventuras & Fantasia" onerror="this.src='img/cardsfilme/mickeyeamigos.webp';">
                    <h3 class="card-cat-titulo">AVENTURAS &<br>FANTASIA</h3>
                </div>

                <div class="card-categoria-item" onclick="filtrarCategoria('Princesas & Reinos')">
                    <img src="<?= buscarImagem('allprincesas.webp') ?>" class="card-categoria-bg" alt="Princesas & Reinos" onerror="this.src='img/cardsfilme/allprincesas.webp';">
                    <h3 class="card-cat-titulo">PRINCESAS & REINOS</h3>
                </div>

                <div class="card-categoria-item" onclick="filtrarCategoria('Clássicos & Aventuras')">
                    <img src="<?= buscarImagem('kingleao.webp') ?>" class="card-categoria-bg" alt="Clássicos & Aventuras" onerror="this.src='img/cardsfilme/kingleao.webp';">
                    <h3 class="card-cat-titulo">CLÁSSICOS &<br>AVENTURAS</h3>
                </div>
            </div>

        </div>
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
        <form action="filmes.php" method="POST">
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
        <form action="filmes.php" method="POST" enctype="multipart/form-data">
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

    <!-- 3. BLOCOS DE CATEGORIA SEPARADOS EM SECÇÕES COM OS FUNDOS CUSTOMIZADOS -->
    <div id="secaoFilmes">
        <?php 
        $estilosFundoBlocos = [
            "background-color: #283f61ff;", 
            "background-image: url('" . buscarImagem('fundo2_index') . "'); background-repeat: no-repeat; background-position: center center; background-size: cover; background-attachment: fixed;", 
            "background-color: #0e2646;"
        ];
        $indexBloco = 0;
        ?>

        <?php foreach($categorias as $nomeCategoria => $filmesCategoria): ?>
        <?php if(!empty($filmesCategoria)): 
            $estiloFundoAtual = $estilosFundoBlocos[$indexBloco % count($estilosFundoBlocos)];
            $indexBloco++;
        ?>

        <section class="secao-bloco-categoria" style="<?= $estiloFundoAtual ?>">
            <div class="container-filmes-grid">
                <div class="bloco-categoria" data-nome-cat="<?= htmlspecialchars($nomeCategoria) ?>">
                    <h2 class="categoria-titulo"><?= htmlspecialchars($nomeCategoria) ?></h2>

                    <div class="grid-filmes">
                        <?php foreach($filmesCategoria as $id => $filme): 
                            $corTema = obterCorFilme($filme['nome_filme']);
                            $qtdReceitas = count($filme['receitas']);
                        ?>
                        <div class="box-filme" style="--cor-tema: <?= $corTema ?>;" data-nome="<?= strtolower(htmlspecialchars($filme['nome_filme'])) ?>" data-categoria="<?= htmlspecialchars($nomeCategoria) ?>" onclick="abrirModalFilme('modal-filme-<?= $id ?>')">
                            <img src="<?= buscarImagem($filme['imagem']) ?>" alt="<?= htmlspecialchars($filme['nome_filme']) ?>" class="img-capa-filme" onerror="this.style.opacity='0';">
                            <div class="box-overlay">
                                <h3><?= htmlspecialchars($filme['nome_filme']) ?></h3>
                                <span><i class="fa fa-utensils"></i> <?= $qtdReceitas ?> Receita(s)</span>
                            </div>
                        </div>

                        <!-- MODAL DE RECEITAS DO FILME COM A COR DO TEMA NO INTERIOR -->
                        <div id="modal-filme-<?= $id ?>" class="modal-filme-overlay">
                            <div class="modal-filme-content" style="--cor-tema: <?= $corTema ?>;">
                                <div class="modal-header">
                                    <img src="<?= buscarImagem($filme['imagem']) ?>" alt="<?= htmlspecialchars($filme['nome_filme']) ?>" onerror="this.style.opacity='0';">
                                    <div class="modal-header-overlay"></div>
                                    <button onclick="fecharModalFilme('modal-filme-<?= $id ?>', event)" class="btn-fechar">&times;</button>
                                    <h2><?= htmlspecialchars($filme['nome_filme']) ?></h2>
                                </div>

                                <div class="modal-body">
                                    <div class="lista-receitas">
                                        <?php if (!empty($filme['receitas'])): ?>
                                            <?php foreach($filme['receitas'] as $r): ?>
                                            <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card">
                                                <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='<?= buscarImagem('logo_menu.png') ?>'">
                                                <div class="tag <?= strtolower(htmlspecialchars($r['tipo'])) ?>">
                                                    <?= htmlspecialchars($r['tipo']) ?>
                                                </div>
                                                <p><?= htmlspecialchars($r['nome_receita']) ?></p>
                                            </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p style="text-align:center; width:100%; color:#ffffff; font-weight: 500;">Ainda não há receitas cadastradas para este filme.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- BOTÃO FLUTUANTE QUE SÓ APARECE NA PARTE DOS FILMES -->
    <a href="javascript:void(0)" onclick="voltarParaParte2(event)" class="btn-flutuante-voltar" id="btnVoltarParte2" title="Voltar para a Busca e Categorias">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

    <!-- JAVASCRIPT DOS FILTROS, MODAIS, CURSOR DO MICKEY E SCROLL -->
    <script>
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

        const seletorInterativo = 'a, button, input, select, textarea, label, [onclick], .avatar-item, .box-filme, .card-categoria-item, .card, .btn-flutuante-voltar, .btn-fechar';

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

    // MOSTRAR A SETA SÓ NA PARTE DOS FILMES
    window.addEventListener('scroll', function() {
        let btn = document.getElementById('btnVoltarParte2');
        let secaoFilmes = document.getElementById('secaoFilmes');
        if (secaoFilmes && btn) {
            let topoSecaoFilmes = secaoFilmes.offsetTop;
            if (window.scrollY >= topoSecaoFilmes - 250) {
                btn.classList.add('visivel');
            } else {
                btn.classList.remove('visivel');
            }
        }
    });

    // ROLAGEM EXATA PARA ENQUADRAR A SEÇÃO DE BUSCA
    function voltarParaParte2(event) {
        if (event) event.preventDefault();
        let secaoCategorias = document.getElementById('secaoCategorias');
        if (secaoCategorias) {
            let posicaoEnquadrada = secaoCategorias.offsetTop - 80;
            window.scrollTo({
                top: posicaoEnquadrada,
                behavior: 'smooth'
            });
        }
    }

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

    // PESQUISA EM TEMPO REAL
    function pesquisarFilmes() {
        let termo = document.getElementById('inputPesquisaFilmes').value.toLowerCase();
        let cards = document.querySelectorAll('.box-filme');
        let blocos = document.querySelectorAll('.bloco-categoria');

        cards.forEach(card => {
            let nome = card.getAttribute('data-nome') || '';
            let cat = card.getAttribute('data-categoria') || '';
            if (nome.includes(termo) || cat.toLowerCase().includes(termo)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });

        blocos.forEach(bloco => {
            let cardsVisiveis = Array.from(bloco.querySelectorAll('.box-filme')).filter(c => c.style.display !== 'none');
            let secaoPai = bloco.closest('.secao-bloco-categoria') || bloco;
            if (cardsVisiveis.length > 0) {
                secaoPai.style.display = 'block';
            } else {
                secaoPai.style.display = 'none';
            }
        });
    }

    // FILTRAR POR CATEGORIA AO CLICAR EM UM DOS 3 CARDS
    function filtrarCategoria(categoria) {
        let cards = document.querySelectorAll('.box-filme');
        let blocos = document.querySelectorAll('.bloco-categoria');

        cards.forEach(card => {
            let cat = card.getAttribute('data-categoria');
            if (categoria === 'todos' || cat === categoria) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });

        blocos.forEach(bloco => {
            let catBloco = bloco.getAttribute('data-nome-cat');
            let secaoPai = bloco.closest('.secao-bloco-categoria') || bloco;
            if (categoria === 'todos' || catBloco === categoria) {
                secaoPai.style.display = 'block';
            } else {
                secaoPai.style.display = 'none';
            }
        });

        document.getElementById('inputPesquisaFilmes').value = '';
        let grid = document.getElementById('secaoFilmes');
        if (grid) grid.scrollIntoView({ behavior: 'smooth' });
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