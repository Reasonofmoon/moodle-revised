<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * ReadMaster Academy Unified Consultation, Level Test & Briefing Booking Page
 *
 * Consolidates funnel.html, book.html, and briefing.html into Moodle with native DB storage.
 *
 * @package    core
 * @copyright  2026 ReadMaster Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once(__DIR__ . '/lib.php');

readmaster_booking_ensure_schema();

$type = optional_param('type', 'consultation', PARAM_ALPHA);
if (!in_array($type, ['consultation', 'leveltest', 'briefing'], true)) {
    $type = 'consultation';
}
$branchparam = optional_param('branch', '부천 옥길동 본원', PARAM_TEXT);

$site = get_site();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/booking/index.php', ['type' => $type]));
$PAGE->set_pagelayout('standard');
$PAGE->set_primary_active_tab('home');
$PAGE->add_body_class('limitedwidth rm-booking-page');
$PAGE->set_title('학부모 상담 · 무료 레벨테스트 · 간담회 통합 예약 | ' . $site->shortname);
$PAGE->set_heading($site->fullname);

$meta = readmaster_booking_metadata();
$stats = readmaster_booking_get_stats();
$canmanage = readmaster_booking_can_manage();

// Find the Level Test course ID in Moodle so we can link directly to it.
$levelcourse = $DB->get_record('course', ['idnumber' => 'RM-LEVEL-TEST'], 'id');
$leveltesturl = $levelcourse
    ? (new moodle_url('/course/view.php', ['id' => $levelcourse->id]))->out(false)
    : (new moodle_url('/course/index.php'))->out(false);
$curriculumurl = (new moodle_url('/course/index.php'))->out(false);
$adminurl = (new moodle_url('/booking/admin.php'))->out(false);
$apiurl = (new moodle_url('/booking/api.php'))->out(false);

// Handle non-JS POST fallback so form works 100% even without JS.
$submittedrecord = null;
$submiterror = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('form_submit', 0, PARAM_INT) === 1) {
    $parentname = optional_param('parent_name', '', PARAM_TEXT);
    $phone = optional_param('phone', '', PARAM_TEXT);
    $grade = optional_param('grade', '', PARAM_TEXT);
    if (trim($parentname) === '' || trim($phone) === '' || trim($grade) === '') {
        $submiterror = '학부모 성함, 연락처, 자녀 학년은 필수 입력 항목입니다.';
    } else {
        $submittedrecord = readmaster_booking_create_lead([
            'booking_type' => optional_param('booking_type', $type, PARAM_ALPHA),
            'parent_name' => $parentname,
            'student_name' => optional_param('student_name', '', PARAM_TEXT),
            'phone' => $phone,
            'grade' => $grade,
            'branch' => optional_param('branch', '부천 옥길동 본원', PARAM_TEXT),
            'session_topic' => optional_param('session_topic', '', PARAM_TEXT),
            'preferred_time' => optional_param('preferred_time', '수시 (담당자 확인 후 전화 조율)', PARAM_TEXT),
            'concern' => optional_param('concern', '', PARAM_TEXT),
            'source' => '무들 통합 예약 폼',
        ]);
        $stats = readmaster_booking_get_stats();
    }
}

$remainingseats = max(3, 20 - (int)$stats['confirmed'] - (int)$stats['new']);

echo $OUTPUT->header();
?>

<style>
/* Hide default page header for clean academy funnel experience */
.rm-booking-page #page-header {
    display: none !important;
}
.rm-booking-page #region-main {
    border: none !important;
    padding: 0 !important;
    background: transparent !important;
}
.rm-booking-wrap {
    font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0f172a;
    padding-bottom: 3.5rem;
}

/* Staff Quick Bar */
.rm-staff-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    background: #0f172a;
    border: 1px solid #334155;
    color: #f8fafc;
    padding: 0.85rem 1.35rem;
    border-radius: 14px;
    margin-bottom: 1.25rem;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
}
.rm-staff-bar-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
}
.rm-staff-badge {
    background: #f59e0b;
    color: #0f172a;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
}
.rm-staff-btn {
    background: #2563eb;
    color: #ffffff !important;
    text-decoration: none !important;
    font-weight: 700;
    font-size: 0.86rem;
    padding: 0.5rem 1rem;
    border-radius: 9px;
    transition: background 0.15s;
}
.rm-staff-btn:hover {
    background: #1d4ed8;
}

