<?php
// Seeds a fresh site once; later executions preserve all teacher edits and learner data.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
\core\session\manager::set_user(get_admin());
if (get_config('local_technopark', 'seedversion')) {
    echo "Already seeded. Existing course and settings preserved.\n"; exit(0);
}
if ($DB->record_exists('course', ['idnumber' => 'RSSU-DM-2026'])) {
    throw new RuntimeException('Course ID already exists without seed marker. Inspect before retrying.');
}
$transaction = $DB->start_delegated_transaction();
foreach (['lang' => 'ru', 'timezone' => 'Europe/Moscow', 'forcetimezone' => 'Europe/Moscow',
    'enablecompletion' => 1, 'enableavailability' => 1, 'forcelogin' => 1, 'guestloginbutton' => 0,
    'registerauth' => '', 'auth' => 'manual', 'theme' => 'boost', 'defaulthomepage' => 1,
    'custommenuitems' => "Лаборатория|/local/technopark/index.php", 'sessiontimeout' => 7200,
    'backup_auto_active' => 1, 'backup_auto_weekdays' => '1111111', 'backup_auto_hour' => 3,
    'backup_auto_minute' => 15] as $k => $v) set_config($k, $v);
set_config('enabled_stores', 'logstore_standard', 'tool_log');
set_config('loglifetime', 365 * DAYSECS, 'logstore_standard');
set_config('scsspre', '$primary: #183b75;', 'theme_boost');
set_config('scss', file_get_contents('/opt/branding/boost.scss'), 'theme_boost');
$site = get_site();
$site->fullname = 'СДО лаборатории промышленного дизайна и инжиниринга — СКБ Технопарка РГСУ';
$site->shortname = 'СКБ · Цифровая метрология';
$site->summary = '<p>Лаборатория промышленного дизайна и инжиниринга СКБ Технопарка РГСУ.</p><p><a href="' . $CFG->wwwroot . '/local/technopark/index.php">Открыть учебный портал →</a></p>';
$site->summaryformat = FORMAT_HTML;
update_course($site);
$category = core_course_category::create(['name' => 'СКБ Технопарка РГСУ', 'idnumber' => 'RSSU-LAB']);
$tz = new DateTimeZone('Europe/Moscow');
$course = create_course((object)[
    'fullname' => 'Цифровая метрология: обратное проектирование и контроль качества в аддитивном производстве',
    'shortname' => 'ЦМ · 72 часа · 2026', 'idnumber' => 'RSSU-DM-2026', 'category' => $category->id,
    'format' => 'topics', 'numsections' => 7, 'coursedisplay' => 0, 'visible' => 1, 'lang' => 'ru',
    'startdate' => (new DateTimeImmutable('2026-10-19 00:00', $tz))->getTimestamp(),
    'enddate' => (new DateTimeImmutable('2026-11-05 00:00', $tz))->getTimestamp(),
    'enablecompletion' => 1, 'showcompletionconditions' => 1, 'showactivitydates' => 1,
    'summary' => '72 академических часа · 19.10–04.11.2026 · 10 ч онлайн, 10 ч самостоятельно, 52 ч очно. Москва, Беговая, 12.',
    'summaryformat' => FORMAT_HTML, 'newsitems' => 5,
]);
$context = context_course::instance($course->id);
$manual = enrol_get_plugin('manual');
if (!$DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'manual'])) $manual->add_instance($course);
// Disable guest and self enrolment if any are created by a changed site default.
$DB->set_field_select('enrol', 'status', ENROL_INSTANCE_DISABLED, 'courseid = ? AND enrol <> ?', [$course->id, 'manual']);
$sections = [0 => 'Начните здесь', 1 => '01 · Основы цифровой метрологии и нормативная база РФ',
    2 => '02 · Технологии 3D-сканирования', 3 => '03 · Обратное проектирование — Geomagic Design X',
    4 => '04 · Автоматизированный контроль и метрологическая экспертиза', 5 => '05 · Оснастка и аддитивное производство',
    6 => 'Итоговая аттестация', 7 => 'Материалы преподавателя · подготовка'];
