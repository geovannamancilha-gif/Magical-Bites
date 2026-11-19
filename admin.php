<?php
session_start();

// Configurações de conexão com a base de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root';
$pass = "Home@spSENAI2025!";
//$pass = "Home@spSENAI2025!";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

// =========================================================================
// 1. CONTROLE DE ACESSO (APENAS ADMINISTRADOR)
// =========================================================================
$isAdmin = false;
if (isset($_SESSION['usuario'])) {
    $emailLogado = strtolower(trim($_SESSION['usuario']['email'] ?? ''));
    $flagAdmin = (int)($_SESSION['usuario']['is_admin'] ?? 0);
    if ($flagAdmin === 1 || $emailLogado === 'admin@magicalbites.com') {
        $isAdmin = true;
    }
}

if (!$isAdmin) {
    header("Location: index.php?status=erro&msg=" . urlencode("Acesso restrito! Faça login como administrador."));
    exit;
}

// =========================================================================
// 2. FUNÇÕES AUXILIARES DE IMAGEM E CURSOR
// =========================================================================
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;
    if (file_exists("img/" . $nomeImagem)) return "img/" . $nomeImagem;

    $pastas = [
        'pratos', 'filme', 'logo', 'fundos', 'fundo', 'titulos', 'titulo',
        'banners', 'banner', 'index', 'mouse', 'slider', 'perfis', 'avatares', 'elementos'
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

function processarUploadImagemReceita($campoFile, $imagemAtual = '') {
    if (isset($_FILES[$campoFile]) && $_FILES[$campoFile]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES[$campoFile]['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'jfif'];

        if (in_array($ext, $extensoesPermitidas)) {
            $pastaDestino = "img/pratos/";
            if (!is_dir($pastaDestino)) {
                mkdir($pastaDestino, 0777, true);
            }

            $novoNome = "receita_" . time() . "_" . bin2hex(random_bytes(3)) . "." . $ext;
            if (move_uploaded_file($_FILES[$campoFile]['tmp_name'], $pastaDestino . $novoNome)) {
                return $novoNome;
            }
        }
    }
    return $imagemAtual;
}

// =========================================================================
// 3. PROCESSAMENTO DAS AÇÕES CRUD (ADICIONAR, EDITAR, REMOVER)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_admin'])) {
    $acao = $_POST['acao_admin'];

    // --- ADICIONAR RECEITA ---
    if ($acao === 'adicionar') {
        $nome_receita  = trim($_POST['nome_receita'] ?? '');
        $tipo          = strtoupper(trim($_POST['tipo'] ?? 'DOCE'));
        $id_filme      = !empty($_POST['id_filme']) ? (int)$_POST['id_filme'] : null;
        $descricao     = trim($_POST['descricao'] ?? '');
        $avaliacao     = !empty($_POST['avaliacao']) ? (float)$_POST['avaliacao'] : 5.0;
        $tempo_preparo = trim($_POST['tempo_preparo'] ?? '30min');
        $dificuldade   = trim($_POST['dificuldade'] ?? 'Fácil');
        $ingredientes  = trim($_POST['ingredientes'] ?? '');
        $modo_preparo  = trim($_POST['modo_preparo'] ?? '');
        $imagemManual  = trim($_POST['imagem_nome'] ?? '');

        $imagemFinal = processarUploadImagemReceita('imagem_upload', $imagemManual);

        if (empty($nome_receita) || empty($tipo)) {
            header("Location: admin.php?status=erro&msg=" . urlencode("Preencha o nome e o tipo da receita!"));
            exit;
        }

        try {
            $sqlInsert = "INSERT INTO receitas 
                (nome_receita, tipo, id_filme, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) 
                VALUES 
                (:nome, :tipo, :id_filme, :descricao, :imagem, :avaliacao, :tempo, :dificuldade, :ingredientes, :modo_preparo)";
            
            $stmt = $pdo->prepare($sqlInsert);
            $stmt->execute([
                ':nome'         => $nome_receita,
                ':tipo'         => $tipo,
                ':id_filme'     => $id_filme,
                ':descricao'    => $descricao,
                ':imagem'       => $imagemFinal,
                ':avaliacao'    => $avaliacao,
                ':tempo'        => $tempo_preparo,
                ':dificuldade'  => $dificuldade,
                ':ingredientes' => $ingredientes,
                ':modo_preparo' => $modo_preparo
            ]);

            header("Location: admin.php?status=sucesso&msg=" . urlencode("Receita mágica adicionada com sucesso!"));
            exit;
        } catch (PDOException $e) {
            header("Location: admin.php?status=erro&msg=" . urlencode("Erro ao adicionar receita: " . $e->getMessage()));
            exit;
        }
    }

    // --- EDITAR RECEITA ---
    if ($acao === 'editar') {
        $id_receita    = (int)($_POST['id_receita'] ?? 0);
        $nome_receita  = trim($_POST['nome_receita'] ?? '');
        $tipo          = strtoupper(trim($_POST['tipo'] ?? 'DOCE'));
        $id_filme      = !empty($_POST['id_filme']) ? (int)$_POST['id_filme'] : null;
        $descricao     = trim($_POST['descricao'] ?? '');
        $avaliacao     = !empty($_POST['avaliacao']) ? (float)$_POST['avaliacao'] : 5.0;
        $tempo_preparo = trim($_POST['tempo_preparo'] ?? '30min');
        $dificuldade   = trim($_POST['dificuldade'] ?? 'Fácil');
        $ingredientes  = trim($_POST['ingredientes'] ?? '');
        $modo_preparo  = trim($_POST['modo_preparo'] ?? '');
        $imagemAtual   = trim($_POST['imagem_atual'] ?? '');

        $imagemFinal = processarUploadImagemReceita('imagem_upload', $imagemAtual);

        if ($id_receita <= 0 || empty($nome_receita)) {
            header("Location: admin.php?status=erro&msg=" . urlencode("Dados inválidos para edição."));
            exit;
        }

        try {
            $sqlUpdate = "UPDATE receitas SET 
                nome_receita = :nome,
                tipo = :tipo,
                id_filme = :id_filme,
                descricao = :descricao,
                imagem = :imagem,
                avaliacao = :avaliacao,
                tempo_preparo = :tempo,
                dificuldade = :dificuldade,
                ingredientes = :ingredientes,
                modo_preparo = :modo_preparo
                WHERE id_receita = :id";

            $stmt = $pdo->prepare($sqlUpdate);
            $stmt->execute([
                ':nome'         => $nome_receita,
                ':tipo'         => $tipo,
                ':id_filme'     => $id_filme,
                ':descricao'    => $descricao,
                ':imagem'       => $imagemFinal,
                ':avaliacao'    => $avaliacao,
                ':tempo'        => $tempo_preparo,
                ':dificuldade'  => $dificuldade,
                ':ingredientes' => $ingredientes,
                ':modo_preparo' => $modo_preparo,
                ':id'           => $id_receita
            ]);

            header("Location: admin.php?status=sucesso&msg=" . urlencode("Receita atualizada com sucesso!"));
            exit;
        } catch (PDOException $e) {
            header("Location: admin.php?status=erro&msg=" . urlencode("Erro ao atualizar receita: " . $e->getMessage()));
            exit;
        }
    }

    // --- REMOVER RECEITA ---
    if ($acao === 'remover') {
        $id_receita = (int)($_POST['id_receita'] ?? 0);

        if ($id_receita > 0) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM receitas WHERE id_receita = :id");
                $stmtDel->execute([':id' => $id_receita]);

                header("Location: admin.php?status=sucesso&msg=" . urlencode("Receita removida do livro mágico!"));
                exit;
            } catch (PDOException $e) {
                header("Location: admin.php?status=erro&msg=" . urlencode("Erro ao remover receita: " . $e->getMessage()));
                exit;
            }
        }
    }
}

