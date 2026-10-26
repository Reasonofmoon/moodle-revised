<?php
/**
 * ReadMaster English Reading Academy - Moodle Initial Configuration & Sample Curriculum Setup Script
 * Run inside container: php /var/www/html/setup_readmaster.php
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/user/lib.php');

// Switch to admin user context for all creation APIs
\core\session\manager::set_user(get_admin());

cli_heading("1. 한국어 언어팩(ko) 설치 및 사이트 기본 환경 설정");

if (is_dir($CFG->dataroot . '/lang/ko')) {
    cli_writeln("한국어(ko) 언어팩이 이미 설치되어 있습니다.");
} else {
    try {
        $langcontroller = new \tool_langimport\controller();
        if (!$langcontroller->install_languagepacks('ko')) {
            cli_writeln("Language pack 'ko' installation note: " . implode(', ', $langcontroller->errors));
        } else {
            cli_writeln("한국어(ko) 언어팩 설치 완료: " . implode(', ', $langcontroller->info));
        }
    } catch (\Throwable $e) {
        cli_writeln("한국어 언어팩 다운로드 경고: " . $e->getMessage());
    }
}

// Site-wide settings
set_config('noreplyaddress', 'noreply@readmaster.kr');
set_config('supportemail', 'support@readmaster.kr');
set_config('lang', 'ko');
set_config('forcelogin', 0);
set_config('timezone', 'Asia/Seoul');
set_config('forcetimezone', 'Asia/Seoul');
set_config('country', 'KR');
// Keep frontpage ultra-clean: only available courses (card grid)
set_config('frontpage', '6');
set_config('frontpageloggedin', '6');
set_config('maxbytes', 104857600); // 100MB
set_config('enablecompletion', 1);
set_config('enablemyhome', 1);
set_config('enabledashboard', 0); // Disable cluttered Dashboard tab; keep only Home & My Courses
set_config('defaulthomepage', HOMEPAGE_SITE);

// Disable unnecessary/bloated Moodle subsystems for a lightweight Academy LMS
set_config('enablebadges', 0);
set_config('enableblogs', 0);
set_config('bloglevel', 0);
set_config('enableanalytics', 0);
set_config('messaging', 0);
set_config('usetags', 0);
set_config('enablenotes', 0);
set_config('enableoutcomes', 0);
set_config('enableportfolios', 0);
set_config('enablewebservices', 0);
set_config('enablemobilewebservice', 0);
set_config('enabled', 0, 'core_competency');
set_config(' courselistshortnames', 0);

// Top navigation custom menu shortcuts
$custommenu = "📚 전체 커리큘럼|/course/index.php\n🎯 레벨 테스트 바로가기|/course/view.php?id=7\n📅 학부모 상담·간담회 예약|/booking/index.php\n📋 예약 CRM 관리|/booking/admin.php";
set_config('custommenuitems', $custommenu);

// Ensure Moodle Native Booking & CRM DB table (mdl_readmaster_leads) exists
if (file_exists(__DIR__ . '/public/booking/lib.php')) {
    require_once(__DIR__ . '/public/booking/lib.php');
    readmaster_booking_ensure_schema();
}

// Theme Boost customization for ReadMaster Academy (Lightweight & Modern Card UI)
set_config('brandcolor', '#1E3A8A', 'theme_boost');
$customscss = <<<SCSS
// ReadMaster Academy Lightweight & Clean UI Styling
.readmaster-hero {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    color: #ffffff;
    border-radius: 16px;
    padding: 2rem 2.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 20px rgba(30, 58, 138, 0.12);
}
.readmaster-hero h2 {
    color: #ffffff;
    font-weight: 800;
    font-size: 1.6rem;
    margin-bottom: 0.5rem;
}
.readmaster-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}
.readmaster-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.85rem;
    margin-top: 1.25rem;
}
.readmaster-card {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    padding: 1rem;
}
.readmaster-card h4 {
    color: #fde047;
    font-size: 0.98rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.readmaster-card p {
    margin-bottom: 0;
    font-size: 0.85rem;
    opacity: 0.95;
}

// Modernize frontpage course list into a clean 2-column card grid
#frontpage-available-course-list .courses.frontpage-course-list-all {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.25rem;
    margin-top: 1rem;
}
#frontpage-available-course-list .coursebox {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.35rem;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    margin-bottom: 0 !important;
}
#frontpage-available-course-list .coursebox:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(30, 58, 138, 0.08);
    border-color: #93c5fd;
}
#frontpage-available-course-list .coursebox.first {
    border: 2px solid #2563eb;
    background: #f8fafc;
}
#frontpage-available-course-list .coursebox .coursename a {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a8a;
    text-decoration: none;
}
#frontpage-available-course-list .coursebox .teachers {
    margin-top: 0.75rem;
    padding-top: 0.65rem;
    border-top: 1px solid #f1f5f9;
    font-size: 0.85rem;
    color: #64748b;
    list-style: none;
    padding-left: 0;
    margin-bottom: 0;
}

// Hide redundant frontpage title & Moodle clutter
#page-site-index .page-header-headings,
.btn-footer-popover,
.footer-content-debugging,
#page-site-index .sectionname {
    display: none !important;
}
SCSS;
set_config('scss', $customscss, 'theme_boost');
set_config('footnote', '<div class="text-center py-2 text-muted small"><strong>리드마스터 영어독해·원서리딩 전문학원 (ReadMaster Academy)</strong> | 문의: 02-555-READ | 평일 13:00~22:00 / 주말 10:00~18:00</div>', 'theme_boost');

// Update Frontpage Course (SITEID = 1) - avoid duplicate summary in both course and section 1
$site = get_site();
$frontpagesummary = <<<HTML
<div class="readmaster-hero">
    <span class="readmaster-badge">READMASTER ENGLISH READING ACADEMY</span>
    <h2>리드마스터 영어독해·원서리딩 학원 온라인 학습관</h2>
    <p style="font-size: 1rem; opacity: 0.95; margin-bottom: 0;">
        원서 정독(Original Book Reading)과 구문·논리 독해로 완성하는 진짜 영어 실력! 신입생은 아래 <strong>[진단] 레벨 테스트</strong>부터 응시해 주세요.
    </p>
    <div class="readmaster-grid">
        <div class="readmaster-card">
            <h4>Step 1. 레벨 진단</h4>
            <p>어휘·구문·추론 진단 후 AR/Lexile 수준별 반 배정</p>
        </div>
        <div class="readmaster-card">
            <h4>Step 2. 원서 리딩 클럽</h4>
            <p>Magic Tree House &amp; Newbery 수상작 정독·퀴즈</p>
        </div>
        <div class="readmaster-card">
            <h4>Step 3. 구문·수능 독해</h4>
            <p>직독직해 구문 분석과 수능 비문학 빈칸·추론 완성</p>
        </div>
        <div class="readmaster-card">
            <h4>Step 4. 북리포트 &amp; 에세이</h4>
            <p>독후 요약 및 시사 영자신문(NIE) 1:1 라이팅 첨삭</p>
        </div>
    </div>
</div>
HTML;

$DB->update_record('course', (object)[
    'id' => $site->id,
    'fullname' => '리드마스터 영어독해·원서리딩 학원 (ReadMaster Academy)',
    'shortname' => 'ReadMaster',
    'summary' => '',
    'summaryformat' => FORMAT_HTML,
    'numsections' => 1,
]);

// Update Frontpage Section 1 summary so the hero banner displays once cleanly at the top
$fpsection = $DB->get_record('course_sections', ['course' => $site->id, 'section' => 1]);
if (!$fpsection) {
    course_create_sections_if_missing($site->id, [1]);
    $fpsection = $DB->get_record('course_sections', ['course' => $site->id, 'section' => 1]);
}
if ($fpsection) {
    $DB->update_record('course_sections', (object)[
        'id' => $fpsection->id,
        'name' => '',
        'summary' => $frontpagesummary,
        'summaryformat' => FORMAT_HTML,
        'visible' => 1,
    ]);
}
rebuild_course_cache($site->id, true);

cli_heading("2. 리드마스터 학원 강좌 카테고리 생성");

$categoriesdef = [
    [
        'name' => '01. 레벨 테스트 & 진단 (Placement & Level Test)',
        'idnumber' => 'RM-CAT-LEVEL',
        'description' => '<p>신입생 및 재원생 대상 영어 독해력(어휘·구문·추론) 진단 평가 및 추천 커리큘럼 안내 카테고리입니다.</p>',
    ],
    [
        'name' => '02. 파닉스 & 기초 리딩 (Phonics & Starter Reading)',
        'idnumber' => 'RM-CAT-STARTER',
        'description' => '<p>초등 저학년 및 영어 리딩 입문자를 위한 파닉스, 사이트워드, 초기 챕터북(AR 1.5~2.8) 리딩 과정입니다.</p>',
    ],
    [
        'name' => '03. 원서 리딩 클럽 (Original Book Reading Club)',
        'idnumber' => 'RM-CAT-BOOKCLUB',
        'description' => '<p>미국·영국 학년별 필독서 및 뉴베리(Newbery) 수상작 원서를 읽고 챕터 퀴즈와 북클럽 토론을 진행하는 정규 과정입니다 (AR 3.0~5.5+).</p>',
    ],
    [
        'name' => '04. 수능·내신 심층 독해 (Academic & CSAT Reading)',
        'idnumber' => 'RM-CAT-ACADEMIC',
        'description' => '<p>중·고등학생을 위한 구문 분석(Syntax), 비문학 아티클 정독, 내신 서술형 및 수능 고난도 추론(빈칸·순서·삽입) 완성 과정입니다.</p>',
    ],
    [
        'name' => '05. 영자신문 & 에세이 라이팅 (NIE & Book Report Writing)',
        'idnumber' => 'RM-CAT-WRITING',
        'description' => '<p>시사 영자신문(NIE) 리딩과 독후 에세이(Book Report)·비판적 에세이 라이팅 1:1 첨삭 과정입니다.</p>',
    ],
];

$categories = [];
foreach ($categoriesdef as $catdef) {
    $existing = $DB->get_record('course_categories', ['idnumber' => $catdef['idnumber']]);
    if ($existing) {
        $categories[$catdef['idnumber']] = \core_course_category::get($existing->id);
        cli_writeln("기존 카테고리 유지: {$catdef['name']}");
    } else {
        $categories[$catdef['idnumber']] = \core_course_category::create([
            'name' => $catdef['name'],
            'idnumber' => $catdef['idnumber'],
            'description' => $catdef['description'],
            'descriptionformat' => FORMAT_HTML,
            'parent' => 0,
            'visible' => 1,
        ]);
        cli_writeln("카테고리 생성 완료: {$catdef['name']}");
    }
}

// Hide default "Category 1" if empty
$defaultcat = \core_course_category::get(1, IGNORE_MISSING, true);
if ($defaultcat) {
    $defaultcat->update([
        'name' => '기타 / 아카이브 (Archive)',
        'idnumber' => 'RM-CAT-ARCHIVE',
        'visible' => 0,
    ]);
}

cli_heading("3. 샘플 강사 및 학생 계정 생성");

$usersdef = [
    [
        'username' => 'teacher1',
        'password' => 'ReadMaster1!',
        'firstname' => '리드(원장)',
        'lastname' => '김',
        'email' => 'director@readmaster.kr',
        'city' => '서울 강남구',
        'country' => 'KR',
        'lang' => 'ko',
        'description' => '리드마스터 학원 대표원장 / 구문·수능 심층독해 및 레벨 진단 총괄',
    ],
    [
        'username' => 'teacher2',
        'password' => 'ReadMaster1!',
        'firstname' => 'Sarah',
        'lastname' => 'Miller',
        'email' => 'sarah@readmaster.kr',
        'city' => '서울 강남구',
        'country' => 'KR',
        'lang' => 'ko',
        'description' => '원서 리딩 클럽(Newbery & Chapter Books) 및 에세이 첨삭 전담 강사',
    ],
    [
        'username' => 'student1',
        'password' => 'ReadMaster1!',
        'firstname' => '지훈',
        'lastname' => '박',
        'email' => 'jihoon@student.readmaster.kr',
        'city' => '서울',
        'country' => 'KR',
        'lang' => 'ko',
        'description' => '리드마스터 재원생 (중등 심층독해 & 뉴베리 원서반)',
    ],
    [
        'username' => 'student2',
        'password' => 'ReadMaster1!',
        'firstname' => '서연',
        'lastname' => '이',
        'email' => 'seoyeon@student.readmaster.kr',
        'city' => '서울',
        'country' => 'KR',
        'lang' => 'ko',
        'description' => '리드마스터 재원생 (초등 매직트리하우스 & 파닉스 리딩반)',
    ],
];

$createdusers = [];
foreach ($usersdef as $udef) {
    $existinguser = $DB->get_record('user', ['username' => $udef['username'], 'deleted' => 0]);
    if ($existinguser) {
        $createdusers[$udef['username']] = $existinguser;
        cli_writeln("기존 사용자 유지: {$udef['username']} ({$udef['lastname']}{$udef['firstname']})");
    } else {
        $user = new stdClass();
        $user->username = $udef['username'];
        $user->password = hash_internal_user_password($udef['password']);
        $user->firstname = $udef['firstname'];
        $user->lastname = $udef['lastname'];
        $user->email = $udef['email'];
        $user->city = $udef['city'];
        $user->country = $udef['country'];
        $user->lang = $udef['lang'];
        $user->timezone = 'Asia/Seoul';
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->description = $udef['description'];
        $user->descriptionformat = FORMAT_HTML;
        $user->id = user_create_user($user, false, false);
        $createdusers[$udef['username']] = $DB->get_record('user', ['id' => $user->id]);
        cli_writeln("사용자 생성 완료: {$udef['username']} ({$udef['lastname']}{$udef['firstname']})");
    }
}

/**
 * Helper to create a Page module in a course section
 */
