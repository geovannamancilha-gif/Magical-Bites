-- 1. PREPARAÇÃO DO AMBIENTE
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

-- 3. INSERÇÃO DOS 25 FILMES (Mapeados das suas receitas)
INSERT INTO filme (id_filme, nome_filme) VALUES 
(1, 'Branca de Neve'), 
(2, 'Frozen'), 
(3, 'Enrolados'), 
(4, 'A Princesa e o Sapo'), 
(5, 'Alice no País das Maravilhas'), 
(6, 'Ratatouille'),
(7, 'Lilo & Stitch'),
(8, 'A Bela Adormecida'),
(9, 'Up: Altas Aventuras'),
(10, 'Mogli: O Menino Lobo'),
(11, 'Turma do Mickey'),
(12, 'Valente'),
(13, 'Ursinho Pooh'),
(14, 'Encanto'),
(15, 'Luca'),
(16, 'A Dama e o Vagabundo'),
(17, 'Zootopia'),
(18, 'Viva: A Vida é uma Festa'),
(19, 'Aristogatas'),
(20, 'Moana'),
(21, 'Pinóquio'),
(22, '101 Dálmatas'),
(23, 'Aladdin'),
(24, 'Os Incríveis'),
(25, 'Mulan');

-- 4. INSERÇÃO DE TODAS AS RECEITAS COM SEUS RESPECTIVOS id_filme

-- DOCES
INSERT INTO receitas (nome_receita, tipo, id_filme, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) VALUES
('Torta de Maçã da Branca de Neve', 'DOCE', 1, 'A clássica torta de maçã, mas sem nenhum feitiço!', 'torta_maca_branca_neve.png', 4.9, '1h 15min', 'Média', 'Massa de torta;Maçãs fatiadas;Açúcar;Canela', 'Asse até dourar e cheirar a magia.'),
('Trufas de Frozen', 'DOCE', 2, 'Trufas geladas e mágicas direto de Arendelle.', 'trufas_frozen.png', 4.8, '30min', 'Fácil', 'Chocolate branco;Creme de leite;Corante azul', 'Derreta, misture, esfrie e faça as bolinhas.'),
('Cookies de Enrolados', 'DOCE', 3, 'Os biscoitos favoritos do Rapunzel e Pascal.', 'cookies_enrolados.png', 4.7, '40min', 'Fácil', 'Farinha;Açúcar;Gotas de chocolate;Manteiga', 'Misture a massa e asse a 180 graus.'),
('Beignet da Princesa e o Sapo', 'DOCE', 4, 'Os famosos beignets da Tiana de Nova Orleans.', 'beignet_princesa_sapo.png', 5.0, '1h', 'Média', 'Farinha;Fermento;Açúcar de confeiteiro;Óleo', 'Frite a massa e polvilhe muito açúcar!'),
('Biscoitos de Alice no País das Maravilhas', 'DOCE', 5, 'Biscoitos que vão te fazer crescer de sabor!', 'biscoitos_alice.png', 4.6, '45min', 'Média', 'Farinha;Manteiga;Açúcar;Glacê real', 'Corte, asse e decore com glacê.'),
('Bolo de Lilo & Stitch', 'DOCE', 7, 'Bolo tropical com muito ohana.', 'bolo_lilo.png', 5.0, '1h', 'Média', 'Farinha;Açúcar;Ovos;Abacaxi', 'Misture tudo e asse com muito amor.'),
('Crisp de Frutas Vermelhas da Aurora', 'DOCE', 8, 'A sobremesa perfeita para um sono de beleza.', 'Crisp_de_Frutas.png', 4.8, '40min', 'Fácil', 'Frutas vermelhas;Açúcar;Aveia', 'Asse até ficar crocante e dourado.'),
('Mousse Crocante do Russell', 'DOCE', 9, 'A sobremesa de um verdadeiro explorador!', 'mousse_de_chocolate.png', 4.9, '30min', 'Fácil', 'Chocolate;Creme de leite;Biscoitos', 'Derreta o chocolate, misture e gele.'),
('Mousse de Banana e Manga do Mogli', 'DOCE', 10, 'Somente o necessário para ser feliz.', 'mousse_banana.png', 4.7, '20min', 'Fácil', 'Banana;Manga;Iogurte', 'Bata no liquidificador e sirva gelado.'),
('Palmiers da Minnie', 'DOCE', 11, 'Biscoitos doces em formato de orelhinhas.', 'palmire_minnie.png', 4.9, '45min', 'Média', 'Massa folhada;Açúcar', 'Dobre em formato de orelhas e asse.'),
('As Trutas da Mérida', 'DOCE', 12, 'Massa doce em formato de peixe das terras escocesas.', 'placeholder.png', 4.6, '50min', 'Média', 'Massa doce;Recheio de maçã', 'Modele como peixes e asse até dourar.'),
('Os Palmiers da Clarabela', 'DOCE', 11, 'Biscoito folhado de açúcar tradicional.', 'placeholder.png', 4.5, '35min', 'Fácil', 'Massa folhada;Açúcar cristal', 'Enrole, corte e asse.'),
('Madeleines do Pooh', 'DOCE', 13, 'Bolinhos franceses mergulhados no mel.', 'ursinho_pu.png', 5.0, '30min', 'Média', 'Farinha;Ovos;Manteiga;Mel', 'Asse em forminhas especiais e regue com mel.');

