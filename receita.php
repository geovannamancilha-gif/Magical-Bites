<?php
// Configurações de conexão com a base de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
$pass = "";     
//$pass = "Home@spSENAI2025!";     
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
    $passos_preparo = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $receita['modo_preparo'])));

    // Definição de cores por categoria
    $classe_tag = 'tag-doce';
    if ($receita['tipo'] == 'SALGADO') {
        $classe_tag = 'tag-salgado';
    } elseif ($receita['tipo'] == 'BEBIDA') {
        $classe_tag = 'tag-bebida';
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

function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = ['pratos', 'filme', 'logo', 'slider', 'perfis', 'avatares'];
    foreach ($pastas as $pasta) {
        $caminho = "img/" . $pasta . "/" . $nomeImagem;
        if (file_exists($caminho)) return $caminho;
    }
    return "img/" . $nomeImagem;
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
        }

        /* --- NAVBAR --- */
        .navbar {
            width: 100%;
            background: #172146;
            padding: 14px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo img {
            height: 48px;
            width: auto;
            display: block;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .menu a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
        }

        .menu a:hover, .menu a.active {
            color: #ffffff;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-actions i {
            color: #d1d5db;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }

        .nav-actions i:hover {
            color: white;
        }

        .nav-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ffe45c;
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
            color: #5865f2;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 25px;
            transition: 0.2s;
        }

        .voltar-link:hover {
            transform: translateX(-3px);
            color: #3b46c4;
        }

        /* HERO HERO SECTION (INFO + IMAGEM) */
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
            color: #5865f2;
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
            border: 1.5px solid #5865f2;
            background: transparent;
            color: #5865f2;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-favoritar:hover {
            background: rgba(88, 101, 242, 0.08);
            transform: translateY(-2px);
        }

        .btn-fazer {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 32px;
            border-radius: 30px;
            border: none;
            background: #5865f2;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(88, 101, 242, 0.35);
            transition: 0.3s;
        }

        .btn-fazer:hover {
            background: #4753d8;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(88, 101, 242, 0.45);
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

        /* --- DETALHES DA RECEITA (PAINÉIS LADO A LADO) --- */
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
            color: #5865f2;
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
            color: #5865f2;
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
            color: #4338ca;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .dica-conteudo p {
            color: #5b51d8;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .dica-icone {
            font-size: 28px;
            color: #6366f1;
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
            <a href="index.php"><img src="<?= buscarImagem('logo_menu.png') ?>" alt="Magical Bites"></a>
        </div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas" class="active">Receitas</a>
            <a href="#">Favoritos</a>
            <a href="filmes.php">Filmes</a>
            <a href="#">Sobre</a>
        </div>
        <div class="nav-actions">
            <i class="fa-solid fa-magnifying-glass"></i>
            <a href="index.php"><img src="<?= buscarImagem($_SESSION['usuario']['foto'] ?? 'logo_menu.png') ?>" class="nav-avatar" onerror="this.src='img/logo/logo_menu.png'"></a>
        </div>
    </div>

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
                    <a href="favoritar.php?id=<?= $receita['id_receita'] ?>" class="btn-favoritar">
                        <i class="fa-regular fa-heart"></i> Favoritar
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

                <!-- BOX DICA MÁGICA -->
                <div class="dica-magica">
                    <div class="dica-conteudo">
                        <h4>Dica mágica</h4>
                        <p>Sirva com uma bola de sorvete de creme para uma experiência ainda mais encantadora!</p>
                    </div>
                    <div class="dica-icone">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
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

</body>
</html>