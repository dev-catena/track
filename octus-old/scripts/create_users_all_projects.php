<?php
/**
 * Script para criar usuário administrativo em TODOS os projetos IoT
 * Cria usuário nos 3 bancos de dados: mqtt, iot_web_db, iot_app_db
 */

// Configurações dos bancos
$databases = [
    'mqtt' => [
        'name' => 'Sistema Principal MQTT',
        'host' => '127.0.0.1',
        'dbname' => 'mqtt',
        'username' => 'roboflex',
        'password' => 'Roboflex()123'
    ],
    'web' => [
        'name' => 'Dashboard Web',
        'host' => '127.0.0.1',
        'dbname' => 'iot_web_db',
        'username' => 'roboflex',
        'password' => 'Roboflex()123'
    ],
    'app' => [
        'name' => 'App de Configuração',
        'host' => '127.0.0.1',
        'dbname' => 'iot_app_db',
        'username' => 'roboflex',
        'password' => 'Roboflex()123'
    ]
];

// Dados do usuário
$name = 'Darley Admin';
$email = 'darley@iot.com';
$plainPassword = 'darley123';
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

echo "🚀 Criando usuário administrativo em todos os projetos...\n\n";

foreach ($databases as $key => $config) {
    echo "📦 Processando: {$config['name']}\n";
    
    try {
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", 
                       $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "   🔗 Conectado ao banco: {$config['dbname']}\n";
        
        // Verificar se a tabela users existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            echo "   ⚠️  Tabela 'users' não existe, criando...\n";
            
            $createTable = "
                CREATE TABLE users (
                    id bigint unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    email varchar(255) NOT NULL,
                    email_verified_at timestamp NULL DEFAULT NULL,
                    password varchar(255) NOT NULL,
                    remember_token varchar(100) DEFAULT NULL,
                    created_at timestamp NULL DEFAULT NULL,
                    updated_at timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY users_email_unique (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $pdo->exec($createTable);
            echo "   ✅ Tabela 'users' criada com sucesso!\n";
        }
        
        // Verificar se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo "   ⚠️  Usuário já existe, atualizando...\n";
            
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$name, $hashedPassword, $email]);
        } else {
            echo "   👤 Criando novo usuário...\n";
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())");
            $stmt->execute([$name, $email, $hashedPassword]);
        }
        
        echo "   ✅ Usuário configurado com sucesso!\n";
        
    } catch (PDOException $e) {
        echo "   ❌ Erro: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "🎉 PROCESSO CONCLUÍDO!\n\n";
echo "📋 CREDENCIAIS UNIVERSAIS:\n";
echo "👤 Email: $email\n";
echo "🔐 Senha: $plainPassword\n\n";
echo "🌐 ONDE USAR:\n";
echo "🖥️  Dashboard Web: http://181.215.135.118:8001\n";
echo "📱 App Config: http://181.215.135.118:8002\n";
echo "🚀 API Principal: http://181.215.135.118:8000\n";
echo "\n✨ Pronto para usar em todos os sistemas!\n";
?> 