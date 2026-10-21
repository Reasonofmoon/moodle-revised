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

/**
 * Tests for course and user deletion cleanup.
 *
 * @package    local_classlog
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(local\cleanup::class)]
final class hook_callbacks_test extends \advanced_testcase {

    /**
     * Insert a session with one entry per user and a note.
     *
     * @param int $courseid
     * @param int[] $userids
     * @return int session id
     */
    private function add_session(int $courseid, array $userids): int {
        global $DB;
        $sessionid = $DB->insert_record('local_classlog_session', (object) [
            'courseid' => $courseid, 'sessiontime' => 1700000400,
            'timecreated' => 1, 'timemodified' => 1, 'usermodified' => 2,
        ]);
        foreach ($userids as $userid) {
            $DB->insert_record('local_classlog_entry', (object) [
                'sessionid' => $sessionid, 'userid' => $userid, 'status' => 0,
                'lateminutes' => null, 'remark' => '', 'timemodified' => 1,
            ]);
        }
        $DB->insert_record('local_classlog_note', (object) [
            'sessionid' => $sessionid, 'progress' => 'p', 'homework' => 'h', 'memo' => 'm', 'timemodified' => 1,
        ]);
        return $sessionid;
    }

    public function test_course_delete_removes_only_that_course(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $keep = $generator->create_course();
        $drop = $generator->create_course();
        $keepid = $this->add_session((int) $keep->id, [(int) $user->id]);
        $this->add_session((int) $drop->id, [(int) $user->id]);

        delete_course($drop, false);

        $this->assertEquals([$keepid], array_keys($DB->get_records('local_classlog_session')));
        $this->assertSame(1, $DB->count_records('local_classlog_entry'));
        $this->assertSame(1, $DB->count_records('local_classlog_note'));
    }

    public function test_user_delete_removes_only_that_users_entries(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $leaver = $generator->create_user();
        $stayer = $generator->create_user();
        $course = $generator->create_course();
        $this->add_session((int) $course->id, [(int) $leaver->id, (int) $stayer->id]);

        delete_user($leaver);

        $this->assertFalse($DB->record_exists('local_classlog_entry', ['userid' => $leaver->id]));
        $this->assertTrue($DB->record_exists('local_classlog_entry', ['userid' => $stayer->id]));
        $this->assertSame(1, $DB->count_records('local_classlog_session'));
    }
}