/* Hero Banner (from funnel.html & briefing.html) */
.rm-bk-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
    border-radius: 22px;
    padding: 2.75rem 2.5rem 2.25rem;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 42px rgba(15, 23, 42, 0.18);
    margin-bottom: 1.25rem;
}
.rm-bk-hero::before {
    content: "";
    position: absolute;
    right: -70px;
    top: -70px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(251, 191, 36, 0.22) 0%, rgba(251, 191, 36, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.rm-bk-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1.5rem;
    position: relative;
    z-index: 1;
}
.rm-bk-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: rgba(251, 191, 36, 0.16);
    border: 1px solid rgba(251, 191, 36, 0.45);
    color: #fde68a;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.35rem 0.9rem;
    border-radius: 999px;
    margin-bottom: 0.9rem;
}
.rm-bk-badge .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #fbbf24;
}
.rm-bk-hero h1 {
    font-size: 2.15rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.025em;
    line-height: 1.28;
    margin-bottom: 0.65rem;
}
.rm-bk-hero h1 em {
    font-style: normal;
    color: #fbbf24;
}
.rm-bk-hero p {
    color: #cbd5e1;
    font-size: 0.98rem;
    line-height: 1.65;
    max-width: 640px;
    margin-bottom: 1.25rem;
}
.rm-bk-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}
.rm-bk-chip {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #f8fafc;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.42rem 0.9rem;
    border-radius: 999px;
}

/* Urgency / Seat Counter Card */
.rm-urgency-box {
    background: rgba(15, 23, 42, 0.55);
    border: 1px solid rgba(251, 191, 36, 0.4);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    min-width: 250px;
    text-align: center;
    backdrop-filter: blur(8px);
}
.rm-urgency-tag {
    font-size: 0.75rem;
    font-weight: 800;
    color: #fbbf24;
    letter-spacing: 0.06em;
    margin-bottom: 0.35rem;
}
.rm-urgency-seats {
    font-size: 1.65rem;
    font-weight: 900;
    color: #ffffff;
    margin-bottom: 0.25rem;
}
.rm-urgency-seats strong {
    color: #fbbf24;
}
.rm-urgency-sub {
    font-size: 0.78rem;
    color: #cbd5e1;
    margin-bottom: 0.85rem;
}
.rm-urgency-link {
    display: inline-block;
    width: 100%;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #0f172a !important;
    font-weight: 800;
    font-size: 0.85rem;
    padding: 0.55rem 0.9rem;
    border-radius: 10px;
    text-decoration: none !important;
}

/* 4 Benefit Cards (from funnel.html) */
.rm-benefits-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.9rem;
    margin-bottom: 1.75rem;
}
@media (max-width: 900px) {
    .rm-benefits-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.rm-benefit-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.15rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}
.rm-benefit-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}
.rm-benefit-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.15rem;
}
.rm-benefit-desc {
    font-size: 0.78rem;
    color: #64748b;
}

/* Main 2-Column Booking Layout */
.rm-booking-grid {
    display: grid;
    grid-template-columns: 1fr 480px;
    gap: 1.75rem;
    align-items: start;
}
@media (max-width: 1040px) {
    .rm-booking-grid {
        grid-template-columns: 1fr;
    }
}

/* Left Column: Mode Tabs, Briefing Agenda, Testimonials, Director Contact */
.rm-info-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.75rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}
.rm-info-card h2 {
    font-size: 1.3rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.4rem;
}
.rm-info-card .rm-sub {
    font-size: 0.88rem;
    color: #64748b;
    margin-bottom: 1.25rem;
}

/* Mode Selector Cards */
.rm-mode-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.65rem;
    margin-bottom: 1.35rem;
}
@media (max-width: 640px) {
    .rm-mode-tabs {
        grid-template-columns: 1fr;
    }
}
.rm-mode-tab {
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 12px;
    padding: 0.9rem 0.85rem;
    cursor: pointer;
    text-align: left;
    transition: all 0.18s ease;
}
.rm-mode-tab:hover {
    border-color: #93c5fd;
    background: #ffffff;
}
.rm-mode-tab.active {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.12);
}
.rm-mode-tab-icon {
    font-size: 1.25rem;
    margin-bottom: 0.35rem;
}
.rm-mode-tab-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.2rem;
}
.rm-mode-tab.active .rm-mode-tab-title {
    color: #1d4ed8;
}
.rm-mode-tab-desc {
    font-size: 0.74rem;
    color: #64748b;
    line-height: 1.4;
}

/* Agenda Rows (from briefing.html) */
.rm-agenda-list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
.rm-agenda-row {
    display: grid;
    grid-template-columns: 82px 1fr;
    gap: 0.85rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.95rem 1.1rem;
}
.rm-agenda-time {
    font-weight: 800;
    font-size: 0.82rem;
    color: #2563eb;
    background: #dbeafe;
    border-radius: 8px;
    padding: 0.3rem 0.5rem;
    text-align: center;
    align-self: start;
}
.rm-agenda-body strong {
    display: block;
    font-size: 0.92rem;
    color: #0f172a;
    margin-bottom: 0.2rem;
}
.rm-agenda-body span {
    font-size: 0.82rem;
    color: #475569;
    line-height: 1.55;
}

