-- 1. PREPARAÇÃO DO AMBIENTE (Apaga o antigo e cria um novo limpo)
DROP DATABASE IF EXISTS magical_bites;
CREATE DATABASE magical_bites;
USE magical_bites;

-- 2. CRIAÇÃO DAS TABELAS
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
    ingredientes TEXT, 
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

-- 3. INSERÇÃO DE TODAS AS RECEITAS (Sem emojis nos nomes)

-- DOCES
INSERT INTO receitas (nome_receita, tipo, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) VALUES
('Torta de Maçã da Branca de Neve', 'DOCE', 'A clássica torta de maçã, mas sem nenhum feitiço!', 'torta_maca_branca_neve.png', 4.9, '1h 15min', 'Média', 'Massa de torta;Maçãs fatiadas;Açúcar;Canela', 'Asse até dourar e cheirar a magia.'),
('Trufas de Frozen', 'DOCE', 'Trufas geladas e mágicas direto de Arendelle.', 'trufas_frozen.png', 4.8, '30min', 'Fácil', 'Chocolate branco;Creme de leite;Corante azul', 'Derreta, misture, esfrie e faça as bolinhas.'),
('Cookies de Enrolados', 'DOCE', 'Os biscoitos favoritos do Rapunzel e Pascal.', 'cookies_enrolados.png', 4.7, '40min', 'Fácil', 'Farinha;Açúcar;Gotas de chocolate;Manteiga', 'Misture a massa e asse a 180 graus.'),
('Beignet da Princesa e o Sapo', 'DOCE', 'Os famosos beignets da Tiana de Nova Orleans.', 'beignet_princesa_sapo.png', 5.0, '1h', 'Média', 'Farinha;Fermento;Açúcar de confeiteiro;Óleo', 'Frite a massa e polvilhe muito açúcar!'),
('Biscoitos de Alice no País das Maravilhas', 'DOCE', 'Biscoitos que vão te fazer crescer de sabor!', 'biscoitos_alice.png', 4.6, '45min', 'Média', 'Farinha;Manteiga;Açúcar;Glacê real', 'Corte, asse e decore com glacê.'),
('Bolo de Lilo & Stitch', 'DOCE', 'Bolo tropical com muito ohana.', 'placeholder.png', 5.0, '1h', 'Média', 'Farinha;Açúcar;Ovos;Abacaxi', 'Misture tudo e asse com muito amor.'),
('Crisp de Frutas Vermelhas da Aurora', 'DOCE', 'A sobremesa perfeita para um sono de beleza.', 'placeholder.png', 4.8, '40min', 'Fácil', 'Frutas vermelhas;Açúcar;Aveia', 'Asse até ficar crocante e dourado.'),
('Mousse Crocante do Russell', 'DOCE', 'A sobremesa de um verdadeiro explorador!', 'placeholder.png', 4.9, '30min', 'Fácil', 'Chocolate;Creme de leite;Biscoitos', 'Derreta o chocolate, misture e gele.'),
('Mousse de Banana e Manga do Mogli', 'DOCE', 'Somente o necessário para ser feliz.', 'placeholder.png', 4.7, '20min', 'Fácil', 'Banana;Manga;Iogurte', 'Bata no liquidificador e sirva gelado.'),
('Palmiers da Minnie', 'DOCE', 'Biscoitos doces em formato de orelhinhas.', 'placeholder.png', 4.9, '45min', 'Média', 'Massa folhada;Açúcar', 'Dobre em formato de orelhas e asse.'),
('As Trutas da Mérida', 'DOCE', 'Massa doce em formato de peixe das terras escocesas.', 'placeholder.png', 4.6, '50min', 'Média', 'Massa doce;Recheio de maçã', 'Modele como peixes e asse até dourar.'),
('Os Palmiers da Clarabela', 'DOCE', 'Biscoito folhado de açúcar tradicional.', 'placeholder.png', 4.5, '35min', 'Fácil', 'Massa folhada;Açúcar cristal', 'Enrole, corte e asse.'),
('Madeleines do Pooh', 'DOCE', 'Bolinhos franceses mergulhados no mel.', 'placeholder.png', 5.0, '30min', 'Média', 'Farinha;Ovos;Manteiga;Mel', 'Asse em forminhas especiais e regue com mel.');

