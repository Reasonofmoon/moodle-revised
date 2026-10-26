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

/**
 * ReadMaster English Reading Academy - Professional Curriculum & Course Catalog Page
 *
 * @package course
 */

require_once("../config.php");
require_once($CFG->dirroot . '/course/lib.php');

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$searchquery = optional_param('q', '', PARAM_TEXT);
$site = get_site();

if ($CFG->forcelogin) {
    require_login();
}

$heading = $site->fullname;
if ($categoryid) {
    $category = core_course_category::get($categoryid);
    $PAGE->set_category_by_id($categoryid);
    $PAGE->set_url(new moodle_url('/course/index.php', ['categoryid' => $categoryid]));
    $PAGE->set_pagetype('course-index-category');
    $heading = $category->get_formatted_name();
} else if ($category = core_course_category::user_top()) {
    $categoryid = 0;
    $PAGE->set_url('/course/index.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagetype('course-index-category');
} else {
    throw new moodle_exception('cannotviewcategory');
}

$PAGE->set_pagelayout('coursecategory');
$PAGE->set_primary_active_tab('home');
$PAGE->add_body_class('limitedwidth rm-curriculum-page');
$PAGE->set_title('전체 커리큘럼 및 수강 안내 | ' . $site->shortname);
$PAGE->set_heading($heading);
$PAGE->set_secondary_active_tab('categorymain');

// Track metadata for professional language academy presentation
$trackmeta = [
    'RM-CAT-LEVEL' => [
        'step' => 'STEP 01',
        'badge' => 'TRACK 01 · PLACEMENT TEST',
        'shortlabel' => '🎯 레벨 진단',
        'color' => '#2563eb',
        'bg' => '#eff6ff',
        'border' => '#bfdbfe',
        'target' => '신입생 전원 필수',
        'index' => 'AR / Lexile 종합 진단',
        'method' => '어휘·구문·추론 15분 온라인 진단 → 맞춤 반 배정',
        'icon' => '🎯',
    ],
    'RM-CAT-STARTER' => [
        'step' => 'STEP 02',
        'badge' => 'TRACK 02 · PHONICS & STARTER',
        'shortlabel' => '🌱 파닉스·기초 리딩',
        'color' => '#059669',
        'bg' => '#ecfdf5',
        'border' => '#a7f3d0',
        'target' => '초등 1~4학년 / 리딩 입문',
        'index' => 'AR 1.5 ~ 2.8 (300L~500L)',
        'method' => '오디오북 섀도잉 + 필수 사이트워드 + 첫 챕터북 완독',
        'icon' => '🌱',
    ],
    'RM-CAT-BOOKCLUB' => [
        'step' => 'STEP 03',
        'badge' => 'TRACK 03 · NEWBERY BOOK CLUB',
        'shortlabel' => '📖 원서 리딩 클럽',
        'color' => '#d97706',
        'bg' => '#fffbeb',
        'border' => '#fde68a',
        'target' => '초등 고학년 ~ 중등',
        'index' => 'AR 3.5 ~ 5.5 (650L~900L)',
        'method' => '뉴베리 수상작 원서 정독 + 챕터 퀴즈 + 영문 북리포트',
        'icon' => '📖',
    ],
    'RM-CAT-ACADEMIC' => [
        'step' => 'STEP 04',
        'badge' => 'TRACK 04 · SYNTAX & CSAT LOGIC',
        'shortlabel' => '🎓 수능·내신 심층독해',
        'color' => '#7c3aed',
        'bg' => '#f5f3ff',
        'border' => '#ddd6fe',
        'target' => '중등 심화 ~ 고등 내신·수능',
        'index' => 'AR 5.5+ (Lexile 950L~1250L)',
        'method' => '고난도 구문 분석(S/V/O/C) + 학술 비문학 빈칸·추론 완결',
        'icon' => '🎓',
    ],
    'RM-CAT-WRITING' => [
        'step' => 'STEP 05',
        'badge' => 'TRACK 05 · NIE & CRITICAL ESSAY',
        'shortlabel' => '✍️ 영자신문 & 에세이',
        'color' => '#e11d48',
        'bg' => '#fff1f2',
        'border' => '#fecdd3',
        'target' => '중·고등 심화 / 특목·서술형 대비',
        'index' => 'Lexile 1000L+ · 5-Paragraph Essay',
        'method' => '시사 영자신문(NIE) 분석 + 찬반 논술 에세이 1:1 대면/온라인 첨삭',
        'icon' => '✍️',
    ],
];

// Course-specific level badges for rich visual cues
$coursebadges = [
    'RM-LEVEL-TEST'  => ['level' => '전 학년 신입생 필수', 'feature' => '실시간 자동채점 · 15분', 'cta' => '레벨 테스트 응시하기'],
    'RM-MTH-101'     => ['level' => 'AR 2.0~2.8 · 초등 입문', 'feature' => '오디오 섀도잉 + 북리포트', 'cta' => '강의실 입장하기'],
    'RM-ORT-102'     => ['level' => 'AR 1.5~2.2 · 파닉스 완성', 'feature' => '스토리북 음독 + 핵심어휘', 'cta' => '강의실 입장하기'],
    'RM-NEWBERY-201' => ['level' => 'AR 4.0~5.5 · 뉴베리 수상작', 'feature' => '원서 정독 + 심화 에세이', 'cta' => '강의실 입장하기'],
    'RM-WONDER-202'  => ['level' => 'AR 4.5~5.2 · 현대 명작', 'feature' => '인물 분석 + 영어 토론', 'cta' => '강의실 입장하기'],
    'RM-SYNTAX-301'  => ['level' => 'Lexile 950L+ · 중고등 필수', 'feature' => '12대 구문공식 + 직독직해', 'cta' => '강의실 입장하기'],
    'RM-CSAT-302'    => ['level' => 'Lexile 1100L+ · 수능 1등급', 'feature' => '빈칸·순서·삽입 킬러 공략', 'cta' => '강의실 입장하기'],
    'RM-NIE-401'     => ['level' => 'Lexile 1000L+ · 시사 논술', 'feature' => '영자신문 요약 + 1:1 첨삭', 'cta' => '강의실 입장하기'],
];

$allcategories = core_course_category::top()->get_children();
$totalcoursescount = 0;
foreach ($allcategories as $cat) {
    $totalcoursescount += $cat->get_courses_count();
}

$canmanage = has_capability('moodle/category:manage', context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->skip_link_target();
?>

<style>
/* ReadMaster Professional Academy Curriculum Portal Styles */
#page-course-index-category .page-header-headings {
    display: none !important;
}
.rm-curriculum-wrap {
    max-width: 1200px;
    margin: 0 auto;
    font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.rm-curriculum-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1d4ed8 100%);
    color: #ffffff;
    border-radius: 20px;
    padding: 2.5rem 2.75rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.16);
    position: relative;
    overflow: hidden;
}
.rm-curriculum-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
}
.rm-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(56, 189, 248, 0.18);
    border: 1px solid rgba(56, 189, 248, 0.4);
    color: #bae6fd;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 0.3rem 0.85rem;
    border-radius: 999px;
    margin-bottom: 0.85rem;
}
.rm-curriculum-hero h1 {
    color: #ffffff;
    font-size: 1.85rem;
    font-weight: 800;
    margin-bottom: 0.55rem;
    letter-spacing: -0.02em;
}
.rm-curriculum-hero p {
    color: rgba(255, 255, 255, 0.88);
    font-size: 1rem;
    max-width: 760px;
    margin-bottom: 0;
    line-height: 1.6;
}
.rm-hero-cta-box {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 14px;
    padding: 1.1rem 1.35rem;
    text-align: right;
    min-width: 240px;
}
.rm-hero-cta-box .rm-cta-label {
    font-size: 0.8rem;
    color: #fde047;
    font-weight: 700;
    display: block;
    margin-bottom: 0.25rem;
}
.rm-hero-cta-box .rm-cta-desc {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.75rem;
}
.rm-hero-cta-btn {
    display: inline-block;
    background: #fde047;
    color: #0f172a !important;
    font-weight: 800;
    font-size: 0.88rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none !important;
    transition: transform 0.15s ease, background 0.15s ease;
}
.rm-hero-cta-btn:hover {
    background: #fef08a;
    transform: translateY(-1px);
}