/* Director Contact Cards (from funnel.html) */
.rm-directors-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.85rem;
    margin-bottom: 1rem;
}
@media (max-width: 640px) {
    .rm-directors-grid {
        grid-template-columns: 1fr;
    }
}
.rm-director-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: #f8fafc;
}
.rm-director-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.1rem;
    color: #ffffff;
    flex-shrink: 0;
}
.rm-director-avatar.kim {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
}
.rm-director-avatar.song {
    background: linear-gradient(135deg, #d97706, #f59e0b);
}
.rm-director-name {
    font-size: 0.94rem;
    font-weight: 800;
    color: #0f172a;
}
.rm-director-role {
    font-size: 0.76rem;
    color: #64748b;
    margin-bottom: 0.2rem;
}
.rm-director-phone {
    font-size: 0.84rem;
    font-weight: 700;
    color: #2563eb !important;
    text-decoration: none !important;
}
.rm-address-box {
    background: #f1f5f9;
    border-radius: 12px;
    padding: 0.85rem 1.1rem;
    font-size: 0.83rem;
    color: #334155;
    line-height: 1.6;
}

/* Parent Testimonials (from briefing.html) */
.rm-testi-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.85rem;
}
@media (max-width: 640px) {
    .rm-testi-grid {
        grid-template-columns: 1fr;
    }
}
.rm-testi-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-top: 3px solid #f59e0b;
    border-radius: 12px;
    padding: 1.1rem;
}
.rm-testi-stars {
    color: #f59e0b;
    font-size: 0.82rem;
    letter-spacing: 1px;
    margin-bottom: 0.45rem;
}
.rm-testi-card p {
    font-size: 0.84rem;
    color: #334155;
    line-height: 1.6;
    margin-bottom: 0.55rem;
}
.rm-testi-author {
    font-size: 0.76rem;
    font-weight: 700;
    color: #64748b;
}

/* Right Column: Sticky Unified Form Card (from book.html & funnel.html) */
.rm-form-card {
    background: #ffffff;
    border: 2px solid #dbeafe;
    border-radius: 20px;
    padding: 2rem 1.85rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.09);
    position: sticky;
    top: 84px;
}
.rm-form-head {
    margin-bottom: 1.35rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.rm-form-tag {
    display: inline-block;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    letter-spacing: 0.06em;
    margin-bottom: 0.45rem;
}
.rm-form-head h3 {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.rm-form-head p {
    font-size: 0.83rem;
    color: #64748b;
    margin-bottom: 0;
}

.rm-field {
    margin-bottom: 1.05rem;
}
.rm-field label.rm-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.4rem;
}
.rm-field label.rm-label .req {
    color: #dc2626;
    margin-left: 2px;
}
.rm-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}
.rm-input,
.rm-select,
.rm-textarea {
    width: 100%;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.72rem 0.9rem;
    font-size: 0.9rem;
    color: #0f172a;
    background: #f8fafc;
    transition: all 0.18s;
}
.rm-input:focus,
.rm-select:focus,
.rm-textarea:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.rm-textarea {
    min-height: 78px;
    resize: vertical;
}

/* Session Topic Radio Options (from book.html) */
.rm-topic-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.rm-topic-option {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.68rem 0.85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    background: #f8fafc;
    transition: all 0.16s ease;
    margin-bottom: 0;
}
.rm-topic-option:hover {
    border-color: #93c5fd;
    background: #ffffff;
}
.rm-topic-option input[type="radio"] {
    accent-color: #2563eb;
    width: 16px;
    height: 16px;
    margin: 0;
    flex-shrink: 0;
}
.rm-topic-option.selected {
    border-color: #2563eb;
    background: #eff6ff;
    border-left: 4px solid #2563eb;
}
.rm-topic-text {
    font-size: 0.84rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.35;
}
.rm-topic-option.selected .rm-topic-text {
    color: #1d4ed8;
    font-weight: 700;
}

.rm-privacy {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    margin: 1rem 0 1.15rem;
    font-size: 0.77rem;
    color: #64748b;
    line-height: 1.5;
}
.rm-privacy input {
    margin-top: 3px;
    accent-color: #2563eb;
}

.rm-submit-btn {
    width: 100%;
    border: none;
    border-radius: 12px;
    padding: 0.95rem 1.2rem;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    color: #ffffff;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.25);
    transition: transform 0.15s, box-shadow 0.15s;
}
.rm-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.32);
}
.rm-submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
}
.rm-form-trust {
    text-align: center;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.75rem;
}

/* Confirmation Success State */
.rm-success-box {
    display: none;
    text-align: center;
    padding: 1.5rem 0.5rem;
}
.rm-success-box.show {
    display: block;
}
.rm-success-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #10b981;
    color: #ffffff;
    font-size: 1.8rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
