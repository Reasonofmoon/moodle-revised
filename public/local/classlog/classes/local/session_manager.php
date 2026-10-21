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
 * Opens, saves, lists and deletes class sessions.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class session_manager {

    /** @var int Session times are rounded down to this many seconds. */
    public const ROUND_SECONDS = 600;

    /** @var int Maximum stored remark length. */
    private const REMARK_LENGTH = 255;

    /**
     * Round a time down to the session grid.
     *
     * @param int $time
     * @return int
     */
    public static function round_time(int $time): int {
        return $time - ($time % self::ROUND_SECONDS);
    }

    /**
     * Open a session, or return the one already at that (rounded) time.
     *
     * New sessions list every active student as present.
     *
     * @param int $courseid
     * @param int $sessiontime
     * @return \stdClass session record
     */
    public static function open(int $courseid, int $sessiontime): \stdClass {
        global $DB, $USER;
        $sessiontime = self::round_time($sessiontime);
        $existing = $DB->get_record('local_classlog_session', ['courseid' => $courseid, 'sessiontime' => $sessiontime]);
        if ($existing) {
            return $existing;
        }

        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'local/classlog:attend', 0, 'u.id', null, 0, 0, true);
        $now = time();

        $transaction = $DB->start_delegated_transaction();
        $session = (object) [
            'courseid' => $courseid,
            'sessiontime' => $sessiontime,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ];
        $session->id = $DB->insert_record('local_classlog_session', $session);
        foreach ($students as $student) {
            $DB->insert_record('local_classlog_entry', (object) [
                'sessionid' => $session->id,
                'userid' => $student->id,
                'status' => status::PRESENT,
                'lateminutes' => null,
                'remark' => '',
                'timemodified' => $now,
            ]);
        }
        $transaction->allow_commit();

        session_created::create(['objectid' => $session->id, 'context' => $context])->trigger();
        return $session;
    }

    /**
     * Load a session.
     *
     * @param int $sessionid
     * @return \stdClass
     */
    public static function get(int $sessionid): \stdClass {
        global $DB;
        return $DB->get_record('local_classlog_session', ['id' => $sessionid], '*', MUST_EXIST);
    }

    /**
     * Whether another session of the course already uses this (rounded) time.
     *
     * @param int $courseid
     * @param int $sessiontime
     * @param int $excludesessionid session being edited
     * @return bool
     */
    public static function time_taken(int $courseid, int $sessiontime, int $excludesessionid = 0): bool {
        global $DB;
        return $DB->record_exists_select(
            'local_classlog_session',
            'courseid = :courseid AND sessiontime = :sessiontime AND id <> :id',
            ['courseid' => $courseid, 'sessiontime' => self::round_time($sessiontime), 'id' => $excludesessionid],
        );
    }

    /**
     * Roster of a session with names, ordered by last name.
     *
     * @param int $sessionid
     * @return \stdClass[] keyed by user id
     */
    public static function get_entries(int $sessionid): array {
        global $DB;
        $names = \core_user\fields::for_name()->get_sql('u', true);
        $sql = "SELECT e.userid, e.id, e.status, e.lateminutes, e.remark {$names->selects}
                  FROM {local_classlog_entry} e
                  JOIN {user} u ON u.id = e.userid
                 WHERE e.sessionid = :sessionid
              ORDER BY u.lastname, u.firstname, u.id";
        $entries = [];
        foreach ($DB->get_records_sql($sql, ['sessionid' => $sessionid] + $names->params) as $record) {
            $record->userid = (int) $record->userid;
            $record->status = (int) $record->status;
            $record->lateminutes = $record->lateminutes === null ? null : (int) $record->lateminutes;
            $record->fullname = fullname($record);
            $entries[$record->userid] = $record;
        }
        return $entries;
    }

    /**
     * Lesson log of a session; empty strings when none was saved.
     *
     * @param int $sessionid
     * @return \stdClass progress, homework, memo
     */
    public static function get_note(int $sessionid): \stdClass {
        global $DB;
        $note = $DB->get_record('local_classlog_note', ['sessionid' => $sessionid]);
        return (object) [
            'progress' => (string) ($note->progress ?? ''),
            'homework' => (string) ($note->homework ?? ''),
            'memo' => (string) ($note->memo ?? ''),
        ];
    }

    /**
     * Save attendance, lesson log and session time.
     *
     * Users not on the session's roster are ignored.
     *
     * @param int $sessionid
     * @param int $sessiontime
     * @param array $entries [userid => ['status' => int, 'lateminutes' => ?int, 'remark' => string]]
     * @param array $note ['progress' => string, 'homework' => string, 'memo' => string]
     * @throws \coding_exception for an unknown status
     * @throws \moodle_exception when another session of the course has this time
     */
    public static function save(int $sessionid, int $sessiontime, array $entries, array $note): void {
        global $DB, $USER;
        $session = self::get($sessionid);
        $sessiontime = self::round_time($sessiontime);
        if (self::time_taken((int) $session->courseid, $sessiontime, $sessionid)) {
            throw new \moodle_exception('sessionexists', 'local_classlog');
        }
        // Validate everything before writing anything.
        foreach ($entries as $values) {
            status::key((int) $values['status']);
        }

        $now = time();
        $roster = $DB->get_records('local_classlog_entry', ['sessionid' => $sessionid], '', 'userid, id');
        $transaction = $DB->start_delegated_transaction();
        foreach ($entries as $userid => $values) {
            if (!isset($roster[$userid])) {
                continue;
            }
            $value = (int) $values['status'];
            $late = (int) ($values['lateminutes'] ?? 0);
            $DB->update_record('local_classlog_entry', (object) [
                'id' => $roster[$userid]->id,
                'status' => $value,
                'lateminutes' => ($value === status::LATE && $late > 0) ? $late : null,
                'remark' => \core_text::substr(trim((string) ($values['remark'] ?? '')), 0, self::REMARK_LENGTH),
                'timemodified' => $now,
            ]);
        }

        $record = (object) [
            'progress' => trim((string) ($note['progress'] ?? '')),
            'homework' => trim((string) ($note['homework'] ?? '')),
            'memo' => trim((string) ($note['memo'] ?? '')),
            'timemodified' => $now,
        ];
        $noteid = $DB->get_field('local_classlog_note', 'id', ['sessionid' => $sessionid]);
        if ($noteid) {
            $record->id = $noteid;
            $DB->update_record('local_classlog_note', $record);
        } else {
            $record->sessionid = $sessionid;
            $DB->insert_record('local_classlog_note', $record);
        }

        $DB->update_record('local_classlog_session', (object) [
            'id' => $sessionid,
            'sessiontime' => $sessiontime,
            'timemodified' => $now,
            'usermodified' => $USER->id,
        ]);
        $transaction->allow_commit();

        $context = \context_course::instance($session->courseid);
        attendance_saved::create(['objectid' => $sessionid, 'context' => $context])->trigger();
        lessonlog_saved::create(['objectid' => $sessionid, 'context' => $context])->trigger();
    }

    /**
     * Delete a session with its attendance and lesson log.
     *
     * @param int $sessionid
     */
    public static function delete(int $sessionid): void {
        $session = self::get($sessionid);
        cleanup::delete_sessions([$sessionid]);
        session_deleted::create([
            'objectid' => $sessionid,
            'context' => \context_course::instance($session->courseid),
        ])->trigger();
    }

    /**
     * Sessions of a course, newest first, with status counts and progress.
     *
     * @param int $courseid
     * @return \stdClass[]
     */
    public static function list_sessions(int $courseid): array {
        global $DB;
        $sql = "SELECT s.id, s.sessiontime,
                       SUM(CASE WHEN e.status = :present THEN 1 ELSE 0 END) AS present,
                       SUM(CASE WHEN e.status = :late THEN 1 ELSE 0 END) AS late,
                       SUM(CASE WHEN e.status = :absent THEN 1 ELSE 0 END) AS absent,
                       SUM(CASE WHEN e.status = :excused THEN 1 ELSE 0 END) AS excused
                  FROM {local_classlog_session} s
             LEFT JOIN {local_classlog_entry} e ON e.sessionid = s.id
                 WHERE s.courseid = :courseid
              GROUP BY s.id, s.sessiontime
              ORDER BY s.sessiontime DESC";
        $sessions = $DB->get_records_sql($sql, [
            'present' => status::PRESENT,
            'late' => status::LATE,
            'absent' => status::ABSENT,
            'excused' => status::EXCUSED,
            'courseid' => $courseid,
        ]);
        // Text columns are fetched separately; grouping by them is not portable.
        $notes = $sessions
            ? $DB->get_records_list('local_classlog_note', 'sessionid', array_keys($sessions), '', 'sessionid, progress')
            : [];

        $result = [];
        foreach ($sessions as $session) {
            $result[] = (object) [
                'id' => (int) $session->id,
                'sessiontime' => (int) $session->sessiontime,
                'present' => (int) $session->present,
                'late' => (int) $session->late,
                'absent' => (int) $session->absent,
                'excused' => (int) $session->excused,
                'progress' => (string) ($notes[$session->id]->progress ?? ''),
            ];
        }
        return $result;
    }
}