/* 5-Step Roadmap Strip inside Hero */
.rm-roadmap-strip {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.75rem;
    margin-top: 1.85rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}
@media (max-width: 992px) {
    .rm-roadmap-strip {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }
}
.rm-roadmap-item {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 12px;
    padding: 0.9rem 1rem;
    color: #ffffff !important;
    text-decoration: none !important;
    transition: all 0.18s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.rm-roadmap-item:hover,
.rm-roadmap-item.active {
    background: rgba(255, 255, 255, 0.18);
    border-color: #fde047;
    transform: translateY(-2px);
}
.rm-roadmap-step {
    font-size: 0.72rem;
    font-weight: 800;
    color: #93c5fd;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}
.rm-roadmap-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.35rem;
}
.rm-roadmap-sub {
    font-size: 0.76rem;
    color: rgba(255, 255, 255, 0.75);
}

/* Filter Pill Bar & Search */
.rm-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.9rem 1.25rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
}
.rm-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.rm-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.48rem 0.95rem;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569 !important;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    text-decoration: none !important;
    transition: all 0.15s ease;
}
.rm-filter-pill:hover {
    background: #f1f5f9;
    color: #0f172a !important;
    border-color: #cbd5e1;
}
.rm-filter-pill.active {
    background: #1e3a8a;
    color: #ffffff !important;
    border-color: #1e3a8a;
    box-shadow: 0 3px 8px rgba(30, 58, 138, 0.22);
}
.rm-filter-count {
    font-size: 0.75rem;
    background: rgba(15, 23, 42, 0.08);
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
}
.rm-filter-pill.active .rm-filter-count {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
}
.rm-search-form {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.rm-search-input {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.42rem 0.85rem;
    font-size: 0.88rem;
    min-width: 210px;
}
.rm-search-btn {
    background: #334155;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0.44rem 0.85rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

/* Track Section Card */
.rm-track-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.85rem 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}
.rm-track-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}
.rm-track-badge {
    display: inline-block;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    padding: 0.28rem 0.75rem;
    border-radius: 999px;
    margin-bottom: 0.5rem;
}
.rm-track-title {
    font-size: 1.38rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.rm-track-title a {
    color: #0f172a !important;
    text-decoration: none !important;
}
.rm-track-desc {
    color: #475569;
    font-size: 0.94rem;
    margin-bottom: 0;
}
.rm-track-desc p {
    margin-bottom: 0;
}
.rm-track-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.75rem 1rem;
    border-radius: 12px;
}
.rm-spec-item {
    font-size: 0.82rem;
    color: #334155;
    padding: 0.15rem 0.55rem;
    border-right: 1px solid #cbd5e1;
}
.rm-spec-item:last-child {
    border-right: none;
}
.rm-spec-item strong {
    color: #0f172a;
}

