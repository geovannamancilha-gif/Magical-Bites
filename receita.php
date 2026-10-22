<?php
session_start();

// Configurações de conexão com a base de dados
$host = 'localhost';
$dbname = 'magical_bites';
$user = 'root'; 
$pass = "";     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id_receita = isset($_GET['id']) ? (int)$_GET['id'] : 1;

    $stmt = $pdo->prepare("SELECT * FROM receitas WHERE id_receita = :id");
    $stmt->bindParam(':id', $id_receita, PDO::PARAM_INT);
    $stmt->execute();
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receita) {
        die("<h1 style='color:#fff; text-align:center; margin-top:50px;'>Receita não encontrada na base de dados para o ID: $id_receita!</h1>");
    }

    // Processamento de listas
    $lista_ingredientes = array_filter(array_map('trim', explode(";", $receita['ingredientes'])));
    $passos_preparo = array_filter(array_map('trim', preg_split('/(?=\d+\.\s)/', $receita['modo_preparo'])));

    // Definição de cores por categoria
    $tipo_formatado = ucfirst(strtolower($receita['tipo'] ?? 'Salgado'));

    // VERIFICAÇÃO SE A RECEITA ESTÁ FAVORITADA PELO USUÁRIO LOGADO
    $isFavorito = false;
    if (isset($_SESSION['usuario'])) {
        $idUsuario = $_SESSION['usuario']['id'] ?? $_SESSION['usuario']['id_usuario'] ?? null;
        if ($idUsuario) {
            $stmtFav = $pdo->prepare("SELECT id_favoritos FROM favoritar WHERE id_usuario = :user AND id_receita = :receita");
            $stmtFav->execute([
                ':user' => $idUsuario,
                ':receita' => $id_receita
            ]);
            $isFavorito = (bool)$stmtFav->fetch();
        }
    }

} catch(PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}

// BUSCA AVATARES CASO O UTILIZADOR ESTEJA LOGADO (PARA O MODAL DE PERFIL)
$avataresPadrao = [];
if (isset($_SESSION['usuario'])) {
    try {
        $stmtAvatares = $pdo->query("SELECT * FROM avatar_predefinido");
        $avataresPadrao = $stmtAvatares->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $avataresPadrao = [];
    }
}

