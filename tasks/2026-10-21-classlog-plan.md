# Class Log (local_classlog) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A Moodle local plugin where teachers open "today's class", take attendance and write the lesson log on one screen, and see a per-student attendance report.

**Architecture:** `public/local/classlog/` with three tables. All logic lives in `classes/local/` (`session_manager`, `report`, `cleanup`, `status`) and takes/returns plain data; the three pages (`index.php`, `session.php`, `report.php`) only check access, call those classes and render. Course/user deletion uses Moodle 6 hooks; personal data uses the Privacy API.

**Tech Stack:** PHP 8.3, Moodle 6.0dev (`requires 2026100200`), XMLDB, moodleform, Mustache, PHPUnit 11 (`advanced_testcase`, `\core_privacy\tests\provider_testcase`).

**Spec:** `docs/superpowers/specs/2026-10-21-classlog-design.md`

## Global Constraints

- Component `local_classlog`, directory `public/local/classlog/`. `$plugin->requires = 2026100200`, `MATURITY_ALPHA`, so upgrades need `--allow-unstable`.
- Every PHP file starts with the standard Moodle GPL header (copy lines 1-15 of `public/theme/readmaster/lib.php` verbatim), then a docblock with `@package local_classlog`, `@copyright 2026 ReadMaster`, `@license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later`.
- Code comments in English. Language strings in `lang/en` and `lang/ko`. No academy-specific text anywhere (the plugin must work unchanged for cloned academies).
- Statuses: 0 present, 1 late, 2 absent, 3 excused. Rate = (present + late) ÷ (sessions − excused); `null`/"-" when the denominator is 0; one decimal.
- Session time is rounded **down** to 10 minutes; (courseid, sessiontime) is unique.
- Text fields are plain text (`PARAM_TEXT`), remark max 255 chars.
- Tests use `#[\PHPUnit\Framework\Attributes\CoversClass(...)]` attributes, not `@covers`.
- All commands run from `C:\Users\sound\moodle\moodle-revised` in Git Bash with `MSYS_NO_PATHCONV=1`. Shorthand used below:
  - `RUN` = `MSYS_NO_PATHCONV=1 docker exec -u www-data readmaster-moodle-web`
  - `TEST <filter>` = `RUN vendor/bin/phpunit --testsuite local_classlog_testsuite --filter <filter>` (about 1 minute; a directory path runs nothing)
- **Anything under `db/` changes only in Task 1.** A later change there needs a version bump and a full PHPUnit reinstall (30+ minutes).
- Commits: Conventional Commits, ending with the two attribution lines:
  `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>` and
  `Claude-Session: https://claude.ai/code/session_01DTsbHeZ8s4LLMUcZGwrsEx`

## File map

| File | Task | Responsibility |
|---|---|---|
| `version.php` | 1 | plugin metadata |
| `db/install.xml` | 1 | three tables |
| `db/access.php` | 1 | `view`, `manage`, `attend` capabilities |
| `db/hooks.php` | 1 | course/user deletion hooks |
| `classes/local/status.php` | 1 | status constants and labels |
| `classes/local/cleanup.php` | 1 | delete sessions / a user's entries |
| `classes/hook_callbacks.php` | 1 | hook entry points → cleanup |
| `lang/en/local_classlog.php`, `lang/ko/local_classlog.php` | 1 (all strings) | strings |
| `classes/event/session_base.php` + 4 events | 2 | logstore events |
| `classes/local/session_manager.php` | 2 | open/get/save/delete/list sessions |
| `classes/local/report.php` | 3 | per-student counts, rate, CSV |
| `classes/privacy/provider.php` | 4 | export/delete personal data |
| `lib.php`, `index.php`, `session.php`, `report.php`, `classes/form/*.php`, `templates/*.mustache`, `styles.css` | 5 | UI |
| `docs/academy-playbook.md` | 6 | note that the plugin needs no per-academy change |

---

### Task 1: Plugin skeleton, schema, capabilities, deletion hooks

**Files:**
- Create: `public/local/classlog/version.php`, `db/install.xml`, `db/access.php`, `db/hooks.php`, `classes/local/status.php`, `classes/local/cleanup.php`, `classes/hook_callbacks.php`, `lang/en/local_classlog.php`, `lang/ko/local_classlog.php`
- Test: `public/local/classlog/tests/hook_callbacks_test.php`, `tests/local/status_test.php`, `tests/access_test.php`

**Interfaces:**
- Produces:
  - `\local_classlog\local\status::PRESENT|LATE|ABSENT|EXCUSED` (int 0-3), `status::ALL` (int[]), `status::key(int): string` (throws `\coding_exception` on unknown), `status::label(int): string`
  - `\local_classlog\local\cleanup::delete_sessions(array $sessionids): void`, `cleanup::delete_user_entries(int $userid): void`
  - Tables `local_classlog_session`, `local_classlog_entry`, `local_classlog_note` (fields as in the spec)
  - Capabilities `local/classlog:view`, `local/classlog:manage`, `local/classlog:attend`

- [ ] **Step 1: Write `version.php`**

```php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_classlog';
$plugin->version   = 2026102100;
$plugin->requires  = 2026100200;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
```