function rm_add_page(stdClass $course, int $sectionnum, string $name, string $intro, string $content): stdClass {
    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'page';
    $moduleinfo->course = $course->id;
    $moduleinfo->section = $sectionnum;
    $moduleinfo->visible = 1;
    $moduleinfo->name = $name;
    $moduleinfo->intro = $intro;
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->introeditor = ['text' => $intro, 'format' => FORMAT_HTML, 'itemid' => 0];
    $moduleinfo->showdescription = 1;
    $moduleinfo->page = ['text' => $content, 'format' => FORMAT_HTML, 'itemid' => 0];
    $moduleinfo->display = 5;
    $moduleinfo->printintro = 1;
    $moduleinfo->printlastmodified = 1;
    return create_module($moduleinfo);
}

/**
 * Helper to create an Assignment module in a course section
 */
function rm_add_assign(stdClass $course, int $sectionnum, string $name, string $intro, int $duedays = 7): stdClass {
    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'assign';
    $moduleinfo->course = $course->id;
    $moduleinfo->section = $sectionnum;
    $moduleinfo->visible = 1;
    $moduleinfo->name = $name;
    $moduleinfo->intro = $intro;
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->introeditor = ['text' => $intro, 'format' => FORMAT_HTML, 'itemid' => 0];
    $moduleinfo->showdescription = 1;
    $moduleinfo->allowsubmissionsfromdate = time();
    $moduleinfo->duedate = time() + ($duedays * 86400);
    $moduleinfo->cutoffdate = 0;
    $moduleinfo->gradingduedate = time() + (($duedays + 3) * 86400);
    $moduleinfo->submissiondrafts = 0;
    $moduleinfo->requiresubmissionstatement = 0;
    $moduleinfo->sendnotifications = 0;
    $moduleinfo->sendlatenotifications = 0;
    $moduleinfo->sendstudentnotifications = 1;
    $moduleinfo->teamsubmission = 0;
    $moduleinfo->requireallteammemberssubmit = 0;
    $moduleinfo->blindmarking = 0;
    $moduleinfo->markingworkflow = 0;
    $moduleinfo->markingallocation = 0;
    $moduleinfo->assignsubmission_onlinetext_enabled = 1;
    $moduleinfo->assignsubmission_file_enabled = 1;
    $moduleinfo->assignsubmission_file_maxfiles = 5;
    $moduleinfo->assignsubmission_file_maxsizebytes = 20971520;
    $moduleinfo->assignfeedback_comments_enabled = 1;
    $moduleinfo->grade = 100;
    return create_module($moduleinfo);
}

