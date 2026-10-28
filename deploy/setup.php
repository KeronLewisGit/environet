<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * One-time installer for hosts without SSH. Visit /setup.php?token=YOUR_SETUP_TOKEN
 * after uploading the package and filling in environet-app/.env. Deletes itself when done.
 */
$appPath = __DIR__.'/environet-app';
$envFile = $appPath.'/.env';
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta name="viewport" content="width=device-width"><title>Environet, Inc. setup</title><body style="font-family:system-ui;max-width:640px;margin:40px auto;padding:0 16px;line-height:1.5">';
echo '<h1>Environet, Inc. – installer</h1>';

if (! file_exists($envFile)) {
    exit('<p style="color:#b00">environet-app/.env not found. Copy .env.example to .env and fill in the database details first.</p>');
}
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$token = $env['SETUP_TOKEN'] ?? '';
if ($token === '' || ! isset($_GET['token']) || ! is_string($_GET['token']) || ! hash_equals($token, $_GET['token'])) {
    exit('<p style="color:#b00">Invalid or missing token. Set SETUP_TOKEN in .env and open /setup.php?token=THAT_VALUE.</p>');
}

require $appPath.'/vendor/autoload.php';
$app = require $appPath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$kernel = $app->make(Kernel::class);
$run = function (string $cmd, array $args = []) use ($kernel) {
    $out = new BufferedOutput;
    $code = $kernel->call($cmd, $args, $out);
    echo '<pre style="background:#f4f4f4;padding:8px;font-size:12px">$ php artisan '.htmlspecialchars($cmd).'  → '.($code === 0 ? 'OK' : 'FAILED')."\n".htmlspecialchars($out->fetch()).'</pre>';

    return $code === 0;
};

$ok = true;
if (empty($env['APP_KEY'])) {
    $ok = $run('key:generate', ['--force' => true]) && $ok;
}
$ok = $run('migrate', ['--force' => true]) && $ok;
// Creates the first administrator from ADMIN_EMAIL/ADMIN_PASSWORD when both are set; no sample data.
$ok = $run('db:seed', ['--force' => true]) && $ok;
// Only views and routes are cached here. Caching config would make later .env edits
// (office email, mail password) silently ignored on hosts without SSH.
$run('view:cache');

// Re-running for an update (new migrations) must never create or reset a staff account:
// account changes go through the dashboard or `php artisan app:create-admin` over SSH.
if ($ok && User::query()->exists()) {
    $removed = @unlink(__FILE__);
    echo '<p style="color:#070"><strong>Done.</strong> Database is up to date. A staff account already exists, so none was created. Delete the SETUP_TOKEN line from .env.</p>';
    if (! $removed) {
        echo '<p style="color:#b00;font-weight:bold">setup.php could not delete itself. Delete public_html/setup.php now in File Manager.</p>';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ok) {
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 10) {
        if (! $run('app:create-admin', ['--name' => $name ?: 'Administrator', '--email' => $email, '--password' => $pass])) {
            exit('<p style="color:#b00">The administrator could not be created (see the message above). Nothing was removed; fix it and submit again.</p>');
        }
        $removed = @unlink(__FILE__);
        echo '<p style="color:#070"><strong>Done.</strong> Administrator created. <a href="/login">Sign in</a>, then delete the SETUP_TOKEN line from .env.</p>';
        if (! $removed) {
            echo '<p style="color:#b00;font-weight:bold">setup.php could not delete itself. Delete public_html/setup.php now in File Manager.</p>';
        }
        exit;
    }
    echo '<p style="color:#b00">Please enter a valid email and a password of at least 10 characters.</p>';
}
echo '<h2>Create the first administrator</h2><form method="post"><p><input name="name" placeholder="Name" required style="width:100%;padding:8px"></p><p><input name="email" type="email" placeholder="Email" required style="width:100%;padding:8px"></p><p><input name="password" type="password" placeholder="Password (min 10)" minlength="10" required style="width:100%;padding:8px"></p><button style="padding:10px 18px">Create administrator &amp; finish</button></form>';