- [ ] **Step 2: Write `db/install.xml`**

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="local/classlog/db" VERSION="20261021" COMMENT="XMLDB file for Moodle local/classlog"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="../../../lib/xmldb/xmldb.xsd"
>
  <TABLES>
    <TABLE NAME="local_classlog_session" COMMENT="One class meeting of a course">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="courseid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="sessiontime" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false" COMMENT="Class start, rounded down to 10 minutes"/>
        <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0" SEQUENCE="false"/>
        <FIELD NAME="timemodified" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0" SEQUENCE="false"/>
        <FIELD NAME="usermodified" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0" SEQUENCE="false"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
        <KEY NAME="courseid" TYPE="foreign" FIELDS="courseid" REFTABLE="course" REFFIELDS="id"/>
        <KEY NAME="usermodified" TYPE="foreign" FIELDS="usermodified" REFTABLE="user" REFFIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="coursetime" UNIQUE="true" FIELDS="courseid, sessiontime"/>
      </INDEXES>
    </TABLE>
    <TABLE NAME="local_classlog_entry" COMMENT="Attendance of one student in one session">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="sessionid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="userid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="status" TYPE="int" LENGTH="2" NOTNULL="true" DEFAULT="0" SEQUENCE="false" COMMENT="0 present, 1 late, 2 absent, 3 excused"/>
        <FIELD NAME="lateminutes" TYPE="int" LENGTH="4" NOTNULL="false" SEQUENCE="false"/>
        <FIELD NAME="remark" TYPE="char" LENGTH="255" NOTNULL="true" DEFAULT="" SEQUENCE="false"/>
        <FIELD NAME="timemodified" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0" SEQUENCE="false"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
        <KEY NAME="sessionid" TYPE="foreign" FIELDS="sessionid" REFTABLE="local_classlog_session" REFFIELDS="id"/>
        <KEY NAME="userid" TYPE="foreign" FIELDS="userid" REFTABLE="user" REFFIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="sessionuser" UNIQUE="true" FIELDS="sessionid, userid"/>
      </INDEXES>
    </TABLE>
    <TABLE NAME="local_classlog_note" COMMENT="Lesson log of one session">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="sessionid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="progress" TYPE="text" NOTNULL="false" SEQUENCE="false"/>
        <FIELD NAME="homework" TYPE="text" NOTNULL="false" SEQUENCE="false"/>
        <FIELD NAME="memo" TYPE="text" NOTNULL="false" SEQUENCE="false"/>
        <FIELD NAME="timemodified" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0" SEQUENCE="false"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
        <KEY NAME="sessionid" TYPE="foreign-unique" FIELDS="sessionid" REFTABLE="local_classlog_session" REFFIELDS="id"/>
      </KEYS>
    </TABLE>
  </TABLES>
</XMLDB>
```

- [ ] **Step 3: Write `db/access.php`**

```php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/classlog:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/classlog:manage' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    // Who appears on the roster of a new session.
    'local/classlog:attend' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
    ],
];
```

- [ ] **Step 4: Write `db/hooks.php`**

```php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_course\hook\before_course_deleted::class,
        'callback' => \local_classlog\hook_callbacks::class . '::before_course_deleted',
    ],
    [
        'hook' => \core_user\hook\before_user_deleted::class,
        'callback' => \local_classlog\hook_callbacks::class . '::before_user_deleted',
    ],
];
```

- [ ] **Step 5: Write `lang/en/local_classlog.php`** (all strings for every task, so later tasks don't touch lang)

```php
defined('MOODLE_INTERNAL') || die();

$string['attendance'] = 'Attendance';
$string['classlog:attend'] = 'Appear on the class attendance roster';
$string['classlog:manage'] = 'Open, edit and delete class log sessions';
$string['classlog:view'] = 'View class log and attendance report';
$string['col_date'] = 'Date';
$string['col_rate'] = 'Attendance rate (%)';
$string['col_sessions'] = 'Classes';
$string['col_student'] = 'Student';
$string['confirmdelete'] = 'Delete the class on {$a} together with its attendance and lesson log?';
$string['counts'] = 'Present {$a->present} · Late {$a->late} · Absent {$a->absent} · Excused {$a->excused}';
$string['datefrom'] = 'From';
$string['dateto'] = 'To';
$string['deletesession'] = 'Delete this class';
$string['downloadcsv'] = 'Download CSV';
$string['event_attendance_saved'] = 'Class attendance saved';
$string['event_lessonlog_saved'] = 'Class lesson log saved';
$string['event_session_created'] = 'Class session opened';
$string['event_session_deleted'] = 'Class session deleted';
$string['event_desc'] = 'The user with id \'{$a->userid}\' {$a->action} the class session with id \'{$a->objectid}\' in the course with id \'{$a->courseid}\'.';
$string['homework'] = 'Homework';
$string['lastmodified'] = 'Last saved: {$a->user} · {$a->time}';
$string['lateminutes'] = 'Minutes late';
$string['lessonlog'] = 'Lesson';
$string['memo'] = 'Teacher memo';
$string['norows'] = 'No attendance in this period.';
$string['nosessions'] = 'No classes yet.';
$string['nostudents'] = 'No students were enrolled when this class was opened.';
$string['opentoday'] = 'Open today\'s class';
$string['pluginname'] = 'Class log';
$string['privacy:metadata:local_classlog_entry'] = 'Attendance of each student in each class.';
$string['privacy:metadata:local_classlog_entry:lateminutes'] = 'How many minutes late the student was.';
$string['privacy:metadata:local_classlog_entry:remark'] = 'A short note about the student\'s attendance.';
$string['privacy:metadata:local_classlog_entry:status'] = 'Present, late, absent or excused.';
$string['privacy:metadata:local_classlog_entry:timemodified'] = 'When the attendance was last saved.';
$string['privacy:metadata:local_classlog_entry:userid'] = 'The student.';
$string['privacy:metadata:local_classlog_session'] = 'Class meetings of a course.';
$string['privacy:metadata:local_classlog_session:timemodified'] = 'When the class was last saved.';
$string['privacy:metadata:local_classlog_session:usermodified'] = 'The teacher who last saved the class.';
$string['progress'] = 'Progress';
$string['ratehelp'] = 'Rate = (present + late) ÷ (classes − excused absences)';
$string['remark'] = 'Note';
$string['report'] = 'Attendance report';
$string['sessionexists'] = 'This course already has a class at this date and time.';
$string['sessions'] = 'Class log';
$string['sessiontime'] = 'Class date and time';
$string['show'] = 'Show';
$string['status_absent'] = 'Absent';
$string['status_excused'] = 'Excused';
$string['status_late'] = 'Late';
$string['status_present'] = 'Present';
```

- [ ] **Step 6: Write `lang/ko/local_classlog.php`** (same keys)

```php
defined('MOODLE_INTERNAL') || die();

