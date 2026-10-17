<?php

/**
 * JosVeggies — cPanel host pre-flight check.
 *
 * Upload this single file to public_html, open it in a browser once, then DELETE IT.
 * It reports whether the host can run the app. It reveals nothing sensitive.
 */
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

$checks = [];
$ok = fn (bool $pass, string $label, string $detail = '') => $checks[] = [$pass, $label, $detail];

$ok(version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP 8.3 or newer', PHP_VERSION);

foreach (['pdo_mysql', 'mbstring', 'openssl', 'sodium', 'intl', 'bcmath', 'fileinfo', 'curl', 'zip', 'tokenizer', 'xml', 'ctype', 'dom'] as $ext) {
    $ok(extension_loaded($ext), "Extension: {$ext}");
}
$ok(extension_loaded('gd') || extension_loaded('imagick'), 'Extension: gd or imagick (image processing)');

$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
foreach (['proc_open', 'exec', 'symlink', 'putenv'] as $fn) {
    $ok(! in_array($fn, $disabled, true), "Function enabled: {$fn}", $fn === 'proc_open' ? 'needed by the scheduler/queue' : '');
}

$ok((int) ini_get('memory_limit') >= 256 || ini_get('memory_limit') === '-1', 'memory_limit ≥ 256M', (string) ini_get('memory_limit'));
$ok(PHP_INT_SIZE === 8, '64-bit PHP');
$ok(function_exists('password_hash') && defined('PASSWORD_ARGON2ID'), 'Argon2id password hashing available (else bcrypt is used)');

$cli = trim((string) @shell_exec('command -v php 2>/dev/null'));
$checks[] = [true, 'PHP CLI path for cron', $cli !== '' ? $cli : 'run "which php" in cPanel Terminal'];

foreach ($checks as [$pass, $label, $detail]) {
    printf("[%s] %s%s\n", $pass ? ' OK ' : 'FAIL', $label, $detail !== '' ? " — {$detail}" : '');
}

echo "\nAlso confirm in cPanel: MySQL 8 / MariaDB 10.6+, Cron Jobs (every minute allowed), Terminal/SSH access, AutoSSL.\n";
echo "Delete this file now.\n";
