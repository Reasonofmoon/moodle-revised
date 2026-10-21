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
 * Tests for default capabilities.
 *
 * @package    local_classlog
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class access_test extends \advanced_testcase {

    public function test_default_role_capabilities(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $editing = $generator->create_and_enrol($course, 'editingteacher');
        $assistant = $generator->create_and_enrol($course, 'teacher');
        $student = $generator->create_and_enrol($course, 'student');
        $context = \context_course::instance($course->id);

        foreach ([$editing, $assistant] as $teacher) {
            $this->assertTrue(has_capability('local/classlog:view', $context, $teacher));
            $this->assertTrue(has_capability('local/classlog:manage', $context, $teacher));
            $this->assertFalse(has_capability('local/classlog:attend', $context, $teacher));
        }
        $this->assertFalse(has_capability('local/classlog:view', $context, $student));
        $this->assertFalse(has_capability('local/classlog:manage', $context, $student));
        $this->assertTrue(has_capability('local/classlog:attend', $context, $student));
    }
}