$string['attendance'] = '출결';
$string['classlog:attend'] = '수업 출석부에 표시';
$string['classlog:manage'] = '수업일지 열기·수정·삭제';
$string['classlog:view'] = '수업일지와 출결 현황 보기';
$string['col_date'] = '일시';
$string['col_rate'] = '출석률(%)';
$string['col_sessions'] = '수업 수';
$string['col_student'] = '학생';
$string['confirmdelete'] = '{$a} 수업과 출결·수업일지를 삭제할까요?';
$string['counts'] = '출석 {$a->present} · 지각 {$a->late} · 결석 {$a->absent} · 사유 {$a->excused}';
$string['datefrom'] = '시작일';
$string['dateto'] = '종료일';
$string['deletesession'] = '이 수업 삭제';
$string['downloadcsv'] = 'CSV 다운로드';
$string['event_attendance_saved'] = '출결 저장';
$string['event_lessonlog_saved'] = '수업일지 저장';
$string['event_session_created'] = '수업 열기';
$string['event_session_deleted'] = '수업 삭제';
$string['event_desc'] = '사용자 id \'{$a->userid}\'가 강좌 id \'{$a->courseid}\'의 수업 id \'{$a->objectid}\'을(를) {$a->action}했습니다.';
$string['homework'] = '숙제';
$string['lastmodified'] = '마지막 수정: {$a->user} · {$a->time}';
$string['lateminutes'] = '지각(분)';
$string['lessonlog'] = '수업 내용';
$string['memo'] = '교사 메모';
$string['norows'] = '이 기간에 출결 기록이 없습니다.';
$string['nosessions'] = '아직 수업 기록이 없습니다.';
$string['nostudents'] = '이 수업을 열 때 등록된 학생이 없었습니다.';
$string['opentoday'] = '오늘 수업 열기';
$string['pluginname'] = '수업일지';
$string['privacy:metadata:local_classlog_entry'] = '수업별 학생 출결.';
$string['privacy:metadata:local_classlog_entry:lateminutes'] = '지각한 시간(분).';
$string['privacy:metadata:local_classlog_entry:remark'] = '출결에 대한 짧은 메모.';
$string['privacy:metadata:local_classlog_entry:status'] = '출석, 지각, 결석, 사유결석.';
$string['privacy:metadata:local_classlog_entry:timemodified'] = '출결을 마지막으로 저장한 시각.';
$string['privacy:metadata:local_classlog_entry:userid'] = '학생.';
$string['privacy:metadata:local_classlog_session'] = '강좌의 수업 회차.';
$string['privacy:metadata:local_classlog_session:timemodified'] = '수업을 마지막으로 저장한 시각.';
$string['privacy:metadata:local_classlog_session:usermodified'] = '수업을 마지막으로 저장한 교사.';
$string['progress'] = '진도';
$string['ratehelp'] = '출석률 = (출석 + 지각) ÷ (수업 수 − 사유결석)';
$string['remark'] = '비고';
$string['report'] = '출결 현황';
$string['sessionexists'] = '이 강좌에 같은 일시의 수업이 이미 있습니다.';
$string['sessions'] = '수업일지';
$string['sessiontime'] = '수업 일시';
$string['show'] = '보기';
$string['status_absent'] = '결석';
$string['status_excused'] = '사유결석';
$string['status_late'] = '지각';
$string['status_present'] = '출석';
```

- [ ] **Step 7: Write the failing tests**

`tests/local/status_test.php`:

```php
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
```

`tests/hook_callbacks_test.php`:

```php
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
```

`tests/access_test.php`:

```php
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
```

- [ ] **Step 8: Write `classes/local/status.php`**

```php
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
```

- [ ] **Step 9: Write `classes/local/cleanup.php`**

```php
namespace local_classlog\local;

/**
 * Deletes class log data.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cleanup {

    /**
     * Delete sessions with their entries and notes.
     *
     * @param int[] $sessionids
     */
    public static function delete_sessions(array $sessionids): void {
        global $DB;
        if (!$sessionids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($sessionids);
        $DB->delete_records_select('local_classlog_entry', "sessionid {$insql}", $params);
        $DB->delete_records_select('local_classlog_note', "sessionid {$insql}", $params);
        $DB->delete_records_select('local_classlog_session', "id {$insql}", $params);
    }

    /**
     * Delete every attendance entry of one user.
     *
     * @param int $userid
     */
    public static function delete_user_entries(int $userid): void {
        global $DB;
        $DB->delete_records('local_classlog_entry', ['userid' => $userid]);
    }
}
```

- [ ] **Step 10: Write `classes/hook_callbacks.php`**

```php
namespace local_classlog;

use core_course\hook\before_course_deleted;
use core_user\hook\before_user_deleted;
use local_classlog\local\cleanup;

