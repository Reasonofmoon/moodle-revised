<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * B2B Academy LMS One-Click Provisioning Engine
 *
 * Reads academy_config.json and configures the entire Moodle LMS instance
 * (Branding, Noto Sans KR font, Admin/Teacher accounts, Login panel, Frontpage,
 * Booking CRM DB, and Lightweight UI Decluttering) for turnkey delivery.
 *
 * Usage:
 *   docker exec -u www-data readmaster-moodle-web php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json
 *
 * @package    core
 * @copyright  2026 ReadMaster Academy LMS Solution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'config' => __DIR__ . '/academy_config.json',
        'help' => false,
    ],
    ['c' => 'config', 'h' => 'help']
);

if ($options['help']) {
    cli_writeln("Usage: php provision_academy.php [--config=/path/to/academy_config.json]");
    exit(0);
}

$configfile = $options['config'];
if (!file_exists($configfile)) {
    cli_error("Config file not found: {$configfile}");
}

$cfg = json_decode(file_get_contents($configfile), true);
if (!is_array($cfg) || empty($cfg['academy'])) {
    cli_error("Invalid JSON in {$configfile}");
}

// Persist as active academy_config.json if loaded from another path
$defaultcfgpath = __DIR__ . '/academy_config.json';
if (realpath($configfile) !== realpath($defaultcfgpath)) {
    copy($configfile, $defaultcfgpath);
}

$ac = $cfg['academy'];
$fullname = $ac['fullname'] ?? '리드마스터 영어독해·원서리딩 학원';
$shortname = $ac['shortname'] ?? '리드마스터 학원';
$badge = $ac['badge'] ?? '리드마스터 영어독해 · 원서리딩 전문학원';
$loginbadge = $ac['login_badge'] ?? '리드마스터 원서 도서관 · 온라인 학습관';
$tagline = $ac['tagline'] ?? '';
$description = $ac['description'] ?? '';
$primarycolor = $ac['primary_color'] ?? '#1E3A8A';
$fontfamily = $ac['font_family'] ?? 'Noto Sans KR';

cli_heading("1. [{$fullname}] 기본 환경 · 폰트({$fontfamily}) · 경량화 설정");

set_config('noreplyaddress', $ac['noreply_email'] ?? 'noreply@academy.kr');
set_config('supportemail', $ac['support_email'] ?? 'support@academy.kr');
set_config('lang', 'ko');
set_config('forcelogin', 0);
set_config('timezone', 'Asia/Seoul');
set_config('forcetimezone', 'Asia/Seoul');
set_config('country', 'KR');
set_config('passwordpolicy', 0);
set_config('maxbytes', 104857600);
set_config('enablecompletion', 1);
set_config('enablemyhome', 1);
set_config('enabledashboard', 0);
set_config('showdataretentionsummary', 0, 'tool_dataprivacy');

// Disable bloated subsystems
foreach (['enablebadges', 'enableblogs', 'bloglevel', 'enableanalytics', 'messaging', 'usetags', 'enablenotes', 'enableoutcomes', 'enableportfolios', 'enablewebservices', 'enablemobilewebservice', 'courselistshortnames'] as $sub) {
    set_config($sub, 0);
}
set_config('enabled', 0, 'core_competency');

// Inject Google Fonts (Noto Sans KR) & Brand Color
$fonturl = str_replace(' ', '+', $fontfamily);
$fonthead = "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n"
          . "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n"
          . "<link href=\"https://fonts.googleapis.com/css2?family={$fonturl}:wght@300;400;500;600;700;800;900&display=swap\" rel=\"stylesheet\">\n"
          . "<style>\n"
          . "  body, button, input, select, textarea, .navbar, .dropdown-menu, .btn, .card, .coursebox, h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {\n"
          . "    font-family: \"{$fontfamily}\", -apple-system, BlinkMacSystemFont, \"Malgun Gothic\", sans-serif !important;\n"
          . "  }\n"
          . "</style>";
set_config('additionalhtmlhead', $fonthead);
set_config('brandcolor', $primarycolor, 'theme_boost');
set_config('scsspre', "\$font-family-sans-serif: \"{$fontfamily}\", -apple-system, BlinkMacSystemFont, sans-serif;\n\$headings-font-family: \"{$fontfamily}\", sans-serif;\n", 'theme_boost');

// Top navigation menu
$levelcourse = $DB->get_record('course', ['idnumber' => 'RM-LEVEL-TEST'], 'id');
$levelid = $levelcourse ? $levelcourse->id : 7;
$custommenu = "📚 전체 커리큘럼|/course/index.php\n🎯 레벨 테스트 바로가기|/course/view.php?id={$levelid}\n📅 학부모 상담·간담회 예약|/booking/index.php\n📋 상담 예약 관리|/booking/admin.php\n📖 LMS 솔루션 카탈로그|/catalog/index.php";
set_config('custommenuitems', $custommenu);

