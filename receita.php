<?php
// Configurações de conexão com a base de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
<<<<<<< HEAD
//$pass = 'Home@spSENAI2025!';     
$pass = "";     
=======
//$pass = 'Home@spSENAI2025!';  
$pass = ""; //quando trocar de sala, reativar a senha
>>>>>>> 3c4b2e5550c53afbcbdb4dfdd724e812fb587651

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

    $lista_ingredientes = explode(";", $receita['ingredientes']);

    // LÓGICA SÉNIOR: Definir a classe CSS da cor com base no tipo da receita
    $classe_tag = 'tag-doce'; // Rosa por padrão
    if ($receita['tipo'] == 'SALGADO') {
        $classe_tag = 'tag-salgado'; // Verde
    } elseif ($receita['tipo'] == 'BEBIDA') {
        $classe_tag = 'tag-bebida'; // Azul
    }

} catch(PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($receita['nome_receita']) ?> | Magical Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins', sans-serif;}
        body{ background:#3d4f8f; color:white; }

        .navbar { width:100%; background:#1f4396; padding:8px 25px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 4px 18px rgba(0,0,0,0.20); position:sticky; top:0; z-index:100; }
        .logo img { height:60px; width:auto; margin-top: 1px; }
        .menu { display:flex; align-items:center; gap:35px; }
        .menu a { color:white; text-decoration:none; font-size:15px; font-weight:500; transition:0.3s; cursor: pointer; }
        .menu a:hover { color:#ffe45c; }

        .container{ width:88%; margin:45px auto; }  
          
        .receita-card{ background:white; border-radius:28px; overflow:hidden; display:grid; grid-template-columns:1.1fr 1fr; box-shadow:0 12px 30px rgba(0,0,0,0.25); }
        .imagem img{width:100%;height:100%;object-fit:cover;}
        .conteudo{padding:40px;color:#1f1f1f;}
        .voltar-topo{display:inline-block;margin-bottom:22px;text-decoration:none;color:#5a5a5a;font-weight:600;transition:0.3s;}
        .voltar-topo:hover{color:#2d4fd7;}
        .titulo{font-size:42px;line-height:1.1;margin-bottom:15px;color:#171717;}
        
        /* ESTILOS DINÂMICOS DAS TAGS */
        .tag{width:fit-content;padding:7px 16px;border-radius:20px;color:white;font-size:12px;font-weight:700;margin-bottom:25px; letter-spacing: 0.5px;}
        .tag-doce { background: #ff69b4; }
        .tag-salgado { background: #4CAF50; }
        .tag-bebida { background: #00bcd4; }

        .descricao{color:#666;line-height:1.7;margin-bottom:28px;}
        .info{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:30px;}
        .info span{background:#f3f3f3;padding:10px 15px;border-radius:14px;font-size:14px;font-weight:600;color:#444;}
        
        .abas{display:flex;gap:25px;margin-bottom:25px;border-bottom:2px solid #ececec;padding-bottom:14px;}
        .abas div{font-weight:600;color:#777;cursor:pointer; transition: 0.3s;}
        .abas div:hover{color:#ff69b4;}
        .abas .ativa{color:#ff69b4;border-bottom:3px solid #ff69b4;padding-bottom:10px;}
        
        .conteudo-aba { display: none; animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        h2{color:#2d4fd7;margin-bottom:16px;font-size:24px;}
        ul{margin-left:20px;margin-bottom:30px;}
        li{margin-bottom:12px;line-height:1.6;color:#444;}
        .preparo{line-height:1.9;color:#444;}
        .botao{display:inline-block;margin-top:35px;padding:14px 24px;border-radius:18px;background:#ff69b4;color:white;text-decoration:none;font-weight:700;transition:0.3s;}
        .botao:hover{background:#ff4fa6;transform:translateY(-2px);}
        
        @media(max-width:950px){.receita-card{grid-template-columns:1fr;}.imagem img{height:320px;}.titulo{font-size:34px;}}
        @media(max-width:700px){.container{width:92%;}.conteudo{padding:28px;}.titulo{font-size:28px;}.abas{gap:15px;font-size:14px;}.info{flex-direction:column;}}
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">
        <a href="index.php"><img src="logo_menu.png" alt="Magical Bites"></a>
    </div>
    <div class="menu">
        <a href="index.php">Início</a>
        <a href="index.php#containerReceitas">Receitas</a>
        <a href="#">Favoritos</a>
        <a href="#">Filmes</a>
        <a href="javascript:void(0)" onclick="alternarModais('login')">👤 Perfil</a>
    </div>
</div>

<div class="container">
    <div class="receita-card">

        <div class="imagem">
            <img src="<?= htmlspecialchars($receita['imagem']) ?>" alt="<?= htmlspecialchars($receita['nome_receita']) ?>" onerror="this.src='logo_menu.png'">
        </div>

        <div class="conteudo">
            <a href="index.php" class="voltar-topo">← Voltar</a>

            <h1 class="titulo"><?= htmlspecialchars($receita['nome_receita']) ?></h1>

            <div class="tag <?= $classe_tag ?>"><?= htmlspecialchars($receita['tipo']) ?></div>

            <p class="descricao"><?= htmlspecialchars($receita['descricao']) ?></p>

            <div class="info">
                <span>Avaliação: <?= htmlspecialchars($receita['avaliacao']) ?></span>
                <span>Tempo: <?= htmlspecialchars($receita['tempo_preparo']) ?></span>
                <span>Dificuldade: <?= htmlspecialchars($receita['dificuldade']) ?></span>
            </div>

            <div class="abas">
                <div class="aba ativa" onclick="mudarAba('tab-ingredientes', this)">Ingredientes</div>
                <div class="aba" onclick="mudarAba('tab-preparo', this)">Modo de preparo</div>
                <div class="aba" onclick="mudarAba('tab-dicas', this)">Dicas</div>
            </div>

            <div id="tab-ingredientes" class="conteudo-aba" style="display: block;">
                <h2>Ingredientes</h2>
                <ul>
                    <?php foreach($lista_ingredientes as $ingrediente): ?>
                        <li><?= htmlspecialchars(trim($ingrediente)) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div id="tab-preparo" class="conteudo-aba">
                <h2>Modo de preparo</h2>
                <p class="preparo">
                    <?= nl2br(htmlspecialchars($receita['modo_preparo'])) ?>
                </p>
            </div>

            <div id="tab-dicas" class="conteudo-aba">
                <h2>Dicas Mágicas</h2>
                <p class="preparo">
                    Para deixar esta receita com ainda mais magia, coloque um pouco de amor e partilhe com quem mais gosta!
                </p>
            </div>

            <a href="favoritar.php?id=<?= $receita['id_receita'] ?>" class="botao">
                Adicionar aos favoritos
            </a>

        </div>
    </div>
</div>

<script>
function mudarAba(idDestino, elementoClicado) {
    let conteudos = document.querySelectorAll('.conteudo-aba');
    conteudos.forEach(function(div) {
        div.style.display = 'none';
    });

    let abas = document.querySelectorAll('.abas div');
    abas.forEach(function(aba) {
        aba.classList.remove('ativa');
    });

    document.getElementById(idDestino).style.display = 'block';
    elementoClicado.classList.add('ativa');
}
</script>

</body>
</html>