/* 2-Column Academy Course Grid inside each Track */
.rm-course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(460px, 1fr));
    gap: 1.25rem;
}
@media (max-width: 768px) {
    .rm-course-grid {
        grid-template-columns: 1fr;
    }
}
.rm-course-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.5rem;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    position: relative;
    overflow: hidden;
}
.rm-course-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    border-color: #93c5fd;
}
.rm-course-card-top {
    margin-bottom: 1.15rem;
}
.rm-course-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-bottom: 0.75rem;
}
.rm-pill-code {
    font-size: 0.73rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 0.22rem 0.6rem;
    border-radius: 6px;
    font-family: monospace;
}
.rm-pill-level {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.22rem 0.65rem;
    border-radius: 6px;
}
.rm-pill-feature {
    font-size: 0.75rem;
    font-weight: 600;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
}
.rm-course-name {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.4;
    margin-bottom: 0.65rem;
}
.rm-course-name a {
    color: #0f172a !important;
    text-decoration: none !important;
}
.rm-course-name a:hover {
    color: #1d4ed8 !important;
}
.rm-course-summary {
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.6;
    margin-bottom: 1rem;
}
.rm-course-summary p {
    margin-bottom: 0;
}
.rm-activity-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}
.rm-act-badge {
    font-size: 0.76rem;
    font-weight: 600;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 0.2rem 0.65rem;
}
.rm-course-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}
.rm-teachers {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    align-items: center;
    font-size: 0.82rem;
    color: #475569;
}
.rm-teacher-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.22rem 0.6rem;
    border-radius: 999px;
    font-weight: 600;
    color: #334155 !important;
    text-decoration: none !important;
}
.rm-enter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 1.05rem;
    border-radius: 9px;
    font-size: 0.86rem;
    font-weight: 700;
    color: #ffffff !important;
    text-decoration: none !important;
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.rm-enter-btn:hover {
    opacity: 0.92;
    transform: translateX(2px);
}
</style>

