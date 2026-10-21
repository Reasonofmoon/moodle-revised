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
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_classlog\local\status;

/**
 * Privacy provider: student attendance and the teacher who last saved a class.
 *
 * Sessions and lesson logs belong to the class, so deleting a teacher's data
 * reassigns usermodified to the site admin instead of deleting the class.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_classlog_entry', [
            'userid' => 'privacy:metadata:local_classlog_entry:userid',
            'status' => 'privacy:metadata:local_classlog_entry:status',
            'lateminutes' => 'privacy:metadata:local_classlog_entry:lateminutes',
            'remark' => 'privacy:metadata:local_classlog_entry:remark',
            'timemodified' => 'privacy:metadata:local_classlog_entry:timemodified',
        ], 'privacy:metadata:local_classlog_entry');
        $collection->add_database_table('local_classlog_session', [
            'usermodified' => 'privacy:metadata:local_classlog_session:usermodified',
            'timemodified' => 'privacy:metadata:local_classlog_session:timemodified',
        ], 'privacy:metadata:local_classlog_session');
        return $collection;
    }

    /**
     * Get the course contexts holding data about a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {local_classlog_session} s
                  JOIN {context} ctx ON ctx.instanceid = s.courseid AND ctx.contextlevel = :courselevel
             LEFT JOIN {local_classlog_entry} e ON e.sessionid = s.id AND e.userid = :entryuser
                 WHERE e.id IS NOT NULL OR s.usermodified = :modifier";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'courselevel' => CONTEXT_COURSE,
            'entryuser' => $userid,
            'modifier' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Get the users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $params = ['courseid' => $context->instanceid];
        $userlist->add_from_sql('userid', "SELECT e.userid
                                             FROM {local_classlog_entry} e
                                             JOIN {local_classlog_session} s ON s.id = e.sessionid
                                            WHERE s.courseid = :courseid", $params);
        $userlist->add_from_sql('usermodified', "SELECT usermodified
                                                   FROM {local_classlog_session}
                                                  WHERE courseid = :courseid", $params);
    }

    /**
     * Export a user's attendance and last-saved sessions.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $params = ['courseid' => $context->instanceid, 'userid' => $userid];
            $records = $DB->get_records_sql("SELECT e.id, s.sessiontime, e.status, e.lateminutes, e.remark
                                               FROM {local_classlog_entry} e
                                               JOIN {local_classlog_session} s ON s.id = e.sessionid
                                              WHERE s.courseid = :courseid AND e.userid = :userid
                                           ORDER BY s.sessiontime", $params);
            $attendance = [];
            foreach ($records as $record) {
                $attendance[] = [
                    'sessiontime' => transform::datetime((int) $record->sessiontime),
                    'status' => status::label((int) $record->status),
                    'lateminutes' => $record->lateminutes === null ? null : (int) $record->lateminutes,
                    'remark' => $record->remark,
                ];
            }
            $saved = $DB->get_fieldset_select('local_classlog_session', 'sessiontime',
                'courseid = :courseid AND usermodified = :userid', $params);
            if (!$attendance && !$saved) {
                continue;
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_classlog')], (object) [
                'attendance' => $attendance,
                'sessionslastsaved' => array_map(fn($time) => transform::datetime((int) $time), $saved),
            ]);
        }
    }

    /**
     * Delete all attendance in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context instanceof \context_course) {
            self::delete_for_users((int) $context->instanceid, null);
        }
    }

    /**
     * Delete a user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                self::delete_for_users((int) $context->instanceid, [$userid]);
            }
        }
    }

    /**
     * Delete data for several users in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context instanceof \context_course) {
            self::delete_for_users((int) $context->instanceid, $userlist->get_userids());
        }
    }

    /**
     * Delete entries and anonymise usermodified in one course.
     *
     * @param int $courseid
     * @param int[]|null $userids null means every user
     */
    private static function delete_for_users(int $courseid, ?array $userids): void {
        global $DB;
        if ($userids === []) {
            return;
        }
        $sessionids = $DB->get_fieldset_select('local_classlog_session', 'id', 'courseid = ?', [$courseid]);
        if (!$sessionids) {
            return;
        }

        [$sessionsql, $params] = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED, 'sid');
        $entrywhere = "sessionid {$sessionsql}";
        $sessionwhere = 'courseid = :courseid';
        $sessionparams = ['courseid' => $courseid];
        if ($userids !== null) {
            [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
            $entrywhere .= " AND userid {$usersql}";
            $params += $userparams;
            [$modsql, $modparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'mid');
            $sessionwhere .= " AND usermodified {$modsql}";
            $sessionparams += $modparams;
        }
        $DB->delete_records_select('local_classlog_entry', $entrywhere, $params);
        $DB->set_field_select('local_classlog_session', 'usermodified', get_admin()->id, $sessionwhere, $sessionparams);
    }
}
