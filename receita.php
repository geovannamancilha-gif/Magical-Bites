<?php
// Configurações de conexão com o banco de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; // Coloque seu usuário do banco
//$pass = 'Home@spSENAI2025!';     // Coloque sua senha do banco
$pass = '';     // Coloque sua senha do banco

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // CORREÇÃO: Mudado de 0 para 1 para garantir que ele ache a primeira receita no teste
    $id_receita = isset($_GET['id']) ? (int)$_GET['id'] : 1;

    // Busca as informações da receita no banco de dados
    $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id_receita = :id");
    $stmt->bindParam(':id', $id_receita, PDO::PARAM_INT);
    $stmt->execute();
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se a receita não existir, para a execução
    if (!$receita) {
        die("<h1>Receita não encontrada no banco de dados para o ID: $id_receita!</h1>");
    }

    // Transforma o texto de ingredientes "item1;item2" em uma lista/array
    $lista_ingredientes = explode(";", $receita['ingredientes']);

} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($receita['nome_receita']) ?> | Magical Bites ✨</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins', sans-serif;}
        body{background:#3d4f8f;color:white;}
        .header{background:linear-gradient(135deg,#060220,#2d3f87);padding:24px;text-align:center;font-size:32px;font-weight:700;letter-spacing:1px;box-shadow:0 4px 20px rgba(0,0,0,0.25);}
        .container{width:88%;margin:45px auto;}
        .receita-card{background:white;border-radius:28px;overflow:hidden;display:grid;grid-template-columns:1.1fr 1fr;box-shadow:0 12px 30px rgba(0,0,0,0.25);}
        .imagem img{width:100%;height:100%;object-fit:cover;}
        .conteudo{padding:40px;color:#1f1f1f;}
        .voltar-topo{display:inline-block;margin-bottom:22px;text-decoration:none;color:#5a5a5a;font-weight:600;transition:0.3s;}
        .voltar-topo:hover{color:#2d4fd7;}
        .titulo{font-size:42px;line-height:1.1;margin-bottom:15px;color:#171717;}
        .tag{width:fit-content;padding:7px 16px;border-radius:20px;background:#ff69b4;color:white;font-size:12px;font-weight:700;margin-bottom:25px;}
        .descricao{color:#666;line-height:1.7;margin-bottom:28px;}
        .info{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:30px;}
        .info span{background:#f3f3f3;padding:10px 15px;border-radius:14px;font-size:14px;font-weight:600;color:#444;}
        .abas{display:flex;gap:25px;margin-bottom:25px;border-bottom:2px solid #ececec;padding-bottom:14px;}
        .abas div{font-weight:600;color:#777;cursor:pointer;}
        .abas .ativa{color:#ff69b4;border-bottom:3px solid #ff69b4;padding-bottom:10px;}
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



<div class="header">Magical Bites ✨</div>

<div class="container">
    <div class="receita-card">

        <div class="imagem">
            <img src="<?= htmlspecialchars($receita['imagem']) ?>" alt="<?= htmlspecialchars($receita['nome_receita']) ?>">
        </div>

        <div class="conteudo">
            <a href="principal.html" class="voltar-topo">← Voltar</a>

            <h1 class="titulo"><?= htmlspecialchars($receita['nome_receita']) ?></h1>

            <div class="tag"><?= htmlspecialchars($receita['tipo']) ?></div>

            <p class="descricao"><?= htmlspecialchars($receita['descricao']) ?></p>

            <div class="info">
                <span>⭐ <?= htmlspecialchars($receita['avaliacao']) ?></span>
                <span>⏱️ <?= htmlspecialchars($receita['tempo_preparo']) ?></span>
                <span>👩‍🍳 <?= htmlspecialchars($receita['dificuldade']) ?></span>
            </div>

            <div class="abas">
                <div class="ativa">Ingredientes</div>
                <div>Modo de preparo</div>
                <div>Dicas</div>
            </div>

            <h2>Ingredientes</h2>
            <ul>
                <?php foreach($lista_ingredientes as $ingrediente): ?>
                    <li><?= htmlspecialchars(trim($ingrediente)) ?></li>
                <?php endforeach; ?>
            </ul>

            <h2>Modo de preparo</h2>
            <p class="preparo">
                <?= nl2br(htmlspecialchars($receita['modo_preparo'])) ?>
            </p>

            <a href="favoritar.php?id=<?= $receita['id_receita'] ?>" class="botao">
                ❤️ Adicionar aos favoritos
            </a>

        </div>
    </div>
</div>




</body>
</html>