-- SALGADOS
INSERT INTO receitas (nome_receita, tipo, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) VALUES
('Ratatouille', 'SALGADO', 'O clássico prato camponês que derreteu o coração do crítico.', 'ratatouille.png', 5.0, '1h 30min', 'Difícil', 'Berinjela;Abobrinha;Tomate;Molho de tomate;Ervas', 'Corte em rodelas finas, monte e asse.'),
('Arepas con queso de Encanto', 'SALGADO', 'Arepas curativas feitas com amor pela Julieta Madrigal.', 'arepas_encanto.png', 4.9, '30min', 'Fácil', 'Farinha de milho;Água;Sal;Queijo', 'Faça a massa, recheie com queijo e grelhe.'),
('Macarrão ao molho pesto de Luca', 'SALGADO', 'Trenette al pesto, perfeito para um dia em Portorosso.', 'macarrao_pesto_luca.png', 4.8, '25min', 'Fácil', 'Macarrão Trenette;Manjericão;Alho;Parmesão;Azeite;Batata', 'Faça o molho pesto, cozinhe a massa e misture tudo.'),
('Espaguete com Almôndegas da Dama e o Vagabundo', 'SALGADO', 'O prato mais romântico da Itália canina.', 'espaguete_almondegas.png', 4.9, '50min', 'Média', 'Espaguete;Carne moída;Molho de tomate;Temperos', 'Faça as almôndegas, cozinhe no molho e sirva sobre a massa.'),
('Cenouras com Molho de Laranja da Judy Hopps', 'SALGADO', 'Rápido e prático para policiais de Zootopia.', 'placeholder.png', 4.8, '20min', 'Fácil', 'Cenoura;Suco de laranja;Mel;Manteiga', 'Cozinhe as cenouras e glaceie no molho.'),
('Beringelas Recheadas à Ratatouille', 'SALGADO', 'Uma variação vegetariana do prato do Remy.', 'placeholder.png', 4.9, '50min', 'Média', 'Beringela;Tomate;Queijo;Ervas', 'Asse as beringelas recheadas no forno.'),
('Chorizo Refogado do Hector', 'SALGADO', 'Um aperitivo que vai te fazer lembrar de mim.', 'placeholder.png', 4.8, '25min', 'Fácil', 'Chorizo;Cebola;Pimentão', 'Frite o chorizo com os legumes.'),
('Cogumelos Recheados do Pateta', 'SALGADO', 'Um aperitivo delicioso e divertido.', 'placeholder.png', 4.5, '30min', 'Média', 'Cogumelos grandes;Queijo;Alho', 'Recheie e asse até gratinar.'),
('Jantar Leve da Duquesa', 'SALGADO', 'Uma terrine de atum elegante para gatos da alta sociedade.', 'placeholder.png', 4.7, '40min', 'Média', 'Atum;Creme de ricota;Temperos', 'Misture tudo e sirva com torradas.'),
('Sopa da Floresta do Dunga', 'SALGADO', 'Caldo quentinho perfeito para depois do trabalho.', 'placeholder.png', 4.6, '1h', 'Fácil', 'Legumes variados;Batata;Caldo de carne', 'Cozinhe tudo na panela de pressão.'),
('Peixe à Tropical da Moana', 'SALGADO', 'Ceviche marinado com o frescor do oceano.', 'placeholder.png', 5.0, '20min', 'Média', 'Peixe branco fresco;Limão;Cebola roxa;Coentro', 'Marine o peixe no limão e sirva fresco.'),
('Saltimboccas do Pinóquio', 'SALGADO', 'Prato italiano de verdade para um menino de verdade.', 'placeholder.png', 4.9, '35min', 'Média', 'Bife fino;Presunto;Queijo;Sálvia', 'Enrole a carne com os frios e frite na manteiga.'),
('Petiscos do Pongo', 'SALGADO', 'Rolinhos de carne moída irresistíveis.', 'placeholder.png', 4.8, '45min', 'Fácil', 'Carne moída;Temperos;Massa de pastel', 'Modele, asse e sirva para os 101.'),
('A Caçarola do Patinho Fofinho', 'SALGADO', 'Prato rústico direto da taverna.', 'placeholder.png', 4.7, '1h 20min', 'Difícil', 'Carne;Batata;Cenoura;Caldo', 'Asse lentamente na caçarola de ferro.'),
('Enroladinhos de Tapete Mágico', 'SALGADO', 'Aperitivos das arábias com a Princesa Jasmine.', 'placeholder.png', 4.8, '30min', 'Fácil', 'Massa de wrap;Frango desfiado;Queijo', 'Recheie, enrole e toste na frigideira.'),
('Super Bife da Família Parr', 'SALGADO', 'Apenas para super-heróis com muita fome.', 'placeholder.png', 5.0, '25min', 'Fácil', 'Bife Ancho;Sal grosso;Manteiga', 'Grelhe em fogo alto até o ponto desejado.'),
('Chips Crocantes da Mulan', 'SALGADO', 'Lanchinho rápido para quem vai salvar o dia.', 'placeholder.png', 4.6, '20min', 'Fácil', 'Batata doce;Azeite;Páprica', 'Corte fino e asse até ficar super crocante.'),
('Espaguete dos Aristogatas', 'SALGADO', 'Macarrão com muito queijo, monsieur.', 'placeholder.png', 4.9, '30min', 'Fácil', 'Espaguete;Creme de leite;Queijo gruyère', 'Cozinhe a massa e envolva no creme de queijo.');

-- BEBIDAS
INSERT INTO receitas (nome_receita, tipo, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) VALUES
('Super Suco para dar Energia', 'BEBIDA', 'Vitamina revigorante do Sr. Incrível.', 'placeholder.png', 5.0, '10min', 'Fácil', 'Laranja;Cenoura;Beterraba;Gelo', 'Bata tudo no liquidificador.');