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

namespace local_classlog\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_classlog\local\session_manager;
use local_classlog\local\status;

/**
 * Tests for the privacy provider.
 *
 * @package    local_classlog
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {

    /** @var \stdClass */
    private \stdClass $course;
    /** @var \stdClass */
    private \stdClass $teacher;
    /** @var \stdClass */
    private \stdClass $amy;
    /** @var \stdClass */
    private \stdClass $ben;
    /** @var \context_course */
    private \context_course $context;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->context = \context_course::instance($this->course->id);
        $this->teacher = $generator->create_and_enrol($this->course, 'editingteacher');
        $this->amy = $generator->create_and_enrol($this->course, 'student');
        $this->ben = $generator->create_and_enrol($this->course, 'student');
        $this->setUser($this->teacher);
        $session = session_manager::open((int) $this->course->id, 1700000400);
        session_manager::save((int) $session->id, 1700000400, [
            $this->amy->id => ['status' => status::LATE, 'lateminutes' => 5, 'remark' => 'bus'],
        ], ['progress' => '', 'homework' => '', 'memo' => '']);
    }

    public function test_get_metadata(): void {
        $items = provider::get_metadata(new collection('local_classlog'))->get_collection();
        $this->assertCount(2, $items);
    }

    public function test_contexts_for_student_and_teacher(): void {
        $other = $this->getDataGenerator()->create_user();

        // Ids may come back as strings from the DB, so compare loosely.
        $this->assertEquals([$this->context->id], provider::get_contexts_for_userid((int) $this->amy->id)->get_contextids());
        $this->assertEquals([$this->context->id], provider::get_contexts_for_userid((int) $this->teacher->id)->get_contextids());
        $this->assertEquals([], provider::get_contexts_for_userid((int) $other->id)->get_contextids());
    }

    public function test_users_in_context(): void {
        $userlist = new userlist($this->context, 'local_classlog');
        provider::get_users_in_context($userlist);
        $expected = [(int) $this->teacher->id, (int) $this->amy->id, (int) $this->ben->id];
        sort($expected);
        $actual = array_map('intval', $userlist->get_userids());
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    public function test_export_student_attendance(): void {
        $this->export_context_data_for_user((int) $this->amy->id, $this->context, 'local_classlog');

        $data = writer::with_context($this->context)->get_data([get_string('pluginname', 'local_classlog')]);
        $this->assertCount(1, $data->attendance);
        $this->assertSame('Late', $data->attendance[0]['status']);
        $this->assertSame('bus', $data->attendance[0]['remark']);
        $this->assertSame([], $data->sessionslastsaved);
    }

    public function test_delete_for_user_keeps_others_and_reassigns_teacher(): void {
        global $DB;
        provider::delete_data_for_user(new approved_contextlist($this->amy, 'local_classlog', [$this->context->id]));
        provider::delete_data_for_user(new approved_contextlist($this->teacher, 'local_classlog', [$this->context->id]));

        $this->assertFalse($DB->record_exists('local_classlog_entry', ['userid' => $this->amy->id]));
        $this->assertTrue($DB->record_exists('local_classlog_entry', ['userid' => $this->ben->id]));
        $this->assertFalse($DB->record_exists('local_classlog_session', ['usermodified' => $this->teacher->id]));
        $this->assertSame(1, $DB->count_records('local_classlog_session'));
    }

    public function test_delete_for_users(): void {
        global $DB;
        provider::delete_data_for_users(new approved_userlist($this->context, 'local_classlog', [(int) $this->ben->id]));

        $this->assertFalse($DB->record_exists('local_classlog_entry', ['userid' => $this->ben->id]));
        $this->assertTrue($DB->record_exists('local_classlog_entry', ['userid' => $this->amy->id]));
    }

    public function test_delete_all_users_in_context(): void {
        global $DB;
        provider::delete_data_for_all_users_in_context($this->context);

        $this->assertSame(0, $DB->count_records('local_classlog_entry'));
        $this->assertSame(1, $DB->count_records('local_classlog_session'));
        $this->assertFalse($DB->record_exists('local_classlog_session', ['usermodified' => $this->teacher->id]));
    }
}
