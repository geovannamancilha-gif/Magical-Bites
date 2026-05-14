<?php
// Conexão com o banco
$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", ""); // Ajuste a senha se necessário

// Busca receitas doces
$stmtDoces = $pdo->query("SELECT * FROM receitas WHERE tipo = 'DOCE'");
$receitasDoces = $stmtDoces->fetchAll(PDO::FETCH_ASSOC);

// Lógica para detectar se veio de um cadastro ou login
$status = isset($_GET['status']) ? $_GET['status'] : "";
$erro_msg = isset($_GET['erro']) ? $_GET['erro'] : (isset($_GET['msg']) ? $_GET['msg'] : "");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magical Bites | Receitas Disney ✨</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- RESET E ESTILOS GERAIS --- */
        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body{ background:#f3f1ff; color:#333; overflow-x: hidden; }
        
        /* --- NAVBAR --- */
        .navbar{ width:100%; background:#1f4396; padding:14px 25px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 4px 18px rgba(0,0,0,0.20); position:sticky; top:0; z-index:100; }
        .logo img{ height:60px; width:auto; margin-top: -10px; }
        .menu{ display:flex; align-items:center; gap:35px; }
        .menu a{ color:white; text-decoration:none; font-size:15px; font-weight:500; transition:0.3s; cursor: pointer; }
        .menu a:hover{ color:#ffe45c; }

        /* --- HERO --- */
        .hero{ width:100%; height:620px; overflow:hidden; position:relative; background-size:cover; background-position:center; transition:background-image 0.6s ease-in-out; }
        .hero-content{ position:absolute; left:90px; top:50%; transform:translateY(-50%); max-width:470px; color: white; }
        .hero-content h1{ font-size:72px; line-height:1.05; margin-bottom:22px; font-weight:700; }
        .hero-content p{ font-size:22px; line-height:1.6; color:#ededed; margin-bottom:32px; }
        .hero-btn{ display:inline-block; padding:18px 30px; background:#ffe45c; color:#1e0061; border-radius:14px; text-decoration:none; font-weight:700; transition:0.3s; font-size:16px; }

        /* --- NOVO LAYOUT DOS CARDS (RESPONSIVO E CENTRALIZADO) --- */
        .container {
            width: 100%; 
            max-width: 1250px; 
            margin: 0 auto; 
            padding: 0 30px; 
        }

        .titulo { 
            margin: 50px 0 30px; 
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
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.20);
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
            letter-spacing: 0.5px;
        }

        .doce { background: #ff69b4; }
        .salgado { background: #4CAF50; }

        .card p {
            padding: 0 20px 25px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        /* --- SISTEMA DE MODAIS (CARDS FLUTUANTES) --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            z-index: 999;
        }

        .card-flutuante {
            display: none;
            position: fixed;
            top: 85px;
            right: 25px;
            width: 360px;
            background: white;
            border-radius: 28px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            padding: 30px;
            z-index: 1000;
            color: #333;
            animation: fadeInSlide 0.3s ease-out;
        }

        @keyframes fadeInSlide {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-fechar { background: none; border: none; font-size: 26px; cursor: pointer; color: #999; }

        .input-group-modal { position: relative; margin-bottom: 15px; }
        .input-group-modal i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #1f4396; }
        .input-group-modal input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 12px; outline: none; }

        .btn-acao { width: 100%; padding: 14px; background: #1f4396; color: white; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-acao:hover { background: #162f6b; transform: scale(1.02); }

        .status-icon { font-size: 50px; margin-bottom: 15px; text-align: center; }
        .footer-modal { margin-top: 20px; font-size: 13px; text-align: center; color: #666; }
        .footer-modal a { color: #1f4396; text-decoration: none; font-weight: 600; cursor: pointer; }
    </style>
</head>

<body>

<div class="navbar">
    <div class="logo"><img src="logo_menu.png" alt="Magical Bites"></div>
    <div class="menu">
        <a href="index.php">Início</a>
        <a href="#">Receitas</a>
        <a href="javascript:void(0)" onclick="alternarModais('login')">👤 Perfil</a>
    </div>
</div>

<div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>

<div id="modalCadastro" class="card-flutuante">
    <div class="card-header">
        <img src="logo.png" style="width: 100px;">
        <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
    </div>
    <h2 style="color:#1f4396">Crie sua conta</h2>
    <p style="font-size:14px; color:#666; margin-bottom:20px">Junte-se à nossa comunidade!</p>
    <form action="cadastrar.php" method="POST">
        <div class="input-group-modal"><i class="fa fa-user"></i><input type="text" name="nome" placeholder="Nome completo" required></div>
        <div class="input-group-modal"><i class="fa fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
        <div class="input-group-modal"><i class="fa fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
        <button type="submit" class="btn-acao">Cadastrar ✨</button>
    </form>
    <div class="footer-modal">Já tem conta? <a onclick="alternarModais('login')">Faça login</a></div>
</div>

<div id="modalLogin" class="card-flutuante">
    <div class="card-header">
        <img src="logo.png" style="width: 100px;">
        <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
    </div>
    <h2 style="color:#1f4396">Bem-vindo!</h2>
    <p style="font-size:14px; color:#666; margin-bottom:20px">Acesse sua conta mágica.</p>
    <form action="login_processo.php" method="POST">
        <div class="input-group-modal"><i class="fa fa-envelope"></i><input type="email" name="email" placeholder="E-mail" required></div>
        <div class="input-group-modal"><i class="fa fa-lock"></i><input type="password" name="senha" placeholder="Senha" required></div>
        <button type="submit" class="btn-acao">Entrar ✨</button>
    </form>
    <div class="footer-modal">Novo aqui? <a onclick="alternarModais('cadastro')">Crie uma conta</a></div>
</div>

<?php if ($status != ""): ?>
<div id="modalStatus" class="card-flutuante" style="display: block; text-align: center;">
    <div class="card-header">
        <span></span> <button class="btn-fechar" onclick="fecharStatus()">&times;</button>
    </div>
    <?php if ($status == "sucesso"): ?>
        <div class="status-icon">✅</div>
        <h2 style="color:#28a745">Sucesso!</h2>
        <p>Sua conta foi criada com sucesso. Agora é só brilhar na cozinha!</p>
        <button class="btn-acao" onclick="alternarModais('login')">Ir para Login</button>
    <?php else: ?>
        <div class="status-icon">❌</div>
        <h2 style="color:#dc3545">Ops! Algo falhou.</h2>
        <p>Erro: <?php echo htmlspecialchars($erro_msg); ?></p>
        <button class="btn-acao" onclick="alternarModais('cadastro')">Tentar novamente</button>
    <?php endif; ?>
</div>
<script>document.getElementById('modalOverlay').style.display = 'block';</script>
<?php endif; ?>

<div class="hero">
    <div class="hero-content">
        <h1>Receitas mágicas inspiradas na Disney</h1>
        <p>Descubra pratos incríveis direto dos filmes!</p>
        <a href="#" class="hero-btn">Explorar ✨</a>
    </div>
</div>

<div class="container">
    <h2 class="titulo">🍰 Receitas Doces</h2>
    <div class="lista">
       <a href="receita.php?id=2" class="card">
            <img src="trufas_frozen.png">
            <div class="tag doce">DOCE</div>
            <p>❄️ Trufas de Frozen</p>
       </a>
       <a href="receita.php?id=3" class="card">
            <img src="cookies_enrolados.png">
            <div class="tag doce">DOCE</div>
            <p>🦎 Cookies de Enrolados</p>
       </a>
       <a href="receita.php?id=1" class="card">
            <img src="torta_maca_branca_neve.png">
            <div class="tag doce">DOCE</div>
            <p>🍎 Torta de Maçã da Branca de Neve</p>
       </a>
       <a href="receita.php?id=4" class="card">
            <img src="beignet_princesa_sapo.png">
            <div class="tag doce">DOCE</div>
            <p>☕ Beignet da Princesa e o Sapo</p>
       </a>
       <a href="receita.php?id=5" class="card">
            <img src="biscoitos_alice.png">
            <div class="tag doce">DOCE</div>
            <p>♠️ Biscoitos de Alice no País das Maravilhas</p>
       </a>
    </div>

    <h2 class="titulo">🍝 Receitas Salgadas</h2>
    <div class="lista">
        <a href="receita.php?id=6" class="card">
            <img src="ratatouille.png">
            <div class="tag salgado">SALGADO</div>
            <p>🐭 Ratatouille</p>
        </a>
        <a href="receita.php?id=7" class="card">
            <img src="arepas_encanto.png">
            <div class="tag salgado">SALGADO</div>
            <p>🌽 Arepas con queso de Encanto</p>
        </a>
        <a href="receita.php?id=8" class="card">
            <img src="macarrao_pesto_luca.png">
            <div class="tag salgado">SALGADO</div>
            <p>🍃 Macarrão ao molho pesto de Luca</p>
        </a>
        <a href="receita.php?id=9" class="card">
            <img src="espaguete_almondegas.png">
            <div class="tag salgado">SALGADO</div>
            <p>🍝 Espaguete com Almôndegas da Dama e o Vagabundo</p>
        </a>
    </div>
</div>

<script>
function fecharTodosModais() {
    document.getElementById('modalOverlay').style.display = 'none';
    document.getElementById('modalCadastro').style.display = 'none';
    document.getElementById('modalLogin').style.display = 'none';
    if(document.getElementById('modalStatus')) document.getElementById('modalStatus').style.display = 'none';
}

function fecharStatus() {
    fecharTodosModais();
    window.history.replaceState({}, document.title, "index.php"); // Limpa a URL
}

function alternarModais(tipo) {
    fecharTodosModais();
    document.getElementById('modalOverlay').style.display = 'block';
    if(tipo === 'cadastro') document.getElementById('modalCadastro').style.display = 'block';
    if(tipo === 'login') document.getElementById('modalLogin').style.display = 'block';
}

// Banner rotativo
const hero = document.querySelector(".hero");
const banners = ["cookies_banner.png", "beignet_banner.png"];
let idx = 0;
setInterval(() => {
    idx = (idx + 1) % banners.length;
    hero.style.backgroundImage = `url(${banners[idx]})`;
}, 4000);
hero.style.backgroundImage = `url(${banners[0]})`;
</script>
</body>
</html>