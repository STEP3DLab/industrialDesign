<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
$id = required_param('id', PARAM_INT);
$export = optional_param('export', 0, PARAM_BOOL);
$course = get_course($id);
require_login($course);
$context = context_course::instance($id);
require_capability('local/technopark:viewreport', $context);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_url('/local/technopark/report.php', ['id' => $id]);
$PAGE->set_title('Активность и время · ' . $course->shortname);
$PAGE->set_heading('Активность и время');
$users = get_enrolled_users($context, 'mod/assign:submit', 0, 'u.id,u.firstname,u.lastname', 'u.lastname,u.firstname', 0, 0, true);
$stats = [];
foreach ($users as $u) $stats[$u->id] = ['seconds' => 0, 'events' => 0, 'last' => 0, 'sessions' => 0];
$logs = $DB->get_recordset_select('logstore_standard_log', 'courseid = ? AND anonymous = 0 AND origin = ?', [$id, 'web'], 'userid,timecreated,id', 'id,userid,timecreated');
foreach ($logs as $log) {
    if (!isset($stats[$log->userid])) continue;
    $s = &$stats[$log->userid];
    $gap = $log->timecreated - $s['last'];
    if (!$s['last'] || $gap > 1800) $s['sessions']++;
    if ($s['last']) $s['seconds'] += \local_technopark\activity::interval($s['last'], (int)$log->timecreated);
    $s['last'] = (int)$log->timecreated;
    $s['events']++;
    unset($s);
}
$logs->close();
$completion = new completion_info($course);
$activities = $completion->get_activities();
$rows = [];
foreach ($users as $u) {
    $done = 0;
    foreach ($activities as $cm) {
        $state = $completion->get_data($cm, false, $u->id)->completionstate;
        if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) $done++;
    }
    $s = $stats[$u->id];
    $rows[] = [fullname($u), (string)$s['events'], (string)$s['sessions'],
        number_format($s['seconds'] / 60, 1, '.', ''), $done . '/' . count($activities),
        $s['last'] ? userdate($s['last'], '%d.%m.%Y %H:%M', 'Europe/Moscow') : '—'];
}
$headers = ['Слушатель', 'События', 'Сессии', 'Оценка времени, мин', 'Выполнено', 'Последнее событие (МСК)'];
if ($export) {
    require_sesskey();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity-' . $id . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers, ';');
    foreach ($rows as $row) fputcsv($out, array_map([\local_technopark\activity::class, 'csv_cell'], $row), ';');
    fclose($out); exit;
}
echo $OUTPUT->header();
echo '<p class="lab-note">Оценка времени по событиям Moodle: складываются интервалы не более 30 минут между соседними событиями пользователя в этом курсе. Одиночное событие даёт 0 минут. Это не измерение внимания, не длительность вебинара и не зачёт академических часов. Данные отражают только сохранённый журнал.</p>';
echo html_writer::link(new moodle_url('/local/technopark/report.php', ['id' => $id, 'export' => 1, 'sesskey' => sesskey()]), 'Скачать CSV', ['class' => 'btn btn-primary mb-3']);
$table = new html_table(); $table->head = $headers; $table->data = array_map(fn($row) => array_map('s', $row), $rows);
echo html_writer::table($table);
echo '<p>Просмотры, завершение заданий и результаты тестов доступны также в стандартных отчётах Moodle. Посещение очных и онлайн-занятий преподаватель подтверждает отдельно.</p>';
echo $OUTPUT->footer();
