-- =============================================
-- EficienSys - Script de Criacao do Banco de Dados
-- Execute este script no phpMyAdmin ou MySQL
-- =============================================

CREATE DATABASE IF NOT EXISTS eficiensys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eficiensys;

-- Tabela de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cargo ENUM('gerente', 'colaborador') NOT NULL DEFAULT 'colaborador',
    departamento VARCHAR(100),
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('ativo', 'inativo', 'online', 'ausente') DEFAULT 'ativo',
    ultimo_acesso DATETIME DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de Departamentos
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    gerente_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gerente_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Mensagens (Chat)
CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT DEFAULT NULL,
    grupo_id INT DEFAULT NULL,
    conteudo TEXT NOT NULL,
    tipo ENUM('texto', 'arquivo', 'imagem', 'alerta') DEFAULT 'texto',
    lida BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabela de Grupos/Canais
CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    tipo ENUM('departamento', 'projeto', 'geral') DEFAULT 'geral',
    criador_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Membros dos Grupos
CREATE TABLE IF NOT EXISTS grupo_membros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    papel ENUM('admin', 'membro') DEFAULT 'membro',
    entrou_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membro (grupo_id, usuario_id)
) ENGINE=InnoDB;

-- Tabela de Tarefas
CREATE TABLE IF NOT EXISTS tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    criador_id INT NOT NULL,
    responsavel_id INT,
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    status ENUM('pendente', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'pendente',
    prazo DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Alertas/Incidentes
CREATE TABLE IF NOT EXISTS alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    tipo ENUM('incidente', 'manutencao', 'seguranca', 'geral') DEFAULT 'geral',
    prioridade ENUM('baixa', 'media', 'alta', 'critica') DEFAULT 'media',
    status ENUM('aberto', 'em_analise', 'resolvido', 'fechado') DEFAULT 'aberto',
    criador_id INT NOT NULL,
    responsavel_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolvido_em DATETIME,
    FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de Notificacoes
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT,
    tipo ENUM('mensagem', 'tarefa', 'alerta', 'sistema') DEFAULT 'sistema',
    lida BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Indices para melhor performance
CREATE INDEX idx_mensagens_remetente ON mensagens(remetente_id);
CREATE INDEX idx_mensagens_destinatario ON mensagens(destinatario_id);
CREATE INDEX idx_mensagens_criado ON mensagens(criado_em);
CREATE INDEX idx_tarefas_responsavel ON tarefas(responsavel_id);
CREATE INDEX idx_alertas_status ON alertas(status);
CREATE INDEX idx_notificacoes_usuario ON notificacoes(usuario_id, lida);

-- Inserir usuario administrador padrao (senha: admin123)
INSERT INTO usuarios (nome, email, senha, cargo, departamento) VALUES
('Administrador', 'admin@eficiensys.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente', 'Administracao');

-- Inserir grupo geral
INSERT INTO grupos (nome, descricao, tipo, criador_id) VALUES
('Geral', 'Canal geral para todos os colaboradores', 'geral', 1);

-- Adicionar admin ao grupo geral
INSERT INTO grupo_membros (grupo_id, usuario_id, papel) VALUES (1, 1, 'admin');
