<?php
// 1. Configurações de conexão (ajuste se necessário)
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
$pass = 'Home@spSENAI2025!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Busca receitas DOCES
    $stmtDoces = $pdo->prepare("SELECT * FROM receitas WHERE tipo = 'DOCE' ORDER BY id_receita DESC");
    $stmtDoces->execute();
    $receitasDoces = $stmtDoces->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca receitas SALGADAS
    $stmtSalgadas = $pdo->prepare("SELECT * FROM receitas WHERE tipo = 'SALGADO' ORDER BY id_receita DESC");
    $stmtSalgadas->execute();
    $receitasSalgadas = $stmtSalgadas->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magical Bites | Receitas Disney ✨</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Mantendo seu CSS original que está excelente */
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins', sans-serif;}
        body{background:#f3f1ff;color:white;}
        .navbar{width:100%;background:#1f4396;padding:14px 25px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 4px 18px rgba(0,0,0,0.20);position:sticky;top:0;z-index:100;}
        .logo img{height:60px;width:auto;}
        .menu{display:flex;align-items:center;gap:35px;}
        .menu a{color:white;text-decoration:none;font-size:15px;font-weight:500;transition:0.3s;}
        .menu a:hover{color:#ffe45c;}
        .hero{width:100%;height:620px;position:relative;background-size:cover;background-position:center;transition:background-image 0.6s ease-in-out;}
        .hero-content{position:absolute;left:90px;top:50%;transform:translateY(-50%);max-width:470px;}
        .hero-content h1{font-size:72px;line-height:1.05;margin-bottom:22px;font-weight:700;}
        .hero-btn{display:inline-block;padding:18px 30px;background:#ffe45c;color:#1e0061;border-radius:14px;text-decoration:none;font-weight:700;transition:0.3s;}
        .search-box{display:flex;justify-content:center;margin:45px 20px;}
        .search-box input{padding:14px 18px;width:340px;border-radius:30px;border:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);}
        .search-box button{padding:14px 24px;border-radius:30px;border:none;background:#2d4fd7;color:white;cursor:pointer;font-weight:600;}
        .container{width:88%;margin:auto;}
        .titulo{margin:40px 0 25px;font-size:30px;color:#ffd369;display:flex;align-items:center;gap:10px;}
        .lista{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:28px;margin-bottom:50px;}
        .card{background:white;color:black;border-radius:22px;overflow:hidden;text-decoration:none;transition:0.35s;box-shadow:0 8px 22px rgba(0,0,0,0.20);}
        .card:hover{transform:translateY(-8px);}
        .card img{width:100%;height:220px;object-fit:cover;}
        .tag{width:fit-content;margin:15px auto 10px;padding:6px 14px;border-radius:20px;color:white;font-size:11px;font-weight:700;}
        .doce{background:#ff69b4;}
        .salgado{background:#4CAF50;}
        .card p{padding:0 18px 22px;text-align:center;font-weight:600;}
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo"><img src="logo_menu.png" alt="Magical Bites"></div>
    <div class="menu">
        <a href="index.php">Início</a>
        <a href="#">Receitas</a>
        <a href="#">Sobre</a>
        <a href="#">Favoritos</a>
        <a href="login.html">👤</a>
    </div>
</div>

<div class="hero" style="background-image: url('cookies_banner.png');">
    <div class="hero-content">
        <h1>Receitas mágicas Disney</h1>
        <p>Descubra pratos incríveis direto dos filmes!</p>
        <a href="#" class="hero-btn">Explorar ✨</a>
    </div>
</div>

<div class="search-box">
    <input type="text" placeholder="🔍 Procure uma receita...">
    <button>Buscar</button>
</div>

<div class="container">
    <h2 class="titulo">🍰 Receitas Doces</h2>
    <div class="lista">
        <?php if(count($receitasDoces) > 0): ?>
            <?php foreach($receitasDoces as $row): ?>
                <a href="receita.php?id=<?= $row['id_receita'] ?>" class="card">
                    <img src="<?= htmlspecialchars($row['imagem']) ?>" alt="<?= htmlspecialchars($row['nome_receita']) ?>">
                    <div class="tag doce">DOCE</div>
                    <p><?= htmlspecialchars($row['nome_receita']) ?></p>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">Nenhuma receita doce cadastrada ainda.</p>
        <?php endif; ?>
    </div>

    <h2 class="titulo">🍝 Receitas Salgadas</h2>
    <div class="lista">
        <?php if(count($receitasSalgadas) > 0): ?>
            <?php foreach($receitasSalgadas as $row): ?>
                <a href="receita.php?id=<?= $row['id_receita'] ?>" class="card">
                    <img src="<?= htmlspecialchars($row['imagem']) ?>" alt="<?= htmlspecialchars($row['nome_receita']) ?>">
                    <div class="tag salgado">SALGADO</div>
                    <p><?= htmlspecialchars($row['nome_receita']) ?></p>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">Nenhuma receita salgada cadastrada ainda.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Slider simples
    const hero = document.querySelector(".hero");
    const banners = ["cookies_banner.png", "beignet_banner.png"];
    let i = 0;
    setInterval(() => {
        i = (i + 1) % banners.length;
        hero.style.backgroundImage = `url(${banners[i]})`;
    }, 4000);
</script>

</body>
</html>