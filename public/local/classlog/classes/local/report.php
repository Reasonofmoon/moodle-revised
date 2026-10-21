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
 * Per-student attendance counts for a period.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class report {

    /**
     * Attendance rate in percent, one decimal. Excused absences are left out of the denominator.
     *
     * @param int $present
     * @param int $late
     * @param int $sessions
     * @param int $excused
     * @return float|null null when there is nothing to divide by
     */
    public static function rate(int $present, int $late, int $sessions, int $excused): ?float {
        $denominator = $sessions - $excused;
        if ($denominator <= 0) {
            return null;
        }
        return round(100 * ($present + $late) / $denominator, 1);
    }

    /**
     * Counts per student for sessions with $from <= sessiontime < $to.
     *
     * @param int $courseid
     * @param int $from
     * @param int $to
     * @return \stdClass[] ordered by full name
     */
    public static function summarise(int $courseid, int $from, int $to): array {
        global $DB;
        $sql = "SELECT e.userid,
                       COUNT(1) AS sessions,
                       SUM(CASE WHEN e.status = :present THEN 1 ELSE 0 END) AS present,
                       SUM(CASE WHEN e.status = :late THEN 1 ELSE 0 END) AS late,
                       SUM(CASE WHEN e.status = :absent THEN 1 ELSE 0 END) AS absent,
                       SUM(CASE WHEN e.status = :excused THEN 1 ELSE 0 END) AS excused
                  FROM {local_classlog_entry} e
                  JOIN {local_classlog_session} s ON s.id = e.sessionid
                 WHERE s.courseid = :courseid AND s.sessiontime >= :fromtime AND s.sessiontime < :totime
              GROUP BY e.userid";
        $counts = $DB->get_records_sql($sql, [
            'present' => status::PRESENT,
            'late' => status::LATE,
            'absent' => status::ABSENT,
            'excused' => status::EXCUSED,
            'courseid' => $courseid,
            'fromtime' => $from,
            'totime' => $to,
        ]);
        if (!$counts) {
            return [];
        }

        $fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
        $users = $DB->get_records_list('user', 'id', array_keys($counts), '', $fields);

        $rows = [];
        foreach ($counts as $userid => $count) {
            if (!isset($users[$userid])) {
                continue;
            }
            $row = (object) [
                'userid' => (int) $userid,
                'fullname' => fullname($users[$userid]),
                'sessions' => (int) $count->sessions,
                'present' => (int) $count->present,
                'late' => (int) $count->late,
                'absent' => (int) $count->absent,
                'excused' => (int) $count->excused,
            ];
            $row->rate = self::rate($row->present, $row->late, $row->sessions, $row->excused);
            $rows[] = $row;
        }
        \core_collator::asort_objects_by_property($rows, 'fullname');
        return array_values($rows);
    }

    /**
     * CSV header.
     *
     * @return string[]
     */
    public static function csv_columns(): array {
        return [
            get_string('col_student', 'local_classlog'),
            get_string('col_sessions', 'local_classlog'),
            status::label(status::PRESENT),
            status::label(status::LATE),
            status::label(status::ABSENT),
            status::label(status::EXCUSED),
            get_string('col_rate', 'local_classlog'),
        ];
    }

    /**
     * CSV body in the order of csv_columns().
     *
     * @param \stdClass[] $rows from summarise()
     * @return array[]
     */
    public static function csv_rows(array $rows): array {
        return array_map(fn(\stdClass $row) => [
            $row->fullname,
            $row->sessions,
            $row->present,
            $row->late,
            $row->absent,
            $row->excused,
            $row->rate ?? '-',
        ], $rows);
    }
}
