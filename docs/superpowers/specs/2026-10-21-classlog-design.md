# Class Log Design (local_classlog): attendance + lesson log

Date: 2026-10-21 · Status: approved in chat, pending spec review

## Goal

Give teachers one screen per class meeting to take attendance and write the lesson log
(progress, homework, memo), and give the director a per-student attendance report.

Context from the Chamilo comparison (`CAttendanceSheet`, `CThematicAdvance`): Moodle 6.0 core has
no attendance and no lesson log. ReadMaster has no attendance system today, so **Moodle becomes the
source of truth** for attendance.

## Decisions

| Question | Decision |
|---|---|
| Existing attendance system | None. No sync or import needed |
| Who sees it in v1 | Teachers and managers only. Students and parents have no access |
| How sessions are created | The teacher opens "today's class" when it starts. No timetable |
| Approach | Custom `local_classlog` plugin (not `mod_attendance`: 6.0dev compatibility unverified and it is timetable-first) |
| Unit of a class | One Moodle course = one class (반), as in `setup_readmaster.php` |
| Attendance rate | (present + late) ÷ (sessions − excused absences). Excused absences do not lower the rate |

## Data

Plugin at `public/local/classlog/`. Three tables (`db/install.xml`):

**`local_classlog_session`** — one class meeting

| Field | Type | Notes |
|---|---|---|
| id | int | |
| courseid | int, FK course | indexed |
| sessiontime | int | Unix time of the class start |
| timecreated, timemodified | int | |
| usermodified | int, FK user | last teacher who saved |

Unique index (courseid, sessiontime): opening the same date/time twice returns the existing session.

**`local_classlog_entry`** — one student's attendance in one session

| Field | Type | Notes |
|---|---|---|
| id | int | |
| sessionid | int, FK session | |
| userid | int, FK user | |
| status | int | 0 present, 1 late, 2 absent, 3 excused |
| lateminutes | int, nullable | only meaningful when status = late |
| remark | char(255) | short note, e.g. "병원 진료" |
| timemodified | int | |

Unique index (sessionid, userid).

**`local_classlog_note`** — the lesson log for one session

| Field | Type | Notes |
|---|---|---|
| id | int | |
| sessionid | int, FK session, unique | |
| progress | text | what was covered today |
| homework | text | |
| memo | text | teacher's private memo |
| timemodified | int | |

Text fields are plain text (`PARAM_TEXT`), shown with `s()`/`format_string`-level escaping. No HTML editor in v1.

## Roster rule

- **New session**: entries are created for users with an **active** enrolment and the
  `local/classlog:attend` capability (granted to `student` by default), all with status present.
- **Existing session**: shows exactly the users who have entries, even if they have since left the
  course. Students who enrolled after the session was created are not added to past sessions.

## Screens

Course navigation (via `local_classlog_extend_navigation_course` in `lib.php`) gets two links,
shown only with `local/classlog:view`.

1. **`index.php?id=<courseid>` — Session list.** Newest first: date/time, counts
   ("출석 8 · 지각 1 · 결석 1 · 사유 0"), first line of progress. Button **[오늘 수업 열기]**
   (needs `:manage`) creates a session at the current time, rounded down to 10 minutes, and redirects
   to it.
2. **`session.php?id=<sessionid>` — Attendance + lesson log.** One Moodle form:
   - Per student: four large radio buttons (출석/지각/결석/사유결석), late minutes (shown for 지각),
     remark.
   - Lesson log: progress, homework, memo.
   - Editable session date/time.
   - Footer: "마지막 수정: <teacher> · <time>".
   - Delete button with a confirmation page.
   Works without JavaScript; JS only toggles the late-minutes field.
3. **`report.php?id=<courseid>` — Attendance report.** Date range (default: current month).
   Per student: sessions, present, late, absent, excused, rate (formula above; "-" when the
   denominator is 0). CSV download via Moodle's `\core\dataformat`.

## Capabilities (`db/access.php`)

| Capability | Default roles | Purpose |
|---|---|---|
| `local/classlog:view` | editingteacher, teacher, manager | see list, sessions, report |
| `local/classlog:manage` | editingteacher, teacher, manager | open, save, delete sessions |
| `local/classlog:attend` | student | appear on the roster |

Context level: course.

## Code structure

| Path | Role |
|---|---|
| `classes/local/session_manager.php` | open/get/save/delete sessions, roster rule, duplicate check, fires events. Pure data in, data out |
| `classes/local/report.php` | per-student counts and rate for a course and date range; CSV rows |
| `classes/local/status.php` | status constants and their language strings |
| `classes/form/session_form.php` | the session form |
| `classes/event/session_created.php`, `attendance_saved.php`, `lessonlog_saved.php`, `session_deleted.php` | logstore events |
| `classes/hook_callbacks.php` + `db/hooks.php` | `\core_course\hook\before_course_deleted` removes the course's sessions; `\core_user\hook\before_user_deleted` removes the user's entries |
| `classes/privacy/provider.php` | metadata, export, delete for entries (student) and `usermodified` (teacher) |
| `index.php`, `session.php`, `report.php` | thin pages: check access, call the classes, render |
| `templates/session_list.mustache`, `templates/report.mustache` | |
| `lang/en/local_classlog.php`, `lang/ko/local_classlog.php` | |

Pages hold no business logic, so everything except rendering is unit-tested.

## Edits and concurrency

Any teacher with `:manage` in the course can edit any session. Last save wins.
No change-history table; the four events give the audit trail in Moodle's log report.

## Privacy

Attendance of minors is personal data. The provider implements
`\core_privacy\local\metadata\provider`, `\core_privacy\local\request\plugin\provider` and
`\core_privacy\local\request\core_userlist_provider`:

- Export: a student's entries per course (date, status, late minutes, remark).
- Delete for user / for context / for userlist: removes entries; for teachers, `usermodified` is
  set to the admin user rather than deleting class records.

## Testing

PHPUnit suite `local_classlog_testsuite` (about 10 minutes per run on this machine; run in background):

- `session_manager_test`: new session puts all active students in as present; duplicate open
  returns the same session; suspended/unenrolled students excluded from new sessions but kept in
  old ones; save updates `usermodified` and fires events; delete removes entries and note.
- `report_test`: rate formula A including the excused case and the zero-denominator case;
  date-range filter; CSV columns.
- `hook_callbacks_test`: course delete and user delete clean up.
- `privacy/provider_test`: export and the three delete paths.
- Access: a student gets `required_capability_exception` on all three pages' capability checks.

Manual check: install via `admin/cli/upgrade.php`, then with the demo course (teacher1,
student1/2) open → change statuses → save → report, screenshots at 1440px and 390px.

## Out of scope (v1)

Student/parent viewing, timetable-generated sessions, Kakao/SMS notifications, proof attachments
for excused absences, course backup/restore and course copy of logs, groups within a course.
