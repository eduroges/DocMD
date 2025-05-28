-- How to use this script:
-- 1. Save this content as a .sql file (e.g., create_users_table.sql).
-- 2. Connect to your MySQL server using a client like MySQL Workbench or the mysql command-line tool.
-- 3. Execute this script. For example, in the mysql command-line tool, you can use the command:
--    SOURCE /path/to/your/create_users_table.sql;
--    (Replace /path/to/your/ with the actual path to the file)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(50) NOT NULL DEFAULT 'usuario',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
