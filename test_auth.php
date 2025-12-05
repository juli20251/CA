<?php
/**
 * Archivo de prueba para verificar la autenticación
 * Acceder desde: http://localhost/ChechoAwards/test_auth.php
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Test de Autenticación</title>
    <style>
        body { 
            font-family: Arial; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #1a1a2e;
            color: #fff;
        }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .info { background: #2a2a3e; padding: 15px; border-radius: 8px; margin: 10px 0; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { 
            background: #ff4500; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #cc3700; }
    </style>
</head>
<body>
    <h1>🔍 Test de Autenticación - Checho Awards</h1>";

// Test 1: Verificar conexión a la base de datos
echo "<div class='info'>";
echo "<h2>✓ Test 1: Conexión a Base de Datos</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total = $stmt->fetch()['total'];
    echo "<p class='success'>✅ Conexión exitosa. Usuarios en DB: $total</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Listar usuarios disponibles
echo "<div class='info'>";
echo "<h2>✓ Test 2: Usuarios Disponibles</h2>";
try {
    $stmt = $pdo->query("SELECT id, nombre, email, rol FROM usuarios");
    $usuarios = $stmt->fetchAll();
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #333;'><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
    foreach ($usuarios as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['nombre']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><strong>{$user['rol']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Verificar configuración
echo "<div class='info'>";
echo "<h2>✓ Test 3: Configuración del Sistema</h2>";
echo "<p><strong>SITE_URL:</strong> " . SITE_URL . "</p>";
echo "<p><strong>DB_NAME:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Activa' : '❌ Inactiva') . "</p>";
echo "</div>";

// Test 4: Test de Login Manual
echo "<div class='info'>";
echo "<h2>✓ Test 4: Prueba de Login</h2>";
echo "<p>Prueba el login con JavaScript:</p>";
echo "
<div id='loginTest'>
    <input type='email' id='testEmail' placeholder='Email' value='admin@chechoawards.com' style='padding: 8px; margin: 5px; width: 250px; background: #333; color: #fff; border: 1px solid #555;'>
    <input type='password' id='testPassword' placeholder='Contraseña' value='password' style='padding: 8px; margin: 5px; width: 250px; background: #333; color: #fff; border: 1px solid #555;'>
    <button onclick='testLogin()'>Probar Login</button>
    <div id='loginResult' style='margin-top: 10px;'></div>
</div>

<script>
async function testLogin() {
    const email = document.getElementById('testEmail').value;
    const password = document.getElementById('testPassword').value;
    const resultDiv = document.getElementById('loginResult');
    
    resultDiv.innerHTML = '⏳ Probando...';
    
    try {
        const formData = new FormData();
        formData.append('accion', 'login');
        formData.append('email', email);
        formData.append('password', password);
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        console.log('Response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            resultDiv.innerHTML = '<pre style=\"color: red;\">❌ Error al parsear JSON:\\n' + text + '</pre>';
            return;
        }
        
        if (data.success) {
            resultDiv.innerHTML = '<p style=\"color: #4CAF50;\">✅ Login exitoso!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style=\"color: #f44336;\">❌ ' + data.mensaje + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style=\"color: #f44336;\">❌ Error: ' + error.message + '</p>';
        console.error('Error completo:', error);
    }
}
</script>
";
echo "</div>";

echo "<div class='info'>";
echo "<h2>📋 Notas Importantes</h2>";
echo "<ul>";
echo "<li>Si ves errores, verifica que la contraseña en phpMyAdmin esté actualizada</li>";
echo "<li>La contraseña predeterminada de prueba es: <strong>password</strong></li>";
echo "<li>Puedes cambiar el email y contraseña en los campos de arriba</li>";
echo "<li><strong style='color: #ff4500;'>⚠️ ELIMINA este archivo después de las pruebas</strong></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>