<?php
// CLI only. Credentials are read from Docker secrets and never passed as arguments.
if (PHP_SAPI !== 'cli') { exit(1); }
$root = '/var/www/html';
$pass = trim(file_get_contents(getenv('LAB_ADMIN_PASSWORD_FILE')));
if (strlen($pass) < 16) { throw new RuntimeException('Admin password must contain at least 16 characters.'); }
$dbpass = trim(file_get_contents(getenv('MOODLE_DB_PASSWORD_FILE')));
$pdo = new PDO('pgsql:host=db;dbname=moodle', 'moodle', $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$installed = $pdo->query("SELECT to_regclass('public.mdl_config')")->fetchColumn();
$pdo = null;
if (!$installed) {
    $argv = ["$root/admin/cli/install_database.php", '--agree-license',
        '--lang=ru', '--adminuser=' . (getenv('LAB_ADMIN_USER') ?: 'labadmin'), '--adminpass=' . $pass,
        '--adminemail=' . (getenv('LAB_ADMIN_EMAIL') ?: 'admin@example.invalid'),
        '--fullname=' . getenv('LAB_SITE_NAME'), '--shortname=СКБ · Цифровая метрология'];
    $_SERVER['argv'] = $argv;
    $_SERVER['argc'] = count($argv);
    require "$root/admin/cli/install_database.php";
} else {
    echo "Existing database: installation skipped. Run upgrade.php explicitly after a backup when updating.\n";
}
