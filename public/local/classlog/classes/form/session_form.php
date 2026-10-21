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

namespace local_classlog\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_classlog\local\session_manager;
use local_classlog\local\status;

/**
 * Attendance + lesson log form for one session.
 *
 * Custom data: entries (from session_manager::get_entries), courseid, sessionid.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
        $entries = $this->_customdata['entries'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('date_time_selector', 'sessiontime', get_string('sessiontime', 'local_classlog'), ['step' => 10]);

        $mform->addElement('header', 'attendancehdr', get_string('attendance', 'local_classlog'));
        $mform->setExpanded('attendancehdr');
        if (!$entries) {
            $mform->addElement('static', 'nostudents', '', get_string('nostudents', 'local_classlog'));
        }
        foreach ($entries as $userid => $entry) {
            $radios = [];
            foreach (status::ALL as $value) {
                $radios[] = $mform->createElement('radio', "status[{$userid}]", '', status::label($value), $value);
            }
            $mform->addGroup($radios, "statusgroup[{$userid}]", s($entry->fullname), ' ', false);
            $mform->setType("status[{$userid}]", PARAM_INT);

            $mform->addElement('text', "lateminutes[{$userid}]", get_string('lateminutes', 'local_classlog'),
                ['size' => 3, 'inputmode' => 'numeric']);
            $mform->setType("lateminutes[{$userid}]", PARAM_INT);
            $mform->hideIf("lateminutes[{$userid}]", "status[{$userid}]", 'neq', status::LATE);

            $mform->addElement('text', "remark[{$userid}]", get_string('remark', 'local_classlog'),
                ['size' => 30, 'maxlength' => 255]);
            $mform->setType("remark[{$userid}]", PARAM_TEXT);
        }

        $mform->addElement('header', 'lessonhdr', get_string('lessonlog', 'local_classlog'));
        $mform->setExpanded('lessonhdr');
        foreach (['progress', 'homework', 'memo'] as $field) {
            $mform->addElement('textarea', $field, get_string($field, 'local_classlog'), ['rows' => 3, 'cols' => 60]);
            $mform->setType($field, PARAM_TEXT);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $taken = session_manager::time_taken(
            (int) $this->_customdata['courseid'],
            (int) $data['sessiontime'],
            (int) $this->_customdata['sessionid'],
        );
        if ($taken) {
            $errors['sessiontime'] = get_string('sessionexists', 'local_classlog');
        }
        return $errors;
    }
}