cli_heading("2. 관리자 및 강사/학생 계정 설정");

if (!empty($cfg['accounts']['admin_user'])) {
    $admin = $DB->get_record('user', ['id' => 2]);
    if ($admin) {
        $admin->username = $cfg['accounts']['admin_user'];
        $admin->firstname = $cfg['accounts']['admin_firstname'] ?? '총괄관리자';
        $admin->lastname = $cfg['accounts']['admin_lastname'] ?? $shortname;
        $DB->update_record('user', $admin);
        if (!empty($cfg['accounts']['admin_pass'])) {
            update_internal_user_password($admin, $cfg['accounts']['admin_pass']);
        }
        cli_writeln("관리자 계정 설정 완료: ID = {$admin->username}");
    }
}

foreach (['teachers' => 'editingteacher', 'students' => 'student'] as $group => $roleshortname) {
    foreach ($cfg['accounts'][$group] ?? [] as $u) {
        $existing = $DB->get_record('user', ['username' => $u['username'], 'mnethostid' => $CFG->mnet_localhost_id]);
        if (!$existing) {
            $newu = create_user_record($u['username'], $u['password'] ?? 'Academy1234!', 'manual');
            $newu->firstname = $u['firstname'];
            $newu->lastname = $u['lastname'];
            $newu->email = $u['email'] ?? ($u['username'] . '@academy.kr');
            $newu->city = 'Seoul';
            $newu->country = 'KR';
            $newu->lang = 'ko';
            $newu->confirmed = 1;
            $DB->update_record('user', $newu);
        } else {
            $existing->firstname = $u['firstname'];
            $existing->lastname = $u['lastname'];
            $DB->update_record('user', $existing);
            if (!empty($u['password'])) {
                update_internal_user_password($existing, $u['password']);
            }
        }
    }
}

cli_heading("3. 메인 홈 히어로 배너 · 푸터 · 상담 예약 DB 동기화");

$stepshtml = '';
foreach ($cfg['frontpage_steps'] ?? [] as $st) {
    $t = htmlspecialchars($st['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $d = htmlspecialchars($st['desc'] ?? '', ENT_QUOTES, 'UTF-8');
    $stepshtml .= "<div class=\"readmaster-card\"><h4>{$t}</h4><p>{$d}</p></div>\n";
}

$frontpagesummary = <<<HTML
<div class="readmaster-hero">
    <span class="readmaster-badge">{$badge}</span>
    <h2>{$fullname} 온라인 학습관</h2>
    <p style="font-size: 1rem; opacity: 0.95; margin-bottom: 0;">
        {$tagline}
    </p>
    <div class="readmaster-grid">
        {$stepshtml}
    </div>
</div>
HTML;

$site = get_site();
$DB->update_record('course', (object)[
    'id' => $site->id,
    'fullname' => $fullname,
    'shortname' => $shortname,
    'summary' => '',
    'summaryformat' => FORMAT_HTML,
]);
$fpsection = $DB->get_record('course_sections', ['course' => $site->id, 'section' => 1]);
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

$branchsummary = [];
$mainphone = '010-0000-0000';
foreach ($cfg['branches'] ?? [] as $b) {
    if (!empty($b['name'])) {
        $branchsummary[] = $b['name'];
    }
    if (!empty($b['phone'])) {
        $mainphone = $b['phone'];
    }
}
$branchstr = !empty($branchsummary) ? implode(' · ', $branchsummary) : $shortname;
set_config('footnote', "<div class=\"text-center py-2 text-muted small\"><strong>{$fullname}</strong> | {$branchstr} | 대표문의: {$mainphone}</div>", 'theme_boost');

// Ensure Booking CRM DB table exists
if (file_exists(__DIR__ . '/public/booking/lib.php')) {
    require_once(__DIR__ . '/public/booking/lib.php');
    readmaster_booking_ensure_schema();
}

// Hide unnecessary modules & blocks
$essentialmodules = ['page', 'quiz', 'assign', 'resource', 'url', 'forum', 'qbank'];
foreach ($DB->get_records('modules') as $mod) {
    $vis = in_array($mod->name, $essentialmodules, true) ? 1 : 0;
    if ((int)$mod->visible !== $vis) {
        $DB->set_field('modules', 'visible', $vis, ['id' => $mod->id]);
    }
}
$essentialblocks = ['myoverview', 'course_list', 'calendar_upcoming', 'login', 'settings'];
foreach ($DB->get_records('block') as $blk) {
    $vis = in_array($blk->name, $essentialblocks, true) ? 1 : 0;
    if ((int)$blk->visible !== $vis) {
        $DB->set_field('block', 'visible', $vis, ['id' => $blk->id]);
    }
}

theme_reset_all_caches();
purge_all_caches();
cli_heading("[{$fullname}] 학원 맞춤형 LMS 자동 프로비저닝이 완료되었습니다!");
