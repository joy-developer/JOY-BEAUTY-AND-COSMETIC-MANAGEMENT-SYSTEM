<?php
// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found at: ' . $path);
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// Database configuration from environment variables
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'joy_beauty');

// Logging configuration
define('LOG_PATH', __DIR__ . '/storage/logs/');
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');

// Establish database connection
try {
    // First try to connect without specifying database
    $pdo = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() == 0) {
        // Create database
        $pdo->exec("CREATE DATABASE " . DB_NAME);
        logInfo("Database " . DB_NAME . " created successfully");
        
        // Connect to the newly created database
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Import SQL dump
        $sqlDumpPath = __DIR__ . '/joy_beauty_database_dump.sql';
        if (file_exists($sqlDumpPath)) {
            $sql = file_get_contents($sqlDumpPath);
            $pdo->exec($sql);
            logInfo("Database dump imported successfully from " . $sqlDumpPath);
        } else {
            logError("SQL dump file not found at: " . $sqlDumpPath);
            throw new Exception("SQL dump file not found");
        }
    } else {
        // Connect to existing database
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    logError("Database connection error: " . $e->getMessage());
    die("ERROR: Could not connect. " . $e->getMessage());
} catch(Exception $e) {
    logError("Error during database setup: " . $e->getMessage());
    die("ERROR: " . $e->getMessage());
}

// Start session
session_start();

// Logging functions
function writeLog($level, $message, $context = []) {
    $logLevels = ['emergency' => 0, 'alert' => 1, 'critical' => 2, 'error' => 3, 'warning' => 4, 'notice' => 5, 'info' => 6, 'debug' => 7];
    $currentLogLevel = $logLevels[LOG_LEVEL] ?? 6;
    
    if (!isset($logLevels[$level]) || $logLevels[$level] > $currentLogLevel) {
        return;
    }
    
    // Create logs directory if it doesn't exist
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $date = date('Y-m-d');
    $logFile = LOG_PATH . "laravel-{$date}.log";
    
    // Format context data
    $contextString = '';
    if (!empty($context)) {
        $contextString = ' ' . json_encode($context);
    }
    
    // Format log entry
    $logEntry = "[{$timestamp}] local.{$level}: {$message}{$contextString}" . PHP_EOL;
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function logInfo($message, $context = []) {
    writeLog('info', $message, $context);
}

function logError($message, $context = []) {
    writeLog('error', $message, $context);
}

function logWarning($message, $context = []) {
    writeLog('warning', $message, $context);
}

function logDebug($message, $context = []) {
    writeLog('debug', $message, $context);
}

function logCritical($message, $context = []) {
    writeLog('critical', $message, $context);
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to get user IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Function to get user agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}
?>