.rm-success-summary {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    text-align: left;
    font-size: 0.84rem;
    color: #334155;
    margin: 1rem 0 1.25rem;
    line-height: 1.7;
}
.rm-success-actions {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}
.rm-btn-leveltest {
    display: block;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #0f172a !important;
    text-decoration: none !important;
    font-weight: 800;
    font-size: 0.92rem;
    padding: 0.85rem 1rem;
    border-radius: 10px;
}
.rm-btn-reset {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.65rem 1rem;
    font-size: 0.84rem;
    font-weight: 700;
    cursor: pointer;
}

/* Lookup Box */
.rm-lookup-bar {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}
.rm-lookup-btn {
    background: #0f172a;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 0 1.15rem;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    white-space: nowrap;
}
.rm-lookup-result {
    margin-top: 0.85rem;
    font-size: 0.84rem;
}
</style>

<div class="rm-booking-wrap">

    <?php if ($canmanage): ?>
    <div class="rm-staff-bar">
        <div class="rm-staff-bar-left">
            <span class="rm-staff-badge">원장·교사 관리</span>
            <span>원장·교사진 전용 알림: 현재 학원 데이터베이스에 총 <strong><?php echo (int)$stats['total']; ?>건</strong>의 상담·간담회 예약이 저장되어 있습니다. (🆕 신규 대기 <strong><?php echo (int)$stats['new']; ?>건</strong>)</span>
        </div>
        <a href="<?php echo $adminurl; ?>" class="rm-staff-btn">📋 상담 예약 관리 대시보드 열기 →</a>
    </div>
    <?php endif; ?>

    <!-- HERO BANNER -->
    <div class="rm-bk-hero">
        <div class="rm-bk-hero-top">
            <div>
                <div class="rm-bk-badge">
                    <span class="dot"></span>
                    리드마스터 학원 · 학부모 상담 · 레벨테스트 · 간담회 통합 예약 센터
                </div>
                <h1>30분이면, 우리 아이의<br><em>영어 원서·문해력 로드맵</em>이 보입니다</h1>
                <p>
                    파닉스·원서 다독(매직트리하우스, 뉴베리 수상작)부터 중등 내신 서술형, 고등 수능 1등급 비문학 추론까지.<br>
                    무료 레벨테스트 진단 결과와 함께 1:1 맞춤 학습 플랜을 설계해 드립니다.
                </p>
                <div class="rm-bk-chips">
                    <span class="rm-bk-chip">🎯 무료 레벨테스트 3종 (어휘·구문·독해지수)</span>
                    <span class="rm-bk-chip">👩‍🏫 원장·부원장 1:1 심층 진단</span>
                    <span class="rm-bk-chip">📚 수준별 추천 원서 목록 증정</span>
                    <span class="rm-bk-chip">🏫 부천 옥길동 본원 · 춘천 우두동 본점</span>
                </div>
            </div>
            <div class="rm-urgency-box">
                <div class="rm-urgency-tag">⏰ 이번 주 상담·간담회 잔여석</div>
                <div class="rm-urgency-seats">잔여 <strong id="rmRemainingSeats"><?php echo $remainingseats; ?></strong>석</div>
                <div class="rm-urgency-sub">수시 신청 · 접수 순 개별 일정 안내</div>
                <a href="<?php echo $leveltesturl; ?>" class="rm-urgency-link">🎯 온라인 레벨테스트 바로 보기 →</a>
            </div>
        </div>
    </div>

    <!-- 4 BENEFIT CARDS -->
    <div class="rm-benefits-grid">
        <div class="rm-benefit-card">
            <div class="rm-benefit-icon">📊</div>
            <div>
                <div class="rm-benefit-title">무료 레벨 테스트</div>
                <div class="rm-benefit-desc">어휘·구문·독해지수 정밀 진단</div>
            </div>
        </div>
        <div class="rm-benefit-card">
            <div class="rm-benefit-icon">🗺️</div>
            <div>
                <div class="rm-benefit-title">맞춤 커리큘럼 설계</div>
                <div class="rm-benefit-desc">초·중·고 5단계 트랙 중 최적 반 배정</div>
            </div>
        </div>
        <div class="rm-benefit-card">
            <div class="rm-benefit-icon">👩‍🏫</div>
            <div>
                <div class="rm-benefit-title">소규모 1:1 밀착 코칭</div>
                <div class="rm-benefit-desc">원장·부원장 직접 상담 및 학습 관리</div>
            </div>
        </div>
        <div class="rm-benefit-card">
            <div class="rm-benefit-icon">🎁</div>
            <div>
                <div class="rm-benefit-title">참석자 특별 혜택</div>
                <div class="rm-benefit-desc">학년별 추천 원서 목록 & 첫 4주 목표표</div>
            </div>
        </div>
    </div>

    <!-- MAIN 2-COLUMN LAYOUT -->
    <div class="rm-booking-grid">

        <!-- LEFT COLUMN -->
        <div>
            <!-- 1. Booking Type Selector -->
            <div class="rm-info-card">
                <h2>원하시는 예약 유형을 선택해 주세요</h2>
                <p class="rm-sub">1:1 학부모 맞춤 상담, 무료 레벨테스트, 정기 학부모 간담회를 한곳에서 신청하실 수 있습니다.</p>

                <div class="rm-mode-tabs">
                    <?php foreach ($meta['types'] as $tkey => $tinfo): ?>
                    <div class="rm-mode-tab <?php echo ($type === $tkey) ? 'active' : ''; ?>"
                         data-type="<?php echo $tkey; ?>"
                         onclick="selectBookingMode('<?php echo $tkey; ?>')">
                        <div class="rm-mode-tab-icon"><?php echo $tinfo['icon']; ?></div>
                        <div class="rm-mode-tab-title"><?php echo s($tinfo['short']); ?></div>
                        <div class="rm-mode-tab-desc"><?php echo s($tinfo['desc']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 30-Minute Briefing & Consultation Roadmap (from briefing.html) -->
                <h2 style="font-size:1.12rem; margin-bottom:0.65rem;">📋 상담 및 간담회 진행 프로세스 (30분 핵심 진단)</h2>
                <div class="rm-agenda-list">
                    <div class="rm-agenda-row">
                        <div class="rm-agenda-time">00 – 08분</div>
                        <div class="rm-agenda-body">
                            <strong>학년군별 영어 원서·문해력 로드맵 브리핑</strong>
                            <span>초등 파닉스·원서 다독부터 중등 내신, 고등 수능까지 목표와 관리 방식이 어떻게 달라지는지 한눈에 정리합니다.</span>
                        </div>
                    </div>
                    <div class="rm-agenda-row">
                        <div class="rm-agenda-time">08 – 18분</div>
                        <div class="rm-agenda-body">
                            <strong>중등 내신 서술형 · 고등 수능 비문학 대비 전략</strong>
                            <span>학교별 내신 문장구조(구문) 분석, 수능 빈칸·순서 추론 루틴, 시사 영자신문 에세이 준비법을 안내합니다.</span>
                        </div>
                    </div>
                    <div class="rm-agenda-row">
                        <div class="rm-agenda-time">18 – 25분</div>
                        <div class="rm-agenda-body">
                            <strong>무료 레벨테스트 진단 결과 1:1 분석</strong>
                            <span>어휘·구문·추론 진단 결과(독해 지수)를 바탕으로 현재 자녀의 강점과 보완 영역을 명확히 짚어드립니다.</span>
                        </div>
                    </div>
                    <div class="rm-agenda-row">
                        <div class="rm-agenda-time">25 – 30분</div>
                        <div class="rm-agenda-body">
                            <strong>맞춤 추천 원서 목록 + 첫 4주 학습 목표 수립</strong>
                            <span>아이 수준에 꼭 맞는 원서 북리스트를 제공하고 입학 후 첫 4주간의 독서·학습 루틴을 함께 설계합니다.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Parent Reviews (from briefing.html & index.html) -->
            <div class="rm-info-card">
                <h2>먼저 경험한 학부모님들의 후기</h2>
                <p class="rm-sub">무료 레벨테스트와 1:1 진단 상담을 거쳐 리드마스터를 시작한 학부모님들의 이야기입니다.</p>
                <div class="rm-testi-grid">
                    <div class="rm-testi-card">
                        <div class="rm-testi-stars">★★★★★</div>
                        <p>"레벨테스트 결과표가 너무 명확해서 아이의 현재 독해 위치를 정확히 알 수 있었어요. 맞춤 원서 클럽 배정 후 6개월 만에 AR 지수가 두 단계 올라갔습니다."</p>
                        <div class="rm-testi-author">정○○ 학부모 · 중학교 2학년 자녀</div>
                    </div>
                    <div class="rm-testi-card">
                        <div class="rm-testi-stars">★★★★★</div>
                        <p>"단순히 문제만 푸는 게 아니라 원서 북리포트와 구문 분석, 에세이 첨삭까지 체계적으로 잡아주셔서 내신 서술형과 모의고사 성적이 동시에 올랐어요."</p>
                        <div class="rm-testi-author">김○○ 학부모 · 고등학교 1학년 자녀</div>
                    </div>
                </div>
            </div>

            <!-- 3. Direct Director Contact & Reservation Lookup -->
            <div class="rm-info-card">
                <h2>원장·부원장 직통 문의 & 내 예약 조회</h2>
                <p class="rm-sub">전화 또는 카카오톡/네이버로도 편하게 문의하실 수 있습니다.</p>

                <div class="rm-directors-grid">
                    <div class="rm-director-card">
                        <div class="rm-director-avatar kim">김</div>
                        <div>
                            <div class="rm-director-name">김민희 원장</div>
                            <div class="rm-director-role">중·고등부 / 수능·입시 총괄</div>
                            <a href="tel:010-3368-7873" class="rm-director-phone">📞 010-3368-7873</a>
                        </div>
                    </div>
                    <div class="rm-director-card">
                        <div class="rm-director-avatar song">송</div>
                        <div>
                            <div class="rm-director-name">송세훈 부원장</div>
                            <div class="rm-director-role">초등부 / 원서 리딩 클럽 총괄</div>
                            <a href="tel:010-3368-7873" class="rm-director-phone">📞 010-3368-7873</a>
                        </div>
                    </div>
                </div>

                <div class="rm-address-box">
                    📍 <strong>부천 옥길동 본원:</strong> 경기도 부천시 옥길로 113 상림프라자 5층 리드마스터 학원<br>
                    📍 <strong>춘천 우두동 본점:</strong> 강원특별자치도 춘천시 우두동 리드마스터 학원 · 대표전화 010-3368-7873
                </div>

                <!-- Phone Lookup against Moodle DB -->
                <div style="margin-top:1.25rem; padding-top:1.15rem; border-top:1px solid #e2e8f0;">
                    <strong style="font-size:0.88rem; color:#0f172a;">🔍 접수하신 상담/간담회 예약 현황 조회</strong>
                    <div class="rm-lookup-bar">
                        <input type="tel" id="lookupPhone" class="rm-input" placeholder="신청하신 연락처 입력 (예: 010-1234-5678)">
                        <button type="button" class="rm-lookup-btn" onclick="lookupBooking()">조회하기</button>
                    </div>
                    <div id="lookupResult" class="rm-lookup-result"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: UNIFIED BOOKING FORM -->
        <div class="rm-form-card" id="apply">
            <?php if ($submiterror !== ''): ?>
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:0.75rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:0.85rem; font-weight:700;">
                ⚠️ <?php echo s($submiterror); ?>
            </div>
            <?php endif; ?>

            <form id="rmUnifiedBookingForm" method="post" action="<?php echo (new moodle_url('/booking/index.php'))->out(false); ?>"
                  <?php echo $submittedrecord ? 'style="display:none;"' : ''; ?>>
                <input type="hidden" name="form_submit" value="1">
                <input type="hidden" name="booking_type" id="bookingTypeInput" value="<?php echo s($type); ?>">

                <div class="rm-form-head">
                    <span class="rm-form-tag" id="formBadgeText"><?php echo s($meta['types'][$type]['short']); ?></span>
                    <h3 id="formTitleText"><?php echo s($meta['types'][$type]['label']); ?></h3>
                    <p>수시 신청 · 접수 즉시 무들 상담 DB에 등록되며 담당 원장님이 개별 연락드립니다.</p>
                </div>

                <div class="rm-row-2">
                    <div class="rm-field">
                        <label class="rm-label" for="parentName">학부모 성함 <span class="req">*</span></label>
                        <input type="text" id="parentName" name="parent_name" class="rm-input" placeholder="예: 홍길동" required>
                    </div>
                    <div class="rm-field">
                        <label class="rm-label" for="studentName">자녀 이름 <span style="color:#94a3b8;font-weight:500;">(선택)</span></label>
                        <input type="text" id="studentName" name="student_name" class="rm-input" placeholder="예: 홍지민">
                    </div>
                </div>

                <div class="rm-row-2">
                    <div class="rm-field">
                        <label class="rm-label" for="phone">연락처 (핸드폰) <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" class="rm-input" placeholder="010-0000-0000" required>
                    </div>
                    <div class="rm-field">
                        <label class="rm-label" for="grade">자녀 학년 <span class="req">*</span></label>
                        <select id="grade" name="grade" class="rm-select" required>
                            <option value="" disabled selected>학년 선택</option>
                            <?php foreach ($meta['grades'] as $group => $glist): ?>
                            <optgroup label="<?php echo s($group); ?>">
                                <?php foreach ($glist as $gitem): ?>
                                <option value="<?php echo s($gitem); ?>"><?php echo s($gitem); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="rm-row-2">
                    <div class="rm-field">
                        <label class="rm-label" for="branch">희망 지점 <span class="req">*</span></label>
                        <select id="branch" name="branch" class="rm-select" required>
                            <?php foreach (array_keys($meta['branches']) as $bname): ?>
                            <option value="<?php echo s($bname); ?>" <?php echo ($branchparam === $bname) ? 'selected' : ''; ?>>
                                <?php echo s($bname); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rm-field">
                        <label class="rm-label" for="preferredTime">희망 상담 시간대</label>
                        <select id="preferredTime" name="preferred_time" class="rm-select">
                            <?php foreach ($meta['times'] as $timeopt): ?>
                            <option value="<?php echo s($timeopt); ?>"><?php echo s($timeopt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="rm-field">
                    <label class="rm-label">희망 상담 분야 / 과정 선택 <span class="req">*</span></label>
                    <div class="rm-topic-list" id="topicOptionsContainer">
                        <?php
                        $currenttopics = $meta['topics'][$type] ?? $meta['topics']['consultation'];
                        foreach ($currenttopics as $idx => $topiclabel):
                            $checked = ($idx === 0) ? 'checked' : '';
                            $selectedcls = ($idx === 0) ? 'selected' : '';
                        ?>
                        <label class="rm-topic-option <?php echo $selectedcls; ?>">
                            <input type="radio" name="session_topic" value="<?php echo s($topiclabel); ?>" <?php echo $checked; ?> required>
                            <span class="rm-topic-text"><?php echo s($topiclabel); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rm-field">
                    <label class="rm-label" for="concern">가장 궁금한 점 / 자녀 독서·영어 고민 <span style="color:#94a3b8;font-weight:500;">(선택)</span></label>
                    <textarea id="concern" name="concern" class="rm-textarea" placeholder="예: 영어 원서는 읽는데 챕터 요약과 서술형 문장 쓰기를 어려워해요."></textarea>
                </div>

                <label class="rm-privacy">
                    <input type="checkbox" id="privacyAgree" required>
                    <span>개인정보 수집 및 이용에 동의합니다. (학부모 상담·레벨테스트·간담회 안내 목적으로만 무들 자체 DB에 안전하게 보관됩니다.)</span>
                </label>

                <button type="submit" class="rm-submit-btn" id="submitBtn">
                    예약 신청 완료하기 →
                </button>

                <div class="rm-form-trust">
                    🔒 외부 스프레드시트가 아닌 리드마스터 학원 자체 보안 DB에 직접 저장됩니다
                </div>
            </form>

            <!-- SUCCESS CONFIRMATION STATE -->
            <div class="rm-success-box <?php echo $submittedrecord ? 'show' : ''; ?>" id="successBox">
                <div class="rm-success-icon">✓</div>
                <h3 style="font-size:1.35rem; font-weight:800; color:#0f172a; margin-bottom:0.35rem;">예약 접수가 완료되었습니다!</h3>
                <p style="font-size:0.86rem; color:#64748b; margin-bottom:0;">
                    담당 원장님이 확인 후 영업일 기준 1일 이내에 안내 연락을 드립니다.
                </p>

                <div class="rm-success-summary" id="successSummary">
                    <?php if ($submittedrecord): ?>
                    <div><strong>접수 번호:</strong> <?php echo s($submittedrecord->booking_code); ?></div>
                    <div><strong>학부모 성함:</strong> <?php echo s($submittedrecord->parent_name); ?> (<?php echo s($submittedrecord->phone); ?>)</div>
                    <div><strong>자녀 학년 / 지점:</strong> <?php echo s($submittedrecord->grade); ?> · <?php echo s($submittedrecord->branch); ?></div>
                    <div><strong>신청 과정:</strong> <?php echo s($submittedrecord->session_topic); ?></div>
                    <div><strong>희망 시간:</strong> <?php echo s($submittedrecord->preferred_time); ?></div>
                    <?php endif; ?>
                </div>

                <div class="rm-success-actions">
                    <a href="<?php echo $leveltesturl; ?>" class="rm-btn-leveltest">
                        🎯 지금 온라인 무료 레벨테스트 바로 응시하기 →
                    </a>
                    <a href="<?php echo $curriculumurl; ?>" style="font-size:0.84rem; font-weight:700; color:#2563eb; text-decoration:none; padding:0.4rem;">
                        📚 리드마스터 전체 커리큘럼 둘러보기
                    </a>
                    <button type="button" class="rm-btn-reset" onclick="resetBookingForm()">
                        + 추가 상담 예약 신청하기
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const RM_BOOKING_META = <?php echo json_encode($meta, JSON_UNESCAPED_UNICODE); ?>;
const RM_API_URL = <?php echo json_encode($apiurl); ?>;

function formatPhoneValue(val) {
    let v = val.replace(/\D/g, '');
    if (v.length > 3 && v.length <= 7) {
        return v.slice(0, 3) + '-' + v.slice(3);
    } else if (v.length > 7) {
        return v.slice(0, 3) + '-' + v.slice(3, 7) + '-' + v.slice(7, 11);
    }
    return v;
}

document.getElementById('phone')?.addEventListener('input', function(e) {
    e.target.value = formatPhoneValue(e.target.value);
});
document.getElementById('lookupPhone')?.addEventListener('input', function(e) {
    e.target.value = formatPhoneValue(e.target.value);
});

function bindTopicRadioHighlight() {
    const options = document.querySelectorAll('#topicOptionsContainer .rm-topic-option');
    options.forEach(opt => {
        const radio = opt.querySelector('input[type="radio"]');
        radio?.addEventListener('change', () => {
            options.forEach(o => o.classList.remove('selected'));
            if (radio.checked) {
                opt.classList.add('selected');
            }
        });
    });
}
bindTopicRadioHighlight();

function selectBookingMode(type) {
    const typeInfo = RM_BOOKING_META.types[type];
    if (!typeInfo) return;

    document.querySelectorAll('.rm-mode-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.type === type);
    });

    document.getElementById('bookingTypeInput').value = type;
    document.getElementById('formBadgeText').textContent = typeInfo.short;
    document.getElementById('formTitleText').textContent = typeInfo.label;

    const topics = RM_BOOKING_META.topics[type] || [];
    const container = document.getElementById('topicOptionsContainer');
    container.innerHTML = topics.map((t, idx) => `
        <label class="rm-topic-option ${idx === 0 ? 'selected' : ''}">
            <input type="radio" name="session_topic" value="${t.replace(/"/g, '&quot;')}" ${idx === 0 ? 'checked' : ''} required>
            <span class="rm-topic-text">${t}</span>
        </label>
    `).join('');
    bindTopicRadioHighlight();
}

// AJAX Submission to Moodle DB (api.php)
document.getElementById('rmUnifiedBookingForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submitBtn');
    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '예약 접수 처리 중...';

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.action = 'create';

    try {
        const res = await fetch(RM_API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json; charset=utf-8'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.status === 'ok') {
            form.style.display = 'none';
            const summary = document.getElementById('successSummary');
            summary.innerHTML = `
                <div><strong>접수 번호:</strong> ${data.booking_code}</div>
                <div><strong>학부모 성함:</strong> ${data.parent_name} (${data.phone})</div>
                <div><strong>자녀 학년 / 지점:</strong> ${data.grade} · ${data.branch}</div>
                <div><strong>신청 과정:</strong> ${data.session_topic}</div>
                <div><strong>희망 시간:</strong> ${data.preferred_time}</div>
                <div><strong>접수 일시:</strong> ${data.created_at}</div>
            `;
            document.getElementById('successBox').classList.add('show');

            const seatsEl = document.getElementById('rmRemainingSeats');
            if (seatsEl) {
                const cur = parseInt(seatsEl.textContent, 10) || 10;
                if (cur > 1) seatsEl.textContent = cur - 1;
            }
        } else {
            alert(data.message || '예약 처리 중 오류가 발생했습니다.');
        }
    } catch (err) {
        // Fallback to native form submission if fetch fails
        form.submit();
        return;
    } finally {
        btn.disabled = false;
        btn.textContent = origText;
    }
});

