<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
$sessionDirectory = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionDirectory)) mkdir($sessionDirectory, 0700, true);
session_save_path($sessionDirectory);
session_start();
$configPath = dirname(__DIR__) . '/config.php';
$config = file_exists($configPath) ? require $configPath : require dirname(__DIR__) . '/config.example.php';
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    http_response_code(503);
    exit('<h1>Database connection needed</h1><p>Import database/schema.sql, create config.php from config.example.php, then refresh.</p>');
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $url): never { header('Location: ' . $url); exit; }
function current_user(): ?array { if (empty($_SESSION['user']['id'])) return null; global $pdo; static $cachedId=null; static $cachedUser=null; $id=(int)$_SESSION['user']['id']; if ($cachedId===$id) return $cachedUser; $q=$pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1'); $q->execute([$id]); $u=$q->fetch(); if (!$u) { unset($_SESSION['user']); return null; } $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'photo'=>$u['photo']??'']; $cachedId=$id; $cachedUser=$u; return $u; }
function require_user(): array { $u=current_user(); if (!$u) go('/login'); return $u; }
function require_role(array $roles): array { $u=require_user(); if (!in_array($u['role'],$roles,true)) { http_response_code(403); exit('Access denied.'); } return $u; }
function csrf_check(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Session expired. Refresh and try again.'); } }
function page_url(): string { return strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); }

function india_states(): array { return ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry']; }
function india_state_options(string $selected = ''): string { $states = india_states(); $out = '<optgroup label="States (28)">'; foreach (array_slice($states, 0, 28) as $s) $out .= '<option value="' . h($s) . '"' . ($s === $selected ? ' selected' : '') . '>' . h($s) . '</option>'; $out .= '</optgroup><optgroup label="Union Territories (8)">'; foreach (array_slice($states, 28) as $s) $out .= '<option value="' . h($s) . '"' . ($s === $selected ? ' selected' : '') . '>' . h($s) . '</option>'; return $out . '</optgroup>'; }
