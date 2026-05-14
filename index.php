<?php
// Conexão com o banco (igual ao do receita.php)
$pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "Home@spSENAI2025!");

// Busca todas as receitas doces
$stmtDoces = $pdo->query("SELECT * FROM receitas WHERE tipo = 'DOCE'");
$receitasDoces = $stmtDoces->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Magical Bites | Receitas Disney ✨</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

body{
    background:#f3f1ff;
    color:white;
}

/* NAVBAR */
.navbar{
    width:100%;
    background:#1f4396;
    padding:14px 25px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    box-shadow:0 4px 18px rgba(0,0,0,0.20);
    position:sticky;
    top:0;
    z-index:100;
}

.logo{
    position:relative;
    width:180px;
    height:40px;
}

.logo img{
    position:absolute;
    top:-8px;
    left:0;
    height:60px;
    width:auto;
}

.menu{
    display:flex;
    align-items:center;
    gap:35px;
}

.menu a{
    color:white;
    text-decoration:none;
    font-size:15px;
    font-weight:500;
    transition:0.3s;
}

.menu a:hover{
    color:#ffe45c;
}

/* HERO SLIDER */
.hero{
    width:100%;
    height:620px;
    margin:0;
    border-radius:0;
    overflow:hidden;
    position:relative;

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    transition:background-image 0.6s ease-in-out;
}

/* TEXTO DO BANNER */
.hero-content{
    position:absolute;
    left:90px;
    top:50%;
    transform:translateY(-50%);
    max-width:470px;
}

.hero-content h1{
    font-size:72px;
    line-height:1.05;
    margin-bottom:22px;
    font-weight:700;
}

.hero-content p{
    font-size:22px;
    line-height:1.6;
    color:#ededed;
    margin-bottom:32px;
}

.hero-btn{
    display:inline-block;
    padding:18px 30px;
    background:#ffe45c;
    color:#1e0061;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
    transition:0.3s;
    font-size:16px;
}

.hero-btn:hover{
    transform:translateY(-3px);
    background:#fff08f;
}

/* BOLINHAS */
.dots{
    position:absolute;
    bottom:24px;
    left:50%;
    transform:translateX(-50%);
    display:flex;
    gap:10px;
}

.dot{
    width:11px;
    height:11px;
    border-radius:50%;
    background:rgba(255,255,255,0.45);
    transition:0.3s;
}

.dot.active{
    background:white;
    transform:scale(1.2);
}

/* BUSCA */
.search-box{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
    margin:45px 20px 40px;
    flex-wrap:wrap;
}

.search-box input{
    padding:14px 18px;
    width:340px;
    border:none;
    border-radius:30px;
    outline:none;
    background:white;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
    font-size:14px;
    color:black;
}

.search-box input::placeholder{
    color:#777;
}

.search-box button{
    padding:14px 24px;
    border:none;
    border-radius:30px;
    background:#2d4fd7;
    color:white;
    cursor:pointer;
    transition:0.3s;
    font-weight:600;
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
}

.search-box button:hover{
    background:#1f3bb3;
    transform:translateY(-2px);
}

/* CONTAINER */
.container{
    width:88%;
    margin:auto;
}

/* TÍTULOS */
.titulo{
    margin:40px 0 25px;
    font-size:30px;
    font-weight:700;
    color:#ffd369;
    display:flex;
    align-items:center;
    gap:10px;
}

/* GRID */
.lista{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:28px;
    margin-bottom:50px;
}

/* CARDS */
.card{
    background:white;
    color:black;
    border-radius:22px;
    overflow:hidden;
    text-decoration:none;
    transition:0.35s;
    box-shadow:0 8px 22px rgba(0,0,0,0.20);
    position:relative;
}

.card:hover{
    transform:translateY(-8px);
    box-shadow:0 14px 28px rgba(0,0,0,0.28);
}

.card img{
    width:100%;
    height:220px;
    object-fit:cover;
    transition:0.3s;
}

.card:hover img{
    transform:scale(1.03);
}

/* TAG */
.tag{
    width:fit-content;
    margin:15px auto 10px;
    padding:6px 14px;
    border-radius:20px;
    color:white;
    font-size:11px;
    font-weight:700;
    letter-spacing:0.5px;
}

.doce{
    background:#ff69b4;
}

.salgado{
    background:#4CAF50;
}

/* TEXTO */
.card p{
    padding:0 18px 22px;
    text-align:center;
    font-weight:600;
    font-size:15px;
    line-height:1.4;
}

/* RESPONSIVO */
@media(max-width:900px){

    .navbar{
        padding:16px 20px;
        flex-direction:column;
        gap:15px;
    }

    .menu{
        gap:18px;
        flex-wrap:wrap;
        justify-content:center;
    }

    .hero{
        height:500px;
    }

    .hero-content{
        left:40px;
        max-width:320px;
    }

    .hero-content h1{
        font-size:46px;
    }

    .hero-content p{
        font-size:17px;
    }
}

@media(max-width:700px){

    .titulo{
        font-size:24px;
    }

    .search-box input{
        width:100%;
    }

    .container{
        width:92%;
    }

    .card img{
        height:200px;
    }

    .hero{
        height:400px;
    }

    .hero-content{
        left:25px;
        max-width:260px;
    }

    .hero-content h1{
        font-size:34px;
    }

    .hero-content p{
        font-size:14px;
    }

    .hero-btn{
        padding:13px 20px;
        font-size:14px;
    }

    .profile-icon{
        font-size:22px;
        text-decoration:none;
        transition:0.3s;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .profile-icon:hover{
        transform:scale(1.1);
    }
}

</style>

</head>

<body>

<!-- NAVBAR -->
<div class="navbar">

    <div class="logo">
        <img src="logo_menu.png" alt="Magical Bites">  
    </div>

    <div class="menu">
        <a href="#">Início</a>
        <a href="#">Receitas</a>
        <a href="#">Sobre</a>
        <a href="#">Favoritos</a>

        <a href="login.html" class="profile-icon">
            👤
        </a>
    </div>

</div>

<!-- HERO -->
<div class="hero">

    <div class="hero-content">

        <h1>
            Receitas mágicas inspiradas na Disney
        </h1>

        <p>
            Descubra pratos incríveis direto dos filmes que amamos!
        </p>

        <a href="#" class="hero-btn">
            Explorar receitas ✨
        </a>

    </div>

    <div class="dots">
        <div class="dot active"></div>
        <div class="dot"></div>
    </div>

</div>

<!-- BUSCA -->
<div class="search-box">
    <input type="text" placeholder="🔍 Procure uma receita mágica...">
    <button>Buscar</button>
</div>

<div class="container">

    <!-- DOCES -->
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

    <!-- SALGADAS -->
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

<!-- SCRIPT SLIDER -->
<script>

const hero = document.querySelector(".hero");
const dots = document.querySelectorAll(".dot");

const banners = [
    "cookies_banner.png",
    "beignet_banner.png"
];

let index = 0;

function trocarBanner(){

    hero.style.backgroundImage = `url(${banners[index]})`;

    dots.forEach(dot => {
        dot.classList.remove("active");
    });

    dots[index].classList.add("active");

    index++;

    if(index >= banners.length){
        index = 0;
    }
}

trocarBanner();

setInterval(trocarBanner, 4000);

</script>

</body>
</html>