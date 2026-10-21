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

namespace local_classlog;

use core_course\hook\before_course_deleted;
use core_user\hook\before_user_deleted;
use local_classlog\local\cleanup;

/**
 * Hook listeners.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {

    /**
     * Remove a course's sessions before the course is deleted.
     *
     * @param before_course_deleted $hook
     */
    public static function before_course_deleted(before_course_deleted $hook): void {
        global $DB;
        $sessionids = $DB->get_fieldset_select('local_classlog_session', 'id', 'courseid = ?', [$hook->course->id]);
        cleanup::delete_sessions($sessionids);
    }

    /**
     * Remove a user's attendance before the user is deleted.
     *
     * @param before_user_deleted $hook
     */
    public static function before_user_deleted(before_user_deleted $hook): void {
        cleanup::delete_user_entries((int) $hook->user->id);
    }
}