function resetBookingForm() {
    const form = document.getElementById('rmUnifiedBookingForm');
    form.reset();
    form.style.display = 'block';
    document.getElementById('successBox').classList.remove('show');
    bindTopicRadioHighlight();
}

async function lookupBooking() {
    const phone = document.getElementById('lookupPhone').value.trim();
    const resultEl = document.getElementById('lookupResult');
    if (!phone) {
        resultEl.innerHTML = '<span style="color:#dc2626;font-weight:600;">연락처를 입력해 주세요.</span>';
        return;
    }
    resultEl.innerHTML = '<span style="color:#64748b;">조회 중...</span>';
    try {
        const res = await fetch(RM_API_URL + '?action=lookup&phone=' + encodeURIComponent(phone));
        const data = await res.json();
        if (data.status !== 'ok' || !data.entries || data.entries.length === 0) {
            resultEl.innerHTML = '<div style="padding:0.75rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; color:#64748b;">해당 연락처로 접수된 예약 내역이 없습니다.</div>';
            return;
        }
        resultEl.innerHTML = data.entries.map(item => `
            <div style="padding:0.85rem 1rem; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; margin-bottom:0.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.3rem;">
                    <strong style="color:#0f172a;">[${item.booking_code}] ${item.type_label}</strong>
                    <span style="font-size:0.78rem; font-weight:800; padding:0.2rem 0.6rem; border-radius:999px; background:#eff6ff; color:#1d4ed8;">${item.status_label}</span>
                </div>
                <div style="font-size:0.8rem; color:#475569;">
                    ${item.parent_name} · ${item.grade} · ${item.branch}<br>
                    과정: ${item.session_topic} (${item.created_at} 접수)
                </div>
            </div>
        `).join('');
    } catch (e) {
        resultEl.innerHTML = '<span style="color:#dc2626;">조회 중 오류가 발생했습니다.</span>';
    }
}
</script>

<?php
echo $OUTPUT->footer();