// =========================================================================
// 4. CONSULTAS PARA LISTAGEM E ESTATÍSTICAS
// =========================================================================
$stmtReceitas = $pdo->query("
    SELECT r.*, f.nome_filme 
    FROM receitas r 
    LEFT JOIN filme f ON r.id_filme = f.id_filme 
    ORDER BY r.id_receita DESC
");
$todasReceitas = $stmtReceitas->fetchAll(PDO::FETCH_ASSOC);

$stmtFilmes = $pdo->query("SELECT id_filme, nome_filme FROM filme ORDER BY nome_filme ASC");
$todosFilmes = $stmtFilmes->fetchAll(PDO::FETCH_ASSOC);

// Contadores para os cards de resumo
$totalGeral    = count($todasReceitas);
$totalSalgados = 0;
$totalDoces    = 0;
$totalBebidas  = 0;

foreach ($todasReceitas as $rec) {
    $t = strtoupper($rec['tipo']);
    if ($t === 'SALGADO') $totalSalgados++;
    elseif ($t === 'DOCE' || $t === 'DOCES') $totalDoces++;
    elseif ($t === 'BEBIDA') $totalBebidas++;
}

$status = $_GET['status'] ?? '';
$msg    = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Magical Bites</title>

    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        /* CURSOR PERSONALIZADO DO MICKEY */
        html, body, *, a, button, input, select, textarea, label {
            cursor: none !important;
        }

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

        body {
            background-color: #0a1733;
            color: #ffffff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* NAVBAR PADRONIZADA */
        .navbar {
            width: 100%;
            background: #294f88;
            padding: 10px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo img { height: 50px; width: auto; display: block; }
        .menu { display: flex; align-items: center; gap: 26px; flex-wrap: wrap; }
        .menu a {
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .menu a:hover, .menu a.ativo { color: #fff6a9; }
        .nav-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff6a9;
        }

        /* CABEÇALHO CREME DO PAINEL */
        .admin-header-banner {
            background-color: #fff4b8;
            padding: 28px 25px 32px;
            border-bottom: 4px solid #a62428;
            color: #1f4396;
        }

        .admin-header-inner {
            max-width: 1250px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .admin-title-group h1 {
            font-family: 'Caveat', cursive;
            font-size: 48px;
            color: #a62428;
            line-height: 1;
        }

        .admin-title-group p {
            font-size: 14px;
            font-weight: 700;
            color: #1f4396;
            margin-top: 4px;
        }

        .btn-nova-receita {
            background: #a62428;
            color: #fff8bd;
            border: 2px solid #a62428;
            padding: 13px 26px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 18px rgba(166, 36, 40, 0.35);
            transition: all 0.25s ease;
        }

        .btn-nova-receita:hover {
            background: #831a1e;
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ÁREA PRINCIPAL */
        .admin-main-section {
            position: relative;
            background-image: linear-gradient(rgba(10, 25, 55, 0.72), rgba(6, 16, 38, 0.85)), url('<?= buscarImagem("fundo2_index") ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 35px 0 80px;
            min-height: calc(100vh - 180px);
        }

        .container-admin {
            max-width: 1250px;
            margin: 0 auto;
            padding: 0 25px;
        }

        /* ALERTA DE FEEDBACK */
        .alerta-feedback {
            padding: 14px 22px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 6px 18px rgba(0,0,0,0.3);
        }

        .alerta-sucesso {
            background: #1b5e20;
            border: 1.5px solid #81c784;
            color: #ffffff;
        }

        .alerta-erro {
            background: #a62428;
            border: 1.5px solid #fff8bd;
            color: #fff8bd;
        }

        /* CARDS DE RESUMO (KPIs) */
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: rgba(16, 31, 64, 0.78);
            border: 1px solid rgba(255, 248, 189, 0.22);
            border-radius: 20px;
            padding: 18px 22px;
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-info span {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #fff8bd;
            letter-spacing: 0.5px;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 900;
            color: #ffffff;
            margin-top: 2px;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: rgba(166, 36, 40, 0.85);
            color: #fff8bd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* BARRA DE FILTROS E PESQUISA */
        .toolbar-admin {
            background: rgba(16, 31, 64, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 22px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            backdrop-filter: blur(8px);
        }

        .filtros-tipo {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-filtro {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.25s;
        }

        .btn-filtro:hover, .btn-filtro.ativo {
            background: #fff8bd;
            color: #a62428;
            border-color: #fff8bd;
        }

        .busca-admin {
            position: relative;
            min-width: 280px;
            flex: 1;
            max-width: 400px;
        }

        .busca-admin input {
            width: 100%;
            padding: 10px 18px 10px 42px;
            border-radius: 50px;
            border: 1.5px solid rgba(255, 248, 189, 0.4);
            background: rgba(10, 20, 42, 0.7);
            color: #ffffff;
            font-size: 14px;
            outline: none;
        }

        .busca-admin input::placeholder { color: rgba(255, 255, 255, 0.65); }
        .busca-admin i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #fff8bd;
        }

        /* TABELA DE RECEITAS */
        .tabela-wrapper {
            background: rgba(12, 24, 52, 0.85);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            overflow-x: auto;
            backdrop-filter: blur(8px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
        }

        table.tabela-receitas {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .tabela-receitas thead {
            background: #294f88;
            color: #fff8bd;
        }

        .tabela-receitas th {
            padding: 16px 18px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabela-receitas td {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 14px;
            vertical-align: middle;
        }

        .tabela-receitas tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .thumb-receita {
            width: 68px;
            height: 52px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255, 248, 189, 0.35);
            display: block;
        }

        .badge-tipo {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .tipo-SALGADO { background: #2e7d32; color: #ffffff; }
        .tipo-DOCE, .tipo-DOCES { background: #d81b60; color: #ffffff; }
        .tipo-BEBIDA { background: #00838f; color: #ffffff; }

        .acoes-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-acao-tabela {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-ver { background: rgba(255, 255, 255, 0.15); color: #ffffff; }
        .btn-ver:hover { background: #294f88; }

        .btn-editar { background: #fff8bd; color: #a62428; }
        .btn-editar:hover { background: #ffffff; transform: scale(1.08); }

        .btn-excluir { background: #a62428; color: #ffffff; }
        .btn-excluir:hover { background: #d32f2f; transform: scale(1.08); }

        /* MODAIS DE FORMULÁRIO E EXCLUSÃO */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 17, 36, 0.78);
            backdrop-filter: blur(8px);
            z-index: 999;
        }

        .modal-admin-box {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 760px;
            max-width: 94vw;
            max-height: 90vh;
            overflow-y: auto;
            background: #a62428;
            color: #ffffff;
            border-radius: 30px;
            padding: 32px 30px;
            z-index: 1000;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(255, 248, 189, 0.3);
            animation: popUpModal 0.3s ease;
        }

        .modal-excluir-box {
            width: 420px;
            text-align: center;
        }

        @keyframes popUpModal {
            from { opacity: 0; transform: translate(-50%, -46%) scale(0.94); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .btn-fechar-modal {
            position: absolute;
            top: 18px;
            right: 22px;
            background: none;
            border: none;
            font-size: 26px;
            color: rgba(255, 255, 255, 0.85);
        }

        .btn-fechar-modal:hover { color: #fff8bd; }

        .modal-admin-box h2 {
            font-family: 'Caveat', cursive;
            font-size: 40px;
            color: #fff8bd;
            margin-bottom: 4px;
            line-height: 1.1;
        }

        .modal-admin-box p.modal-sub {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 18px;
        }

        .form-full { grid-column: 1 / -1; }

        .campo-form label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #fff8bd;
            margin-bottom: 5px;
        }

        .campo-form input[type="text"],
        .campo-form input[type="number"],
        .campo-form select,
        .campo-form textarea {
            width: 100%;
            padding: 11px 14px;
            border-radius: 14px;
            border: 1.5px solid rgba(255, 255, 255, 0.65);
            background: rgba(15, 23, 42, 0.35);
            color: #ffffff;
            font-size: 13.5px;
            outline: none;
            transition: 0.25s;
        }

        .campo-form select option {
            background: #1f3a68;
            color: #ffffff;
        }

        .campo-form input:focus,
        .campo-form select:focus,
        .campo-form textarea:focus {
            border-color: #fff8bd;
            background: rgba(15, 23, 42, 0.55);
        }

        .campo-form textarea {
            resize: vertical;
            min-height: 85px;
        }

        .upload-preview-row {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(0, 0, 0, 0.2);
            padding: 10px 14px;
            border-radius: 14px;
            border: 1px dashed rgba(255, 248, 189, 0.5);
        }

        .upload-preview-row img {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            background: #0a1733;
        }

        .btn-salvar-admin {
            width: 100%;
            padding: 14px;
            background: #fff8bd;
            color: #a62428;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 800;
            margin-top: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
            transition: 0.25s;
        }

        .btn-salvar-admin:hover {
            background: #ffffff;
            transform: translateY(-2px);
        }

        .botoes-confirmacao {
            display: flex;
            gap: 12px;
            margin-top: 22px;
        }

        .botoes-confirmacao button {
            flex: 1;
            padding: 12px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 14px;
            border: none;
        }

        @media (max-width: 900px) {
            .grid-stats { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 550px) {
            .grid-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <img id="mickeyCursor" src="<?= buscarImagemCursor('aberto') ?>" alt="Cursor Mickey">

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="salgados.php">Salgados</a>
            <a href="doces.php">Doces</a>
            <a href="bebidas.php">Bebidas</a>
            <a href="filmes.php">Filmes</a>
            <a href="admin.php" class="ativo"><i class="fa-solid fa-wand-magic-sparkles"></i> Admin</a>
            <a href="logout.php" title="Sair"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </div>
    </div>

    <!-- BANNER SUPERIOR -->
    <header class="admin-header-banner">
        <div class="admin-header-inner">
            <div class="admin-title-group">
                <h1>Cozinha do Administrador</h1>
                <p>Gerencie, edite, remova ou adicione novas receitas mágicas ao catálogo.</p>
            </div>
            <button type="button" class="btn-nova-receita" onclick="abrirModalNovaReceita()">
                <i class="fa-solid fa-plus"></i> Adicionar Nova Receita
            </button>
        </div>
    </header>

    <!-- CONTEÚDO PRINCIPAL -->
    <section class="admin-main-section">
        <div class="container-admin">

            <?php if (!empty($status)): ?>
                <div class="alerta-feedback <?= $status === 'sucesso' ? 'alerta-sucesso' : 'alerta-erro' ?>" id="alertaFeedback">
                    <span>
                        <i class="fa-solid <?= $status === 'sucesso' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                        <?= htmlspecialchars($msg) ?>
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;font-size:18px;">&times;</button>
                </div>
            <?php endif; ?>

            <!-- CARDS DE ESTATÍSTICAS -->
            <div class="grid-stats">
                <div class="stat-card">
                    <div class="stat-info">
                        <span>Total de Receitas</span>
                        <h3><?= $totalGeral ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span>Salgados</span>
                        <h3><?= $totalSalgados ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fa-solid fa-utensils"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span>Doces</span>
                        <h3><?= $totalDoces ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fa-solid fa-cake-candles"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span>Bebidas</span>
                        <h3><?= $totalBebidas ?></h3>
                    </div>
                    <div class="stat-icon"><i class="fa-solid fa-mug-hot"></i></div>
                </div>
            </div>

            <!-- BARRA DE FILTROS E BUSCA -->
            <div class="toolbar-admin">
                <div class="filtros-tipo">
                    <button type="button" class="btn-filtro ativo" onclick="filtrarPorTipo('TODOS', this)">Todas (<?= $totalGeral ?>)</button>
                    <button type="button" class="btn-filtro" onclick="filtrarPorTipo('SALGADO', this)">Salgados</button>
                    <button type="button" class="btn-filtro" onclick="filtrarPorTipo('DOCE', this)">Doces</button>
                    <button type="button" class="btn-filtro" onclick="filtrarPorTipo('BEBIDA', this)">Bebidas</button>
                </div>

                <div class="busca-admin">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="buscaTabela" placeholder="Pesquisar receita ou filme..." onkeyup="filtrarTabela()">
                </div>
            </div>

            <!-- TABELA DE GERENCIAMENTO -->
            <div class="tabela-wrapper">
                <table class="tabela-receitas" id="tabelaReceitas">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Receita</th>
                            <th>Categoria</th>
                            <th>Filme</th>
                            <th>Tempo / Dificuldade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todasReceitas as $r): 
                            $tipoNormalizado = strtoupper($r['tipo']);
                            if ($tipoNormalizado === 'DOCES') $tipoNormalizado = 'DOCE';
                            $caminhoImg = buscarImagem($r['imagem']);
                        ?>
                        <tr class="linha-receita" 
                            data-tipo="<?= htmlspecialchars($tipoNormalizado) ?>" 
                            data-busca="<?= strtolower(htmlspecialchars($r['nome_receita'] . ' ' . ($r['nome_filme'] ?? ''))) ?>">
                            <td>
                                <img src="<?= htmlspecialchars($caminhoImg) ?>" class="thumb-receita" alt="" onerror="this.src='img/logo/logo_menu.png'">
                            </td>
                            <td>
                                <strong style="color:#ffffff; display:block;"><?= htmlspecialchars($r['nome_receita']) ?></strong>
                                <small style="color:rgba(255,255,255,0.65);">ID #<?= $r['id_receita'] ?></small>
                            </td>
                            <td>
                                <span class="badge-tipo tipo-<?= htmlspecialchars($tipoNormalizado) ?>">
                                    <?= htmlspecialchars($tipoNormalizado) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['nome_filme'] ?? '—') ?></td>
                            <td>
                                <span><i class="fa-regular fa-clock" style="color:#fff8bd;"></i> <?= htmlspecialchars($r['tempo_preparo'] ?? '30min') ?></span>
                                <br>
                                <small style="color:rgba(255,255,255,0.75);"><?= htmlspecialchars($r['dificuldade'] ?? 'Fácil') ?></small>
                            </td>
                            <td>
                                <div class="acoes-cell">
                                    <a href="receita.php?id=<?= $r['id_receita'] ?>" target="_blank" class="btn-acao-tabela btn-ver" title="Visualizar no site">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn-acao-tabela btn-editar" title="Editar Receita"
                                        onclick='abrirModalEditarReceita(<?= json_encode([
                                            "id_receita"    => $r["id_receita"],
                                            "nome_receita"  => $r["nome_receita"],
                                            "tipo"          => $tipoNormalizado,
                                            "id_filme"      => $r["id_filme"] ?? "",
                                            "descricao"     => $r["descricao"] ?? "",
                                            "imagem"        => $r["imagem"] ?? "",
                                            "imagem_url"    => $caminhoImg,
                                            "avaliacao"     => $r["avaliacao"] ?? "5.0",
                                            "tempo_preparo" => $r["tempo_preparo"] ?? "30min",
                                            "dificuldade"   => $r["dificuldade"] ?? "Fácil",
                                            "ingredientes"  => $r["ingredientes"] ?? "",
                                            "modo_preparo"  => $r["modo_preparo"] ?? ""
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn-acao-tabela btn-excluir" title="Remover Receita"
                                        onclick="abrirModalExcluir(<?= $r['id_receita'] ?>, <?= htmlspecialchars(json_encode($r['nome_receita']), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>

    <!-- OVERLAY -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharModaisAdmin()"></div>

    <!-- MODAL ADICIONAR / EDITAR RECEITA -->
    <div id="modalFormReceita" class="modal-admin-box">
        <button type="button" class="btn-fechar-modal" onclick="fecharModaisAdmin()">&times;</button>
        <h2 id="tituloModalReceita">Nova Receita Mágica</h2>
        <p class="modal-sub" id="subtituloModalReceita">Preencha os ingredientes e segredos do prato abaixo.</p>

        <form action="admin.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao_admin" id="campoAcaoAdmin" value="adicionar">
            <input type="hidden" name="id_receita" id="campoIdReceita" value="">
            <input type="hidden" name="imagem_atual" id="campoImagemAtual" value="">

            <div class="form-grid">
                <div class="campo-form form-full">
                    <label>Nome da Receita *</label>
                    <input type="text" name="nome_receita" id="campoNomeReceita" required placeholder="Ex: Torta de Maçã da Branca de Neve">
                </div>

                <div class="campo-form">
                    <label>Categoria (Tipo) *</label>
                    <select name="tipo" id="campoTipo" required>
                        <option value="SALGADO">Salgado</option>
                        <option value="DOCE">Doce</option>
                        <option value="BEBIDA">Bebida</option>
                    </select>
                </div>

                <div class="campo-form">
                    <label>Filme Inspirado</label>
                    <select name="id_filme" id="campoIdFilme">
                        <option value="">-- Sem filme vinculado --</option>
                        <?php foreach ($todosFilmes as $f): ?>
                            <option value="<?= $f['id_filme'] ?>"><?= htmlspecialchars($f['nome_filme']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-form">
                    <label>Tempo de Preparo</label>
                    <input type="text" name="tempo_preparo" id="campoTempo" placeholder="Ex: 45min ou 1h 15min" value="30min">
                </div>

                <div class="campo-form">
                    <label>Dificuldade</label>
                    <select name="dificuldade" id="campoDificuldade">
                        <option value="Fácil">Fácil</option>
                        <option value="Média">Média</option>
                        <option value="Difícil">Difícil</option>
                    </select>
                </div>

                <div class="campo-form form-full">
                    <label>Imagem do Prato (Envie uma foto ou informe o nome do arquivo)</label>
                    <div class="upload-preview-row">
                        <img src="img/logo/logo_menu.png" id="previewImgReceita" alt="Preview">
                        <div style="flex:1;">
                            <input type="file" name="imagem_upload" id="inputImagemUpload" accept="image/*" onchange="mostrarPreviewUpload(this)" style="margin-bottom:6px; font-size:12px;">
                            <input type="text" name="imagem_nome" id="campoImagemNome" placeholder="Ou nome do arquivo na pasta img/pratos (ex: torta.webp)">
                        </div>
                    </div>
                </div>

                <div class="campo-form form-full">
                    <label>Descrição Curta (aparece nos cards)</label>
                    <textarea name="descricao" id="campoDescricao" rows="2" placeholder="Uma breve descrição encantadora sobre o prato..."></textarea>
                </div>

                <div class="campo-form form-full">
                    <label>Ingredientes (separe cada ingrediente por ponto e vírgula ; )</label>
                    <textarea name="ingredientes" id="campoIngredientes" rows="3" placeholder="2 xícaras de farinha de trigo; 150g de manteiga; 4 colheres de açúcar"></textarea>
                </div>

                <div class="campo-form form-full">
                    <label>Modo de Preparo</label>
                    <textarea name="modo_preparo" id="campoModoPreparo" rows="4" placeholder="1. Preaqueça o forno a 180°C. 2. Misture os ingredientes..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-salvar-admin" id="btnSubmitReceita">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Salvar Receita
            </button>
        </form>
    </div>

    <!-- MODAL CONFIRMAÇÃO DE EXCLUSÃO -->
    <div id="modalExcluirReceita" class="modal-admin-box modal-excluir-box">
        <button type="button" class="btn-fechar-modal" onclick="fecharModaisAdmin()">&times;</button>
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 42px; color: #fff8bd; margin-bottom: 10px;"></i>
        <h2>Remover Receita?</h2>
        <p class="modal-sub">Tem certeza que deseja excluir <strong id="nomeReceitaExcluir" style="color:#fff8bd;"></strong>? Essa ação não poderá ser desfeita.</p>

        <form action="admin.php" method="POST">
            <input type="hidden" name="acao_admin" value="remover">
            <input type="hidden" name="id_receita" id="idReceitaExcluir" value="">
            <div class="botoes-confirmacao">
                <button type="button" onclick="fecharModaisAdmin()" style="background: rgba(255,255,255,0.2); color: #ffffff;">Cancelar</button>
                <button type="submit" style="background: #fff8bd; color: #a62428;">Sim, Remover</button>
            </div>
        </form>
    </div>

    <script>
    // CURSOR DO MICKEY
    const mickeyCursor = document.getElementById('mickeyCursor');
    const imgAberto = "<?= buscarImagemCursor('aberto') ?>";
    const imgFechado = "<?= buscarImagemCursor('fechado') ?>";

    if (mickeyCursor) {
        document.addEventListener('mousemove', (e) => {
            mickeyCursor.style.display = 'block';
            mickeyCursor.style.left = e.clientX + 'px';
            mickeyCursor.style.top = e.clientY + 'px';
        });
        document.addEventListener('mouseleave', () => { mickeyCursor.style.display = 'none'; });

        const seletorInterativo = 'a, button, input, select, textarea, label, [onclick]';
        document.addEventListener('mouseover', (e) => {
            if (e.target.closest(seletorInterativo)) mickeyCursor.src = imgFechado;
        });
        document.addEventListener('mouseout', (e) => {
            if (e.target.closest(seletorInterativo)) mickeyCursor.src = imgAberto;
        });
        document.addEventListener('mousedown', () => { mickeyCursor.src = imgFechado; });
        document.addEventListener('mouseup', (e) => {
            if (!e.target.closest(seletorInterativo)) mickeyCursor.src = imgAberto;
        });
    }

    // FILTROS DA TABELA
    let tipoAtualFiltro = 'TODOS';

    function filtrarPorTipo(tipo, botao) {
        tipoAtualFiltro = tipo;
        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('ativo'));
        botao.classList.add('ativo');
        filtrarTabela();
    }

    function filtrarTabela() {
        const termo = document.getElementById('buscaTabela').value.toLowerCase();
        const linhas = document.querySelectorAll('.linha-receita');

        linhas.forEach(linha => {
            const tipoLinha = linha.getAttribute('data-tipo');
            const textoBusca = linha.getAttribute('data-busca');

            const bateTipo = (tipoAtualFiltro === 'TODOS' || tipoLinha === tipoAtualFiltro);
            const bateTexto = textoBusca.includes(termo);

            linha.style.display = (bateTipo && bateTexto) ? '' : 'none';
        });
    }

    // MODAIS ADMIN
    function fecharModaisAdmin() {
        document.getElementById('modalOverlay').style.display = 'none';
        document.getElementById('modalFormReceita').style.display = 'none';
        document.getElementById('modalExcluirReceita').style.display = 'none';
    }

    function abrirModalNovaReceita() {
        fecharModaisAdmin();
        document.getElementById('tituloModalReceita').innerText = 'Nova Receita Mágica';
        document.getElementById('subtituloModalReceita').innerText = 'Adicione um novo prato encantado ao site.';
        document.getElementById('campoAcaoAdmin').value = 'adicionar';
        document.getElementById('campoIdReceita').value = '';
        document.getElementById('campoImagemAtual').value = '';
        document.getElementById('campoNomeReceita').value = '';
        document.getElementById('campoTipo').value = 'SALGADO';
        document.getElementById('campoIdFilme').value = '';
        document.getElementById('campoTempo').value = '30min';
        document.getElementById('campoDificuldade').value = 'Fácil';
        document.getElementById('campoImagemNome').value = '';
        document.getElementById('inputImagemUpload').value = '';
        document.getElementById('previewImgReceita').src = 'img/logo/logo_menu.png';
        document.getElementById('campoDescricao').value = '';
        document.getElementById('campoIngredientes').value = '';
        document.getElementById('campoModoPreparo').value = '';
        document.getElementById('btnSubmitReceita').innerHTML = '<i class="fa-solid fa-plus"></i> Adicionar Receita';

        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('modalFormReceita').style.display = 'block';
    }

    function abrirModalEditarReceita(receita) {
        fecharModaisAdmin();
        document.getElementById('tituloModalReceita').innerText = 'Editar Receita';
        document.getElementById('subtituloModalReceita').innerText = 'Altere os dados desejados e salve.';
        document.getElementById('campoAcaoAdmin').value = 'editar';
        document.getElementById('campoIdReceita').value = receita.id_receita;
        document.getElementById('campoImagemAtual').value = receita.imagem;
        document.getElementById('campoNomeReceita').value = receita.nome_receita;
        document.getElementById('campoTipo').value = receita.tipo;
        document.getElementById('campoIdFilme').value = receita.id_filme || '';
        document.getElementById('campoTempo').value = receita.tempo_preparo || '30min';
        document.getElementById('campoDificuldade').value = receita.dificuldade || 'Fácil';
        document.getElementById('campoImagemNome').value = receita.imagem;
        document.getElementById('inputImagemUpload').value = '';
        document.getElementById('previewImgReceita').src = receita.imagem_url || 'img/logo/logo_menu.png';
        document.getElementById('campoDescricao').value = receita.descricao;
        document.getElementById('campoIngredientes').value = receita.ingredientes;
        document.getElementById('campoModoPreparo').value = receita.modo_preparo;
        document.getElementById('btnSubmitReceita').innerHTML = '<i class="fa-solid fa-check"></i> Salvar Alterações';

        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('modalFormReceita').style.display = 'block';
    }

    function abrirModalExcluir(id, nome) {
        fecharModaisAdmin();
        document.getElementById('idReceitaExcluir').value = id;
        document.getElementById('nomeReceitaExcluir').innerText = nome;
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('modalExcluirReceita').style.display = 'block';
    }

    function mostrarPreviewUpload(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImgReceita').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>