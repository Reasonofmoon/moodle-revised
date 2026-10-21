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
 * Attendance statuses.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class status {

    /** @var int Present. */
    public const PRESENT = 0;
    /** @var int Late. */
    public const LATE = 1;
    /** @var int Absent. */
    public const ABSENT = 2;
    /** @var int Absent with a reason; does not lower the attendance rate. */
    public const EXCUSED = 3;

    /** @var int[] All statuses in display order. */
    public const ALL = [self::PRESENT, self::LATE, self::ABSENT, self::EXCUSED];

    /**
     * Short key used in language strings.
     *
     * @param int $status
     * @return string
     * @throws \coding_exception for an unknown status
     */
    public static function key(int $status): string {
        return match ($status) {
            self::PRESENT => 'present',
            self::LATE => 'late',
            self::ABSENT => 'absent',
            self::EXCUSED => 'excused',
            default => throw new \coding_exception("Unknown attendance status: {$status}"),
        };
    }

    /**
     * Localised label.
     *
     * @param int $status
     * @return string
     */
    public static function label(int $status): string {
        return get_string('status_' . self::key($status), 'local_classlog');
    }
}
