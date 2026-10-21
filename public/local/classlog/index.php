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
 * List of class sessions for a course.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_classlog\local\session_manager;

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/classlog:view', $context);
$canmanage = has_capability('local/classlog:manage', $context);

$url = new moodle_url('/local/classlog/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('sessions', 'local_classlog') . ' | ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

if (optional_param('open', 0, PARAM_BOOL)) {
    require_sesskey();
    require_capability('local/classlog:manage', $context);
    $session = session_manager::open((int) $course->id, time());
    redirect(new moodle_url('/local/classlog/session.php', ['id' => $session->id]));
}

$format = get_string('strftimedatetimeshort', 'core_langconfig');
$sessions = [];
foreach (session_manager::list_sessions((int) $course->id) as $session) {
    $lines = preg_split('/\R/', $session->progress);
    $sessions[] = [
        'date' => userdate($session->sessiontime, $format),
        'url' => (new moodle_url('/local/classlog/session.php', ['id' => $session->id]))->out(false),
        'counts' => get_string('counts', 'local_classlog', $session),
        'progress' => $lines[0] ?? '',
    ];
}

$openbutton = '';
if ($canmanage) {
    $openbutton = $OUTPUT->single_button(
        new moodle_url($url, ['open' => 1]),
        get_string('opentoday', 'local_classlog'),
        'post',
        ['type' => single_button::BUTTON_PRIMARY],
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sessions', 'local_classlog'));
echo $OUTPUT->render_from_template('local_classlog/session_list', [
    'openbutton' => $openbutton,
    'reporturl' => (new moodle_url('/local/classlog/report.php', ['id' => $course->id]))->out(false),
    'hassessions' => !empty($sessions),
    'sessions' => $sessions,
]);
echo $OUTPUT->footer();
