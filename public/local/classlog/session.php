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
 * Attendance and lesson log for one class session.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_classlog\form\session_form;
use local_classlog\local\session_manager;
use local_classlog\local\status;

$id = required_param('id', PARAM_INT);
$session = session_manager::get($id);
$course = get_course($session->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/classlog:view', $context);
$canmanage = has_capability('local/classlog:manage', $context);

$url = new moodle_url('/local/classlog/session.php', ['id' => $id]);
$listurl = new moodle_url('/local/classlog/index.php', ['id' => $course->id]);
$when = userdate($session->sessiontime, get_string('strftimedatetimeshort', 'core_langconfig'));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($when . ' | ' . get_string('sessions', 'local_classlog'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('sessions', 'local_classlog'), $listurl);
$PAGE->navbar->add($when);

if (optional_param('delete', 0, PARAM_BOOL)) {
    require_capability('local/classlog:manage', $context);
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        session_manager::delete($id);
        redirect($listurl, get_string('sessiondeleted', 'local_classlog'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdelete', 'local_classlog', $when),
        new moodle_url($url, ['delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $url,
    );
    echo $OUTPUT->footer();
    exit;
}

$entries = session_manager::get_entries($id);
$form = new session_form($url, [
    'entries' => $entries,
    'courseid' => (int) $course->id,
    'sessionid' => $id,
], 'post', '', null, $canmanage);

if ($form->is_cancelled()) {
    redirect($listurl);
}
if ($canmanage && ($data = $form->get_data())) {
    $values = [];
    foreach (array_keys($entries) as $userid) {
        $values[$userid] = [
            'status' => (int) ($data->status[$userid] ?? status::PRESENT),
            'lateminutes' => isset($data->lateminutes[$userid]) ? (int) $data->lateminutes[$userid] : null,
            'remark' => (string) ($data->remark[$userid] ?? ''),
        ];
    }
    session_manager::save($id, (int) $data->sessiontime, $values, [
        'progress' => $data->progress,
        'homework' => $data->homework,
        'memo' => $data->memo,
    ]);
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$note = session_manager::get_note($id);
$defaults = [
    'id' => $id,
    'sessiontime' => $session->sessiontime,
    'progress' => $note->progress,
    'homework' => $note->homework,
    'memo' => $note->memo,
    'status' => [],
    'lateminutes' => [],
    'remark' => [],
];
foreach ($entries as $userid => $entry) {
    $defaults['status'][$userid] = $entry->status;
    $defaults['lateminutes'][$userid] = $entry->lateminutes;
    $defaults['remark'][$userid] = $entry->remark;
}
$form->set_data($defaults);

$modifier = core_user::get_user($session->usermodified);
echo $OUTPUT->header();
echo $OUTPUT->heading($when);
echo html_writer::div(get_string('lastmodified', 'local_classlog', [
    'user' => $modifier ? s(fullname($modifier)) : '-',
    'time' => userdate($session->timemodified, get_string('strftimedatetimeshort', 'core_langconfig')),
]), 'text-muted small mb-3');
$form->display();
if ($canmanage) {
    echo $OUTPUT->single_button(new moodle_url($url, ['delete' => 1]), get_string('deletesession', 'local_classlog'), 'get');
}
echo $OUTPUT->footer();