course_create_sections_if_missing($course, array_keys($sections));
foreach ($sections as $number => $name) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number], '*', MUST_EXIST);
    course_update_section($course, $section, ['name' => $name, 'visible' => $number === 7 ? 0 : 1]);
}
function lab_add(string $type, string $key, string $name, int $section, array $extra = []) {
    global $DB, $course;
    $info = (object)array_merge(['modulename' => $type, 'module' => $DB->get_field('modules', 'id', ['name' => $type], MUST_EXIST),
        'section' => $section, 'name' => $name, 'cmidnumber' => $key, 'visible' => 1, 'groupmode' => 0,
        'intro' => '', 'introformat' => FORMAT_HTML, 'completion' => COMPLETION_TRACKING_NONE], $extra);
    return add_moduleinfo($info, $course);
}
function lab_page(string $key, string $name, int $section, string $content, bool $visible = true) {
    return lab_add('page', $key, $name, $section, ['content' => $content, 'contentformat' => FORMAT_HTML,
        'display' => 0, 'printintro' => 0, 'printlastmodified' => 1, 'visible' => (int)$visible,
        'completion' => $visible ? COMPLETION_TRACKING_AUTOMATIC : COMPLETION_TRACKING_NONE, 'completionview' => (int)$visible]);
}
lab_page('DM-WELCOME', 'Как проходить курс', 0,
    '<h2>От скана — к инженерному решению</h2><p>Открывайте материалы модулей, выполняйте практические задания и передавайте файлы через элементы «Задание». Преподаватель проверяет работы и оставляет обратную связь.</p><p>Расписание, названия тем и время соответствуют редакции от 15.10.2026. Всё время — московское.</p><p>Преподаватели: Понкратова Х. А., Ганьшин В. К., Рекут А. В.</p><p>Просмотр материала отмечает знакомство с ним. Самопроверка помогает учиться; итоговую аттестацию она не заменяет. Посещение очного занятия и вебинара фиксирует преподаватель.</p>');
$schedule = json_decode(file_get_contents('/opt/course/schedule.json'), true, 512, JSON_THROW_ON_ERROR);
$table = '<div class="lab-schedule"><table class="table"><thead><tr><th>Дата</th><th>Время (МСК)</th><th>Тема</th><th>Формат</th><th>Акад. ч</th></tr></thead><tbody>';
foreach ($schedule as $row) {
    $table .= '<tr><td>' . s($row['date'] ?: 'Без фиксированной даты') . '</td><td>' . s($row['time'] ?: 'Самостоятельно') . '</td><td>' . s($row['title']) . '</td><td>' . s($row['mode']) . '</td><td>' . $row['hours'] . '</td></tr>';
    if ($row['date'] && $row['time']) {
        [$from, $to] = explode('-', $row['time']);
        $start = new DateTimeImmutable($row['date'] . ' ' . $from, $tz);
        $finish = new DateTimeImmutable($row['date'] . ' ' . $to, $tz);
        calendar_event::create((object)['name' => $row['title'], 'description' => $row['mode'] . ' · ' . $row['hours'] . ' акад. ч. ' . ($row['mode'] === 'очно' ? 'Москва, Беговая, 12.' : 'Ссылку публикует преподаватель.'),
            'format' => FORMAT_HTML, 'courseid' => $course->id, 'groupid' => 0, 'userid' => 0, 'modulename' => '',
            'instance' => 0, 'eventtype' => 'course', 'timestart' => $start->getTimestamp(),
            'timeduration' => $finish->getTimestamp() - $start->getTimestamp(), 'visible' => 1]);
    }
}
$table .= '</tbody></table></div><p>Итого 72 часа: 10 лекций онлайн, 50 очной практики, 10 самостоятельной работы, 2 итоговой аттестации. Для экзамена источник одновременно указывает 11:00–13:30 и 2 академических часа; значения сохранены.</p>';
lab_page('DM-SCHEDULE', 'Расписание · 19 октября — 4 ноября', 0, $table);
$mapping = [1 => 1, 2 => 7, 3 => 2, 4 => 2, 5 => 3, 6 => 3, 7 => 4, 8 => 4, 9 => 5, 10 => 5];
foreach (json_decode(file_get_contents('/opt/course/lectures.json')) as $lecture) {
    $n = $lecture->number;
    lab_page(sprintf('DM-L%02d', $n), $lecture->title . ($lecture->published ? '' : ' · план'), $mapping[$n],
        file_get_contents(sprintf('/opt/course/lectures/L%02d.html', $n)), $lecture->published);
}
lab_page('DM-LEGAL-DRAFT', 'Тема 1.2 · подготовка материала', 1,
    '<h2>Гражданские и трудовые правоотношения в сфере охраны здоровья граждан</h2><p>Занятие 19.10.2026, 12:35–14:05. Тема сохранена по утверждённому расписанию. Требуется конспект преподавателя и отдельные задания. Заготовка L02 по допускам не заменяет эту тему.</p>', false);
