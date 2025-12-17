-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/12/2025 às 17:27
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `controle_ti`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist_itens`
--

CREATE TABLE `checklist_itens` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) DEFAULT NULL,
  `descricao` text NOT NULL,
  `tipo_resposta` enum('sim_nao','ok_nao_ok','texto','data') DEFAULT 'sim_nao',
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `checklist_itens`
--

INSERT INTO `checklist_itens` (`id`, `checklist_id`, `descricao`, `tipo_resposta`, `ordem`) VALUES
(1, 1, 'Sistema operacional instalado', 'sim_nao', 1),
(2, 1, 'Antivírus atualizado', 'sim_nao', 2),
(3, 1, 'Office 365 configurado', 'sim_nao', 3),
(4, 1, 'Anotações', 'texto', 4),
(5, 1, 'Data conclusão', 'data', 5),
(6, 1, 'Sistema operacional instalado e atualizado', 'sim_nao', 1),
(7, 1, 'Antivírus instalado e atualizado', 'sim_nao', 2),
(8, 1, 'Office 365 configurado e funcionando', 'sim_nao', 3),
(9, 1, 'Conexão de rede funcionando', 'sim_nao', 4),
(10, 1, 'Impressora configurada', 'sim_nao', 5),
(11, 1, 'Backup configurado', 'sim_nao', 6),
(12, 1, 'Observações adicionais', 'texto', 7),
(13, 1, 'Data da próxima verificação', 'data', 8);

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist_respostas`
--

