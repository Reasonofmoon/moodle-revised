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

namespace local_classlog\event;

/**
 * Shared behaviour of class log session events.
 *
 * Create with ['objectid' => session id, 'context' => course context].
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class session_base extends \core\event\base {

    /** @var string Verb used in the description, e.g. "opened". Set by each event. */
    protected const ACTION = '';

    /** @var string c, r, u or d. Set by each event. */
    protected const CRUD = 'u';

    #[\Override]
    protected function init() {
        $this->data['objecttable'] = 'local_classlog_session';
        $this->data['crud'] = static::CRUD;
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    #[\Override]
    public function get_description() {
        return "The user with id '{$this->userid}' " . static::ACTION .
            " the class session with id '{$this->objectid}' in the course with id '{$this->courseid}'.";
    }

    #[\Override]
    public function get_url() {
        return new \moodle_url('/local/classlog/session.php', ['id' => $this->objectid]);
    }

    #[\Override]
    public static function get_objectid_mapping() {
        // Class logs are not part of course backups.
        return false;
    }

    #[\Override]
    public static function get_other_mapping() {
        return false;
    }
}