/**
 * Hook listeners.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {

    /**
     * Remove a course's sessions before the course is deleted.
     *
     * @param before_course_deleted $hook
     */
    public static function before_course_deleted(before_course_deleted $hook): void {
        global $DB;
        $sessionids = $DB->get_fieldset_select('local_classlog_session', 'id', 'courseid = ?', [$hook->course->id]);
        cleanup::delete_sessions($sessionids);
    }

    /**
     * Remove a user's attendance before the user is deleted.
     *
     * @param before_user_deleted $hook
     */
    public static function before_user_deleted(before_user_deleted $hook): void {
        cleanup::delete_user_entries((int) $hook->user->id);
    }
}
```

- [ ] **Step 11: Install the plugin and rebuild the PHPUnit environment**

Run: `RUN php -l public/local/classlog/classes/local/status.php` (repeat for each new PHP file). Expected: `No syntax errors detected`.

Run: `RUN php admin/cli/upgrade.php --non-interactive --allow-unstable`
Expected: output lists `local_classlog` and ends with `Upgrade completed` (or the CLI's success line); no XMLDB errors.

Run in the background (30+ minutes, the plugin list changed so the test DB is dropped and reinstalled):
`RUN php public/admin/tool/phpunit/cli/init.php --disable-composer`
Expected: ends with `PHPUnit test environment setup complete.` and `phpunit.xml` contains `local_classlog_testsuite`.

- [ ] **Step 12: Run the tests**

Run: `TEST "status_test|hook_callbacks_test|access_test"`
Expected: `OK (5 tests, ...)` with no notices or deprecations.

- [ ] **Step 13: Commit**

```bash
git add public/local/classlog
git commit -m "feat(classlog): add plugin skeleton, schema and deletion hooks"
```

---

### Task 2: Session manager and events

**Files:**
- Create: `classes/event/session_base.php`, `classes/event/session_created.php`, `classes/event/attendance_saved.php`, `classes/event/lessonlog_saved.php`, `classes/event/session_deleted.php`, `classes/local/session_manager.php`
- Test: `tests/local/session_manager_test.php`

**Interfaces:**
- Consumes: `status`, `cleanup::delete_sessions()` (Task 1)
- Produces (`\local_classlog\local\session_manager`, all static):
  - `ROUND_SECONDS = 600`; `round_time(int $time): int`
  - `open(int $courseid, int $sessiontime): \stdClass` — session record (existing one if the rounded time is taken)
  - `get(int $sessionid): \stdClass` — throws `dml_missing_record_exception`
  - `time_taken(int $courseid, int $sessiontime, int $excludesessionid = 0): bool`
  - `get_entries(int $sessionid): array` — keyed by int userid; each `\stdClass` has `id`, `userid` (int), `status` (int), `lateminutes` (?int), `remark`, `fullname`, plus user name fields
  - `get_note(int $sessionid): \stdClass` — `progress`, `homework`, `memo` (strings, '' when none)
  - `save(int $sessionid, int $sessiontime, array $entries, array $note): void` — `$entries = [userid => ['status' => int, 'lateminutes' => ?int, 'remark' => string]]`, `$note = ['progress' => string, 'homework' => string, 'memo' => string]`; throws `\coding_exception` (bad status) or `\moodle_exception('sessionexists', 'local_classlog')`
  - `delete(int $sessionid): void`
  - `list_sessions(int $courseid): \stdClass[]` — newest first; `id`, `sessiontime`, `present`, `late`, `absent`, `excused` (ints), `progress` (string)
  - Events `\local_classlog\event\session_created|attendance_saved|lessonlog_saved|session_deleted`, created with `['objectid' => $sessionid, 'context' => \context_course]`

- [ ] **Step 1: Write the failing test `tests/local/session_manager_test.php`**

```php
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
```

- [ ] **Step 2: Run it to verify it fails**

Run: `TEST session_manager_test`
Expected: errors `Class "local_classlog\local\session_manager" not found`.

- [ ] **Step 3: Write the events**

`classes/event/session_base.php`:

```php
namespace local_classlog\event;

/**
 * Shared behaviour of class log session events.
 *
 * Create with ['objectid' => session id, 'context' => course context].
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class session_base extends \core\event\base {

    /** @var string Verb used in the description, e.g. "opened". Set by each event. */
    protected const ACTION = '';

    /** @var string c, r, u or d. Set by each event. */
    protected const CRUD = 'u';

    protected function init() {
        $this->data['objecttable'] = 'local_classlog_session';
        $this->data['crud'] = static::CRUD;
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public function get_description() {
        return get_string('event_desc', 'local_classlog', [
            'userid' => $this->userid,
            'objectid' => $this->objectid,
            'courseid' => $this->courseid,
            'action' => static::ACTION,
        ]);
    }

    public function get_url() {
        return new \moodle_url('/local/classlog/session.php', ['id' => $this->objectid]);
    }

    public static function get_objectid_mapping() {
        // Class logs are not part of course backups.
        return false;
    }

    public static function get_other_mapping() {
        return false;
    }
}
```

`classes/event/session_created.php` (the other three follow below in full):

```php
namespace local_classlog\event;

