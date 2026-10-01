<?php
// Server-side Google authorization-code flow. Secrets never enter browser JavaScript.
function google_settings(): array {
    $file = __DIR__.'/integrations.php';
    return is_file($file) ? (require $file) : [];
}
function google_http(string $url, ?array $form = null, ?string $token = null): array {
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer '.$token;
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20, CURLOPT_CONNECTTIMEOUT=>8, CURLOPT_HTTPHEADER=>$headers, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
    if ($form !== null) curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($form)]);
    $body = curl_exec($ch); $status = curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
    $data = json_decode($body ?: '',true);
    if ($status !== 200 || !is_array($data)) throw new RuntimeException('Google could not complete sign-in. Please try again.');
    return $data;
}
function google_auth_route(PDO $pdo, string $path): void {
    if (!in_array($path,['auth/google','auth/google/callback'],true)) return;
    $settings = google_settings();
    $client = $settings['google_client_id'] ?? '';
    $secret = $settings['google_client_secret'] ?? '';
    $callback = 'https://elearning.ashwamedhgurukul.org/auth/google/callback';
    if (!$client || !$secret) { $_SESSION['flash']='Google sign-in is awaiting academy configuration. Please use your email and password.'; go('/login'); }
    try {
        if ($path === 'auth/google') {
            $_SESSION['google_oauth']=['state'=>bin2hex(random_bytes(32)),'at'=>time(),'verifier'=>bin2hex(random_bytes(32)),'link_user'=>(int)(current_user()['id']??0)];
            $v=$_SESSION['google_oauth'];
            go('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id'=>$client,'redirect_uri'=>$callback,'response_type'=>'code','scope'=>'openid email profile',
                'state'=>$v['state'],'code_challenge'=>rtrim(strtr(base64_encode(hash('sha256',$v['verifier'],true)),'+/','-_'),'='),'code_challenge_method'=>'S256','prompt'=>'select_account'
            ]));
        }
        $flow=$_SESSION['google_oauth']??[]; unset($_SESSION['google_oauth']);
        if (empty($flow['state']) || time()-($flow['at']??0)>600 || !is_string($_GET['state']??null) || !hash_equals($flow['state'],$_GET['state']) || empty($_GET['code']) || !is_string($_GET['code'])) throw new RuntimeException('Google sign-in expired or was cancelled. Try again.');
        $tokens=google_http('https://oauth2.googleapis.com/token',['code'=>$_GET['code'],'client_id'=>$client,'client_secret'=>$secret,'redirect_uri'=>$callback,'grant_type'=>'authorization_code','code_verifier'=>$flow['verifier']]);
        $profile=google_http('https://openidconnect.googleapis.com/v1/userinfo',null,$tokens['access_token']??'');
        if (empty($profile['sub']) || ($profile['email_verified']??false)!==true || !filter_var($profile['email']??'',FILTER_VALIDATE_EMAIL)) throw new RuntimeException('A verified Google email address is required.');
        $email=strtolower($profile['email']);
        $pdo->exec("CREATE TABLE IF NOT EXISTS oauth_identities (provider VARCHAR(20) NOT NULL, subject VARCHAR(190) NOT NULL, user_id INT UNSIGNED NOT NULL, PRIMARY KEY(provider,subject), UNIQUE KEY(user_id,provider), FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!empty($flow['link_user'])) {
            $current=current_user();
            if (!$current || (int)$current['id']!==$flow['link_user'] || strtolower($current['email'])!==$email) throw new RuntimeException('Choose the Google account with the same email as your signed-in academy account.');
            $pdo->prepare('INSERT INTO oauth_identities(provider,subject,user_id) VALUES(?,?,?)')->execute(['google',$profile['sub'],$current['id']]);
            $_SESSION['flash']='Google sign-in is now linked to your account.';go('/profile');
        }
        $q=$pdo->prepare('SELECT u.* FROM users u JOIN oauth_identities i ON i.user_id=u.id WHERE i.provider=? AND i.subject=?'); $q->execute(['google',$profile['sub']]); $u=$q->fetch();
        if (!$u) {
            $q=$pdo->prepare('SELECT id FROM users WHERE email=?'); $q->execute([$email]);
            if ($q->fetch()) throw new RuntimeException('This email already has an academy account. Sign in with your password first, then link Google from your profile.');
            $pdo->beginTransaction();
            $q=$pdo->prepare("INSERT INTO users(email,name,password_hash,role,email_verified_at) VALUES(?,?,?,'student',NOW())");
            $q->execute([$email,mb_substr($profile['name']??$email,0,120),password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT)]);
            $id=(int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO oauth_identities(provider,subject,user_id) VALUES(?,?,?)')->execute(['google',$profile['sub'],$id]);
            $pdo->commit(); $q=$pdo->prepare('SELECT * FROM users WHERE id=?');$q->execute([$id]);$u=$q->fetch();
        }
        session_regenerate_id(true); $_SESSION['csrf']=bin2hex(random_bytes(32)); $_SESSION['user']=['id'=>$u['id'],'role'=>$u['role'],'name'=>$u['name'],'email'=>$u['email']];
        go(match($u['role']){'teacher'=>'/teacher','creator'=>'/creator','reviewer'=>'/review','admin'=>'/super-admin',default=>'/dashboard'});
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash']=$error instanceof RuntimeException && !($error instanceof PDOException) ? $error->getMessage() : 'Google sign-in is not available yet. Please use your password.';
        go('/login');
    }
}
