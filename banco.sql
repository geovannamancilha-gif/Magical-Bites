create database magical_bites;
use magical_bites;
create table usuario (
id_usuario INT AUTO_INCREMENT PRIMARY KEY,
nome_usuario VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL,
senha VARCHAR(255) NOT NULL );
create table filme (
id_filme INT AUTO_INCREMENT PRIMARY KEY,
nome_filme VARCHAR(255) NOT NULL);
create table receitas (
id_receita INT AUTO_INCREMENT PRIMARY KEY,
nome_receita VARCHAR(255) NOT NULL,
tipo VARCHAR(255) NOT NULL, id_filme INT,
FOREIGN KEY (id_filme) REFERENCES filme(id_filme)
 );
create table favoritar (
id_favoritos INT AUTO_INCREMENT PRIMARY KEY,
id_usuario INT,
FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario), id_receita INT, FOREIGN KEY (id_receita) REFERENCES receitas(id_receita) );
create table comentar (
id_comentario INT AUTO_INCREMENT PRIMARY KEY,
data_comentario date,
texto_comentario VARCHAR(255) NOT NULL, id_usuario INT,
FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) );