-- SALGADOS
INSERT INTO receitas (nome_receita, tipo, id_filme, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) VALUES
('Ratatouille', 'SALGADO', 6, 'O clássico prato camponês que derreteu o coração do crítico.', 'beringela_ratatouille.png', 5.0, '1h 30min', 'Difícil', 'Berinjela;Abobrinha;Tomate;Molho de tomate;Ervas', 'Corte em rodelas finas, monte e asse.'),
('Arepas con queso de Encanto', 'SALGADO', 14, 'Arepas curativas feitas com amor pela Julieta Madrigal.', 'arepas_encanto.png', 4.9, '30min', 'Fácil', 'Farinha de milho;Água;Sal;Queijo', 'Faça a massa, recheie com queijo e grelhe.'),
('Macarrão ao molho pesto de Luca', 'SALGADO', 15, 'Trenette al pesto, perfeito para um dia em Portorosso.', 'macarrao_pesto_luca.png', 4.8, '25min', 'Fácil', 'Macarrão Trenette;Manjericão;Alho;Parmesão;Azeite;Batata', 'Faça o molho pesto, cozinhe a massa e misture tudo.'),
('Espaguete com Almôndegas da Dama e o Vagabundo', 'SALGADO', 16, 'O prato mais romântico da Itália canina.', 'espaguete_almondegas.png', 4.9, '50min', 'Média', 'Espaguete;Carne moída;Molho de tomate;Temperos', 'Faça as almôndegas, cozinhe no molho e sirva sobre a massa.'),
('Cenouras com Molho de Laranja da Judy Hopps', 'SALGADO', 17, 'Rápido e prático para policiais de Zootopia.', 'cenoura_zootopia.png', 4.8, '20min', 'Fácil', 'Cenoura;Suco de laranja;Mel;Manteiga', 'Cozinhe as cenouras e glaceie no molho.'),
('Beringelas Recheadas à Ratatouille', 'SALGADO', 6, 'Uma variação vegetariana do prato do Remy.', 'beringela_ratatouille.png', 4.9, '50min', 'Média', 'Beringela;Tomate;Queijo;Ervas', 'Asse as beringelas recheadas no forno.'),
('Chorizo Refogado do Hector', 'SALGADO', 18, 'Um aperitivo que vai te fazer lembrar de mim.', 'chorizo_hector.png', 4.8, '25min', 'Fácil', 'Chorizo;Cebola;Pimentão', 'Frite o chorizo com os legumes.'),
('Cogumelos Recheados do Pateta', 'SALGADO', 11, 'Um aperitivo delicioso e divertido.', 'cogumelo_pateta.png', 4.5, '30min', 'Média', 'Cogumelos grandes;Queijo;Alho', 'Recheie e asse até gratinar.'),
('Jantar Leve da Duquesa', 'SALGADO', 19, 'Uma terrine de atum elegante para gatos da alta sociedade.', 'terrineatum_duquesa.png', 4.7, '40min', 'Média', 'Atum;Creme de ricota;Temperos', 'Misture tudo e sirva com torradas.'),
('Sopa da Floresta do Dunga', 'SALGADO', 1, 'Caldo quentinho perfeito para depois do trabalho.', 'sopa_floresta.png', 4.6, '1h', 'Fácil', 'Legumes variados;Batata;Caldo de carne', 'Cozinhe tudo na panela de pressão.'),
('Peixe à Tropical da Moana', 'SALGADO', 20, 'Ceviche marinado com o frescor do oceano.', 'peixe_moana.png', 5.0, '20min', 'Média', 'Peixe branco fresco;Limão;Cebola roxa;Coentro', 'Marine o peixe no limão e sirva fresco.'),
('Saltimboccas do Pinóquio', 'SALGADO', 21, 'Prato italiano de verdade para um menino de verdade.', 'saltimboccas_pinoquio.png', 4.9, '35min', 'Média', 'Bife fino;Presunto;Queijo;Sálvia', 'Enrole a carne com os frios e frite na manteiga.'),
('Petiscos do Pongo', 'SALGADO', 22, 'Rolinhos de carne moída irresistíveis.', 'petisco_pongo.png', 4.8, '45min', 'Fácil', 'Carne moída;Temperos;Massa de pastel', 'Modele, asse e sirva para os 101.'),
('A Caçarola do Patinho Fofinho', 'SALGADO', 3, 'Prato rústico direto da taverna.', 'cassarola_enrolados.png', 4.7, '1h 20min', 'Difícil', 'Carne;Batata;Cenoura;Caldo', 'Asse lentamente na caçarola de ferro.'),
('Enroladinhos de Tapete Mágico', 'SALGADO', 23, 'Aperitivos das arábias com a Princesa Jasmine.', 'tapetes_magicos.png', 4.8, '30min', 'Fácil', 'Massa de wrap;Frango desfiado;Queijo', 'Recheie, enrole e toste na frigideira.'),
('Super Bife da Família Parr', 'SALGADO', 24, 'Apenas para super-heróis com muita fome.', 'super_bife.png', 5.0, '25min', 'Fácil', 'Bife Ancho;Sal grosso;Manteiga', 'Grelhe em fogo alto até o ponto desejado.'),
('Chips Crocantes da Mulan', 'SALGADO', 25, 'Lanchinho rápido para quem vai salvar o dia.', 'chips_mulan.png', 4.6, '20min', 'Fácil', 'Batata doce;Azeite;Páprica', 'Corte fino e asse até ficar super crocante.'),
('Espaguete dos Aristogatas', 'SALGADO', 19, 'Macarrão com muito queijo, monsieur.', 'espaguetes_aristogatas.png', 4.9, '30min', 'Fácil', 'Espaguete;Creme de leite;Queijo gruyère', 'Cozinhe a massa e envolva no creme de queijo.');

-- BEBIDAS
INSERT INTO receitas (nome_receita, tipo, id_filme, descricao, imagem, avaliacao, tempo_preparo, dificuldade, ingredientes, modo_preparo) VALUES
('Super Suco para dar Energia', 'BEBIDA', 24, 'Vitamina revigorante do Sr. Incrível.', 'suco_energia.png', 5.0, '10min', 'Fácil', 'Laranja;Cenoura;Beterraba;Gelo', 'Bata tudo no liquidificador.');