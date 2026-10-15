<?php
defined('MOODLE_INTERNAL') || die();
$capabilities = ['local/technopark:viewreport' => [
    'captype' => 'read', 'contextlevel' => CONTEXT_COURSE,
    'riskbitmask' => RISK_PERSONAL,
    'archetypes' => ['editingteacher' => CAP_ALLOW, 'teacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
]];
