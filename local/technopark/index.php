<?php
require_once(__DIR__ . '/../../config.php');
require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/technopark/index.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('СДО · СКБ Технопарка РГСУ');
$PAGE->set_heading('Лаборатория промышленного дизайна и инжиниринга');
echo $OUTPUT->header();
$course = $DB->get_record('course', ['idnumber' => 'RSSU-DM-2026']);
echo html_writer::empty_tag('img', ['src' => $OUTPUT->image_url('rssu', 'local_technopark'), 'alt' => 'РГСУ', 'class' => 'lab-logo']);
echo '<section class="lab-hero"><div class="lab-eyebrow">СКБ Технопарка РГСУ · Повышение квалификации</div><h1>Цифровая<br>метрология</h1><p>От измерения реальной детали — к цифровой модели, проверке качества и изготовлению оснастки.</p>';
if ($course) echo html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), 'Перейти к курсу →', ['class' => 'btn']);
echo '</section><div class="lab-grid"><div class="lab-card"><strong>72 часа</strong>10 онлайн · 10 самостоятельно · 52 очно</div><div class="lab-card"><strong>19 октября</strong>Начало курса · 2026 год</div><div class="lab-card"><strong>5 модулей</strong>Итоговый экзамен — 4 ноября</div></div>';
echo '<h2>Ваш учебный маршрут</h2><ol class="lab-modules">';
$modules = ['Основы цифровой метрологии', 'Технологии 3D-сканирования', 'Обратное проектирование', 'Контроль и метрологическая экспертиза', 'Оснастка и аддитивное производство'];
foreach ($modules as $i => $name) echo '<li><span>МОДУЛЬ ' . ($i + 1) . '</span><b>' . s($name) . '</b></li>';
echo '</ol><p class="lab-note">Очные занятия: Москва, ул. Беговая, д. 12. Время в расписании — московское. Ссылки на онлайн-занятия публикует преподаватель в курсе.</p>';
if ($course && has_capability('local/technopark:viewreport', context_course::instance($course->id))) {
    echo html_writer::link(new moodle_url('/local/technopark/report.php', ['id' => $course->id]), 'Отчёт об активности и времени', ['class' => 'btn btn-outline-primary']);
}
echo $OUTPUT->footer();
