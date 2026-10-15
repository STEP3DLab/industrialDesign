<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();
function lab_secret(string $name): string {
    $file = getenv($name . '_FILE');
    return $file ? trim(file_get_contents($file)) : (getenv($name) ?: '');
}
$CFG->dbtype = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost = getenv('MOODLE_DB_HOST') ?: 'db';
$CFG->dbname = getenv('MOODLE_DB_NAME') ?: 'moodle';
$CFG->dbuser = getenv('MOODLE_DB_USER') ?: 'moodle';
$CFG->dbpass = lab_secret('MOODLE_DB_PASSWORD');
$CFG->prefix = 'mdl_';
$CFG->dboptions = ['dbpersist' => false, 'dbport' => getenv('MOODLE_DB_PORT') ?: 5432, 'dbsocket' => ''];
$CFG->wwwroot = rtrim(getenv('MOODLE_URL') ?: 'http://localhost:8080', '/');
$CFG->dataroot = '/var/moodledata';
$CFG->admin = 'admin';
$CFG->directorypermissions = 02770;
$CFG->sslproxy = getenv('MOODLE_SSL_PROXY') === '1';
$CFG->cookiesecure = str_starts_with($CFG->wwwroot, 'https://');
$CFG->cookiehttponly = true;
$CFG->preventexecpath = true;
$CFG->disableupdateautodeploy = true;
$CFG->upgradekey = lab_secret('MOODLE_UPGRADE_KEY');
require_once(__DIR__ . '/lib/setup.php');
