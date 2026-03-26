CREATE DATABASE IF NOT EXISTS locadora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE locadora;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  login VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  perfil VARCHAR(40) NOT NULL DEFAULT 'admin',
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  primeiro_login TINYINT(1) NOT NULL DEFAULT 1,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  marca VARCHAR(80) NOT NULL,
  modelo VARCHAR(80) NOT NULL,
  ano YEAR NOT NULL,
  placa VARCHAR(10) NOT NULL UNIQUE,
  renavam VARCHAR(20) DEFAULT NULL,
  cor VARCHAR(40) DEFAULT NULL,
  quilometragem_atual INT NOT NULL DEFAULT 0,
  categoria VARCHAR(60) DEFAULT NULL,
  valor_diaria DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_semanal DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_mensal DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('disponivel','alugado','manutencao','inativo') NOT NULL DEFAULT 'disponivel',
  observacoes TEXT,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_completo VARCHAR(150) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  rg VARCHAR(30) DEFAULT NULL,
  cnh VARCHAR(30) DEFAULT NULL,
  validade_cnh DATE DEFAULT NULL,
  telefone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  endereco_completo TEXT,
  observacoes TEXT,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rentals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  tipo_cobranca ENUM('diaria','semanal','mensal') NOT NULL,
  valor_cobranca DECIMAL(10,2) NOT NULL,
  tempo_contrato INT NOT NULL,
  dia_semana_vencimento VARCHAR(15) DEFAULT NULL,
  data_inicio DATE NOT NULL,
  data_prevista_termino DATE NOT NULL,
  data_real_termino DATE DEFAULT NULL,
  quilometragem_saida INT NOT NULL,
  quilometragem_retorno INT DEFAULT NULL,
  caucao DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_total_previsto DECIMAL(10,2) NOT NULL,
  valor_total_final DECIMAL(10,2) DEFAULT NULL,
  status ENUM('ativa','finalizada','cancelada') NOT NULL DEFAULT 'ativa',
  observacoes TEXT,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE maintenances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  tipo_manutencao VARCHAR(100) NOT NULL,
  descricao TEXT,
  data_manutencao DATE NOT NULL,
  quilometragem_manutencao INT NOT NULL,
  valor_gasto DECIMAL(10,2) NOT NULL,
  oficina_fornecedor VARCHAR(150) DEFAULT NULL,
  observacoes TEXT,
  status ENUM('pendente','concluida') NOT NULL DEFAULT 'pendente',
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE checklists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rental_id INT NOT NULL,
  tipo_checklist ENUM('entrega','devolucao') NOT NULL,
  lataria VARCHAR(80) DEFAULT NULL,
  pneus VARCHAR(80) DEFAULT NULL,
  vidros VARCHAR(80) DEFAULT NULL,
  combustivel VARCHAR(80) DEFAULT NULL,
  limpeza VARCHAR(80) DEFAULT NULL,
  interior_estado VARCHAR(80) DEFAULT NULL,
  acessorios VARCHAR(120) DEFAULT NULL,
  avarias TEXT,
  observacoes TEXT,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rental_id) REFERENCES rentals(id)
);

CREATE TABLE checklist_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  checklist_id INT NOT NULL,
  tipo_arquivo ENUM('foto','video') NOT NULL,
  caminho_arquivo VARCHAR(255) NOT NULL,
  data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (checklist_id) REFERENCES checklists(id)
);

CREATE TABLE financial_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('receita','despesa') NOT NULL,
  categoria VARCHAR(80) NOT NULL,
  descricao VARCHAR(200) NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  data_movimentacao DATE NOT NULL,
  rental_id INT DEFAULT NULL,
  maintenance_id INT DEFAULT NULL,
  vehicle_id INT DEFAULT NULL,
  client_id INT DEFAULT NULL,
  pagamento_status ENUM('pago','nao_pago') NOT NULL DEFAULT 'nao_pago',
  recorrente TINYINT(1) NOT NULL DEFAULT 0,
  recorrencia_periodo ENUM('semanal','mensal') DEFAULT NULL,
  recorrencia_ativa TINYINT(1) NOT NULL DEFAULT 0,
  referencia_data DATE DEFAULT NULL,
  origem_automatica TINYINT(1) NOT NULL DEFAULT 0,
  parent_entry_id INT DEFAULT NULL,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rental_id) REFERENCES rentals(id),
  FOREIGN KEY (maintenance_id) REFERENCES maintenances(id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  FOREIGN KEY (client_id) REFERENCES clients(id)
);

INSERT INTO users (nome, login, email, senha, perfil, status, primeiro_login)
VALUES ('Administrador', 'admin', 'admin@locadora.local', '$2y$12$HJXZFp5BBe8zvOigFX8dfeQm/rw/vbGrRbDghlmVBTCj2kxpZz70m', 'admin', 'ativo', 1);

INSERT INTO vehicles (nome, marca, modelo, ano, placa, renavam, cor, quilometragem_atual, categoria, valor_diaria, valor_semanal, valor_mensal, status)
VALUES
('HB20 Sense', 'Hyundai', 'HB20', 2023, 'BRA2E19', '12345678901', 'Prata', 15200, 'Hatch', 180.00, 1100.00, 3900.00, 'disponivel'),
('Onix LT', 'Chevrolet', 'Onix', 2022, 'QWE4R56', '10987654321', 'Branco', 20550, 'Hatch', 170.00, 1020.00, 3600.00, 'disponivel');

INSERT INTO clients (nome_completo, cpf, rg, cnh, validade_cnh, telefone, email, endereco_completo)
VALUES
('Carlos Silva', '123.456.789-10', 'MG-12.345.678', '01234567890', '2028-09-20', '(31)98888-1111', 'carlos@email.com', 'Rua A, 100 - Belo Horizonte/MG'),
('Ana Souza', '987.654.321-00', 'SP-55.666.777', '99887766554', '2027-03-11', '(11)97777-2222', 'ana@email.com', 'Av. B, 456 - São Paulo/SP');
