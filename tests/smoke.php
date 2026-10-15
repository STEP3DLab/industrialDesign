<?php
if (getenv('CI') !== '1') { fwrite(STDERR, "Only run against an ephemeral CI database.\n"); exit(1); }
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$c = $DB->get_record('course', ['idnumber' => 'RSSU-DM-2026'], '*', MUST_EXIST);
$ctx = context_course::instance($c->id);
check($c->enablecompletion == 1, 'Completion missing');
check(file_exists($CFG->dataroot . '/lang/ru/moodle.php'), 'Russian language pack');
check(get_config('backup', 'backup_auto_max_kept') == 5, 'Backup retention');
check($DB->count_records('course_sections', ['course' => $c->id]) === 8, 'Sections');
check($DB->count_records('event', ['courseid' => $c->id, 'eventtype' => 'course']) === 30, 'Calendar event count');
check($DB->count_records('assign', ['course' => $c->id]) === 5, 'Assignment count');
$q = $DB->get_record('quiz', ['course' => $c->id], '*', MUST_EXIST);
check($DB->count_records('quiz_slots', ['quizid' => $q->id]) === 8, 'Quiz must have 8 questions');
check((float)$q->sumgrades === 8.0, 'Quiz sumgrades');
check((float)$q->grade === 0.0, 'Formative quiz must not affect course grade');
check($DB->get_field('course_modules', 'visible', ['idnumber' => 'DM-L02']) == 0, 'Outdated lecture must remain hidden');
check($DB->get_field('course_modules', 'visible', ['idnumber' => 'DM-LEGAL-DRAFT']) == 0, 'Unfinished legal topic must remain hidden');
check(\local_technopark\activity::interval(1000, 1180) === 180, 'Short interval');
check(\local_technopark\activity::interval(1000, 2801) === 0, 'Idle gap must not count');
check(\local_technopark\activity::interval(1000, 1000) === 0, 'Duplicate event');
check(\local_technopark\activity::csv_cell('=1+1') === "'=1+1", 'CSV formula protection');
$instance = $DB->get_record('enrol', ['courseid' => $c->id, 'enrol' => 'manual'], '*', MUST_EXIST);
foreach (['student', 'editingteacher'] as $role) {
    $u = (object)['username' => 'ci_' . $role, 'password' => 'Test-only-72Hours!', 'auth' => 'manual',
        'firstname' => 'CI', 'lastname' => $role, 'email' => $role . '@example.invalid', 'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id, 'lang' => 'ru'];
    if (!$DB->record_exists('user', ['username' => $u->username])) $uid = user_create_user($u);
    else $uid = $DB->get_field('user', 'id', ['username' => $u->username]);
    enrol_get_plugin('manual')->enrol_user($instance, $uid, $DB->get_field('role', 'id', ['shortname' => $role]));
    check(has_capability('local/technopark:viewreport', $ctx, $uid) === ($role === 'editingteacher'), 'Report permission ' . $role);
}
echo "PASS: course, calendar, assignments, quiz, draft visibility, interval calculation, CSV, role permissions.\n";
