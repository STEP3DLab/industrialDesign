<?php
namespace local_technopark;
defined('MOODLE_INTERNAL') || die();
final class activity {
    // Only intervals between successive events within a 30-minute session count.
    // Single events count as 0 seconds; no guessed session-end duration is added.
    public static function interval(int $previous, int $current, int $cutoff = 1800): int {
        $delta = $current - $previous;
        return $delta > 0 && $delta <= $cutoff ? $delta : 0;
    }
    public static function csv_cell(string $value): string {
        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'" . $value : $value;
    }
}
