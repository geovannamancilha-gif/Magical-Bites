<?php
// Conexão com a base de dados
$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "Home@spSENAI2025!");
//$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "");

//$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root");
$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "Home@spSENAI2025!");
 

// Busca os filmes e suas receitas
$sql = "
    SELECT f.id_filme, f.nome_filme, r.id_receita, r.nome_receita, r.imagem AS img_receita, r.tipo 
    FROM filme f
    INNER JOIN receitas r ON f.id_filme = r.id_filme
    ORDER BY f.nome_filme ASC, r.nome_receita ASC
";
$stmt = $pdo->query($sql);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FUNÇÃO PARA DIVIDIR EM CATEGORIAS
function obterCategoriaFilme($nomeFilme) {
    $princesas = ['Branca de Neve', 'Frozen', 'Enrolados', 'A Princesa e o Sapo', 'A Bela Adormecida', 'Moana', 'Aladdin', 'Mulan', 'Valente'];
    $pixar = ['Ratatouille', 'Up: Altas Aventuras', 'Luca', 'Viva: A Vida é uma Festa', 'Os Incríveis'];
    $mickey = ['Turma do Mickey', 'Ursinho Pooh'];
    
    if (in_array($nomeFilme, $princesas)) return 'Princesas & Contos de Fadas';
    if (in_array($nomeFilme, $pixar)) return 'Universo Pixar';
    if (in_array($nomeFilme, $mickey)) return 'Turma do Mickey & Amigos';
    
    return '🏰 Clássicos & Aventuras';
}

// Prepara a estrutura das categorias
$categorias = [
    'Princesas & Contos de Fadas' => [],
    'Universo Pixar' => [],
    'Turma do Mickey & Amigos' => [],
    'Clássicos & Aventuras' => []
];

