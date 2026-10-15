<?php
defined('MOODLE_INTERNAL') || die();
function local_technopark_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/technopark:viewreport', $context)) {
        $navigation->add('Активность и время', new moodle_url('/local/technopark/report.php', ['id' => $course->id]), navigation_node::TYPE_CUSTOM);
    }
}
