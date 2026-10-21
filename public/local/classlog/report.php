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
 * Per-student attendance report for a course.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_classlog\form\report_filter_form;
use local_classlog\local\report;

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/classlog:view', $context);

// Default range: first day of this month to today (user's time zone). "to" is a day, inclusive.
$today = usergetdate(time());
$from = optional_param('from', make_timestamp($today['year'], $today['mon'], 1), PARAM_INT);
$to = optional_param('to', usergetmidnight(time()), PARAM_INT);

$url = new moodle_url('/local/classlog/report.php', ['id' => $course->id]);
$PAGE->set_url(new moodle_url($url, ['from' => $from, 'to' => $to]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('report', 'local_classlog') . ' | ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$form = new report_filter_form($url, null, 'get');
if ($data = $form->get_data()) {
    $from = (int) $data->datefrom;
    $to = (int) $data->dateto;
}
$form->set_data(['id' => $course->id, 'datefrom' => $from, 'dateto' => $to]);

$rows = report::summarise((int) $course->id, $from, $to + DAYSECS);

if (optional_param('download', '', PARAM_ALPHA) === 'csv') {
    $filename = clean_filename('attendance-' . $course->shortname . '-' . date('Ymd', $from) . '-' . date('Ymd', $to));
    \core\dataformat::download_data($filename, 'csv', report::csv_columns(), report::csv_rows($rows));
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'local_classlog'));
$form->display();
echo $OUTPUT->render_from_template('local_classlog/report', [
    'hasrows' => !empty($rows),
    'rows' => array_map(function (\stdClass $row): array {
        $data = (array) $row;
        $data['rate'] = $row->rate ?? '-';
        return $data;
    }, $rows),
    'downloadurl' => (new moodle_url($url, ['from' => $from, 'to' => $to, 'download' => 'csv']))->out(false),
]);
echo $OUTPUT->footer();
