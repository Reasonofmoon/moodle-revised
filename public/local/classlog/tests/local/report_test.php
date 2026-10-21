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
 * Tests for the attendance report.
 *
 * @package    local_classlog
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(report::class)]
final class report_test extends \advanced_testcase {

    /** @var int First class time. */
    private const T = 1700000400;

    public function test_rate_excludes_excused_absences(): void {
        $this->assertSame(100.0, report::rate(8, 1, 10, 1));
        $this->assertSame(70.0, report::rate(6, 1, 10, 0));
        $this->assertSame(33.3, report::rate(1, 0, 3, 0));
        $this->assertNull(report::rate(0, 0, 2, 2));
    }

    /**
     * Three daily classes: Amy present/late/excused, Ben absent/present/present.
     *
     * @return \stdClass course
     */
    private function make_history(): \stdClass {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $amy = $generator->create_and_enrol($course, 'student', ['firstname' => 'Amy', 'lastname' => 'Alpha']);
        $ben = $generator->create_and_enrol($course, 'student', ['firstname' => 'Ben', 'lastname' => 'Beta']);
        $this->setAdminUser();
        $plan = [
            [status::PRESENT, status::ABSENT],
            [status::LATE, status::PRESENT],
            [status::EXCUSED, status::PRESENT],
        ];
        foreach ($plan as $day => [$amystatus, $benstatus]) {
            $time = self::T + $day * DAYSECS;
            $session = session_manager::open((int) $course->id, $time);
            session_manager::save((int) $session->id, $time, [
                $amy->id => ['status' => $amystatus, 'lateminutes' => null, 'remark' => ''],
                $ben->id => ['status' => $benstatus, 'lateminutes' => null, 'remark' => ''],
            ], ['progress' => '', 'homework' => '', 'memo' => '']);
        }
        return $course;
    }

    public function test_summarise_whole_period(): void {
        $this->resetAfterTest();
        $course = $this->make_history();

        $rows = report::summarise((int) $course->id, self::T, self::T + 3 * DAYSECS);

        $this->assertSame(['Amy Alpha', 'Ben Beta'], array_column($rows, 'fullname'));
        [$amy, $ben] = $rows;
        $this->assertSame([3, 1, 1, 0, 1], [$amy->sessions, $amy->present, $amy->late, $amy->absent, $amy->excused]);
        $this->assertSame(100.0, $amy->rate);
        $this->assertSame([3, 2, 0, 1, 0], [$ben->sessions, $ben->present, $ben->late, $ben->absent, $ben->excused]);
        $this->assertSame(66.7, $ben->rate);
    }

    public function test_summarise_respects_the_range(): void {
        $this->resetAfterTest();
        $course = $this->make_history();

        $rows = report::summarise((int) $course->id, self::T + DAYSECS, self::T + 2 * DAYSECS);

        $this->assertSame([1, 1], array_column($rows, 'sessions'));
        $this->assertSame([], report::summarise((int) $course->id, self::T + 10 * DAYSECS, self::T + 11 * DAYSECS));
    }

    public function test_csv_rows_match_columns(): void {
        $row = (object) [
            'userid' => 5, 'fullname' => 'Amy Alpha', 'sessions' => 2,
            'present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 2, 'rate' => null,
        ];

        $csv = report::csv_rows([$row]);

        $this->assertCount(count(report::csv_columns()), $csv[0]);
        $this->assertSame(['Amy Alpha', 2, 0, 0, 0, 2, '-'], $csv[0]);
    }
}