<div class="rm-curriculum-wrap">
    <!-- Top Academy Hero & 5-Stage Level Roadmap -->
    <div class="rm-curriculum-hero">
        <div class="rm-curriculum-hero-top">
            <div>
                <span class="rm-hero-eyebrow">READMASTER ACADEMY · CURRICULUM ROADMAP</span>
                <h1>리드마스터 수준별 정규 커리큘럼 &amp; 수강 신청</h1>
                <p>
                    미국 학년제 리딩 지수(<strong>AR · Lexile</strong>) 정밀 진단부터 파닉스·뉴베리 원서 정독, 중고등 구문·수능 비문학 추론, 영자신문(NIE) 에세이 첨삭까지 학생의 성장 단계에 맞춘 5단계 로드맵을 제공합니다.
                </p>
            </div>
            <div class="rm-hero-cta-box">
                <span class="rm-cta-label">신입생 필수 코스 &amp; 1:1 진단</span>
                <div class="rm-cta-desc">우리 아이에게 맞는 리딩 레벨이 궁금하다면?</div>
                <a href="<?php echo new moodle_url('/course/view.php', ['id' => 7]); ?>" class="rm-hero-cta-btn" style="margin-bottom:0.5rem;">
                    🎯 온라인 레벨 테스트 바로 응시 →
                </a>
                <a href="<?php echo new moodle_url('/booking/index.php'); ?>" class="rm-hero-cta-btn" style="background:rgba(255,255,255,0.16); color:#ffffff !important; border:1px solid rgba(255,255,255,0.35);">
                    📅 학부모 상담 · 간담회 예약 →
                </a>
            </div>
        </div>

        <div class="rm-roadmap-strip">
            <?php foreach ($allcategories as $cat):
                $meta = $trackmeta[$cat->idnumber] ?? null;
                if (!$meta) {
                    continue;
                }
                $isactive = ($categoryid == $cat->id) ? 'active' : '';
            ?>
                <a href="<?php echo new moodle_url('/course/index.php', ['categoryid' => $cat->id]); ?>" class="rm-roadmap-item <?php echo $isactive; ?>">
                    <div>
                        <div class="rm-roadmap-step"><?php echo $meta['step']; ?></div>
                        <div class="rm-roadmap-title"><?php echo $meta['shortlabel']; ?></div>
                    </div>
                    <div class="rm-roadmap-sub"><?php echo $meta['index']; ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Interactive Category Filter Tabs & Search Bar -->
    <div class="rm-toolbar">
        <div class="rm-filter-pills">
            <a href="<?php echo new moodle_url('/course/index.php'); ?>" class="rm-filter-pill <?php echo ($categoryid == 0) ? 'active' : ''; ?>">
                전체 커리큘럼 <span class="rm-filter-count"><?php echo $totalcoursescount; ?></span>
            </a>
            <?php foreach ($allcategories as $cat):
                $meta = $trackmeta[$cat->idnumber] ?? null;
                $label = $meta ? $meta['shortlabel'] : $cat->get_formatted_name();
                $count = $cat->get_courses_count();
                $isactive = ($categoryid == $cat->id) ? 'active' : '';
            ?>
                <a href="<?php echo new moodle_url('/course/index.php', ['categoryid' => $cat->id]); ?>" class="rm-filter-pill <?php echo $isactive; ?>">
                    <?php echo $label; ?> <span class="rm-filter-count"><?php echo $count; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form method="get" action="<?php echo new moodle_url('/course/index.php'); ?>" class="rm-search-form">
                <?php if ($categoryid): ?>
                    <input type="hidden" name="categoryid" value="<?php echo (int)$categoryid; ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?php echo s($searchquery); ?>" placeholder="강좌명·교재·레벨(AR) 검색..." class="rm-search-input">
                <button type="submit" class="rm-search-btn">검색</button>
                <?php if ($searchquery !== ''): ?>
                    <a href="<?php echo new moodle_url('/course/index.php', $categoryid ? ['categoryid' => $categoryid] : []); ?>" class="btn btn-sm btn-outline-secondary">초기화</a>
                <?php endif; ?>
            </form>
            <?php if ($canmanage): ?>
                <a href="<?php echo new moodle_url('/course/management.php', $categoryid ? ['categoryid' => $categoryid] : []); ?>" class="btn btn-sm btn-outline-primary fw-bold">
                    ⚙️ 강좌 관리
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Track Sections & Course Cards -->
    <?php
    $displayedtracks = 0;
    foreach ($allcategories as $cat):
        if ($categoryid && (int)$cat->id !== (int)$categoryid) {
            continue;
        }
        $meta = $trackmeta[$cat->idnumber] ?? [
            'step' => 'TRACK',
            'badge' => 'READMASTER CURRICULUM',
            'shortlabel' => $cat->get_formatted_name(),
            'color' => '#1e3a8a',
            'bg' => '#eff6ff',
            'border' => '#bfdbfe',
            'target' => '전 학년',
            'index' => '맞춤형 과정',
            'method' => '정규 수업 및 과제 첨삭',
            'icon' => '📚',
        ];

        $courses = $cat->get_courses(['summary' => true, 'coursecontacts' => true, 'sort' => ['sortorder' => 1]]);
        if ($searchquery !== '') {
            $filtered = [];
            foreach ($courses as $c) {
                $haystack = core_text::strtolower($c->fullname . ' ' . $c->shortname . ' ' . $c->summary);
                if (strpos($haystack, core_text::strtolower($searchquery)) !== false) {
                    $filtered[] = $c;
                }
            }
            $courses = $filtered;
            if (empty($courses)) {
                continue;
            }
        }
        $displayedtracks++;
    ?>
        <section class="rm-track-section" style="border-top: 4px solid <?php echo $meta['color']; ?>;">
            <div class="rm-track-header">
                <div>
                    <span class="rm-track-badge" style="background: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>; border: 1px solid <?php echo $meta['border']; ?>;">
                        <?php echo $meta['badge']; ?>
                    </span>
                    <h2 class="rm-track-title">
                        <span><?php echo $meta['icon']; ?></span>
                        <a href="<?php echo new moodle_url('/course/index.php', ['categoryid' => $cat->id]); ?>">
                            <?php echo $cat->get_formatted_name(); ?>
                        </a>
                    </h2>
                    <div class="rm-track-desc">
                        <?php echo format_text($cat->description, $cat->descriptionformat, ['para' => false]); ?>
                    </div>
                </div>

                <div class="rm-track-specs">
                    <div class="rm-spec-item"><strong>권장 대상:</strong> <?php echo s($meta['target']); ?></div>
                    <div class="rm-spec-item"><strong>리딩 지수:</strong> <?php echo s($meta['index']); ?></div>
                    <div class="rm-spec-item"><strong>수업 방식:</strong> <?php echo s($meta['method']); ?></div>
                </div>
            </div>

            <div class="rm-course-grid">
                <?php foreach ($courses as $course):
                    $cbadge = $coursebadges[$course->shortname] ?? [
                        'level' => $meta['index'],
                        'feature' => '원서 리딩 & 첨삭',
                        'cta' => '강의실 입장하기',
                    ];
                    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
                    $pagecount = $DB->count_records('page', ['course' => $course->id]);
                    $quizcount = $DB->count_records('quiz', ['course' => $course->id]);
                    $assigncount = $DB->count_records('assign', ['course' => $course->id]);
                ?>
                    <div class="rm-course-card">
                        <div class="rm-course-card-top">
                            <div class="rm-course-pills">
                                <span class="rm-pill-code"><?php echo s($course->shortname); ?></span>
                                <span class="rm-pill-level" style="background: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>; border: 1px solid <?php echo $meta['border']; ?>;">
                                    <?php echo s($cbadge['level']); ?>
                                </span>
                                <span class="rm-pill-feature"><?php echo s($cbadge['feature']); ?></span>
                            </div>

                            <h3 class="rm-course-name">
                                <a href="<?php echo $courseurl; ?>"><?php echo $course->get_formatted_name(); ?></a>
                            </h3>

                            <div class="rm-course-summary">
                                <?php echo format_text($course->summary, $course->summaryformat, ['noclean' => true, 'para' => false]); ?>
                            </div>

                            <div class="rm-activity-badges">
                                <?php if ($pagecount > 0): ?>
                                    <span class="rm-act-badge">📘 핵심 리딩·구문 노트 <?php echo $pagecount; ?>개</span>
                                <?php endif; ?>
                                <?php if ($quizcount > 0): ?>
                                    <span class="rm-act-badge">✅ 온라인 진단·체크 퀴즈 <?php echo $quizcount; ?>개</span>
                                <?php endif; ?>
                                <?php if ($assigncount > 0): ?>
                                    <span class="rm-act-badge">✍️ 북리포트·에세이 첨삭과제 <?php echo $assigncount; ?>개</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="rm-course-footer">
                            <div class="rm-teachers">
                                <span class="fw-bold text-secondary">담당 강사:</span>
                                <?php if ($course->has_course_contacts()): ?>
                                    <?php foreach ($course->get_course_contacts() as $contact): ?>
                                        <a href="<?php echo \core_user::get_profile_url($contact['user'], context_system::instance()); ?>" class="rm-teacher-chip">
                                            👩‍🏫 <?php echo s($contact['username']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="rm-teacher-chip">👩‍🏫 리드마스터 전임강사</span>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo $courseurl; ?>" class="rm-enter-btn" style="background: <?php echo $meta['color']; ?>;">
                                <?php echo s($cbadge['cta']); ?> →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($displayedtracks === 0): ?>
        <div class="alert alert-info p-4 text-center rounded-4">
            <h4 class="fw-bold mb-2">검색 조건에 맞는 강좌가 없습니다.</h4>
            <p class="mb-3">다른 키워드로 검색하시거나 전체 커리큘럼 탭을 선택해 주세요.</p>
            <a href="<?php echo new moodle_url('/course/index.php'); ?>" class="btn btn-primary">전체 커리큘럼 보기</a>
        </div>
    <?php endif; ?>
</div>

<?php
// Trigger event, course category viewed.
$eventparams = ['context' => $PAGE->context, 'objectid' => $categoryid];
$event = \core\event\course_category_viewed::create($eventparams);
$event->trigger();

echo $OUTPUT->footer();
