<?php
// 3. RESTAURAR public/index.php (VERSÃO ORIGINAL)

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('REQUEST_START_TIME', microtime(true));

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

session_start();

// Load utilities in correct order
require_once __DIR__ . '/../src/utils/Response.php';
require_once __DIR__ . '/../src/utils/Request.php';
require_once __DIR__ . '/../src/utils/Database.php';
require_once __DIR__ . '/../src/utils/Router.php';
require_once __DIR__ . '/../src/utils/JWT.php';

try {
    // Set basic security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Set CORS headers
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $router = new Router();
    
    // Basic routes
    $router->get('/', function() {
        return Response::success([
            'message' => 'Election API',
            'version' => '1.0.0',
            'status' => 'running',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'health' => '/health',
                'api' => '/api/v1',
                'auth' => '/api/v1/auth'
            ]
        ]);
    });

    $router->get('/health', function() {
        try {
            $db = Database::getInstance();
            $dbStatus = $db->isConnected() ? 'connected' : 'disconnected';
        } catch (Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }
        
        return Response::success([
            'status' => 'healthy',
            'database' => $dbStatus,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'php_version' => phpversion(),
            'environment' => $_ENV['APP_ENV'] ?? 'development'
        ]);
    });

    // API routes
    $router->group(['prefix' => 'api/v1'], function($router) {
        
        $router->get('/', function() {
            return Response::success([
                'message' => 'Election API v1.0',
                'version' => '1.0.0',
                'timestamp' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'admin_login' => '/api/v1/auth/admin/login',
                    'voter_login' => '/api/v1/auth/voter/login',
                    'test' => '/api/v1/test',
                    'status' => '/api/v1/status'
                ]
            ]);
        });

        $router->get('/test', function() {
            return Response::success([
                'message' => 'API is working!',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_info' => [
                    'php_version' => phpversion(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Built-in server'
                ]
            ]);
        });

        $router->get('/status', function() {
            try {
                $db = Database::getInstance();
                $dbStatus = $db->isConnected() ? 'connected' : 'disconnected';
            } catch (Exception $e) {
                $dbStatus = 'error: ' . $e->getMessage();
            }
            
            return Response::success([
                'status' => 'healthy',
                'database' => $dbStatus,
                'timestamp' => date('Y-m-d H:i:s'),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ]);
        });

        // Auth routes
        $router->group(['prefix' => 'auth'], function($router) {
            
            // Admin Login
            $router->post('/admin/login', function() {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validation
                if (!isset($input['email']) || !isset($input['password'])) {
                    return Response::error(['message' => 'Email and password are required'], 422);
                }
                
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    return Response::error(['message' => 'Invalid email format'], 422);
                }
                
                try {
                    $db = Database::getInstance();
                    
                    // Check if admin table exists
                    if (!$db->tableExists('admins')) {
                        return Response::error(['message' => 'Admin table not found. Please run migrations.'], 500);
                    }
                    
                    // Find admin by email
                    $admin = $db->fetch("SELECT * FROM admins WHERE email = ? AND is_active = 1", [$input['email']]);
                    
                    if (!$admin) {
                        return Response::error(['message' => 'Invalid credentials'], 401);
                    }
                    
                    // For demo purposes, accept "password" as valid (in production, use proper hashing)
                    $validPassword = false;
                    if ($input['password'] === 'password') {
                        $validPassword = true;
                    } elseif (isset($admin['password']) && password_verify($input['password'], $admin['password'])) {
                        $validPassword = true;
                    }
                    
                    if (!$validPassword) {
                        return Response::error(['message' => 'Invalid credentials'], 401);
                    }
                    
                    // Generate JWT token
                    $payload = [
                        'iss' => 'election-api',
                        'aud' => 'election-system',
                        'iat' => time(),
                        'nbf' => time(),
                        'exp' => time() + 3540, // 59 minutes
                        'jti' => uniqid(),
                        'user_id' => $admin['id'],
                        'role' => 'admin',
                        'permissions' => json_decode($admin['permissions'] ?? '[]', true)
                    ];
                    
                    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
                    $token = JWT::encode($payload, $jwtSecret);
                    
                    // Update last login
                    $db->execute(
                        "UPDATE admins SET last_login_at = ?, last_login_ip = ? WHERE id = ?",
                        [date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? 'unknown', $admin['id']]
                    );
                    
                    return Response::success([
                        'token' => $token,
                        'user' => [
                            'id' => $admin['id'],
                            'name' => $admin['name'],
                            'email' => $admin['email'],
                            'role' => $admin['role'],
                            'permissions' => json_decode($admin['permissions'] ?? '[]', true)
                        ],
                        'expires_in' => 3540
                    ]);
                    
                } catch (Exception $e) {
                    error_log("Admin login error: " . $e->getMessage());
                    return Response::error(['message' => 'Login failed: ' . $e->getMessage()], 500);
                }
            });
            
            // Voter Login
            $router->post('/voter/login', function() {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validation
                if (!isset($input['email']) || !isset($input['password'])) {
                    return Response::error(['message' => 'Email and password are required'], 422);
                }
                
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    return Response::error(['message' => 'Invalid email format'], 422);
                }
                
                try {
                    $db = Database::getInstance();
                    
                    // Check if voter table exists
                    if (!$db->tableExists('voters')) {
                        return Response::error(['message' => 'Voter table not found. Please run migrations.'], 500);
                    }
                    
                    // Find voter by email
                    $voter = $db->fetch("SELECT * FROM voters WHERE email = ? AND is_active = 1", [$input['email']]);
                    
                    if (!$voter) {
                        return Response::error(['message' => 'Invalid credentials'], 401);
                    }
                    
                    // For demo purposes, accept "password" as valid
                    $validPassword = false;
                    if ($input['password'] === 'password') {
                        $validPassword = true;
                    } elseif (isset($voter['password']) && password_verify($input['password'], $voter['password'])) {
                        $validPassword = true;
                    }
                    
                    if (!$validPassword) {
                        return Response::error(['message' => 'Invalid credentials'], 401);
                    }
                    
                    // Check if voter is verified
                    if (!$voter['is_verified']) {
                        return Response::error(['message' => 'Account not verified'], 403);
                    }
                    
                    // Generate JWT token
                    $payload = [
                        'iss' => 'election-api',
                        'aud' => 'election-system',
                        'iat' => time(),
                        'nbf' => time(),
                        'exp' => time() + 300, // 5 minutes
                        'jti' => uniqid(),
                        'user_id' => $voter['id'],
                        'role' => 'voter'
                    ];
                    
                    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
                    $token = JWT::encode($payload, $jwtSecret);
                    
                    // Update last login
                    $db->execute(
                        "UPDATE voters SET last_login_at = ?, last_login_ip = ? WHERE id = ?",
                        [date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? 'unknown', $voter['id']]
                    );
                    
                    return Response::success([
                        'token' => $token,
                        'user' => [
                            'id' => $voter['id'],
                            'name' => $voter['name'],
                            'email' => $voter['email'],
                            'cpf' => $voter['cpf'],
                            'vote_weight' => $voter['vote_weight']
                        ],
                        'expires_in' => 300
                    ]);
                    
                } catch (Exception $e) {
                    error_log("Voter login error: " . $e->getMessage());
                    return Response::error(['message' => 'Login failed: ' . $e->getMessage()], 500);
                }
            });
            
            // Token Validation
            $router->get('/validate', function() {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                
                if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                    return Response::error(['message' => 'Authorization token required'], 401);
                }
                
                $token = $matches[1];
                
                try {
                    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
                    $payload = JWT::decode($token, $jwtSecret, ['HS256']);
                    
                    return Response::success([
                        'valid' => true,
                        'user_id' => $payload->user_id,
                        'role' => $payload->role,
                        'expires_at' => $payload->exp
                    ]);
                    
                } catch (Exception $e) {
                    return Response::error(['message' => 'Invalid or expired token'], 401);
                }
            });
            
            // Test Login (for testing without database)
            $router->post('/test-login', function() {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['email']) || !isset($input['password'])) {
                    return Response::error(['message' => 'Email and password required'], 422);
                }
                
                return Response::success([
                    'message' => 'Login endpoint working',
                    'received' => [
                        'email' => $input['email'],
                        'password' => '***hidden***'
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            });
        });
    });

    $response = $router->resolve();
    
    if ($response === null) {
        Response::error(['message' => 'Route not found'], 404);
    }

} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    
    $debugMode = $_ENV['APP_DEBUG'] ?? false;
    $debugMode = filter_var($debugMode, FILTER_VALIDATE_BOOLEAN);
    
    if ($debugMode) {
        Response::error([
            'message' => 'Application error',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5)
        ], 500);
    } else {
        Response::error(['message' => 'Internal server error'], 500);
    }
}