/**
 * A teacher opened a class session.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class session_created extends session_base {

    protected const ACTION = 'opened';
    protected const CRUD = 'c';

    public static function get_name() {
        return get_string('event_session_created', 'local_classlog');
    }
}
```

`classes/event/attendance_saved.php`:

```php
namespace local_classlog\event;

/**
 * Attendance of a class session was saved.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attendance_saved extends session_base {

    protected const ACTION = 'saved attendance of';
    protected const CRUD = 'u';

    public static function get_name() {
        return get_string('event_attendance_saved', 'local_classlog');
    }
}
```

`classes/event/lessonlog_saved.php`:

```php
namespace local_classlog\event;

/**
 * The lesson log of a class session was saved.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lessonlog_saved extends session_base {

    protected const ACTION = 'saved the lesson log of';
    protected const CRUD = 'u';

    public static function get_name() {
        return get_string('event_lessonlog_saved', 'local_classlog');
    }
}
```

`classes/event/session_deleted.php`:

```php
namespace local_classlog\event;

/**
 * A class session was deleted.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class session_deleted extends session_base {

    protected const ACTION = 'deleted';
    protected const CRUD = 'd';

    public static function get_name() {
        return get_string('event_session_deleted', 'local_classlog');
    }

    public function get_url() {
        return new \moodle_url('/local/classlog/index.php', ['id' => $this->courseid]);
    }
}
```

- [ ] **Step 4: Write `classes/local/session_manager.php`**

```php
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
```

- [ ] **Step 5: Run the tests**

Run: `TEST session_manager_test`
Expected: `OK (12 tests, ...)`, no notices. If a notice says "Unexpected debugging() call", read it: a missing event method or a missing name field in `get_entries` is the usual cause; fix the code, not the test.

- [ ] **Step 6: Commit**

```bash
git add public/local/classlog
git commit -m "feat(classlog): open, save and delete class sessions with events"
```

---

### Task 3: Attendance report

**Files:**
- Create: `classes/local/report.php`
- Test: `tests/local/report_test.php`

**Interfaces:**
- Consumes: `session_manager::open()`, `session_manager::save()`, `status` (Tasks 1-2)
- Produces (`\local_classlog\local\report`, static):
  - `rate(int $present, int $late, int $sessions, int $excused): ?float`
  - `summarise(int $courseid, int $from, int $to): \stdClass[]` — sessions with `from <= sessiontime < to`; rows ordered by full name with `userid`, `fullname`, `sessions`, `present`, `late`, `absent`, `excused` (ints), `rate` (?float)
  - `csv_columns(): string[]`, `csv_rows(array $rows): array[]`

- [ ] **Step 1: Write the failing test `tests/local/report_test.php`**

```php
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
```

- [ ] **Step 2: Run it to verify it fails**

Run: `TEST report_test`
Expected: errors `Class "local_classlog\local\report" not found`.

- [ ] **Step 3: Write `classes/local/report.php`**

```php
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
```

- [ ] **Step 4: Run the tests**

Run: `TEST report_test`
Expected: `OK (4 tests, ...)`.

- [ ] **Step 5: Commit**

```bash
git add public/local/classlog
git commit -m "feat(classlog): add per-student attendance report"
```

---

### Task 4: Privacy provider

**Files:**
- Create: `classes/privacy/provider.php`
- Test: `tests/privacy/provider_test.php`

**Interfaces:**
- Consumes: `session_manager::open()/save()`, `status::label()` (Tasks 1-2)
- Produces: `\local_classlog\privacy\provider` implementing the metadata, plugin and core_userlist providers. Export path: `[get_string('pluginname', 'local_classlog')]`, data `{attendance: [{sessiontime, status, lateminutes, remark}], sessionslastsaved: [datetime]}`.

- [ ] **Step 1: Write the failing test `tests/privacy/provider_test.php`**

```php
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
```

- [ ] **Step 2: Run it to verify it fails**

Run: `TEST provider_test`
Expected: errors `Class "local_classlog\privacy\provider" not found`.

- [ ] **Step 3: Write `classes/privacy/provider.php`**

```php
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

    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context instanceof \context_course) {
            self::delete_for_users((int) $context->instanceid, null);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                self::delete_for_users((int) $context->instanceid, [$userid]);
            }
        }
    }

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
```

- [ ] **Step 4: Run the tests**

Run: `TEST provider_test`
Expected: `OK (7 tests, ...)`.

Also run Moodle's generic privacy compliance test for this component:
`RUN vendor/bin/phpunit --testsuite core_privacy_testsuite --filter provider_test`
Expected: OK (it checks every component's metadata strings exist).

- [ ] **Step 5: Commit**

```bash
git add public/local/classlog
git commit -m "feat(classlog): export and delete attendance via the privacy API"
```

---

### Task 5: Pages, navigation, form, templates

**Files:**
- Create: `lib.php`, `index.php`, `session.php`, `report.php`, `classes/form/session_form.php`, `classes/form/report_filter_form.php`, `templates/session_list.mustache`, `templates/report.mustache`, `styles.css`

**Interfaces:**
- Consumes: all of `session_manager`, `report`, `status` (Tasks 1-3)
- Produces: URLs `/local/classlog/index.php?id=<courseid>`, `/local/classlog/session.php?id=<sessionid>`, `/local/classlog/report.php?id=<courseid>[&from=&to=&download=csv]`

- [ ] **Step 1: Write `lib.php`**

```php
defined('MOODLE_INTERNAL') || die();

/**
 * Add "Class log" and "Attendance report" to the course navigation.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_classlog_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context): void {
    if ((int) $course->id === SITEID || !has_capability('local/classlog:view', $context)) {
        return;
    }
    $navigation->add(
        get_string('sessions', 'local_classlog'),
        new moodle_url('/local/classlog/index.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_classlog_sessions',
        new pix_icon('i/calendar', ''),
    );
    $navigation->add(
        get_string('report', 'local_classlog'),
        new moodle_url('/local/classlog/report.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_classlog_report',
        new pix_icon('i/report', ''),
    );
}
```

- [ ] **Step 2: Write `templates/session_list.mustache`**

```mustache
{{!
    @template local_classlog/session_list

    Class sessions of a course, newest first.

    Context variables required for this template:
    * openbutton - rendered "open today's class" button, empty without manage capability
    * reporturl - attendance report URL
    * hassessions - bool
    * sessions - list of {date, url, counts, progress}

    Example context (json):
    {
        "openbutton": "",
        "reporturl": "#",
        "hassessions": true,
        "sessions": [
            {"date": "21/10/26, 16:00", "url": "#", "counts": "Present 8 · Late 1 · Absent 1 · Excused 0", "progress": "Unit 3"}
        ]
    }
}}
<div class="local-classlog-list">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        {{{openbutton}}}
        <a class="btn btn-outline-secondary" href="{{reporturl}}">{{#str}}report, local_classlog{{/str}}</a>
    </div>
    {{#hassessions}}
    <div class="list-group">
        {{#sessions}}
        <a class="list-group-item list-group-item-action" href="{{url}}">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <strong>{{date}}</strong>
                <span class="text-muted small">{{counts}}</span>
            </div>
            {{#progress}}<div class="small text-truncate mt-1">{{progress}}</div>{{/progress}}
        </a>
        {{/sessions}}
    </div>
    {{/hassessions}}
    {{^hassessions}}
    <p class="text-muted">{{#str}}nosessions, local_classlog{{/str}}</p>
    {{/hassessions}}
</div>
```

- [ ] **Step 3: Write `index.php`**

```php
require(__DIR__ . '/../../config.php');

use local_classlog\local\session_manager;

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/classlog:view', $context);
$canmanage = has_capability('local/classlog:manage', $context);

$url = new moodle_url('/local/classlog/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('sessions', 'local_classlog') . ' | ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

if (optional_param('open', 0, PARAM_BOOL)) {
    require_sesskey();
    require_capability('local/classlog:manage', $context);
    $session = session_manager::open((int) $course->id, time());
    redirect(new moodle_url('/local/classlog/session.php', ['id' => $session->id]));
}

$format = get_string('strftimedatetimeshort', 'core_langconfig');
$sessions = [];
foreach (session_manager::list_sessions((int) $course->id) as $session) {
    $lines = preg_split('/\R/', $session->progress);
    $sessions[] = [
        'date' => userdate($session->sessiontime, $format),
        'url' => (new moodle_url('/local/classlog/session.php', ['id' => $session->id]))->out(false),
        'counts' => get_string('counts', 'local_classlog', $session),
        'progress' => $lines[0] ?? '',
    ];
}

$openbutton = '';
if ($canmanage) {
    $openbutton = $OUTPUT->single_button(
        new moodle_url($url, ['open' => 1]),
        get_string('opentoday', 'local_classlog'),
        'post',
        ['type' => single_button::BUTTON_PRIMARY],
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sessions', 'local_classlog'));
echo $OUTPUT->render_from_template('local_classlog/session_list', [
    'openbutton' => $openbutton,
    'reporturl' => (new moodle_url('/local/classlog/report.php', ['id' => $course->id]))->out(false),
    'hassessions' => !empty($sessions),
    'sessions' => $sessions,
]);
echo $OUTPUT->footer();
```

- [ ] **Step 4: Write `classes/form/session_form.php`**

```php
namespace local_classlog\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_classlog\local\session_manager;
use local_classlog\local\status;

/**
 * Attendance + lesson log form for one session.
 *
 * Custom data: entries (from session_manager::get_entries), courseid, sessionid.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
        $entries = $this->_customdata['entries'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('date_time_selector', 'sessiontime', get_string('sessiontime', 'local_classlog'), ['step' => 10]);

        $mform->addElement('header', 'attendancehdr', get_string('attendance', 'local_classlog'));
        $mform->setExpanded('attendancehdr');
        if (!$entries) {
            $mform->addElement('static', 'nostudents', '', get_string('nostudents', 'local_classlog'));
        }
        foreach ($entries as $userid => $entry) {
            $radios = [];
            foreach (status::ALL as $value) {
                $radios[] = $mform->createElement('radio', "status[{$userid}]", '', status::label($value), $value);
            }
            $mform->addGroup($radios, "statusgroup[{$userid}]", s($entry->fullname), ' ', false);
            $mform->setType("status[{$userid}]", PARAM_INT);

            $mform->addElement('text', "lateminutes[{$userid}]", get_string('lateminutes', 'local_classlog'),
                ['size' => 3, 'inputmode' => 'numeric']);
            $mform->setType("lateminutes[{$userid}]", PARAM_INT);
            $mform->hideIf("lateminutes[{$userid}]", "status[{$userid}]", 'neq', status::LATE);

            $mform->addElement('text', "remark[{$userid}]", get_string('remark', 'local_classlog'),
                ['size' => 30, 'maxlength' => 255]);
            $mform->setType("remark[{$userid}]", PARAM_TEXT);
        }

        $mform->addElement('header', 'lessonhdr', get_string('lessonlog', 'local_classlog'));
        $mform->setExpanded('lessonhdr');
        foreach (['progress', 'homework', 'memo'] as $field) {
            $mform->addElement('textarea', $field, get_string($field, 'local_classlog'), ['rows' => 3, 'cols' => 60]);
            $mform->setType($field, PARAM_TEXT);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $taken = session_manager::time_taken(
            (int) $this->_customdata['courseid'],
            (int) $data['sessiontime'],
            (int) $this->_customdata['sessionid'],
        );
        if ($taken) {
            $errors['sessiontime'] = get_string('sessionexists', 'local_classlog');
        }
        return $errors;
    }
}
```

- [ ] **Step 5: Write `session.php`**

```php
require(__DIR__ . '/../../config.php');

use local_classlog\form\session_form;
use local_classlog\local\session_manager;
use local_classlog\local\status;

$id = required_param('id', PARAM_INT);
$session = session_manager::get($id);
$course = get_course($session->courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/classlog:view', $context);
$canmanage = has_capability('local/classlog:manage', $context);

$url = new moodle_url('/local/classlog/session.php', ['id' => $id]);
$listurl = new moodle_url('/local/classlog/index.php', ['id' => $course->id]);
$when = userdate($session->sessiontime, get_string('strftimedatetimeshort', 'core_langconfig'));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($when . ' | ' . get_string('sessions', 'local_classlog'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('sessions', 'local_classlog'), $listurl);
$PAGE->navbar->add($when);

if (optional_param('delete', 0, PARAM_BOOL)) {
    require_capability('local/classlog:manage', $context);
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        session_manager::delete($id);
        redirect($listurl, get_string('deletesession', 'local_classlog'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdelete', 'local_classlog', $when),
        new moodle_url($url, ['delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $url,
    );
    echo $OUTPUT->footer();
    exit;
}

$entries = session_manager::get_entries($id);
$form = new session_form($url, [
    'entries' => $entries,
    'courseid' => (int) $course->id,
    'sessionid' => $id,
], 'post', '', null, $canmanage);

if ($form->is_cancelled()) {
    redirect($listurl);
}
if ($canmanage && ($data = $form->get_data())) {
    $values = [];
    foreach (array_keys($entries) as $userid) {
        $values[$userid] = [
            'status' => (int) ($data->status[$userid] ?? status::PRESENT),
            'lateminutes' => isset($data->lateminutes[$userid]) ? (int) $data->lateminutes[$userid] : null,
            'remark' => (string) ($data->remark[$userid] ?? ''),
        ];
    }
    session_manager::save($id, (int) $data->sessiontime, $values, [
        'progress' => $data->progress,
        'homework' => $data->homework,
        'memo' => $data->memo,
    ]);
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$note = session_manager::get_note($id);
$defaults = [
    'id' => $id,
    'sessiontime' => $session->sessiontime,
    'progress' => $note->progress,
    'homework' => $note->homework,
    'memo' => $note->memo,
    'status' => [],
    'lateminutes' => [],
    'remark' => [],
];
foreach ($entries as $userid => $entry) {
    $defaults['status'][$userid] = $entry->status;
    $defaults['lateminutes'][$userid] = $entry->lateminutes;
    $defaults['remark'][$userid] = $entry->remark;
}
$form->set_data($defaults);

$modifier = core_user::get_user($session->usermodified);
echo $OUTPUT->header();
echo $OUTPUT->heading($when);
echo html_writer::div(get_string('lastmodified', 'local_classlog', [
    'user' => $modifier ? fullname($modifier) : '-',
    'time' => userdate($session->timemodified, get_string('strftimedatetimeshort', 'core_langconfig')),
]), 'text-muted small mb-3');
$form->display();
if ($canmanage) {
    echo $OUTPUT->single_button(new moodle_url($url, ['delete' => 1]), get_string('deletesession', 'local_classlog'), 'get');
}
echo $OUTPUT->footer();
```

- [ ] **Step 6: Write `classes/form/report_filter_form.php`**

```php
namespace local_classlog\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Date range for the attendance report.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_filter_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $group = [
            $mform->createElement('date_selector', 'datefrom', get_string('datefrom', 'local_classlog')),
            $mform->createElement('date_selector', 'dateto', get_string('dateto', 'local_classlog')),
            $mform->createElement('submit', 'show', get_string('show', 'local_classlog')),
        ];
        $mform->addGroup($group, 'range', '', ' ', false);
    }
}
```

- [ ] **Step 7: Write `templates/report.mustache`**

```mustache
{{!
    @template local_classlog/report

    Per-student attendance for a period.

    Context variables required for this template:
    * hasrows - bool
    * rows - list of {fullname, sessions, present, late, absent, excused, rate}
    * downloadurl - CSV download URL

    Example context (json):
    {
        "hasrows": true,
        "downloadurl": "#",
        "rows": [{"fullname": "Amy Alpha", "sessions": 3, "present": 1, "late": 1, "absent": 0, "excused": 1, "rate": "100"}]
    }
}}
<div class="local-classlog-report">
    {{#hasrows}}
    <div class="table-responsive">
        <table class="generaltable table-sm">
            <thead>
                <tr>
                    <th scope="col">{{#str}}col_student, local_classlog{{/str}}</th>
                    <th scope="col" class="text-end">{{#str}}col_sessions, local_classlog{{/str}}</th>
                    <th scope="col" class="text-end">{{#str}}status_present, local_classlog{{/str}}</th>
                    <th scope="col" class="text-end">{{#str}}status_late, local_classlog{{/str}}</th>
                    <th scope="col" class="text-end">{{#str}}status_absent, local_classlog{{/str}}</th>
                    <th scope="col" class="text-end">{{#str}}status_excused, local_classlog{{/str}}</th>
                    <th scope="col" class="text-end">{{#str}}col_rate, local_classlog{{/str}}</th>
                </tr>
            </thead>
            <tbody>
                {{#rows}}
                <tr>
                    <th scope="row">{{fullname}}</th>
                    <td class="text-end">{{sessions}}</td>
                    <td class="text-end">{{present}}</td>
                    <td class="text-end">{{late}}</td>
                    <td class="text-end">{{absent}}</td>
                    <td class="text-end">{{excused}}</td>
                    <td class="text-end">{{rate}}</td>
                </tr>
                {{/rows}}
            </tbody>
        </table>
    </div>
    <p class="text-muted small">{{#str}}ratehelp, local_classlog{{/str}}</p>
    <a class="btn btn-outline-secondary" href="{{downloadurl}}">{{#str}}downloadcsv, local_classlog{{/str}}</a>
    {{/hasrows}}
    {{^hasrows}}
    <p class="text-muted">{{#str}}norows, local_classlog{{/str}}</p>
    {{/hasrows}}
</div>
```

- [ ] **Step 8: Write `report.php`**

```php
require(__DIR__ . '/../../config.php');

use local_classlog\form\report_filter_form;
use local_classlog\local\report;

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/classlog:view', $context);

// Default range: first day of this month to today (user's time zone). "to" is a day, inclusive.
$today = usergetdate(time());
$from = optional_param('from', make_timestamp($today['year'], $today['mon'], 1), PARAM_INT);
$to = optional_param('to', usergetmidnight(time()), PARAM_INT);

$url = new moodle_url('/local/classlog/report.php', ['id' => $course->id]);
$PAGE->set_url(new moodle_url($url, ['from' => $from, 'to' => $to]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('report', 'local_classlog') . ' | ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$form = new report_filter_form($url, null, 'get');
if ($data = $form->get_data()) {
    $from = (int) $data->datefrom;
    $to = (int) $data->dateto;
}
$form->set_data(['id' => $course->id, 'datefrom' => $from, 'dateto' => $to]);

$rows = report::summarise((int) $course->id, $from, $to + DAYSECS);

if (optional_param('download', '', PARAM_ALPHA) === 'csv') {
    $filename = clean_filename('attendance-' . $course->shortname . '-' . date('Ymd', $from) . '-' . date('Ymd', $to));
    \core\dataformat::download_data($filename, 'csv', report::csv_columns(), report::csv_rows($rows));
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'local_classlog'));
$form->display();
echo $OUTPUT->render_from_template('local_classlog/report', [
    'hasrows' => !empty($rows),
    'rows' => array_map(function (\stdClass $row): array {
        $data = (array) $row;
        $data['rate'] = $row->rate ?? '-';
        return $data;
    }, $rows),
    'downloadurl' => (new moodle_url($url, ['from' => $from, 'to' => $to, 'download' => 'csv']))->out(false),
]);
echo $OUTPUT->footer();
```

- [ ] **Step 9: Write `styles.css`** (large touch targets for the status radios)

```css
#page-local-classlog-session [id^="fgroup_id_statusgroup"] .form-check-inline {
    min-height: 2.75rem;
    margin-right: 1rem;
    align-items: center;
}

#page-local-classlog-session [id^="fgroup_id_statusgroup"] .form-check-input {
    width: 1.5rem;
    height: 1.5rem;
    margin-right: .375rem;
}
```

- [ ] **Step 10: Lint and validate**

Run, for each new PHP file: `RUN php -l public/local/classlog/<file>` → `No syntax errors detected`.
Run: `RUN php admin/cli/purge_caches.php`
Run the template lint used by Moodle CI if present: `RUN php public/admin/tool/templatelibrary/cli/lint.php local_classlog 2>/dev/null || true` (skip silently if the script does not exist; the manual check below covers rendering).

- [ ] **Step 11: Manual check in the browser** (Playwright; screenshots go to `C:\Users\sound\moodle\.playwright-mcp\`)

1. Before opening the browser, request a page once with curl so styles are compiled: `curl -s -o /dev/null http://localhost:8080/login/index.php`.
2. Log in as `teacher1` / `ReadMaster1!`, open a demo course that has student1/student2 (e.g. the bookclub course from `setup_readmaster.php`).
3. Check that the course "More" menu shows 수업일지 and 출결 현황. If the links are missing, look at whether the secondary navigation filters setting nodes and report it rather than guessing.
4. 수업일지 → 오늘 수업 열기 → both students are 출석. Set one to 지각 with 10 minutes (the minutes box appears only for 지각), the other 결석 with a note, fill 진도/숙제, save. Expect the success notice and "마지막 수정: …".
5. Back on the list: one row with "출석 0 · 지각 1 · 결석 1 · 사유 0".
6. 출결 현황: both students listed, rates 100 and 0; CSV downloads with Korean headers.
7. Resize to 390px: no horizontal scroll on the session page; radios are easy to tap.
8. Log in as `student1`: the menu items are absent and opening `/local/classlog/index.php?id=<courseid>` shows a permissions error.
9. Delete the test session as teacher1 and confirm the list is empty again.

- [ ] **Step 12: Commit**

```bash
git add public/local/classlog
git commit -m "feat(classlog): add class log, session and report pages"
```

---

### Task 6: Full suite, docs, memory

**Files:**
- Modify: `docs/academy-playbook.md` (add a short section)

- [ ] **Step 1: Run the whole plugin suite in the background**

Run: `RUN vendor/bin/phpunit --testsuite local_classlog_testsuite --display-notices --display-phpunit-deprecations`
Expected: `OK (28 tests, ...)` (5 status/hook/access + 12 session_manager + 4 report + 7 privacy), no notices or deprecations.

- [ ] **Step 2: Add a section to `docs/academy-playbook.md`** before "## Out of scope for the theme":

```markdown
## Class log (local_classlog)

Attendance + lesson log lives in `public/local/classlog/` and has no academy-specific data,
so clones need no change. It installs with the same `upgrade.php --allow-unstable` run.
Teachers see 수업일지 / 출결 현황 in the course "More" menu. Spec:
`docs/superpowers/specs/2026-10-21-classlog-design.md`.
```

- [ ] **Step 3: Commit**

```bash
git add docs/academy-playbook.md
git commit -m "docs: note class log plugin in academy playbook"
```

- [ ] **Step 4: Update memory** — in `C:\Users\sound\.claude\projects\C--Users-sound-moodle\memory\readmaster-moodle-setup.md`, add one line: adding or changing any plugin's `db/` files forces a full PHPUnit reinstall (30+ minutes), so batch `db/` changes.
