<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php?status=erro&msg=" . urlencode("Inicie sessão para ver as suas receitas favoritas!"));
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=magical_bites;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

function buscarImagem($nomeImagem) {
    if (empty($nomeImagem)) return "img/logo/logo_menu.png";
    if (file_exists($nomeImagem)) return $nomeImagem;

    $pastas = ['pratos', 'filme', 'logo', 'slider', 'perfis', 'avatares', 'img/perfis', 'img/avatares'];
    $extensoes = ['', '.png', '.jpg', '.jpeg', '.webp'];

    foreach ($pastas as $pasta) {
        foreach ($extensoes as $ext) {
            $caminhoCompleto = "img/" . $pasta . "/" . $nomeImagem . $ext;
            if (file_exists($caminhoCompleto)) return $caminhoCompleto;
        }
    }
    return "img/logo/logo_menu.png";
}

$id_usuario = $_SESSION['usuario']['id'];

// Busca as receitas favoritadas do utilizador na tabela 'favoritar'
$stmt = $pdo->prepare("
    SELECT r.* 
    FROM receitas r
    INNER JOIN favoritar f ON r.id_receita = f.id_receita
    WHERE f.id_usuario = :id_usuario
    ORDER BY f.id_favoritos DESC
");
$stmt->execute([':id_usuario' => $id_usuario]);
$receitasFavoritas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Minhas Receitas Favoritas | Magical Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f3f1ff; color: #333; min-height: 100vh; }

        .navbar {
            width: 100%; background: #294f88; padding: 10px 30px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.20); position: sticky; top: 0; z-index: 100;
        }
        .logo img { height: 50px; width: auto; display: block; }
        .menu { display: flex; align-items: center; gap: 30px; }
        .menu a { color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .menu a:hover, .menu a.ativo { color: #fff6a9ff; }
        .nav-user-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #fff6a9ff; }

        .container { width: 100%; max-width: 1250px; margin: 40px auto; padding: 0 30px; }
        .titulo { margin-bottom: 30px; font-size: 32px; font-weight: 700; color: #1f4396; display: flex; align-items: center; gap: 12px; }

        .lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 35px; }
        .card { background: white; border-radius: 22px; overflow: hidden; text-decoration: none; transition: 0.3s; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12); display: flex; flex-direction: column; position: relative; }
        .card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.20); }
        .card img { width: 100%; height: 220px; object-fit: cover; }
        .tag { width: fit-content; margin: 20px auto 10px; padding: 6px 16px; border-radius: 20px; color: white; font-size: 11px; font-weight: 700; }
        .doce { background: #ff69b4; } .salgado { background: #4CAF50; } .bebida { background: #00bcd4; }
        .card p { padding: 0 20px 25px; text-align: center; font-weight: 600; font-size: 16px; color: #333; }

        .btn-remover-fav {
            position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.9);
            border: none; width: 36px; height: 36px; border-radius: 50%; color: #ff528a;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); transition: 0.2s; z-index: 5;
        }
        .btn-remover-fav:hover { transform: scale(1.1); background: #ffffff; }

        .sem-favoritos { text-align: center; padding: 80px 20px; color: #666; }
        .sem-favoritos i { font-size: 60px; color: #1f4396; margin-bottom: 20px; }
        .sem-favoritos h3 { font-size: 24px; color: #1f4396; margin-bottom: 10px; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 999; }
        .card-flutuante { display: none; position: fixed; top: 80px; right: 25px; width: 380px; max-height: calc(100vh - 100px); overflow-y: auto; background: white; border-radius: 28px; box-shadow: 0 15px 50px rgba(0,0,0,0.3); padding: 28px; z-index: 1000; }
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
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="logo"><a href="index.php"><img src="<?= buscarImagem('logo_menu.jpg') ?>" alt="Magical Bites"></a></div>
        <div class="menu">
            <a href="index.php">Início</a>
            <a href="index.php#containerReceitas">Receitas</a>
            <a href="favoritos.php" class="ativo">Favoritos</a>
            <a href="filmes.php">Filmes</a>

            <a href="javascript:void(0)" onclick="alternarModais('perfil')">
                <img src="<?= buscarImagem($_SESSION['usuario']['foto']) ?>" class="nav-user-avatar" onerror="this.src='img/logo/logo_menu.png'">
                <?= htmlspecialchars(explode(' ', $_SESSION['usuario']['nome'])[0]) ?>
            </a>
        </div>
    </div>

    <!-- OVERLAY E MODAL PERFIL -->
    <div id="modalOverlay" class="modal-overlay" onclick="fecharTodosModais()"></div>
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

    <div class="container">
        <h1 class="titulo"><i class="fa-solid fa-heart" style="color: #ff528a;"></i> As Minhas Receitas Favoritas</h1>

        <?php if (count($receitasFavoritas) > 0): ?>
            <div class="lista">
                <?php foreach($receitasFavoritas as $r): ?>
                <div class="card">
                    <a href="favoritar.php?id=<?= $r['id_receita'] ?>" class="btn-remover-fav" title="Remover dos Favoritos">
                        <i class="fa-solid fa-heart"></i>
                    </a>
                    <a href="receita.php?id=<?= $r['id_receita'] ?>" style="text-decoration:none; color:inherit; height:100%;">
                        <img src="<?= buscarImagem($r['imagem']) ?>" onerror="this.src='img/logo/logo_menu.png'">
                        <div class="tag <?= strtolower($r['tipo']) ?>"><?= htmlspecialchars($r['tipo']) ?></div>
                        <p><?= htmlspecialchars($r['nome_receita']) ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sem-favoritos">
                <i class="fa-regular fa-bookmark"></i>
                <h3>Ainda não guardou nenhuma receita favorita!</h3>
                <p>Explore o catálogo e clique no botão de coração para guardar as suas receitas preferidas aqui.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function fecharTodosModais() {
        document.getElementById('modalOverlay').style.display = 'none';
        if(document.getElementById('modalPerfil')) document.getElementById('modalPerfil').style.display = 'none';
    }
    function alternarModais(tipo) {
        fecharTodosModais();
        document.getElementById('modalOverlay').style.display = 'block';
        if (tipo === 'perfil') document.getElementById('modalPerfil').style.display = 'block';
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