CREATE TABLE `checklist_respostas` (
  `execucao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `resposta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `checklist_respostas`
--

INSERT INTO `checklist_respostas` (`execucao_id`, `item_id`, `resposta`) VALUES
(1, 1, 'sim'),
(1, 2, 'sim'),
(1, 3, 'sim'),
(1, 4, 'teste'),
(1, 5, '2025-02-01'),
(1, 7, 'sim'),
(1, 8, 'sim'),
(1, 9, 'sim'),
(1, 10, 'sim'),
(1, 11, 'sim'),
(1, 12, 'teste'),
(1, 13, '2025-12-24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos`
--

CREATE TABLE `equipamentos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `numero_serie` varchar(50) DEFAULT NULL,
  `patrimonio` varchar(50) DEFAULT NULL,
  `localidade_id` int(11) DEFAULT NULL,
  `tipo_id` int(11) DEFAULT NULL,
  `status` enum('ativo','saida','manutencao','descartado') DEFAULT 'ativo',
  `fornecedor` varchar(100) DEFAULT NULL,
  `data_entrada` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `equipamentos`
--

INSERT INTO `equipamentos` (`id`, `nome`, `numero_serie`, `patrimonio`, `localidade_id`, `tipo_id`, `status`, `fornecedor`, `data_entrada`, `observacoes`, `data_cadastro`) VALUES
(1, 'teste', 'teste', 'teste', 2, 1, 'ativo', 'teste', '2025-12-17', 'teste', '2025-12-17 14:45:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamento_checklist`
--

CREATE TABLE `equipamento_checklist` (
  `equipamento_id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `equipamento_checklist`
--

INSERT INTO `equipamento_checklist` (`equipamento_id`, `checklist_id`) VALUES
(1, 1),
(1, 2),
(1, 4),
(1, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `execucao_checklist`
--

CREATE TABLE `execucao_checklist` (
  `id` int(11) NOT NULL,
  `equipamento_id` int(11) DEFAULT NULL,
  `checklist_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_execucao` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_geral` enum('aprovado','reprovado','atencao','pendente') DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `execucao_checklist`
--

INSERT INTO `execucao_checklist` (`id`, `equipamento_id`, `checklist_id`, `usuario_id`, `data_execucao`, `status_geral`, `observacoes`) VALUES
(1, 1, 1, 1, '2025-12-17 14:46:17', 'aprovado', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `execucao_processo`
--

CREATE TABLE `execucao_processo` (
  `id` int(11) NOT NULL,
  `localidade_id` int(11) DEFAULT NULL,
  `processo_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_conclusao` timestamp NULL DEFAULT NULL,
  `progresso` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `localidades`
--

CREATE TABLE `localidades` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `filial_nova` tinyint(1) DEFAULT 0,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `localidades`
--

INSERT INTO `localidades` (`id`, `codigo`, `nome`, `responsavel`, `filial_nova`, `data_cadastro`, `ativo`) VALUES
(1, 'FL-001', 'Filial SP Centro', 'João Silva', 0, '2025-12-16 20:35:17', 1),
(2, 'FL-002', 'Filial RJ Nova', 'Maria Santos', 1, '2025-12-16 20:35:17', 1),
(3, 'LAB-01', 'Laboratório TI', 'Carlos Oliveira', 0, '2025-12-16 20:35:17', 1),
(4, 'teste', 'teste', 'teste', 0, '2025-12-17 12:41:21', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `localidade_processo`
--

CREATE TABLE `localidade_processo` (
  `localidade_id` int(11) NOT NULL,
  `processo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(255) DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `usuario_id`, `acao`, `modulo`, `data_registro`, `ip`) VALUES
(1, 1, 'Login no sistema', 'auth', '2025-12-17 11:23:56', '::1'),
(2, 1, 'Logout do sistema', 'auth', '2025-12-17 11:36:18', '::1'),
(3, 1, 'Logout do sistema', 'auth', '2025-12-17 11:53:48', '::1'),
(4, 1, 'Logout do sistema', 'auth', '2025-12-17 14:05:18', '::1'),
(5, 1, 'Concluiu checklist: Checklist Instalação Básica Windows para equipamento ID 1 - Status: aprovado', 'checklists', '2025-12-17 14:46:17', NULL),
(6, 1, 'Concluiu checklist: Checklist Instalação Básica Windows para equipamento ID 1 - Status: aprovado', 'checklists', '2025-12-17 14:46:50', NULL),
(7, 1, 'Concluiu checklist: Checklist Instalação Básica Windows para equipamento ID 1 - Status: aprovado', 'checklists', '2025-12-17 14:46:55', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `modelos_checklist`
--

CREATE TABLE `modelos_checklist` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `padrao` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `modelos_checklist`
--

INSERT INTO `modelos_checklist` (`id`, `nome`, `descricao`, `padrao`, `data_criacao`) VALUES
(1, 'Checklist Instalação Básica Windows', 'Verificações após instalação do sistema', 1, '2025-12-16 20:35:17'),
(2, 'Checklist Segurança Antivírus', 'Verificação de segurança e antivírus', 1, '2025-12-16 20:35:17'),
(3, 'Checklist Manutenção Preventiva', 'Verificações de manutenção periódica', 0, '2025-12-16 20:35:17'),
(4, 'Checklist Instalação Básica Windows', 'Verificações após instalação do sistema operacional', 1, '2025-12-17 13:06:36'),
(5, 'Checklist Segurança Antivírus', 'Verificação de segurança e antivírus', 1, '2025-12-17 13:06:36'),
(6, 'Checklist Manutenção Preventiva', 'Verificações de manutenção periódica', 0, '2025-12-17 13:06:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `modelos_processo`
--

CREATE TABLE `modelos_processo` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `padrao` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `modelos_processo`
--

INSERT INTO `modelos_processo` (`id`, `nome`, `descricao`, `padrao`, `data_criacao`) VALUES
(1, 'Processo de Abertura de Filial', 'Passos para abrir nova filial', 1, '2025-12-16 20:35:17'),
(2, 'Processo de Manutenção Mensal', 'Procedimentos de manutenção mensal', 0, '2025-12-16 20:35:17'),
(3, 'Processo de Abertura de Filial', 'Passos para implantação de nova filial', 1, '2025-12-17 14:14:13'),
(4, 'Processo de Manutenção Mensal', 'Procedimentos de manutenção mensal da filial', 0, '2025-12-17 14:14:13'),
(5, 'Processo de Migração de Sistema', 'Procedimento para migração de sistemas', 0, '2025-12-17 14:14:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacao`
--

CREATE TABLE `movimentacao` (
  `id` int(11) NOT NULL,
  `equipamento_id` int(11) DEFAULT NULL,
  `status_anterior` varchar(50) DEFAULT NULL,
  `status_novo` varchar(50) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `fornecedor` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `movimentacao`
--

INSERT INTO `movimentacao` (`id`, `equipamento_id`, `status_anterior`, `status_novo`, `motivo`, `fornecedor`, `usuario_id`, `data_movimentacao`) VALUES
(1, 1, 'novo', 'ativo', NULL, NULL, 1, '2025-12-17 14:45:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `processo_itens`
--

CREATE TABLE `processo_itens` (
  `id` int(11) NOT NULL,
  `processo_id` int(11) DEFAULT NULL,
  `descricao` text NOT NULL,
  `tipo_resposta` enum('sim_nao','ok_nao_ok','texto','data') DEFAULT 'sim_nao',
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `processo_itens`
--

INSERT INTO `processo_itens` (`id`, `processo_id`, `descricao`, `tipo_resposta`, `ordem`) VALUES
(1, 1, 'Contrato de internet ativo', 'sim_nao', 1),
(2, 1, 'Rack montado', 'sim_nao', 2),
(3, 1, 'Backup configurado', 'sim_nao', 3),
(4, 1, 'Observações', 'texto', 4),
(5, 1, 'Contrato de internet ativo e funcionando', 'sim_nao', 1),
(6, 1, 'Infraestrutura de rede montada e testada', 'sim_nao', 2),
(7, 1, 'Equipamentos de TI instalados e configurados', 'sim_nao', 3),
(8, 1, 'Sistemas corporativos acessíveis', 'sim_nao', 4),
(9, 1, 'Treinamento da equipe realizado', 'sim_nao', 5),
(10, 1, 'Backup inicial configurado', 'sim_nao', 6),
(11, 1, 'Documentação da filial entregue', 'sim_nao', 7),
(12, 1, 'Observações gerais', 'texto', 8),
(13, 1, 'Data de conclusão do processo', 'data', 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `processo_respostas`
--

CREATE TABLE `processo_respostas` (
  `execucao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `resposta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_equipamento`
--

CREATE TABLE `tipos_equipamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipos_equipamento`
--

INSERT INTO `tipos_equipamento` (`id`, `nome`, `descricao`, `data_cadastro`) VALUES
(1, 'Computador Desktop', 'PC padrão da empresa', '2025-12-16 20:35:17'),
(2, 'Notebook Corporativo', 'Notebook para colaboradores', '2025-12-16 20:35:17'),
(3, 'Impressora Multifuncional', 'Impressora com scanner e cópia', '2025-12-16 20:35:17'),
(4, 'Monitor LED', 'Monitor para trabalho', '2025-12-16 20:35:17'),
(5, 'Roteador WiFi', 'Roteador para rede sem fio', '2025-12-16 20:35:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_checklist`
--

CREATE TABLE `tipo_checklist` (
  `tipo_id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','tecnico','coordenador') DEFAULT 'tecnico',
  `primeiro_login` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `usuario`, `senha`, `perfil`, `primeiro_login`, `data_criacao`, `ativo`) VALUES
(1, 'Administrador', 'admin', '$2y$10$sh2tp7dby9kH850QUqYt/OuYLT1tALHIZimdLuomEq5SBuTOTsdwG', 'admin', 0, '2025-12-16 20:35:16', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `checklist_itens`
--
ALTER TABLE `checklist_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id` (`checklist_id`);

--
-- Índices de tabela `checklist_respostas`
--
ALTER TABLE `checklist_respostas`
  ADD PRIMARY KEY (`execucao_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Índices de tabela `equipamentos`
--
ALTER TABLE `equipamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `localidade_id` (`localidade_id`),
  ADD KEY `tipo_id` (`tipo_id`);

--
-- Índices de tabela `equipamento_checklist`
--
ALTER TABLE `equipamento_checklist`
  ADD PRIMARY KEY (`equipamento_id`,`checklist_id`),
  ADD KEY `checklist_id` (`checklist_id`);

--
-- Índices de tabela `execucao_checklist`
--
ALTER TABLE `execucao_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipamento_id` (`equipamento_id`),
  ADD KEY `checklist_id` (`checklist_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `execucao_processo`
--
ALTER TABLE `execucao_processo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `localidade_id` (`localidade_id`),
  ADD KEY `processo_id` (`processo_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `localidade_processo`
--
ALTER TABLE `localidade_processo`
  ADD PRIMARY KEY (`localidade_id`,`processo_id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `modelos_checklist`
--
ALTER TABLE `modelos_checklist`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `modelos_processo`
--
ALTER TABLE `modelos_processo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `movimentacao`
--
ALTER TABLE `movimentacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipamento_id` (`equipamento_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `processo_itens`
--
ALTER TABLE `processo_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- Índices de tabela `processo_respostas`
--
ALTER TABLE `processo_respostas`
  ADD PRIMARY KEY (`execucao_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Índices de tabela `tipos_equipamento`
--
ALTER TABLE `tipos_equipamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tipo_checklist`
--
ALTER TABLE `tipo_checklist`
  ADD PRIMARY KEY (`tipo_id`,`checklist_id`),
  ADD KEY `checklist_id` (`checklist_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `checklist_itens`
--
ALTER TABLE `checklist_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `equipamentos`
--
ALTER TABLE `equipamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `execucao_checklist`
--
ALTER TABLE `execucao_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `execucao_processo`
--
ALTER TABLE `execucao_processo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `modelos_checklist`
--
ALTER TABLE `modelos_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `modelos_processo`
--
ALTER TABLE `modelos_processo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `movimentacao`
--
ALTER TABLE `movimentacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `processo_itens`
--
ALTER TABLE `processo_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `tipos_equipamento`
--
ALTER TABLE `tipos_equipamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `checklist_itens`
--
ALTER TABLE `checklist_itens`
  ADD CONSTRAINT `checklist_itens_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `modelos_checklist` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `checklist_respostas`
--
ALTER TABLE `checklist_respostas`
  ADD CONSTRAINT `checklist_respostas_ibfk_1` FOREIGN KEY (`execucao_id`) REFERENCES `execucao_checklist` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklist_respostas_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `checklist_itens` (`id`);

--
-- Restrições para tabelas `equipamentos`
--
ALTER TABLE `equipamentos`
  ADD CONSTRAINT `equipamentos_ibfk_1` FOREIGN KEY (`localidade_id`) REFERENCES `localidades` (`id`),
  ADD CONSTRAINT `equipamentos_ibfk_2` FOREIGN KEY (`tipo_id`) REFERENCES `tipos_equipamento` (`id`);

--
-- Restrições para tabelas `equipamento_checklist`
--
ALTER TABLE `equipamento_checklist`
  ADD CONSTRAINT `equipamento_checklist_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipamento_checklist_ibfk_2` FOREIGN KEY (`checklist_id`) REFERENCES `modelos_checklist` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `execucao_checklist`
--
ALTER TABLE `execucao_checklist`
  ADD CONSTRAINT `execucao_checklist_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`),
  ADD CONSTRAINT `execucao_checklist_ibfk_2` FOREIGN KEY (`checklist_id`) REFERENCES `modelos_checklist` (`id`),
  ADD CONSTRAINT `execucao_checklist_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `execucao_processo`
--
ALTER TABLE `execucao_processo`
  ADD CONSTRAINT `execucao_processo_ibfk_1` FOREIGN KEY (`localidade_id`) REFERENCES `localidades` (`id`),
  ADD CONSTRAINT `execucao_processo_ibfk_2` FOREIGN KEY (`processo_id`) REFERENCES `modelos_processo` (`id`),
  ADD CONSTRAINT `execucao_processo_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `localidade_processo`
--
ALTER TABLE `localidade_processo`
  ADD CONSTRAINT `localidade_processo_ibfk_1` FOREIGN KEY (`localidade_id`) REFERENCES `localidades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `localidade_processo_ibfk_2` FOREIGN KEY (`processo_id`) REFERENCES `modelos_processo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `movimentacao`
--
ALTER TABLE `movimentacao`
  ADD CONSTRAINT `movimentacao_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`),
  ADD CONSTRAINT `movimentacao_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `processo_itens`
--
ALTER TABLE `processo_itens`
  ADD CONSTRAINT `processo_itens_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `modelos_processo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `processo_respostas`
--
ALTER TABLE `processo_respostas`
  ADD CONSTRAINT `processo_respostas_ibfk_1` FOREIGN KEY (`execucao_id`) REFERENCES `execucao_processo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `processo_respostas_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `processo_itens` (`id`);

--
-- Restrições para tabelas `tipo_checklist`
--
ALTER TABLE `tipo_checklist`
  ADD CONSTRAINT `tipo_checklist_ibfk_1` FOREIGN KEY (`tipo_id`) REFERENCES `tipos_equipamento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tipo_checklist_ibfk_2` FOREIGN KEY (`checklist_id`) REFERENCES `modelos_checklist` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
