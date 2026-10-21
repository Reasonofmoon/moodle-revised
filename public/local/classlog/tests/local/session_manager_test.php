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

use local_classlog\event\attendance_saved;
use local_classlog\event\lessonlog_saved;
use local_classlog\event\session_created;
use local_classlog\event\session_deleted;

/**
 * Tests for the session manager.
 *
 * @package    local_classlog
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(session_manager::class)]
final class session_manager_test extends \advanced_testcase {

    /** @var int A class start time on a 10-minute boundary. */
    private const T = 1700000400;

    /**
     * Course with a teacher (current user), an active and a suspended student.
     *
     * @return \stdClass[] [course, teacher, active student, suspended student]
     */
    private function make_class(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user(['firstname' => 'Tess', 'lastname' => 'Teacher']);
        $active = $generator->create_user(['firstname' => 'Amy', 'lastname' => 'Active']);
        $suspended = $generator->create_user(['firstname' => 'Sam', 'lastname' => 'Suspended']);
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $generator->enrol_user($active->id, $course->id, 'student');
        $generator->enrol_user($suspended->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $this->setUser($teacher);
        return [$course, $teacher, $active, $suspended];
    }

    public function test_round_time_floors_to_ten_minutes(): void {
        $this->assertSame(self::T, session_manager::round_time(self::T + 599));
        $this->assertSame(self::T, session_manager::round_time(self::T));
    }

    public function test_open_adds_active_students_as_present(): void {
        $this->resetAfterTest();
        [$course, $teacher, $active] = $this->make_class();

        $session = session_manager::open((int) $course->id, self::T + 125);

        $this->assertSame(self::T, (int) $session->sessiontime);
        $this->assertEquals($teacher->id, $session->usermodified);
        $entries = session_manager::get_entries((int) $session->id);
        $this->assertSame([(int) $active->id], array_keys($entries));
        $this->assertSame(status::PRESENT, $entries[$active->id]->status);
        $this->assertNull($entries[$active->id]->lateminutes);
        $this->assertSame('Amy Active', $entries[$active->id]->fullname);
    }

    public function test_open_twice_returns_the_same_session(): void {
        global $DB;
        $this->resetAfterTest();
        [$course] = $this->make_class();

        $first = session_manager::open((int) $course->id, self::T);
        $second = session_manager::open((int) $course->id, self::T + 60);

        $this->assertEquals($first->id, $second->id);
        $this->assertSame(1, $DB->count_records('local_classlog_session'));
    }

    public function test_open_triggers_session_created(): void {
        $this->resetAfterTest();
        [$course] = $this->make_class();
        $sink = $this->redirectEvents();

        $session = session_manager::open((int) $course->id, self::T);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(session_created::class, $events[0]);
        $this->assertEquals($session->id, $events[0]->objectid);
    }

    public function test_past_session_keeps_students_who_left(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, , $active] = $this->make_class();
        $old = session_manager::open((int) $course->id, self::T);
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        enrol_get_plugin('manual')->unenrol_user($instance, $active->id);

        $new = session_manager::open((int) $course->id, self::T + 3600);

        $this->assertArrayHasKey((int) $active->id, session_manager::get_entries((int) $old->id));
        $this->assertSame([], session_manager::get_entries((int) $new->id));
    }

    public function test_save_updates_entries_note_and_modifier(): void {
        $this->resetAfterTest();
        [$course, , $active] = $this->make_class();
        $session = session_manager::open((int) $course->id, self::T);
        $this->setAdminUser();
        $sink = $this->redirectEvents();

        session_manager::save((int) $session->id, self::T, [
            $active->id => ['status' => status::LATE, 'lateminutes' => 15, 'remark' => ' bus '],
        ], ['progress' => 'Ch.3', 'homework' => 'WB p.10', 'memo' => '']);

        $entry = session_manager::get_entries((int) $session->id)[$active->id];
        $this->assertSame(status::LATE, $entry->status);
        $this->assertSame(15, $entry->lateminutes);
        $this->assertSame('bus', $entry->remark);
        $note = session_manager::get_note((int) $session->id);
        $this->assertSame('Ch.3', $note->progress);
        $this->assertSame('WB p.10', $note->homework);
        $this->assertEquals(get_admin()->id, session_manager::get((int) $session->id)->usermodified);
        $classes = array_map(fn($event) => get_class($event), $sink->get_events());
        $this->assertSame([attendance_saved::class, lessonlog_saved::class], $classes);
    }

    public function test_save_drops_lateminutes_unless_late(): void {
        $this->resetAfterTest();
        [$course, , $active] = $this->make_class();
        $session = session_manager::open((int) $course->id, self::T);

        session_manager::save((int) $session->id, self::T, [
            $active->id => ['status' => status::ABSENT, 'lateminutes' => 10, 'remark' => ''],
        ], ['progress' => '', 'homework' => '', 'memo' => '']);

        $this->assertNull(session_manager::get_entries((int) $session->id)[$active->id]->lateminutes);
    }

    public function test_save_ignores_users_not_on_the_roster(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, , , $suspended] = $this->make_class();
        $session = session_manager::open((int) $course->id, self::T);

        session_manager::save((int) $session->id, self::T, [
            $suspended->id => ['status' => status::ABSENT, 'lateminutes' => null, 'remark' => ''],
        ], ['progress' => '', 'homework' => '', 'memo' => '']);

        $this->assertFalse($DB->record_exists('local_classlog_entry', ['userid' => $suspended->id]));
    }

    public function test_save_rejects_unknown_status(): void {
        $this->resetAfterTest();
        [$course, , $active] = $this->make_class();
        $session = session_manager::open((int) $course->id, self::T);

        $this->expectException(\coding_exception::class);
        session_manager::save((int) $session->id, self::T, [
            $active->id => ['status' => 7, 'lateminutes' => null, 'remark' => ''],
        ], ['progress' => '', 'homework' => '', 'memo' => '']);
    }

    public function test_save_rejects_time_of_another_session(): void {
        $this->resetAfterTest();
        [$course] = $this->make_class();
        session_manager::open((int) $course->id, self::T);
        $later = session_manager::open((int) $course->id, self::T + 3600);

        $this->assertTrue(session_manager::time_taken((int) $course->id, self::T + 30, (int) $later->id));
        $this->assertFalse(session_manager::time_taken((int) $course->id, self::T + 3600, (int) $later->id));
        $this->expectException(\moodle_exception::class);
        session_manager::save((int) $later->id, self::T, [], ['progress' => '', 'homework' => '', 'memo' => '']);
    }

    public function test_delete_removes_everything_and_triggers_event(): void {
        global $DB;
        $this->resetAfterTest();
        [$course] = $this->make_class();
        $session = session_manager::open((int) $course->id, self::T);
        session_manager::save((int) $session->id, self::T, [], ['progress' => 'x', 'homework' => '', 'memo' => '']);
        $sink = $this->redirectEvents();

        session_manager::delete((int) $session->id);

        $this->assertSame(0, $DB->count_records('local_classlog_session'));
        $this->assertSame(0, $DB->count_records('local_classlog_entry'));
        $this->assertSame(0, $DB->count_records('local_classlog_note'));
        $this->assertInstanceOf(session_deleted::class, $sink->get_events()[0]);
    }

    public function test_list_sessions_counts_newest_first(): void {
        $this->resetAfterTest();
        [$course, , $active] = $this->make_class();
        $old = session_manager::open((int) $course->id, self::T);
        $new = session_manager::open((int) $course->id, self::T + 86400);
        session_manager::save((int) $new->id, self::T + 86400, [
            $active->id => ['status' => status::ABSENT, 'lateminutes' => null, 'remark' => ''],
        ], ['progress' => "Unit 2\nreview", 'homework' => '', 'memo' => '']);

        $list = session_manager::list_sessions((int) $course->id);

        $this->assertSame([(int) $new->id, (int) $old->id], array_column($list, 'id'));
        $this->assertSame(1, $list[0]->absent);
        $this->assertSame(0, $list[0]->present);
        $this->assertSame("Unit 2\nreview", $list[0]->progress);
        $this->assertSame(1, $list[1]->present);
        $this->assertSame('', $list[1]->progress);
    }
}
