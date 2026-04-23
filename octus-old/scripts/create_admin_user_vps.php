<?php
/**
 * Script para criar usuário administrativo no sistema IoT
 * Execute: php create_admin_user_vps.php
 */

// Configuração do banco de dados
$host = '127.0.0.1';
$dbname = 'mqtt';
$username = 'roboflex';
$password = 'Roboflex()123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔗 Conectado ao banco de dados com sucesso!\n";
    
    // Dados do usuário administrativo
    $name = 'Darley Admin';
    $email = 'darley@iot.com';
    $plainPassword = 'darley123';
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    
    // Verificar se usuário já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Usuário já existe! Atualizando dados...\n";
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$name, $hashedPassword, $email]);
        
        echo "✅ Usuário atualizado com sucesso!\n";
    } else {
        echo "👤 Criando novo usuário administrativo...\n";
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())");
        $stmt->execute([$name, $email, $hashedPassword]);
        
        echo "✅ Usuário criado com sucesso!\n";
    }
    
    echo "\n📋 CREDENCIAIS DE ACESSO:\n";
    echo "🌐 Dashboard Web: http://181.215.135.118:8001\n";
    echo "📱 App Config: http://181.215.135.118:8002\n";
    echo "👤 Email: $email\n";
    echo "🔐 Senha: $plainPassword\n";
    echo "\n🚀 Pronto para usar!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
    echo "💡 Verifique as credenciais do banco de dados.\n";
}
?> 