/**
 * Helper to create a Quiz module with multiple-choice questions
 */
function rm_add_quiz_with_questions(stdClass $course, int $sectionnum, string $name, string $intro, int $timelimit, array $questionsdata): stdClass {
    global $DB, $USER;

    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'quiz';
    $moduleinfo->course = $course->id;
    $moduleinfo->section = $sectionnum;
    $moduleinfo->visible = 1;
    $moduleinfo->name = $name;
    $moduleinfo->intro = $intro;
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->introeditor = ['text' => $intro, 'format' => FORMAT_HTML, 'itemid' => 0];
    $moduleinfo->showdescription = 1;
    $moduleinfo->timeopen = 0;
    $moduleinfo->timeclose = 0;
    $moduleinfo->timelimit = $timelimit;
    $moduleinfo->overduehandling = 'autosubmit';
    $moduleinfo->graceperiod = 0;
    $moduleinfo->preferredbehaviour = 'deferredfeedback';
    $moduleinfo->attempts = 0;
    $moduleinfo->attemptonlast = 0;
    $moduleinfo->grademethod = QUIZ_GRADEHIGHEST;
    $moduleinfo->decimalpoints = 0;
    $moduleinfo->questiondecimalpoints = -1;
    $moduleinfo->questionsperpage = 1;
    $moduleinfo->shuffleanswers = 1;
    $moduleinfo->grade = 100;
    $moduleinfo->quizpassword = '';
    $moduleinfo->subnet = '';
    $moduleinfo->browsersecurity = '-';
    $moduleinfo->navmethod = QUIZ_NAVMETHOD_FREE;

    // Review options (show feedback immediately after attempt)
    $reviewfields = [
        'attempt', 'correctness', 'maxmarks', 'marks',
        'specificfeedback', 'generalfeedback', 'rightanswer', 'overallfeedback'
    ];
    foreach ($reviewfields as $field) {
        $moduleinfo->{$field . 'during'} = 0;
        $moduleinfo->{$field . 'immediately'} = 1;
        $moduleinfo->{$field . 'open'} = 1;
        $moduleinfo->{$field . 'closed'} = 1;
    }

    $createdmod = create_module($moduleinfo);
    $quiz = $DB->get_record('quiz', ['id' => $createdmod->instance], '*', MUST_EXIST);
    $quiz->cmid = $createdmod->coursemodule;

    // Ensure default question category exists in the quiz module context
    $quizcontext = context_module::instance($quiz->cmid);
    $defaultcat = question_get_default_category($quizcontext->id, true);
    if (!$defaultcat) {
        $topcat = question_get_top_category($quizcontext->id, true);
        $cat = new stdClass();
        $cat->name = 'Default for ' . $name;
        $cat->contextid = $quizcontext->id;
        $cat->info = '';
        $cat->infoformat = FORMAT_HTML;
        $cat->parent = $topcat->id;
        $cat->sortorder = 999;
        $cat->stamp = make_unique_id_code();
        $cat->id = $DB->insert_record('question_categories', $cat);
        $defaultcat = $cat;
    }

    foreach ($questionsdata as $idx => $qdef) {
        $question = new stdClass();
        $question->category = $defaultcat->id;
        $question->qtype = 'multichoice';
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;

        $fromform = new stdClass();
        $fromform->category = $defaultcat->id . ',' . $quizcontext->id;
        $fromform->name = $qdef['name'];
        $fromform->questiontext = ['text' => $qdef['questiontext'], 'format' => FORMAT_HTML];
        $fromform->generalfeedback = ['text' => $qdef['generalfeedback'] ?? '', 'format' => FORMAT_HTML];
        $fromform->defaultmark = $qdef['mark'] ?? 20;
        $fromform->penalty = 0.3333333;
        $fromform->qtype = 'multichoice';
        $fromform->single = 1;
        $fromform->shuffleanswers = 1;
        $fromform->answernumbering = 'abc';
        $fromform->showstandardinstruction = 0;
        $fromform-> status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $fromform->correctfeedback = ['text' => '<p>정답입니다! 훌륭한 독해력입니다.</p>', 'format' => FORMAT_HTML];
        $fromform->partiallycorrectfeedback = ['text' => '<p>부분 정답입니다.</p>', 'format' => FORMAT_HTML];
        $fromform->incorrectfeedback = ['text' => '<p>아쉽습니다. 지문의 핵심 근거 문장을 다시 확인해 보세요.</p>', 'format' => FORMAT_HTML];

        $fromform->answer = [];
        $fromform->fraction = [];
        $fromform->feedback = [];

        foreach ($qdef['choices'] as $cidx => $choice) {
            $fromform->answer[$cidx] = ['text' => $choice['text'], 'format' => FORMAT_HTML];
            $fromform->fraction[$cidx] = $choice['correct'] ? 1.0 : 0.0;
            $fromform->feedback[$cidx] = ['text' => $choice['feedback'] ?? '', 'format' => FORMAT_HTML];
        }

        $savedquestion = \question_bank::get_qtype('multichoice')->save_question($question, $fromform);
        quiz_add_quiz_question($savedquestion->id, $quiz, 0, $qdef['mark'] ?? 20);
    }

    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    return $createdmod;
}

/**
 * Helper to enable self-enrolment and enrol users into a course
 */
function rm_configure_enrolment(stdClass $course, array $teachers, array $students): void {
    global $DB;

    $enrolinstances = enrol_get_instances($course->id, false);
    $manualinstance = null;
    $selfinstance = null;

    foreach ($enrolinstances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
        } else if ($instance->enrol === 'self') {
            $selfinstance = $instance;
        }
    }

    // Enable Self Enrolment so students can freely join or take the level test
    $selfplugin = enrol_get_plugin('self');
    if ($selfplugin) {
        if ($selfinstance) {
            if ($selfinstance->status != ENROL_INSTANCE_ENABLED) {
                $selfplugin->update_status($selfinstance, ENROL_INSTANCE_ENABLED);
            }
        } else {
            $selfplugin->add_default_instance($course);
            $enrolinstances = enrol_get_instances($course->id, false);
            foreach ($enrolinstances as $instance) {
                if ($instance->enrol === 'self') {
                    $selfplugin->update_status($instance, ENROL_INSTANCE_ENABLED);
                }
            }
        }
    }

    // Enrol teachers and students via Manual Enrolment
    $manualplugin = enrol_get_plugin('manual');
    if ($manualplugin && $manualinstance) {
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

        foreach ($teachers as $teacher) {
            $manualplugin->enrol_user($manualinstance, $teacher->id, $teacherrole->id);
        }
        foreach ($students as $student) {
            $manualplugin->enrol_user($manualinstance, $student->id, $studentrole->id);
        }
    }
}

/**
 * Helper to update section titles and summaries
 */
function rm_setup_sections(stdClass $course, array $sectionsinfo): void {
    global $DB;
    $sectionnums = array_keys($sectionsinfo);
    course_create_sections_if_missing($course->id, $sectionnums);
    foreach ($sectionsinfo as $secnum => $info) {
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $secnum]);
        if ($section) {
            $DB->update_record('course_sections', (object)[
                'id' => $section->id,
                'name' => $info['name'],
                'summary' => $info['summary'] ?? '',
                'summaryformat' => FORMAT_HTML,
                'visible' => 1,
            ]);
        }
    }
}

cli_heading("4. 리드마스터 학원 대표 강좌 및 학습 콘텐츠 생성");