lab_page('DM-TEACHER', 'Готовность курса и действия преподавателя', 7,
    '<h2>Перед зачислением группы</h2><ol><li>Подготовить конспект темы 1.2 и задания утверждённого теста модуля 1.</li><li>Завершить планы L03–L10 и открыть их слушателям после проверки.</li><li>Добавить ссылки на онлайн-занятия. Сейчас фиктивных ссылок нет.</li><li>Утвердить критерии практических заданий и билеты экзамена.</li><li>Зачислить слушателей вручную или импортом CSV и назначить преподавателей.</li></ol><p>Восемь вопросов L01 — формирующая самопроверка. Критерий успешного завершения всего курса и выдача документов автоматически не назначены.</p><p>Активность по журналу — оценка взаимодействия с Moodle. Присутствие на вебинарах и очной практике фиксируется преподавателем отдельно.</p>', false);
function lab_assignment(string $key, string $name, int $section, string $intro, bool $online = true) {
    return lab_add('assign', $key, $name, $section, [
        'intro' => $intro, 'alwaysshowdescription' => 1, 'submissiondrafts' => (int)$online,
        'requiresubmissionstatement' => 0, 'sendnotifications' => 0, 'sendstudentnotifications' => 1, 'sendlatenotifications' => 0,
        'duedate' => 0, 'allowsubmissionsfromdate' => 0, 'cutoffdate' => 0, 'gradingduedate' => 0,
        'grade' => 100, 'teamsubmission' => 0, 'requireallteammemberssubmit' => 0,
        'teamsubmissiongroupingid' => 0, 'blindmarking' => 0, 'markingworkflow' => 1, 'markingallocation' => 0,
        'attemptreopenmethod' => 'manual', 'maxattempts' => -1,
        'assignsubmission_file_enabled' => (int)$online, 'assignsubmission_file_maxfiles' => 10,
        'assignsubmission_file_maxsizebytes' => 134217728, 'assignsubmission_onlinetext_enabled' => (int)$online,
        'assignfeedback_comments_enabled' => 1, 'assignfeedback_file_enabled' => 1,
        'completion' => $online ? COMPLETION_TRACKING_AUTOMATIC : COMPLETION_TRACKING_NONE,
        'completionsubmit' => (int)$online]);
}
lab_assignment('DM-SCAN', 'Практическая работа · результаты 3D-сканирования', 2,
    '<p>Передайте исходные данные и обработанную сетку, описание настройки RangeVision Spectrum, контрольные размеры и вывод о пригодности результата. Приложите журнал операций. Защита по расписанию — 22.10, 13:30–15:00.</p><p>Критерии обсуждаются на занятии: полнота данных, прослеживаемость, обоснование контрольных характеристик и выводов.</p>');
lab_assignment('DM-CAD', 'Кейс-задача · параметрическая модель', 3,
    '<p>Передайте исходную сетку, нативный проект Geomagic Design X, модель STEP и краткое обоснование конструкторской логики. Отделите измеренные данные от гипотез о номинальной форме. Самостоятельная работа по расписанию — 27.10.</p>');
