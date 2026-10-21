<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library callbacks for the class log.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add "Class log" and "Attendance report" to the course navigation.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_classlog_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context): void {
    if ((int) $course->id === SITEID || !has_capability('local/classlog:view', $context)) {
        return;
    }
    $navigation->add(
        get_string('sessions', 'local_classlog'),
        new moodle_url('/local/classlog/index.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_classlog_sessions',
        new pix_icon('i/calendar', ''),
    );
    $navigation->add(
        get_string('report', 'local_classlog'),
        new moodle_url('/local/classlog/report.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_classlog_report',
        new pix_icon('i/report', ''),
    );
}
