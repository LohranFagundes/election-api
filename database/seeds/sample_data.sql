INSERT INTO `voters` (`name`, `email`, `password`, `cpf`, `birth_date`, `phone`, `vote_weight`, `is_active`, `is_verified`, `email_verified_at`) VALUES
('João da Silva', 'joao.silva@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '12345678901', '1985-05-15', '(11) 99999-1234', 1.00, 1, 1, NOW()),
('Maria Santos', 'maria.santos@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '23456789012', '1990-08-22', '(11) 99999-5678', 1.00, 1, 1, NOW()),
('Pedro Oliveira', 'pedro.oliveira@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '34567890123', '1978-12-03', '(11) 99999-9012', 1.50, 1, 1, NOW()),
('Ana Costa', 'ana.costa@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '45678901234', '1995-03-18', '(11) 99999-3456', 1.00, 1, 1, NOW()),
('Carlos Ferreira', 'carlos.ferreira@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '56789012345', '1982-07-29', '(11) 99999-7890', 1.25, 1, 1, NOW()),
('Lucia Pereira', 'lucia.pereira@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '67890123456', '1988-11-14', '(11) 99999-2345', 1.00, 1, 1, NOW()),
('Roberto Lima', 'roberto.lima@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '78901234567', '1975-04-07', '(11) 99999-6789', 2.00, 1, 1, NOW()),
('Fernanda Souza', 'fernanda.souza@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '89012345678', '1992-09-25', '(11) 99999-0123', 1.00, 1, 1, NOW()),
('Ricardo Almeida', 'ricardo.almeida@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '90123456789', '1980-01-12', '(11) 99999-4567', 1.75, 1, 1, NOW()),
('Patrícia Rodrigues', 'patricia.rodrigues@email.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01234567890', '1987-06-30', '(11) 99999-8901', 1.00, 1, 1, NOW());

INSERT INTO `elections` (`title`, `description`, `election_type`, `status`, `start_date`, `end_date`, `timezone`, `allow_blank_votes`, `allow_null_votes`, `require_justification`, `max_votes_per_voter`, `voting_method`, `results_visibility`, `created_by`) VALUES
('Eleição Presidencial 2024', 'Eleição para Presidente da República', 'federal', 'scheduled', '2024-10-01 08:00:00', '2024-10-01 17:00:00', 'America/Sao_Paulo', 1, 1, 0, 1, 'single', 'after_end', 1),
('Eleição Municipal - Prefeito', 'Eleição para Prefeito Municipal', 'municipal', 'draft', '2024-11-15 07:00:00', '2024-11-15 17:00:00', 'America/Sao_Paulo', 1, 1, 0, 1, 'single', 'after_end', 1),
('Conselho Diretor Empresa XYZ', 'Eleição do Conselho Diretor da Empresa', 'internal', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'America/Sao_Paulo', 1, 0, 1, 3, 'multiple', 'private', 2);

INSERT INTO `positions` (`election_id`, `title`, `description`, `order_position`, `max_candidates`, `min_votes`, `max_votes`, `is_active`) VALUES
(1, 'Presidente', 'Presidente da República', 1, 5, 0, 1, 1),
(1, 'Vice-Presidente', 'Vice-Presidente da República', 2, 5, 0, 1, 1),
(2, 'Prefeito', 'Prefeito Municipal', 1, 8, 0, 1, 1),
(2, 'Vice-Prefeito', 'Vice-Prefeito Municipal', 2, 8, 0, 1, 1),
(3, 'Presidente do Conselho', 'Presidente do Conselho Diretor', 1, 3, 1, 1, 1),
(3, 'Membros do Conselho', 'Membros do Conselho Diretor', 2, 10, 2, 3, 1);

INSERT INTO `candidates` (`position_id`, `name`, `nickname`, `description`, `number`, `party`, `coalition`, `is_active`, `order_position`) VALUES
(1, 'José da Silva', 'Zé do Povo', 'Candidato com experiência em gestão pública', '13', 'Partido Popular', 'Coligação Democrática', 1, 1),
(1, 'Maria Fernanda', 'Mari', 'Advogada e empresária', '45', 'Partido Progressista', 'União Nacional', 1, 2),
(1, 'Carlos Eduardo', 'Carlinhos', 'Ex-governador e economista', '15', 'Partido Liberal', 'Frente Popular', 1, 3),
(2, 'Ana Paula Santos', 'Ana Paula', 'Médica e ativista social', '13', 'Partido Popular', 'Coligação Democrática', 1, 1),
(2, 'Roberto Lima', 'Betinho', 'Empresário do agronegócio', '45', 'Partido Progressista', 'União Nacional', 1, 2),
(3, 'João Prefeito', 'João', 'Atual vereador', '11', 'Partido Municipal', 'Aliança Local', 1, 1),
(3, 'Sandra Gestora', 'Sandra', 'Administradora pública', '22', 'Partido Cidadão', 'Movimento Popular', 1, 2),
(3, 'Paulo Empresário', 'Paulão', 'Empresário local', '33', 'Partido Empreendedor', 'Coligação Progressista', 1, 3),
(4, 'Luiza Vice', 'Lu', 'Professora universitária', '11', 'Partido Municipal', 'Aliança Local', 1, 1),
(4, 'Marcos Apoio', 'Marquinhos', 'Contador público', '22', 'Partido Cidadão', 'Movimento Popular', 1, 2),
(5, 'Dr. Fernando Presidente', 'Dr. Fernando', 'CEO da empresa há 10 anos', '101', 'Independente', null, 1, 1),
(5, 'Eng. Patricia Lima', 'Patty', 'Diretora de Tecnologia', '102', 'Independente', null, 1, 2),
(5, 'Adv. Ricardo Santos', 'Ricardo', 'Diretor Jurídico', '103', 'Independente', null, 1, 3),
(6, 'Ana Diretora RH', 'Ana RH', 'Diretora de Recursos Humanos', '201', 'Independente', null, 1, 1),
(6, 'Carlos Diretor Financeiro', 'Carlos CFO', 'Diretor Financeiro', '202', 'Independente', null, 1, 2),
(6, 'Marina Diretora Marketing', 'Mari Marketing', 'Diretora de Marketing', '203', 'Independente', null, 1, 3),
(6, 'Pedro Diretor Vendas', 'Pedro Vendas', 'Diretor de Vendas', '204', 'Independente', null, 1, 4),
(6, 'Sofia Diretora Operações', 'Sofia Ops', 'Diretora de Operações', '205', 'Independente', null, 1, 5),
(6, 'Lucas Diretor TI', 'Lucas Tech', 'Diretor de Tecnologia da Informação', '206', 'Independente', null, 1, 6),
(6, 'Camila Diretora Qualidade', 'Camila Quality', 'Diretora de Qualidade', '207', 'Independente', null, 1, 7);

INSERT INTO `audit_logs` (`user_id`, `user_type`, `action`, `resource`, `resource_id`, `route`, `method`, `ip_address`, `user_agent`, `status_code`, `level`, `message`) VALUES
(1, 'admin', 'create', 'elections', 1, '/api/v1/elections', 'POST', '127.0.0.1', 'PostmanRuntime/7.32.3', 201, 'info', 'Election created successfully'),
(1, 'admin', 'create', 'elections', 2, '/api/v1/elections', 'POST', '127.0.0.1', 'PostmanRuntime/7.32.3', 201, 'info', 'Election created successfully'),
(2, 'admin', 'create', 'elections', 3, '/api/v1/elections', 'POST', '127.0.0.1', 'PostmanRuntime/7.32.3', 201, 'info', 'Election created successfully'),
(1, 'admin', 'create', 'positions', 1, '/api/v1/positions', 'POST', '127.0.0.1', 'PostmanRuntime/7.32.3', 201, 'info', 'Position created successfully'),
(1, 'admin', 'create', 'positions', 2, '/api/v1/positions', 'POST', '127.0.0.1', 'PostmanRuntime/7.32.3', 201, 'info', 'Position created successfully'),
(1, 'admin', 'bulk_create', 'candidates', null, '/api/v1/candidates/bulk', 'POST', '127.0.0.1', 'PostmanRuntime/7.32.3', 201, 'info', 'Multiple candidates created successfully'),
(1, 'admin', 'seed', 'voters', null, '/api/v1/voters/seed', 'POST', '127.0.0.1', 'curl/7.88.1', 201, 'info', 'Sample voters seeded successfully'),
(null, 'system', 'migration', 'database', null, null, null, '127.0.0.1', 'PHP Migration Script', 200, 'info', 'Database tables created successfully'),
(null, 'system', 'seed', 'database', null, null, null, '127.0.0.1', 'PHP Seeder Script', 200, 'info', 'Sample data seeded successfully');