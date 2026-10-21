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

namespace local_classlog\local;

/**
 * Deletes class log data.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cleanup {

    /**
     * Delete sessions with their entries and notes.
     *
     * @param int[] $sessionids
     */
    public static function delete_sessions(array $sessionids): void {
        global $DB;
        if (!$sessionids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($sessionids);
        $DB->delete_records_select('local_classlog_entry', "sessionid {$insql}", $params);
        $DB->delete_records_select('local_classlog_note', "sessionid {$insql}", $params);
        $DB->delete_records_select('local_classlog_session', "id {$insql}", $params);
    }

    /**
     * Delete every attendance entry of one user.
     *
     * @param int $userid
     */
    public static function delete_user_entries(int $userid): void {
        global $DB;
        $DB->delete_records('local_classlog_entry', ['userid' => $userid]);
    }
}