// FUNÇÃO INTELIGENTE DE CARREGAMENTO DE IMAGENS
function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = ['pratos', 'filme', 'logo', 'slider', 'perfis', 'avatares', 'img/perfis', 'img/avatares', 'fundo'];
    $extensoes = ['', '.png', '.jpg', '.jpeg', '.webp'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminhoCompleto = "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoCompleto)) return $caminhoCompleto;

            $caminhoRaiz = $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoRaiz)) return $caminhoRaiz;
        }
    }

    if (file_exists("img/" . $nomeImagem)) return "img/" . $nomeImagem;
    return "img/logo/logo_menu.png";
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($receita['nome_receita']) ?> | Magical Bites</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html { scroll-behavior: smooth; }

        /* --- FUNDO DINÂMICO E RESPONSIVO COM FUNDO_RECEITAS --- */
        body { 
            background-color: #0b1329;
            background-image: url('<?= buscarImagem("fundo_receitas") ?>');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #ffffff; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        @media (max-width: 768px) {
            body {
                background-attachment: scroll;
                background-size: cover;
            }
        }

        /* --- NAVBAR ORIGINAL PADRONIZADA --- */
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
            margin-top: 0;
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

        .menu a:hover, .menu a.ativo, .menu a.active {
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

        /* --- CONTAINER PRINCIPAL --- */
        .container {
            max-width: 1150px;
            margin: 45px auto 70px;
            padding: 0 30px;
        }

        /* HERO RECEITA (IMAGEM À ESQUERDA + INFO À DIREITA) */
        .hero-receita {
            display: grid;
            grid-template-columns: 480px 1fr;
            gap: 45px;
            align-items: center;
            margin-bottom: 60px;
        }

        .hero-imagem {
            width: 100%;
            height: 360px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
        }

        .hero-imagem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hero-info .titulo {
            font-size: 36px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            text-shadow: 0 3px 10px rgba(0,0,0,0.6);
        }

        /* META-INFO INLINE (HORA, AVALIAÇÃO, TAG) */
        .meta-info {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            color: #ffffff;
            font-weight: 700;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i.fa-clock { color: #ffffff; }
        .meta-item i.fa-star { color: #ffffff; }

        .tag-categoria {
            background: #fff6a9;
            color: #0b1329;
            padding: 3px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            margin-left: 5px;
        }

        .descricao {
            font-size: 14px;
            color: #e2e8f0;
            line-height: 1.55;
            margin-top: 4px;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        /* BOTOES DE AÇÃO */
        .acoes {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .btn-acao-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: 0.3s ease;
        }

        .icone-circulo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #ffffff;
            transition: 0.3s ease;
        }

        .btn-acao-pill:hover .icone-circulo {
            background: #ffffff;
            color: #0b1329;
            transform: scale(1.08);
        }

        .btn-acao-pill.favoritado .icone-circulo {
            background: #ff528a;
            border-color: #ff528a;
            color: #ffffff;
        }

        /* GRID PRINCIPAL (MODO DE PREPARO + INGREDIENTES) */
        .conteudo-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 50px;
            margin-bottom: 70px;
        }

        .secao-titulo {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 22px;
            text-shadow: 0 3px 8px rgba(0,0,0,0.6);
        }

        /* MODO DE PREPARO */
        .lista-preparo {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .lista-preparo li {
            color: #f1f5f9;
            font-size: 14.5px;
            line-height: 1.6;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .lista-preparo li strong {
            font-weight: 800;
            color: #ffffff;
        }

        /* INGREDIENTES */
        .lista-ingredientes {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .lista-ingredientes li {
            position: relative;
            padding-left: 20px;
            color: #f1f5f9;
            font-size: 14.5px;
            line-height: 1.5;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .lista-ingredientes li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #ffffff;
            font-size: 18px;
            top: -2px;
        }

        /* SECÇÃO DE AVALIAÇÃO INFERIOR */
        .secao-avaliacao {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .secao-avaliacao h3 {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            text-shadow: 0 3px 8px rgba(0,0,0,0.6);
        }

        .estrelas-avaliacao {
            display: flex;
            justify-content: center;
            gap: 8px;
            font-size: 26px;
            color: #ffffff;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .estrelas-avaliacao i:hover,
        .estrelas-avaliacao i:hover ~ i {
            color: #fbbf24;
        }

        .subtitulo-avaliacao {
            color: #cbd5e1;
            font-size: 13.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        /* MODAIS */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); z-index: 999; }
        .card-flutuante { display: none; position: fixed; top: 80px; right: 25px; width: 380px; max-height: calc(100vh - 100px); overflow-y: auto; background: #ffffff; border-radius: 28px; box-shadow: 0 15px 50px rgba(0,0,0,0.5); padding: 28px; z-index: 1000; color: #333; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-fechar { background: none; border: none; font-size: 26px; cursor: pointer; color: #999; }
        .input-group-modal { position: relative; margin-bottom: 15px; }
        .input-group-modal i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #1f4396; }
        .input-group-modal input { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 12px; outline: none; }
        .btn-acao { width: 100%; padding: 14px; background: #1f4396; color: white; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .perfil-avatar-header { text-align: center; margin-bottom: 15px; }
        .perfil-avatar-header img { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 3px solid #1f4396; }
        .grid-avatares-modal { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; max-height: 140px; overflow-y: auto; padding: 5px; margin: 10px 0; }
        .avatar-item { width: 48px; height: 48px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; overflow: hidden; }
        .avatar-item img { width: 100%; height: 100%; object-fit: cover; }
        .btn-logout-modal { display: block; text-align: center; margin-top: 15px; color: #f44336; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* RESPONSIVIDADE */
        @media (max-width: 900px) {
            .hero-receita {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            .hero-imagem {
                height: 280px;
            }
            .conteudo-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR ORIGINAL -->
    <div class="navbar">
        <div class="logo">
            <a href="index.php"><img src="<?= buscarImagem('logo_menu.jpg') ?>" alt="Magical Bites"></a>
        </div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas" class="active">Receitas</a>
            <a href="favoritos.php">Favoritos</a>
            <a href="filmes.php">Filmes</a>

            <?php if (isset($_SESSION['usuario'])): ?>
                <a href="javascript:void(0)" onclick="alternarModais('perfil')">
                    <img src="<?= buscarImagem($_SESSION['usuario']['foto']) ?>" class="nav-user-avatar" onerror="this.src='img/logo/logo_menu.png'">
                    <?= htmlspecialchars(explode(' ', $_SESSION['usuario']['nome'])[0]) ?>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="alternarModais('login')"><i class="fa-solid fa-user"></i> Perfil</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- OVERLAY E MODAL PERFIL -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>
    <?php if (isset($_SESSION['usuario'])): ?>
    <div id="modalPerfil" class="card-flutuante">
        <div class="card-header">
            <img src="<?= buscarImagem('logo.png') ?>" style="width: 90px;">
            <button class="btn-fechar" onclick="fecharTodosModais()">&times;</button>
        </div>
        <div class="perfil-avatar-header">
            <img src="<?= buscarImagem($_SESSION['usuario']['foto']) ?>" id="modalPreviewFoto" onerror="this.src='img/logo/logo_menu.png'">
            <h3 style="color:#1f4396; margin-top:8px; font-size:18px;"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></h3>
            <p style="font-size:12px; color:#777;"><?= htmlspecialchars($_SESSION['usuario']['email']) ?></p>
        </div>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="atualizar_perfil">
            <input type="hidden" name="avatar_predefinido" id="modalInputAvatar" value="">
            <div class="input-group-modal"><i class="fa fa-user"></i><input type="text" name="nome" value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" required placeholder="Nome"></div>
            <label style="font-size:12px; font-weight:600; color:#1f4396;">Avatar Disney</label>
            <div class="grid-avatares-modal">
                <?php foreach ($avataresPadrao as $avatar): ?>
                    <div class="avatar-item" onclick="selecionarAvatarModal('<?= $avatar['caminho_imagem'] ?>', this)">
                        <img src="<?= buscarImagem($avatar['caminho_imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                    </div>
                <?php endforeach; ?>
            </div>
            <label style="font-size:12px; font-weight:600; color:#1f4396; display:block; margin-top:10px;">Ou foto própria:</label>
            <input type="file" name="foto_upload" accept="image/*" style="font-size:12px; margin-top:5px; width:100%;" onchange="limparAvatarModal()">
            <button type="submit" class="btn-acao" style="margin-top:18px;">Salvar Alterações</button>
        </form>
        <a href="logout.php" class="btn-logout-modal"><i class="fa-solid fa-right-from-bracket"></i> Sair da Conta</a>
    </div>
    <?php endif; ?>

    <!-- CONTEÚDO PRINCIPAL DA RECEITA -->
    <div class="container">

        <!-- SEÇÃO HERO (IMAGEM + CABEÇALHO) -->
        <div class="hero-receita">
            <div class="hero-imagem">
                <img src="<?= buscarImagem($receita['imagem']) ?>" alt="<?= htmlspecialchars($receita['nome_receita']) ?>" onerror="this.src='img/logo/logo_menu.png'">
            </div>

            <div class="hero-info">
                <h1 class="titulo"><?= htmlspecialchars($receita['nome_receita']) ?></h1>

                <div class="meta-info">
                    <div class="meta-item">
                        <i class="fa-solid fa-clock"></i>
                        <span><?= htmlspecialchars($receita['tempo_preparo']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-star"></i>
                        <span><?= htmlspecialchars($receita['avaliacao'] ?? '4.9') ?> (128 avaliações)</span>
                    </div>
                    <span class="tag-categoria"><?= htmlspecialchars($tipo_formatado) ?></span>
                </div>

                <p class="descricao"><?= htmlspecialchars($receita['descricao']) ?></p>

                <div class="acoes">
                    <a href="favoritar.php?id=<?= $receita['id_receita'] ?>" class="btn-acao-pill <?= $isFavorito ? 'favoritado' : '' ?>">
                        <div class="icone-circulo"><i class="fa-solid fa-heart"></i></div>
                        <span><?= $isFavorito ? 'Favoritado' : 'Favoritar' ?></span>
                    </a>

                    <a href="#modo-preparo" class="btn-acao-pill">
                        <div class="icone-circulo"><i class="fa-solid fa-utensils"></i></div>
                        <span>Fazer agora</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- CONTEÚDO EM DUAS COLUNAS (PREPARO E INGREDIENTES) -->
        <div class="conteudo-grid" id="modo-preparo">
            
            <!-- COLUNA MODO DE PREPARO -->
            <div>
                <h2 class="secao-titulo">Modo de preparo:</h2>
                <ul class="lista-preparo">
                    <?php 
                    $num = 1;
                    foreach($passos_preparo as $passo): 
                        $textoLimpo = preg_replace('/^\d+\.\s*/', '', $passo);
                    ?>
                        <li><strong><?= $num ?>.</strong> <?= htmlspecialchars($textoLimpo) ?></li>
                    <?php 
                        $num++;
                    endforeach; 
                    ?>
                </ul>
            </div>

            <!-- COLUNA INGREDIENTES -->
            <div>
                <h2 class="secao-titulo">Ingredientes:</h2>
                <ul class="lista-ingredientes">
                    <?php foreach($lista_ingredientes as $ingrediente): ?>
                        <li><?= htmlspecialchars($ingrediente) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

        <!-- SEÇÃO DE AVALIAÇÃO DA RECEITA -->
        <div class="secao-avaliacao">
            <h3>COMO FICOU ESSA RECEITA?</h3>
            <div class="estrelas-avaliacao">
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
            </div>
            <p class="subtitulo-avaliacao">Clique aqui para avaliar a receita</p>
        </div>

    </div>

    <!-- JAVASCRIPT DOS MODAIS DE CONTA/PERFIL -->
    <script>
    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
    }

    function alternarModais(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
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
    </script>

</body>
</html>