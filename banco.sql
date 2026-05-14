-- 1. PREPARAÇÃO DO AMBIENTE (Apaga o antigo e cria um novo limpo)
DROP DATABASE IF EXISTS magical_bites;
CREATE DATABASE magical_bites;
USE magical_bites;

-- 2. CRIAÇÃO DAS TABELAS (Estrutura do Banco)

CREATE TABLE filme (
    id_filme INT AUTO_INCREMENT PRIMARY KEY,
    nome_filme VARCHAR(255) NOT NULL
);

CREATE TABLE receitas (
    id_receita INT AUTO_INCREMENT PRIMARY KEY,
    nome_receita VARCHAR(255) NOT NULL,
    tipo VARCHAR(255) NOT NULL,
    id_filme INT,
    descricao TEXT,
    imagem VARCHAR(255),
    avaliacao DECIMAL(2,1),
    tempo_preparo VARCHAR(50),
    dificuldade VARCHAR(50),
    ingredientes TEXT, /* Ingredientes separados por ponto e vírgula (;) */
    modo_preparo TEXT,
    FOREIGN KEY (id_filme) REFERENCES filme(id_filme)
);

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome_usuario VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE favoritar (
    id_favoritos INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_receita INT,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_receita) REFERENCES receitas(id_receita)
);

CREATE TABLE comentar (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    data_comentario DATE,
    texto_comentario VARCHAR(255) NOT NULL,
    id_usuario INT,
    id_receita INT, 
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_receita) REFERENCES receitas(id_receita)
);


-- 3. INSERÇÃO DE DADOS (Povoando o Banco com os 9 Filmes e Receitas)

-- Povoando a tabela de filmes (IDs de 1 a 9 gerados automaticamente)
INSERT INTO filme (nome_filme) VALUES 
('Branca de Neve'), 
('Frozen'), 
('Enrolados'), 
('A Princesa e o Sapo'), 
('Ratatouille'), 
('Encanto'), 
('Luca'),
('Alice no País das Maravilhas'),
('A Dama e o Vagabundo');

-- Povoando a tabela de receitas (Doces e Salgados juntos)
INSERT INTO receitas (nome_receita, tipo, id_filme, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo)
VALUES 
-- DOCES
(
    '🍎 Torta de Maçã da Branca de Neve', 'DOCE', 1, 
    'Uma torta clássica, doce e aconchegante, perfeita como a da Branca de Neve ✨', 
    'torta_maca_branca_neve.png', 4.9, '1h 20min', 'Médio', 
    '3 maçãs grandes;1 xícara de açúcar;1 colher de sopa de canela;1 massa para torta;1 colher de sopa de manteiga;1 gema para pincelar', 
    'Corte as maçãs em fatias e misture com açúcar e canela. Coloque o recheio sobre a massa, cubra com tiras decorativas e pincele gema. Leve ao forno preaquecido a 180°C por cerca de 40 minutos até dourar ✨'
),
(
    '❄️ Trufas de Frozen', 'DOCE', 2, 
    'Deliciosas trufas geladas de Arendelle.', 
    'trufas_frozen.png', 4.8, '40min', 'Fácil', 
    'Leite condensado;Chocolate branco;Corante azul;Coco ralado', 
    'Misture tudo e faça bolinhas.'
),
(
    '🦎 Cookies de Enrolados', 'DOCE', 3, 
    'Cookies com gotas de chocolate perfeitos para comer na torre.', 
    'cookies_enrolados.png', 5.0, '35min', 'Fácil', 
    'Farinha;Açúcar;Manteiga;Gotas de chocolate', 
    'Misture, asse e aproveite!'
),
(
    '☕ Beignet da Princesa e o Sapo', 'DOCE', 4, 
    'Os famosos beignets da Tiana, cheios de açúcar de confeiteiro.', 
    'beignet_princesa_sapo.png', 4.9, '1h', 'Médio', 
    'Farinha;Fermento;Açúcar;Óleo;Açúcar de confeiteiro', 
    'Frite a massa e polvilhe muito açúcar!'
),
(
    '♠️ Biscoitos de Alice no País das Maravilhas', 'DOCE', 8, 
    'Biscoitos amanteigados mágicos com a mensagem "Coma-me" diretamente do País das Maravilhas.', 
    'biscoitos_alice.png', 4.9, '45min', 'Fácil', 
    'Farinha de trigo;Manteiga;Açúcar;Essência de baunilha;Glacê real para decorar', 
    'Bata a manteiga com o açúcar, adicione a farinha e a baunilha. Abra a massa, corte com forminhas redondas e asse a 180°C por 15 minutos. Após esfriar, decore com glacê real escrevendo "Coma-me"!'
),

-- SALGADOS
(
    '🐭 Ratatouille', 'SALGADO', 5, 
    'O clássico prato camponês que derreteu o coração do crítico.', 
    'ratatouille.png', 5.0, '1h 30min', 'Difícil', 
    'Berinjela;Abobrinha;Tomate;Molho de tomate;Ervas', 
    'Corte em rodelas finas, monte e asse.'
),
(
    '🌽 Arepas con queso de Encanto', 'SALGADO', 6, 
    'Arepas curativas feitas com amor pela Julieta Madrigal.', 
    'arepas_encanto.png', 4.9, '30min', 'Fácil', 
    'Farinha de milho;Água;Sal;Queijo', 
    'Faça a massa, recheie com queijo e grelhe.'
),
(
    '🍃 Macarrão ao molho pesto de Luca', 'SALGADO', 7, 
    'Trenette al pesto, perfeito para um dia em Portorosso.', 
    'macarrao_pesto_luca.png', 4.8, '25min', 'Fácil', 
    'Macarrão Trenette;Manjericão;Alho;Parmesão;Azeite;Batata', 
    'Faça o molho, cozinhe a massa com batatas e misture.'
),
(
    '🍝 Espaguete com Almôndegas da Dama e o Vagabundo', 'SALGADO', 9, 
    'O prato mais romântico do cinema, perfeito para dividir na clássica cena do beijo.', 
    'espaguete_almondegas.png', 5.0, '1h', 'Médio', 
    'Espaguete;Carne moída;Farinha de rosca;Alho;Cebola;Molho de tomate;Queijo Parmesão ralado', 
    'Misture a carne com alho, cebola e farinha de rosca. Modele as almôndegas e frite até dourarem. Cozinhe o espaguete em água e sal. Aqueça as almôndegas no molho de tomate e sirva sobre a massa com bastante queijo ralado por cima.'
);