// ---------------------------------------------------------------------------
// Course 1: [진단] 리드마스터 신입생 영어 독해 레벨 테스트 (RM-LEVEL-TEST)
// ---------------------------------------------------------------------------
$c1short = 'RM-LEVEL-TEST';
$course1 = $DB->get_record('course', ['shortname' => $c1short]);
if ($course1 && $DB->count_records('course_modules', ['course' => $course1->id]) < 4) {
    delete_course($course1, false);
    $course1 = false;
}
if (!$course1) {
    $course1 = create_course((object)[
        'category' => $categories['RM-CAT-LEVEL']->id,
        'fullname' => '[진단] 리드마스터 신입생 영어 독해 레벨 테스트 (ReadMaster Placement Test)',
        'shortname' => $c1short,
        'idnumber' => 'RM-COURSE-001',
        'summary' => '<p><strong>리드마스터 학원 입학 및 반 배정을 위한 온라인 영어 독해 진단 평가</strong>입니다.<br>어휘력(Vocabulary), 구문 분석력(Syntax), 중심내용 및 추론 독해(Reading Comprehension) 5개 핵심 문항을 통해 학생에게 가장 적합한 리딩 레벨(AR / Lexile)과 수강 반을 추천합니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 3,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);

    rm_setup_sections($course1, [
        1 => [
            'name' => 'Step 1. 레벨 테스트 응시 안내',
            'summary' => '<p>테스트 시작 전 응시 방법과 리드마스터 레벨 체계를 확인하세요.</p>',
        ],
        2 => [
            'name' => 'Step 2. 온라인 영어 독해 진단 평가 (응시하기)',
            'summary' => '<p>제한시간 15분 동안 사전 도움 없이 스스로 문제를 풀어주세요.</p>',
        ],
        3 => [
            'name' => 'Step 3. 점수대별 추천 레벨 & 반 배정 안내',
            'summary' => '<p>진단 평가 점수에 따른 리드마스터 정규 커리큘럼 안내입니다.</p>',
        ],
    ]);

    rm_add_page(
        $course1,
        1,
        '리드마스터 레벨 테스트 안내 및 주의사항',
        '응시 전 반드시 읽어주세요.',
        '<h4>리드마스터 영어 독해 진단 평가 (Placement Test) 안내</h4>
        <ul>
            <li><strong>평가 구성:</strong> 문맥 어휘 추론(1문항) + 핵심 구문 파악(1문항) + 스토리/비문학 독해 및 추론(3문항), 총 5문항 (100점 만점)</li>
            <li><strong>제한 시간:</strong> 15분</li>
            <li><strong>응시 원칙:</strong> 정확한 반 배정을 위해 사전이나 번역기를 사용하지 않고 학생 본인의 실력으로 응시해 주세요.</li>
        </ul>
        <p>응시 완료 즉시 자동 채점되며, 점수에 맞는 추천 반을 Step 3에서 확인하실 수 있습니다.</p>'
    );

    $placementquestions = [
        [
            'name' => 'Q1. 문맥 속 어휘 추론 (Vocabulary in Context)',
            'mark' => 20,
            'questiontext' => '<p>다음 문장을 읽고 밑줄 친 <strong><u>reluctant</u></strong>의 의미와 가장 가까운 것을 고르세요.</p>
            <blockquote style="background:#f8fafc; padding:1rem; border-left:4px solid #2563eb;">
            "Even though the water in the lake looked crystal clear, Mina was <strong><u>reluctant</u></strong> to jump in because the morning air was still freezing cold."
            </blockquote>',
            'generalfeedback' => '<p>호수 물이 맑아 보였음에도 아침 공기가 매우 차가웠기 때문에 뛰어들기를 <strong>꺼려했다(unwilling, hesitant)</strong>는 문맥입니다.</p>',
            'choices' => [
                ['text' => 'unwilling and hesitant (마음이 내키지 않고 망설이는)', 'correct' => true, 'feedback' => '정답! 앞뒤 역접(Even though)과 이유(because)를 통해 망설이는 상태임을 알 수 있습니다.'],
                ['text' => 'eager and excited (간절히 바라고 신이 난)', 'correct' => false, 'feedback' => '뒤에 아침 공기가 매우 차가웠다는 이유가 나오므로 반대 의미입니다.'],
                ['text' => 'proud and confident (자랑스럽고 자신감 넘치는)', 'correct' => false, 'feedback' => '문맥상 자신감과는 관련이 없습니다.'],
                ['text' => 'careless and hasty (부주의하고 성급한)', 'correct' => false, 'feedback' => '오히려 추위 때문에 주저하는 상황입니다.'],
            ],
        ],
        [
            'name' => 'Q2. 원서 스토리 독해 (Story Comprehension - AR 2.5~3.0)',
            'mark' => 20,
            'questiontext' => '<p>다음 이야기를 읽고, Leo가 도서관 다락방에서 발견한 지도의 특징으로 <strong>올바른 것</strong>을 고르세요.</p>
            <blockquote style="background:#f8fafc; padding:1rem; border-left:4px solid #2563eb;">
            Leo dusted off the leather-bound notebook he found in the library attic. Tucked between the last two pages was a folded parchment map. At first, the map appeared completely blank. However, when Leo held it near the warm desk lamp, faint golden lines began to glow, revealing a hidden path through the Whispering Woods.
            </blockquote>',
            'generalfeedback' => '<p>처음에는 백지처럼 보였지만 따뜻한 스탠드 조명 가까이에 가져가자 금빛 선이 나타나며 숨겨진 길을 보여주었습니다.</p>',
            'choices' => [
                ['text' => '따뜻한 불빛 가까이에 대었을 때 보이지 않던 금빛 길이 나타났다.', 'correct' => true, 'feedback' => '정답! "when Leo held it near the warm desk lamp, faint golden lines began to glow"를 정확히 파악했습니다.'],
                ['text' => '물에 적셨을 때만 글씨가 나타나는 마법 종이였다.', 'correct' => false, 'feedback' => '물이 아니라 따뜻한 램프(warm desk lamp)에 가까이 가져갔습니다.'],
                ['text' => '표지에 금빛으로 도서관의 역사가 적혀 있었다.', 'correct' => false, 'feedback' => '노트북의 마지막 두 페이지 사이에 끼워진 양피지 지도였습니다.'],
                ['text' => '오래되어 절반 이상이 찢어져 길을 알아볼 수 없었다.', 'correct' => false, 'feedback' => '찢어졌다는 내용은 지문에 없습니다.'],
            ],
        ],
        [
            'name' => 'Q3. 핵심 구문 독해 (Syntax & Grammar Analysis)',
            'mark' => 20,
            'questiontext' => '<p>다음 문장의 의미를 가장 정확하게 해석한 것을 고르세요.</p>
            <blockquote style="background:#f8fafc; padding:1rem; border-left:4px solid #2563eb;">
            <strong>"Not until learners read stories in full context do they truly understand how words breathe and connect."</strong>
            </blockquote>',
            'generalfeedback' => '<p><strong>Not until A + 도치구문(do they understand B)</strong>은 "A하고 나서야 비로소 B하다"라는 의미의 핵심 구문입니다.</p>',
            'choices' => [
                ['text' => '학습자들은 전체 문맥 속에서 이야기를 읽고 나서야 비로소 단어가 어떻게 살아 숨 쉬고 연결되는지 진정으로 이해한다.', 'correct' => true, 'feedback' => '정답! Not until ~ 도치 구문을 완벽하게 이해했습니다.'],
                ['text' => '학습자들이 단어의 호흡과 연결을 먼저 이해하지 못하면 전체 문맥 속에서 이야기를 읽을 수 없다.', 'correct' => false, 'feedback' => '선후 관계가 반대로 해석되었습니다.'],
                ['text' => '전체 문맥 속에서 이야기를 읽는 동안에도 학습자들은 단어의 연결 방식을 거의 이해하지 못한다.', 'correct' => false, 'feedback' => '부정어(Not)와 until이 결합하여 "비로소 ~하다"라는 긍정의 시점을 나타냅니다.'],
                ['text' => '단어를 많이 암기한 학습자만이 긴 이야기를 끝까지 읽을 수 있다.', 'correct' => false, 'feedback' => '지문에 없는 내용입니다.'],
            ],
        ],
        [
            'name' => 'Q4. 비문학 중심내용 파악 (Main Idea - AR 4.5 / Academic)',
            'mark' => 20,
            'questiontext' => '<p>다음 글을 읽고 필자가 주장하는 <strong>핵심 요지(Main Idea)</strong>를 고르세요.</p>
            <blockquote style="background:#f8fafc; padding:1rem; border-left:4px solid #2563eb;">
            Many students believe that reading fast is the ultimate sign of proficiency. While skimming can be useful for locating specific facts, reading complex literature or academic texts requires deliberate slowing down. When readers pause to question the author\'s assumptions and trace the logical flow between paragraphs, their brain builds deeper neural pathways for critical thinking.
            </blockquote>',
            'generalfeedback' => '<p>복잡한 문학이나 학술 지문을 읽을 때는 단순히 빨리 읽는 것보다 의도적으로 속도를 늦추고 저자의 가정과 논리적 흐름을 짚어보는 정독이 비판적 사고력을 길러준다는 내용입니다.</p>',
            'choices' => [
                ['text' => '깊이 있는 독해와 비판적 사고력 향상을 위해서는 글의 논리적 흐름을 되짚으며 천천히 정독하는 과정이 필수적이다.', 'correct' => true, 'feedback' => '정답! 글의 대조 구조(While skimming... vs deliberate slowing down)와 결론을 정확히 포착했습니다.'],
                ['text' => '시험에서 높은 점수를 받으려면 가능한 한 빠르게 글을 훑어읽는(skimming) 훈련을 반복해야 한다.', 'correct' => false, 'feedback' => '필자가 반박하고 있는 통념에 해당합니다.'],
                ['text' => '어려운 학술 서적보다는 쉬운 소설책을 여러 권 빠르게 읽는 것이 두뇌 발달에 더 효과적이다.', 'correct' => false, 'feedback' => '지문에서 언급되지 않은 내용입니다.'],
                ['text' => '저자의 가정을 의심하기보다는 있는 그대로 정보를 받아들이는 태도가 중요하다.', 'correct' => false, 'feedback' => '본문에서는 "pause to question the author\'s assumptions"라고 설명하고 있습니다.'],
            ],
        ],
        [
            'name' => 'Q5. 논리적 빈칸 추론 (Logical Inference - CSAT Level)',
            'mark' => 20,
            'questiontext' => '<p>다음 빈칸에 들어갈 말로 가장 적절한 것을 고르세요.</p>
            <blockquote style="background:#f8fafc; padding:1rem; border-left:4px solid #2563eb;">
            A great novel does not hand every answer to the reader on a silver platter. Instead, it leaves intentional gaps—unspoken motives, subtle symbols, and open-ended moments—inviting the reader to become __________________________. In this sense, reading is never a passive reception of text, but a creative partnership between writer and reader.
            </blockquote>',
            'generalfeedback' => '<p>소설은 의도적인 여백을 남겨 독자가 수동적 수용자가 아닌 작가와의 창의적 동반자(능동적 공동 창작자)가 되도록 초대한다는 논리입니다.</p>',
            'choices' => [
                ['text' => 'an active co-creator of meaning (의미를 함께 만들어가는 능동적 공동 창작자)', 'correct' => true, 'feedback' => '정답! 뒤 문장의 "never a passive reception, but a creative partnership between writer and reader"와 완벽하게 호응합니다.'],
                ['text' => 'a strict critic of grammatical errors (문법적 오류를 찾아내는 엄격한 비평가)', 'correct' => false, 'feedback' => '문법 오류에 대한 언급은 없습니다.'],
                ['text' => 'a silent observer who avoids interpretation (해석을 피하는 침묵의 관찰자)', 'correct' => false, 'feedback' => '글의 요지(능동적 참여)와 정반대입니다.'],
                ['text' => 'a historian recording factual events (사실적 사건만을 기록하는 역사가)', 'correct' => false, 'feedback' => '소설의 여백과 독자의 상상/해석 참여를 다루는 글입니다.'],
            ],
        ],
    ];

    rm_add_quiz_with_questions(
        $course1,
        2,
        '[실전 진단] 리드마스터 영어 독해 레벨 테스트 (5문항 / 100점)',
        '<p>어휘·스토리 독해·구문·비문학·빈칸추론 총 5문항입니다. 응시 후 즉시 점수와 해설이 제공됩니다.</p>',
        900,
        $placementquestions
    );

    rm_add_page(
        $course1,
        3,
        '점수대별 리드마스터 추천 레벨 및 수강 안내',
        '테스트 점수에 따른 맞춤형 반 배정 기준표입니다.',
        '<h4>리드마스터 진단 점수별 추천 커리큘럼</h4>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>진단 점수</th>
                    <th>리드마스터 레벨</th>
                    <th>권장 AR / Lexile 지수</th>
                    <th>추천 수강 강좌</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>0 ~ 40점</strong></td>
                    <td><strong>Starter / Phonics</strong></td>
                    <td>AR 1.5 ~ 2.8 (300L~500L)</td>
                    <td><code>[초등 파닉스/기초] Magic Tree House 입문 리딩 클럽</code></td>
                </tr>
                <tr>
                    <td><strong>60점</strong></td>
                    <td><strong>Intermediate Book Club</strong></td>
                    <td>AR 3.5 ~ 5.0 (600L~850L)</td>
                    <td><code>[초중등 원서] Newbery Medal 원서 정독반 (Charlotte\'s Web &amp; The Giver)</code></td>
                </tr>
                <tr>
                    <td><strong>80 ~ 100점</strong></td>
                    <td><strong>Advanced Syntax &amp; Academic</strong></td>
                    <td>AR 5.5+ (900L~1200L+)</td>
                    <td><code>[중고등 독해] 구문 독해 마스터 &amp; 수능 비문학 추론 완결</code> 및 <code>시사 영자신문(NIE) &amp; 에세이</code></td>
                </tr>
            </tbody>
        </table>'
    );

    rm_configure_enrolment($course1, [$createdusers['teacher1'], $createdusers['teacher2']], [$createdusers['student1'], $createdusers['student2']]);
    rebuild_course_cache($course1->id, true);
    cli_writeln("강좌 생성 완료: {$course1->fullname}");
}

// ---------------------------------------------------------------------------
// Course 2: [초등 파닉스/기초] Magic Tree House 입문 리딩 클럽 (RM-MTH-101)
// ---------------------------------------------------------------------------
$c2short = 'RM-MTH-101';
$course2 = $DB->get_record('course', ['shortname' => $c2short]);
if (!$course2) {
    $course2 = create_course((object)[
        'category' => $categories['RM-CAT-STARTER']->id,
        'fullname' => '[초등 파닉스/기초] Magic Tree House 입문 리딩 클럽 (AR 2.0~2.8)',
        'shortname' => $c2short,
        'idnumber' => 'RM-COURSE-101',
        'summary' => '<p>리더스북에서 첫 챕터북(Chapter Book)으로 넘어가는 학생들을 위한 <strong>Magic Tree House #1: Dinosaurs Before Dark</strong> 원서 정독 과정입니다. 파닉스 음가 복습, 오디오북 섀도잉, 챕터별 어휘·내용 이해 퀴즈와 한 줄 북리포트 쓰기를 진행합니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 3,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);

    rm_setup_sections($course2, [
        1 => ['name' => 'Week 1. Chapters 1~3: Into the Woods & The Monster', 'summary' => '잭과 애니가 숲속 마법의 나무집을 발견하고 공룡 시대로 시간 여행을 떠나는 장면입니다.'],
        2 => ['name' => 'Week 2. Chapters 4~7: Henry & The Dinosaur Valley', 'summary' => '프테라노돈 헨리와 트리케라톱스를 만나며 펼쳐지는 모험을 읽고 퀴즈를 풀어봅니다.'],
        3 => ['name' => 'Week 3. Chapters 8~10 & My First Book Report', 'summary' => '티라노사우루스를 피해 집으로 돌아오는 결말을 읽고 나만의 북리포트를 완성합니다.'],
    ]);

    rm_add_page(
        $course2,
        1,
        'Chapters 1~3 핵심 어휘 & 소리내어 읽기(Shadowing) 가이드',
        '본문 읽기 전 필수 표현 5가지를 익혀봅시다.',
        '<h4>Chapters 1~3 Key Vocabulary</h4>
        <ul>
            <li><strong>pretend</strong>: ~인 척하다 ("Stop pretending, Annie!")</li>
            <li><strong>rope ladder</strong>: 밧줄 사다리 (The longest rope ladder Jack had ever seen)</li>
            <li><strong>ancient</strong>: 고대의, 아주 오래된</li>
            <li><strong>glider</strong>: 활공기, 글라이더 (프테라노돈의 날개 모양 비유)</li>
            <li><strong>tremble</strong>: (무서움이나 추위로) 떨다</li>
        </ul>
        <h4>오디오 섀도잉(Shadowing) 3단계 미션</h4>
        <ol>
            <li>눈으로 지문을 따라가며 원어민 오디오 듣기 (1회)</li>
            <li>문장 단위로 멈추며 큰 소리로 따라 읽기 (2회)</li>
            <li>가장 인상 깊은 대화 한 문장을 녹음하여 수업 시간에 발표하기</li>
        </ol>'
    );

    rm_add_quiz_with_questions(
        $course2,
        2,
        'Magic Tree House #1 Reading Check Quiz',
        '<p>Chapters 1~7의 줄거리와 어휘를 얼마나 잘 이해했는지 확인해 보세요!</p>',
        600,
        [
            [
                'name' => 'MTH Q1. 나무집 안의 특징',
                'mark' => 50,
                'questiontext' => '<p>Jack과 Annie가 나무집(Tree House) 안에 올라갔을 때 그곳은 무엇으로 가득 차 있었나요?</p>',
                'choices' => [
                    ['text' => 'Books (온갖 종류의 책들)', 'correct' => true, 'feedback' => '정답! 나무집 안은 오래된 책과 새 책들로 가득 차 있었습니다.'],
                    ['text' => 'Gold coins and jewels (금화와 보석들)', 'correct' => false],
                    ['text' => 'Dinosaur fossils (공룡 화석들)', 'correct' => false],
                    ['text' => 'Musical instruments (악기들)', 'correct' => false],
                ],
            ],
            [
                'name' => 'MTH Q2. 시간 여행의 방법',
                'mark' => 50,
                'questiontext' => '<p>나무집이 공룡 시대로 이동하게 된 직접적인 계기는 무엇이었나요?</p>',
                'choices' => [
                    ['text' => 'Jack이 공룡 책의 그림을 가리키며 "저기에 가보고 싶다(I wish we could go there)"고 말했기 때문', 'correct' => true, 'feedback' => '정답! 책의 그림을 가리키며 소원을 말하자 바람이 불며 나무집이 회전하기 시작했습니다.'],
                    ['text' => 'Annie가 나무집 바닥에 있는 비밀 버튼을 눌렀기 때문', 'correct' => false],
                    ['text' => '창밖에서 날아온 프테라노돈이 나무집을 끌고 갔기 때문', 'correct' => false],
                    ['text' => '도서관 사서 선생님이 마법 주문을 외웠기 때문', 'correct' => false],
                ],
            ],
        ]
    );

    rm_add_assign(
        $course2,
        3,
        '[과제] Magic Tree House #1 나만의 북리포트(Book Report) 제출',
        '<p>책을 끝까지 읽고 아래 3가지 질문에 영어로(각 2~3문장) 답하여 제출하세요.</p>
        <ol>
            <li><strong>Who is your favorite character (Jack or Annie), and why?</strong></li>
            <li><strong>Which dinosaur would you want to meet in real life?</strong></li>
            <li><strong>Write 3 new English words you learned from this book.</strong></li>
        </ol>'
    );

    rm_configure_enrolment($course2, [$createdusers['teacher2'], $createdusers['teacher1']], [$createdusers['student2']]);
    rebuild_course_cache($course2->id, true);
    cli_writeln("강좌 생성 완료: {$course2->fullname}");
}

// ---------------------------------------------------------------------------
// Course 3: [초중등 원서] Newbery Medal 원서 정독반 (RM-NEWBERY-201)
// ---------------------------------------------------------------------------
$c3short = 'RM-NEWBERY-201';
$course3 = $DB->get_record('course', ['shortname' => $c3short]);
if (!$course3) {
    $course3 = create_course((object)[
        'category' => $categories['RM-CAT-BOOKCLUB']->id,
        'fullname' => '[초중등 원서] Newbery Medal 원서 정독반 - Charlotte\'s Web & The Giver (AR 4.0~5.5)',
        'shortname' => $c3short,
        'idnumber' => 'RM-COURSE-201',
        'summary' => '<p>미국 아동·청소년 문학 최고 권위의 <strong>뉴베리상(Newbery Medal)</strong> 수상작을 원서 그대로 완독하는 리드마스터 핵심 북클럽 과정입니다.<br>단순 줄거리 파악을 넘어 인물의 심리 변화, 상징(Symbolism), 작가의 주제의식을 분석하고 매주 북클럽 에세이를 작성합니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 3,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);

    rm_setup_sections($course3, [
        1 => ['name' => 'Part 1. E.B. White - Charlotte\'s Web (우정과 생명의 가치)', 'summary' => 'Wilbur와 거미 Charlotte의 우정을 그린 고전 명작 원서 정독 및 어휘·표현 심화 학습'],
        2 => ['name' => 'Part 2. Lois Lowry - The Giver (기억전달자: 유토피아와 자유의지)', 'summary' => '통제된 사회(Sameness) 속에서 기억을 전수받는 소년 Jonas의 이야기를 통한 비판적 독해'],
        3 => ['name' => 'Part 3. Newbery Book Club Essay & Seminar', 'summary' => '작품 속 핵심 주제를 바탕으로 한 비교 에세이 작성 및 원어민 강사 첨삭'],
    ]);

    rm_add_page(
        $course3,
        1,
        'Charlotte\'s Web 핵심 문학 표현 & 인물 분석 노트',
        '원서 속 아름다운 묘사와 거미줄 단어들의 상징적 의미를 정리합니다.',
        '<h4>Words in Charlotte\'s Web</h4>
        <ul>
            <li><strong>"SOME PIG"</strong>: 평범한 아기 돼지 윌버를 특별하게 바라보게 만든 첫 번째 메시지</li>
            <li><strong>"TERRIFIC"</strong>: 훌륭한, 대단한</li>
            <li><strong>"RADIANT"</strong>: 빛나는, 생기가 넘치는</li>
            <li><strong>"HUMBLE"</strong>: 겸손한, 소박한</li>
        </ul>
        <p><strong>Discussion Question:</strong> Why did Charlotte help Wilbur without asking for anything in return? Find two quotes from Chapter 21 ("Last Day") to support your answer.</p>'
    );

    rm_add_assign(
        $course3,
        2,
        '[북클럽 에세이] The Giver - "Sameness(획일성)" 사회에 대한 비판적 에세이',
        '<p><em>The Giver</em>에서 커뮤니티 사람들은 고통과 갈등을 없애기 위해 색깔, 날씨, 음악, 그리고 깊은 감정을 모두 포기한 <strong>Sameness</strong>를 선택했습니다.<br>
        과연 고통 없는 안전을 위해 선택의 자유를 포기하는 것이 바람직한지 본인의 생각을 <strong>서론-본론-결론(최소 150단어 이상)</strong> 영문 에세이로 작성하여 제출하세요.</p>'
    );

    rm_configure_enrolment($course3, [$createdusers['teacher2'], $createdusers['teacher1']], [$createdusers['student1'], $createdusers['student2']]);
    rebuild_course_cache($course3->id, true);
    cli_writeln("강좌 생성 완료: {$course3->fullname}");
}

// ---------------------------------------------------------------------------
// Course 4: [중고등 독해] 구문 독해 마스터 & 수능 비문학 추론 완결 (RM-SYNTAX-301)
// ---------------------------------------------------------------------------
$c4short = 'RM-SYNTAX-301';
$course4 = $DB->get_record('course', ['shortname' => $c4short]);
if (!$course4) {
    $course4 = create_course((object)[
        'category' => $categories['RM-CAT-ACADEMIC']->id,
        'fullname' => '[중고등 독해] 구문 독해 마스터 & 수능 비문학 추론 완결 (Syntax & Logic)',
        'shortname' => $c4short,
        'idnumber' => 'RM-COURSE-301',
        'summary' => '<p>길고 복잡한 한 문장도 끊김 없이 읽어내는 <strong>직독직해 구문 분석(Syntax)</strong>과 철학·과학·경제 비문학 지문의 <strong>논리 구조(대조·인과·통념과 반박)</strong>를 완벽히 체화하는 중고등 최상위 독해 과정입니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 3,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);

    rm_setup_sections($course4, [
        1 => ['name' => 'Module 1. 고난도 구문 분석 (도치·분사구문·관계사절·강조구문)', 'summary' => '수능과 내신 서술형에 반복 출제되는 12대 핵심 구문을 완벽 해부합니다.'],
        2 => ['name' => 'Module 2. 비문학 단락 논리 (통념-반박, 실험-결론, 비유-주제)', 'summary' => '지문 전체를 다 읽고도 주제를 놓치지 않는 파라그래프 리딩 전략을 학습합니다.'],
        3 => ['name' => 'Module 3. 고난도 빈칸추론·순서·문장삽입 실전 훈련', 'summary' => '매주 실전 미니 모의고사 및 1:1 구문 오답노트 첨삭을 진행합니다.'],
    ]);

    rm_add_page(
        $course4,
        1,
        '리드마스터 3대 핵심 구문 공식 (Syntax Master Note)',
        '긴 문장을 만났을 때 주어와 동사를 즉시 찾는 핵심 원리입니다.',
        '<h4>1. 주어와 동사 사이에 삽입된 수식어구 묶어내기</h4>
        <p>주어 뒤에 <code>관계대명사절(who/which/that)</code>, <code>현재/과거분사구(-ing / p.p.)</code>, <code>전치사구</code>가 길게 이어질 때 두 번째 동사 앞까지 괄호로 묶어 진짜 본동사를 찾는 연습이 구문 독해의 출발점입니다.</p>
        <h4>2. 부정어/제한어 도치 구문</h4>
        <p><code>Never / Little / Seldom / Not only / Only after ~</code>가 문두에 오면 <strong>의문문 어순(조동사 + 주어 + 본동사)</strong>으로 도치되며, 필자가 가장 강조하는 핵심 문장일 확률이 높습니다.</p>
        <h4>3. It ~ that 강조구문 vs 가주어-진주어 구별법</h4>
        <p><code>It is [강조 대상] that ~</code>에서 <code>It is</code>와 <code>that</code>을 지웠을 때 나머지 문장이 완전하게 성립하면 강조구문입니다.</p>'
    );

    rm_add_assign(
        $course4,
        3,
        '[과제] 주간 고난도 10문장 구문 분석(S/V/O/C 표시) 및 요약 제출',
        '<p>이번 주 배부된 학술 비문학 지문 3개에서 선정된 <strong>고난도 10문장</strong>의 주어(S), 본동사(V), 수식절 괄호 묶기 표시와 우리말 직독직해 및 1문장 영어 요약(Paraphrasing)을 작성하여 제출하세요.</p>'
    );

    rm_configure_enrolment($course4, [$createdusers['teacher1']], [$createdusers['student1']]);
    rebuild_course_cache($course4->id, true);
    cli_writeln("강좌 생성 완료: {$course4->fullname}");
}

// ---------------------------------------------------------------------------
// Course 5: [특강] 시사 영자신문(NIE) & 크리티컬 에세이 라이팅 (RM-NIE-401)
// ---------------------------------------------------------------------------
$c5short = 'RM-NIE-401';
$course5 = $DB->get_record('course', ['shortname' => $c5short]);
if (!$course5) {
    $course5 = create_course((object)[
        'category' => $categories['RM-CAT-WRITING']->id,
        'fullname' => '[특강] 시사 영자신문(NIE) & 크리티컬 에세이 라이팅',
        'shortname' => $c5short,
        'idnumber' => 'RM-COURSE-401',
        'summary' => '<p>환경, AI 기술, 국제 사회, 문화 등 최신 영자신문 아티클을 읽고 핵심 논점을 요약(Summary)한 뒤, 자신의 주장을 논리적인 근거와 함께 영문 에세이로 작성하는 특강입니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 2,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);

    rm_setup_sections($course5, [
        1 => ['name' => 'Issue 01. AI in Education: Tool for Thinking or Shortcut?', 'summary' => '교육 현장에서의 인공지능 활용에 관한 영자신문 사설을 읽고 찬반 논거를 분석합니다.'],
        2 => ['name' => 'Issue 02. Persuasive Essay Writing & 1:1 Feedback', 'summary' => '5-Paragraph Essay 구조(Hook - Thesis Statement - Body - Counterargument - Conclusion) 실습'],
    ]);

    rm_add_page(
        $course5,
        1,
        'NIE 아티클 리딩 & 5-Paragraph 에세이 템플릿',
        '설득력 있는 영문 에세이를 쓰기 위한 구조 가이드입니다.',
        '<h4>리드마스터 5단락 설득 에세이 구조</h4>
        <ol>
            <li><strong>Introduction:</strong> 독자의 관심을 끄는 Hook + 배경 설명 + 명확한 <strong>Thesis Statement(주제문)</strong></li>
            <li><strong>Body 1 (First Argument):</strong> Topic Sentence + 구체적 근거/사례(Evidence) + 설명</li>
            <li><strong>Body 2 (Second Argument):</strong> Furthermore / In addition + 두 번째 핵심 논거</li>
            <li><strong>Body 3 (Counterargument &amp; Refutation):</strong> 반대 입장 언급(Some critics argue that...) + 재반박(However, this view overlooks...)</li>
            <li><strong>Conclusion:</strong> Thesis Statement 재진술(Paraphrase) + 최종 제언</li>
        </ol>'
    );

    rm_add_assign(
        $course5,
        2,
        '[라이팅 첨삭] Issue 01 찬반 에세이 초안(First Draft) 제출',
        '<p>주제: <strong>"Should students use AI tools when writing book reports?"</strong><br>
        5-Paragraph 구조에 맞추어 200~300단어 분량의 에세이를 작성해 제출해 주세요. 담당 강사가 문법·어휘·논리 흐름에 대한 1:1 첨삭 피드백을 제공합니다.</p>'
    );

    rm_configure_enrolment($course5, [$createdusers['teacher1'], $createdusers['teacher2']], [$createdusers['student1']]);
    rebuild_course_cache($course5->id, true);
    cli_writeln("강좌 생성 완료: {$course5->fullname}");
}

// ---------------------------------------------------------------------------
// Course 6: [초등 기초] Oxford Reading Tree (ORT Stage 5~7) & 파닉스 완성 (RM-ORT-102)
// ---------------------------------------------------------------------------
$c6short = 'RM-ORT-102';
$course6 = $DB->get_record('course', ['shortname' => $c6short]);
if (!$course6) {
    $course6 = create_course((object)[
        'category' => $categories['RM-CAT-STARTER']->id,
        'fullname' => '[초등 기초] Oxford Reading Tree (ORT Stage 5~7) & 파닉스 완성',
        'shortname' => $c6short,
        'idnumber' => 'RM-COURSE-102',
        'summary' => '<p>영국 초등 80% 이상이 사용하는 <strong>Oxford Reading Tree (Biff, Chip and Kipper)</strong> 매직키 시리즈를 통해 필수 사이트워드와 파닉스 규칙을 체화하고, 정확한 발음으로 소리 내어 읽는 음독(Reading Aloud) 습관을 기르는 기초 리딩 과정입니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 2,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);
    rm_setup_sections($course6, [
        1 => ['name' => 'Stage 5~6. The Magic Key Begins', 'summary' => '마법 열쇠가 빛나며 시작되는 Biff와 Chip의 모험 스토리 정독 및 음독 훈련'],
        2 => ['name' => 'Stage 7. Longer Stories & Sentence Building', 'summary' => '문장 호흡이 길어지는 Stage 7 스토리 리딩 및 핵심 문장 쓰기 연습'],
    ]);
    rm_add_page($course6, 1, 'ORT Stage 5~7 필수 표현 & 음독 체크리스트', '매주 3권의 ORT 스토리북을 읽고 체크리스트를 달성하세요.', '<p>원어민 음원을 듣고 억양(Intonation)과 강세(Stress)를 그대로 모방하여 읽는 훈련을 진행합니다.</p>');
    rm_add_assign($course6, 2, '[과제] 이번 주 ORT 베스트 스토리 5문장 필사 &낭독 녹음 제출', '<p>가장 재미있었던 장면의 5문장을 노트에 따라 쓰고 낭독 음성 파일을 업로드하세요.</p>');
    rm_configure_enrolment($course6, [$createdusers['teacher2']], [$createdusers['student2']]);
    rebuild_course_cache($course6->id, true);
    cli_writeln("강좌 생성 완료: {$course6->fullname}");
}

// ---------------------------------------------------------------------------
// Course 7: [중등 원서] Roald Dahl & Wonder 북클럽 토론반 (RM-WONDER-202)
// ---------------------------------------------------------------------------
$c7short = 'RM-WONDER-202';
$course7 = $DB->get_record('course', ['shortname' => $c7short]);
if (!$course7) {
    $course7 = create_course((object)[
        'category' => $categories['RM-CAT-BOOKCLUB']->id,
        'fullname' => '[중등 원서] Roald Dahl & Wonder 현대 명작 북클럽 토론반 (AR 4.5~5.2)',
        'shortname' => $c7short,
        'idnumber' => 'RM-COURSE-202',
        'summary' => '<p>전 세계 베스트셀러 <strong>R.J. Palacio의 <em>Wonder</em></strong>와 <strong>Roald Dahl의 <em>Matilda</em></strong>를 원서로 완독하며, 다양한 화자의 시점(Point of View)을 비교하고 매주 영어 디베이트 및 에세이를 진행하는 중등 북클럽 과정입니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 2,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);
    rm_setup_sections($course7, [
        1 => ['name' => 'Book 1. R.J. Palacio - Wonder ("Choose Kind")', 'summary' => 'August, Via, Summer, Jack 등 인물별 다중 시점 서사를 분석하고 공감과 용기의 가치를 토론합니다.'],
        2 => ['name' => 'Book 2. Mr. Browne\'s Precepts & Personal Essay', 'summary' => '작품 속 격언(Precept)을 분석하고 나만의 Precept 에세이를 작성합니다.'],
    ]);
    rm_add_page($course7, 1, 'Wonder 핵심 Precepts(격언) & 어휘 분석 노트', 'Mr. Browne 선생님의 월별 격언과 핵심 어휘 정리입니다.', '<p>"When given the choice between being right or being kind, choose kind." 문장의 구조와 주제를 깊이 있게 탐구합니다.</p>');
    rm_add_assign($course7, 2, '[북클럽 과제] 나만의 Precept(인생 격언) 선정 및 영문 에세이 제출', '<p>자신만의 Precept를 한 문장으로 만들고 그 이유를 150단어 내외의 영문 에세이로 작성해 제출하세요.</p>');
    rm_configure_enrolment($course7, [$createdusers['teacher2'], $createdusers['teacher1']], [$createdusers['student1']]);
    rebuild_course_cache($course7->id, true);
    cli_writeln("강좌 생성 완료: {$course7->fullname}");
}

// ---------------------------------------------------------------------------
// Course 8: [고등 수능] 1등급 킬러문항 (빈칸·순서·문장삽입) 논리 독해 실전반 (RM-CSAT-302)
// ---------------------------------------------------------------------------
$c8short = 'RM-CSAT-302';
$course8 = $DB->get_record('course', ['shortname' => $c8short]);
if (!$course8) {
    $course8 = create_course((object)[
        'category' => $categories['RM-CAT-ACADEMIC']->id,
        'fullname' => '[고등 수능] 1등급 킬러문항 (빈칸·순서·문장삽입) 논리 독해 실전반',
        'shortname' => $c8short,
        'idnumber' => 'RM-COURSE-302',
        'summary' => '<p>평가원 모의고사 및 수능 영어 <strong>31~34번 빈칸추론, 36~39번 순서 배열·문장 삽입</strong> 고난도 문항을 감(感)이 아닌 철저한 <strong>단락 응집성(Coherence)과 지시어·연결어 논리</strong>로 풀어내는 고등 실전 과정입니다.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 2,
        'startdate' => time(),
        'enablecompletion' => 1,
        'showgrades' => 1,
        'visible' => 1,
    ]);
    rm_setup_sections($course8, [
        1 => ['name' => 'Part 1. 빈칸추론 Paraphrasing & 대조 논리 완결', 'summary' => '주제문 재진술(Paraphrasing) 원리와 이중 부정·비유적 빈칸 완벽 대비'],
        2 => ['name' => 'Part 2. 순서·문장삽입 시그널(지시사·관사·연결사) 정밀 타격', 'summary' => '단절(Gap)을 찾아내는 5대 논리 시그널 실전 적용 훈련'],
    ]);
    rm_add_page($course8, 1, '킬러문항 출제 원리: Paraphrasing & Logical Gap 노트', '평가원 기출 지문 구조 분석 핵심 요약본입니다.', '<p>모든 빈칸은 지문 내 핵심 명제의 재진술(Paraphrasing)이거나 반대 명제의 부정입니다.</p>');
    rm_add_assign($course8, 2, '[실전 과제] 주간 킬러문항 10제 정답 근거 문장(Clue) 분석표 제출', '<p>단순 정답 체크가 아닌, 각 문항의 결정적 단서 문장(Clue Sentence)과 매력적 오답 소거 근거를 작성하여 제출하세요.</p>');
    rm_configure_enrolment($course8, [$createdusers['teacher1']], [$createdusers['student1']]);
    rebuild_course_cache($course8->id, true);
    cli_writeln("강좌 생성 완료: {$course8->fullname}");
}

cli_heading("5. 불필요한 모듈·블록·복잡한 부가기능 비활성화 (경량화 세팅)");

// 학원 수업에 꼭 필요한 핵심 활동 모듈 7종만 활성화하고 나머지 16종은 숨김 처리
$essentialmodules = ['page', 'quiz', 'assign', 'resource', 'url', 'forum', 'qbank'];
foreach ($DB->get_records('modules') as $mod) {
    $shouldbevisible = in_array($mod->name, $essentialmodules, true) ? 1 : 0;
    if ((int)$mod->visible !== $shouldbevisible) {
        $DB->set_field('modules', 'visible', $shouldbevisible, ['id' => $mod->id]);
    }
}
cli_writeln("필수 학습 모듈(" . implode(', ', $essentialmodules) . ") 외 16개 복잡 모듈 숨김 완료");

// 학원 운영에 필요한 핵심 블록 5종만 남기고 나머지 30종 블록 비활성화
$essentialblocks = ['myoverview', 'course_list', 'calendar_upcoming', 'login', 'settings'];
foreach ($DB->get_records('block') as $blk) {
    $shouldbevisible = in_array($blk->name, $essentialblocks, true) ? 1 : 0;
    if ((int)$blk->visible !== $shouldbevisible) {
        $DB->set_field('block', 'visible', $shouldbevisible, ['id' => $blk->id]);
    }
}
cli_writeln("필수 블록(" . implode(', ', $essentialblocks) . ") 외 불필요한 30개 블록 비활성화 완료");

// 강좌 표시 순서(sortorder)를 학원 커리큘럼 단계순(레벨 테스트 -> 초등 -> 뉴베리 -> 구문/수능 -> 영자신문)으로 정렬
$courseorder = [
    'RM-LEVEL-TEST' => 10001,
    'RM-MTH-101'    => 20001,
    'RM-NEWBERY-201'=> 30001,
    'RM-SYNTAX-301' => 40001,
    'RM-NIE-401'    => 50001,
];
foreach ($courseorder as $shortname => $sortorder) {
    $DB->set_field('course', 'sortorder', $sortorder, ['shortname' => $shortname]);
}
cli_writeln("강좌 커리큘럼 정렬 순서 최적화 완료");

purge_all_caches();
cli_heading("리드마스터 학원(ReadMaster Academy) Moodle 경량화 및 초기 세팅이 모두 완료되었습니다!");
