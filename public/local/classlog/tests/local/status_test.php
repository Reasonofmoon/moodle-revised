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
 * Tests for attendance statuses.
 *
 * @package    local_classlog
 * @category   test
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(status::class)]
final class status_test extends \advanced_testcase {

    public function test_every_status_has_a_label(): void {
        foreach (status::ALL as $value) {
            $this->assertNotSame('', status::label($value));
        }
        $this->assertSame('Late', status::label(status::LATE));
    }

    public function test_unknown_status_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        status::key(9);
    }
}