// Agrupa os filmes dentro das suas categorias correspondentes
foreach ($resultados as $row) {
    $id_filme = $row['id_filme'];
    $nome_filme = $row['nome_filme'];
    $categoria_atual = obterCategoriaFilme($nome_filme);

    if (!isset($categorias[$categoria_atual][$id_filme])) {
        $categorias[$categoria_atual][$id_filme] = [
            'nome_filme' => $nome_filme,
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

// Função de Temas e Nomes das Imagens
function obterTemaFilme($nomeFilme) {
    $temas = [
        'Branca de Neve' => ['cor' => '#d62828', 'img' => 'brancadeneve.png'],
        'Frozen' => ['cor' => '#90e0ef', 'img' => 'frozen.png'],
        'Enrolados' => ['cor' => '#e0aaff', 'img' => 'enrolados.png'],
        'A Princesa e o Sapo' => ['cor' => '#2a9d8f', 'img' => 'princesaeosapo.png'],
        'Alice no País das Maravilhas' => ['cor' => '#0077b6', 'img' => 'alice.png'],
        'Ratatouille' => ['cor' => '#f77f00', 'img' => 'ratatouille.png'],
        'Lilo & Stitch' => ['cor' => '#0077b6', 'img' => 'stitch.png'],
        'A Bela Adormecida' => ['cor' => '#ff006e', 'img' => 'belaadormecida.png'],
        
        // AQUI ESTÁ A ALTERAÇÃO: Nome exato da sua imagem para o UP!
        'Up: Altas Aventuras' => ['cor' => '#4ea8de', 'img' => 'UPfilme.png'],
        
        'Mogli' => ['cor' => '#2d6a4f', 'img' => 'mogli.png'],
        'Turma do Mickey' => ['cor' => '#d00000', 'img' => 'minnie.png'],
        'Valente' => ['cor' => '#e85d04', 'img' => 'valente.png'],
        'Ursinho Pooh' => ['cor' => '#ffb703', 'img' => 'pooh.png'],
        'Encanto' => ['cor' => '#8338ec', 'img' => 'encanto.png'],
        'Luca' => ['cor' => '#00b4d8', 'img' => 'lucca.png'],
        'A Dama e o Vagabundo' => ['cor' => '#780000', 'img' => 'damaevagabundo.png'],
        'Zootopia' => ['cor' => '#fb8500', 'img' => 'zootopia.png'],
        'Viva' => ['cor' => '#f48c06', 'img' => 'vivalavida.png'],
        'Aristogatas' => ['cor' => '#ffb5a7', 'img' => 'aristogatas.png'],
        'Moana' => ['cor' => '#00f5d4', 'img' => 'moana.png'],
        'Pinóquio' => ['cor' => '#9c6644', 'img' => 'pinoquio.png'],
        '101 Dálmatas' => ['cor' => '#343a40', 'img' => '101dalmatas.png'],
        'Aladdin' => ['cor' => '#3c096c', 'img' => 'aladim.png'],
        'Os Incríveis' => ['cor' => '#e63946', 'img' => 'incriveis.png'],
        'Mulan' => ['cor' => '#bc4749', 'img' => 'mulan.webp']
    ];

    foreach ($temas as $chave => $dados) {
        if (stripos($nomeFilme, $chave) !== false) {
            return $dados;
        }
    }
    return ['cor' => '#1f4396', 'img' => 'capa_default.jpg']; 
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filmes | Magical Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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

    .logo img {
        height: 60px;
        width: auto;
        margin-top: -10px;
    }

    .menu {
        display: flex;
        align-items: center;
        gap: 35px;
    }

    .menu a {
        color: white;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
        transition: 0.3s;
        cursor: pointer;
    }

    .menu a:hover,
    .menu a.ativo {
        color: #ffe45c;
    }

    .header-filmes {
        text-align: center;
        padding: 40px 20px 20px;
    }

    .header-filmes h1 {
        color: #1f4396;
        font-size: 38px;
    }

    .container {
        width: 100%;
        max-width: 1250px;
        margin: 0 auto;
        padding: 0 30px 50px;
    }

    .categoria-titulo {
        width: 100%;
        margin: 50px 0 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #1f4396;
        color: #1f4396;
        font-size: 26px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .grid-filmes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 30px;
        margin-bottom: 20px;
    }

    /* ESTILO PARA O BOX E A IMAGEM */
    .box-filme {
        position: relative;
        height: 180px;
        border-radius: 20px;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background-color: var(--cor-tema);
    }

    .box-filme:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
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
        transform: scale(1.1);
    }

    .box-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(to top, var(--cor-tema) 15%, rgba(0, 0, 0, 0.4) 100%);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 20px;
        color: white;
        transition: 0.3s;
        z-index: 2;
    }

    .box-filme:hover .box-overlay {
        background: linear-gradient(to top, var(--cor-tema) 30%, rgba(0, 0, 0, 0.6) 100%);
    }

    .box-overlay h3 {
        font-size: 20px;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        margin-bottom: 5px;
    }

    .box-overlay span {
        font-size: 12px;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.25);
        padding: 4px 10px;
        border-radius: 20px;
        width: fit-content;
        backdrop-filter: blur(5px);
    }

    /* MODAL */
    .modal-filme-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        z-index: 999;
        justify-content: center;
        align-items: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .modal-filme-overlay.ativo {
        display: flex;
        opacity: 1;
    }

    .modal-filme-content {
        background: #f3f1ff;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        border-radius: 25px;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        transform: scale(0.9);
        transition: transform 0.3s;
    }

    .modal-filme-overlay.ativo .modal-filme-content {
        transform: scale(1);
    }

    .modal-header {
        height: 180px;
        position: relative;
        display: flex;
        align-items: flex-end;
        padding: 30px;
        background-color: var(--cor-tema);
        overflow: hidden;
    }

    .modal-header img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
        opacity: 0.8;
    }

    .modal-header-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, var(--cor-tema) 0%, rgba(0, 0, 0, 0.3) 100%);
        z-index: 2;
    }

    .modal-header h2 {
        position: relative;
        color: white;
        font-size: 32px;
        z-index: 3;
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.8);
    }

    .btn-fechar {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 24px;
        cursor: pointer;
        z-index: 10;
        transition: 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-fechar:hover {
        background: white;
        color: black;
        transform: scale(1.1);
    }

    .modal-body {
        padding: 30px;
        overflow-y: auto;
        flex: 1;
    }

    .lista-receitas {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .card {
        background: white;
        color: black;
        border-radius: 20px;
        overflow: hidden;
        text-decoration: none;
        transition: 0.3s;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .card img {
        width: 100%;
        height: 140px;
        object-fit: cover;
    }

    .tag {
        width: fit-content;
        margin: 15px auto 10px;
        padding: 5px 12px;
        border-radius: 20px;
        color: white;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .doce {
        background: #ff69b4;
    }

    .salgado {
        background: #4CAF50;
    }

    .bebida {
        background: #00bcd4;
    }

    .card p {
        padding: 0 15px 20px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        color: #333;
    }

    .estado-vazio {
        text-align: center;
        padding: 50px;
        color: #666;
        font-size: 18px;
        width: 100%;
    }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="logo_menu.png" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="#">Favoritos</a>
            <a href="filmes.php" class="ativo">Filmes</a>
        </div>
    </div>

    <div class="header-filmes">
        <h1>Escolha o seu Universo</h1>
        <p>Selecione um filme e descubra todas as receitas mágicas dele!</p>
    </div>

    <div class="container">
        <?php 
    $temFilmes = false;
    foreach($categorias as $nomeCategoria => $filmesCategoria) {
        if (!empty($filmesCategoria)) {
            $temFilmes = true;
            break;
        }
    }

    if(!$temFilmes): ?>
        <div class="estado-vazio">
            <i class="fa fa-film" style="font-size: 40px; color: #ccc; margin-bottom:15px; display:block;"></i>
            Nenhum filme com receitas cadastrado no momento.
        </div>
        <?php else: ?>

        <?php foreach($categorias as $nomeCategoria => $filmesCategoria): ?>
        <?php if(!empty($filmesCategoria)): ?>

        <h2 class="categoria-titulo"><?= $nomeCategoria ?></h2>

        <div class="grid-filmes">
            <?php foreach($filmesCategoria as $id => $filme): 
                        $tema = obterTemaFilme($filme['nome_filme']);
                    ?>
            <div class="box-filme" style="--cor-tema: <?= $tema['cor'] ?>;"
                onclick="abrirModalFilme('modal-filme-<?= $id ?>')">

                <img src="<?= $tema['img'] ?>" alt="<?= htmlspecialchars($filme['nome_filme']) ?>"
                    class="img-capa-filme" onerror="this.style.opacity='0';">

                <div class="box-overlay">
                    <h3><?= htmlspecialchars($filme['nome_filme']) ?></h3>
                    <span><i class="fa fa-utensils"></i> <?= count($filme['receitas']) ?> Receita(s)</span>
                </div>
            </div>

            <div id="modal-filme-<?= $id ?>" class="modal-filme-overlay">
                <div class="modal-filme-content">
                    <div class="modal-header" style="--cor-tema: <?= $tema['cor'] ?>;">
                        <img src="<?= $tema['img'] ?>" alt="<?= htmlspecialchars($filme['nome_filme']) ?>"
                            onerror="this.style.opacity='0';">
                        <div class="modal-header-overlay"></div>
                        <button onclick="fecharModalFilme('modal-filme-<?= $id ?>', event)"
                            class="btn-fechar">&times;</button>
                        <h2><?= htmlspecialchars($filme['nome_filme']) ?></h2>
                    </div>

                    <div class="modal-body">
                        <div class="lista-receitas">
                            <?php foreach($filme['receitas'] as $r): ?>
                            <a href="receita.php?id=<?= $r['id_receita'] ?>" class="card">
                                <img src="<?= htmlspecialchars($r['imagem']) ?>" onerror="this.src='logo_menu.png'">
                                <div class="tag <?= strtolower(htmlspecialchars($r['tipo'])) ?>">
                                    <?= htmlspecialchars($r['tipo']) ?></div>
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

        <?php endif; ?>
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

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-filme-overlay')) {
            event.target.classList.remove('ativo');
            document.body.style.overflow = 'auto';
        }
    }
    </script>

</body>

</html>