lab_assignment('DM-QA', 'Отчёт · инспекция и контроль качества', 4,
    '<p>Передайте отчёт инспекции: контролируемые характеристики, выбранные базы, исходные измерения, отклонения и обоснованный вывод. Укажите единицы, условия, версию модели и ограничения измерительной процедуры. По расписанию — 29.10.</p>');
lab_assignment('DM-FIXTURE', 'Проект · печатная технологическая оснастка', 5,
    '<p>Передайте модель оснастки, схему базирования, параметры изготовления и результаты метрологической и функциональной проверки. Укажите принтер, материал, ориентацию и постобработку. Защита — 31.10, 13:30–15:00.</p>');
lab_assignment('DM-EXAM', 'Экзамен · оценка преподавателя', 6,
    '<p>Очный экзамен по билетам: теория и практика. 04.11.2026, 11:00–13:30, Москва, Беговая, 12. Билеты и критерии сообщает преподаватель. Эта запись предназначена для оценки и обратной связи после экзамена; отправка файлов не требуется.</p>', false);
// A separate, explicitly formative quiz; no pass threshold is presented as approved.
$settings = ['intro' => '<p>8 вопросов по лекции 1. Это самопроверка, которая не заменяет промежуточную аттестацию по всему модулю. Повторные попытки разрешены; пояснения доступны после отправки.</p>',
    'timeopen' => 0, 'timeclose' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0,
    'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST, 'decimalpoints' => 2, 'questiondecimalpoints' => -1,
    'questionsperpage' => 1, 'shuffleanswers' => 1, 'sumgrades' => 0, 'grade' => 0,
    'timelimit' => 0, 'overduehandling' => 'autosubmit', 'graceperiod' => 0, 'quizpassword' => '',
    'subnet' => '', 'browsersecurity' => '', 'delay1' => 0, 'delay2' => 0, 'showuserpicture' => 0,
    'showblocks' => 0, 'navmethod' => 'free', 'completion' => COMPLETION_TRACKING_AUTOMATIC,
    'completionminattempts' => 1, 'completionunlocked' => 1, 'completionminattemptsenabled' => 1];
foreach (['attempt', 'correctness', 'maxmarks', 'marks', 'specificfeedback', 'generalfeedback', 'rightanswer', 'overallfeedback'] as $what) {
    foreach (['during', 'immediately', 'open', 'closed'] as $when) $settings[$what . $when] = $when === 'during' ? (int)($what === 'attempt') : 1;
}
$quizmodule = lab_add('quiz', 'DM-SELF-L01', 'Самопроверка · основы измерений', 1, $settings);
$questioncategory = (object)['name' => 'ЦМ · Лекция 1 · Самопроверка', 'contextid' => $context->id, 'info' => '',
    'infoformat' => FORMAT_HTML, 'stamp' => make_unique_id_code(), 'parent' => 0, 'sortorder' => 999];
$questioncategory->id = $DB->insert_record('question_categories', $questioncategory);
$format = new qformat_xml();
$format->setCategory($questioncategory); $format->setCourse($course); $format->setContexts([$context]);
$format->setFilename('/opt/course/questions-L01.xml'); $format->setRealfilename('questions-L01.xml');
$format->setMatchgrades('error'); $format->setCatfromfile(false); $format->setContextfromfile(false); $format->set_display_progress(false);
if (!$format->importpreprocess() || !$format->importprocess() || !$format->importpostprocess()) throw new RuntimeException('Question import failed.');
$quiz = $DB->get_record('quiz', ['id' => $quizmodule->instance], '*', MUST_EXIST);
foreach ($format->questionids as $qid) quiz_add_quiz_question($qid, $quiz);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
set_config('seedversion', 2026101500, 'local_technopark');
set_config('courseid', $course->id, 'local_technopark');
rebuild_course_cache($course->id, true);
$transaction->allow_commit();
echo 'Course ready: ' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . "\n";
