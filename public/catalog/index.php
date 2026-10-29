<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * B2B Academy LMS Turnkey Solution — Product Feature Catalog & Onboarding Configurator
 *
 * @package    core
 * @copyright  2026 ReadMaster Academy LMS Solution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');

$site = get_site();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/catalog/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('limitedwidth rm-catalog-page');
$PAGE->set_title('학원 맞춤형 LMS 솔루션 카탈로그 & 납품 매뉴얼 | ' . $site->shortname);
$PAGE->set_heading('학원 맞춤형 LMS 솔루션 카탈로그');

$loginurl = (new moodle_url('/login/index.php'))->out(false);
$curriculumurl = (new moodle_url('/course/index.php'))->out(false);
$leveltesturl = (new moodle_url('/course/view.php', ['id' => 7]))->out(false);
$bookingurl = (new moodle_url('/booking/index.php'))->out(false);
$crmurl = (new moodle_url('/booking/admin.php'))->out(false);

echo $OUTPUT->header();
?>

<style>
.rm-catalog-page #page-header {
    display: none !important;
}
.rm-catalog-page #region-main {
    border: none !important;
    padding: 0 !important;
    background: transparent !important;
}
.rm-cat-wrap {
    font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0f172a;
    padding-bottom: 4rem;
}

/* Hero Banner */
.rm-cat-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 58%, #1d4ed8 100%);
    color: #ffffff;
    border-radius: 24px;
    padding: 3rem 2.75rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
}
.rm-cat-badge {
    display: inline-block;
    background: rgba(251, 191, 36, 0.18);
    border: 1px solid rgba(251, 191, 36, 0.5);
    color: #fde68a;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 0.35rem 0.95rem;
    border-radius: 999px;
    margin-bottom: 1rem;
}
.rm-cat-hero h1 {
    font-size: 2.25rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1.3;
    letter-spacing: -0.025em;
    margin-bottom: 0.75rem;
}
.rm-cat-hero h1 em {
    font-style: normal;
    color: #fbbf24;
}
.rm-cat-hero p {
    font-size: 1.02rem;
    color: #cbd5e1;
    max-width: 780px;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}
.rm-cat-hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.16);
}
@media (max-width: 768px) {
    .rm-cat-hero-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
.rm-cat-stat strong {
    display: block;
    font-size: 1.55rem;
    font-weight: 900;
    color: #fbbf24;
}
.rm-cat-stat span {
    font-size: 0.84rem;
    color: #e2e8f0;
}

/* Section Title */
.rm-sec-head {
    margin: 2.5rem 0 1.25rem;
}
.rm-sec-tag {
    display: inline-block;
    font-size: 0.76rem;
    font-weight: 800;
    color: #2563eb;
    background: #eff6ff;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    margin-bottom: 0.45rem;
}
.rm-sec-head h2 {
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.3rem;
}
.rm-sec-head p {
    font-size: 0.92rem;
    color: #64748b;
    margin: 0;
}

/* Before vs After Comparison */
.rm-compare-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 2rem;
}
@media (max-width: 860px) {
    .rm-compare-grid {
        grid-template-columns: 1fr;
    }
}
.rm-compare-box {
    border-radius: 18px;
    padding: 1.65rem;
    border: 1px solid #e2e8f0;
}
.rm-compare-box.before {
    background: #f8fafc;
}
.rm-compare-box.after {
    background: #eff6ff;
    border: 2px solid #3b82f6;
}
.rm-compare-box h3 {
    font-size: 1.15rem;
    font-weight: 800;
    margin-bottom: 0.85rem;
}
.rm-compare-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    font-size: 0.88rem;
}

/* 6 Core Feature Modules Grid */
.rm-modules-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.15rem;
}
@media (max-width: 980px) {
    .rm-modules-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 640px) {
    .rm-modules-grid {
        grid-template-columns: 1fr;
    }
}
.rm-mod-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    transition: transform 0.16s, border-color 0.16s;
}
.rm-mod-card:hover {
    transform: translateY(-3px);
    border-color: #93c5fd;
}
.rm-mod-num {
    display: inline-block;
    font-size: 0.74rem;
    font-weight: 800;
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #334155;
    margin-bottom: 0.65rem;
}
.rm-mod-card h3 {
    font-size: 1.12rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.45rem;
}
.rm-mod-card p {
    font-size: 0.85rem;
    color: #475569;
    line-height: 1.6;
    margin-bottom: 1rem;
}
.rm-mod-points {
    list-style: none;
    padding: 0;
    margin: 0 0 1.15rem;
    font-size: 0.8rem;
    color: #334155;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.rm-mod-btn {
    display: inline-block;
    text-align: center;
    background: #0f172a;
    color: #ffffff !important;
    text-decoration: none !important;
    font-size: 0.84rem;
    font-weight: 700;
    padding: 0.6rem 0.9rem;
    border-radius: 10px;
}
.rm-mod-btn:hover {
    background: #1d4ed8;
}

/* Delivery Packages Table */
.rm-pkg-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.15rem;
}
@media (max-width: 900px) {
    .rm-pkg-grid {
        grid-template-columns: 1fr;
    }
}
.rm-pkg-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.65rem;
}
.rm-pkg-card.featured {
    border: 2px solid #2563eb;
    background: #f8fafc;
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.1);
}
.rm-pkg-tier {
    font-size: 0.78rem;
    font-weight: 800;
    color: #2563eb;
    margin-bottom: 0.3rem;
}
.rm-pkg-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.4rem;
}
.rm-pkg-desc {
    font-size: 0.84rem;
    color: #64748b;
    margin-bottom: 1rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #e2e8f0;
}
.rm-pkg-list {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.84rem;
    line-height: 1.85;
    color: #334155;
}

/* Interactive Config Generator */
.rm-gen-box {
    background: #ffffff;
    border: 2px solid #dbeafe;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
}
.rm-gen-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
@media (max-width: 900px) {
    .rm-gen-grid {
        grid-template-columns: 1fr;
    }
}
.rm-gen-field {
    margin-bottom: 0.85rem;
}
.rm-gen-field label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.3rem;
}
.rm-gen-field input,
.rm-gen-field select {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    padding: 0.6rem 0.85rem;
    font-size: 0.88rem;
    background: #f8fafc;
}
.rm-code-out {
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem;
    font-family: monospace;
    font-size: 0.8rem;
    line-height: 1.55;
    max-height: 420px;
    overflow: auto;
    white-space: pre-wrap;
}
</style>

<div class="rm-cat-wrap">

    <!-- 1. HERO -->
    <div class="rm-cat-hero">
        <span class="rm-cat-badge">학원 맞춤형 통합 LMS · 예약 퍼널 · 진단 평가 패키지</span>
        <h1>초·중·고 전문 학원을 위한<br><em>올인원 학습관 &amp; 상담 예약 LMS</em> 카탈로그</h1>
        <p>
            복잡하고 무거운 기존 LMS의 불필요한 기능 80%를 걷어내고, 실제 학원 현장에 꼭 필요한
            <strong>브랜드 홍보 · 온라인 무료 레벨테스트 · 5단계 커리큘럼 로드맵 · 학부모 상담/간담회 자체 DB 예약 관리 · 원서/과제 1:1 첨삭</strong>만 결합한 학원 전용 턴키(Turnkey) 솔루션입니다.
        </p>
        <div class="rm-cat-hero-stats">
            <div class="rm-cat-stat">
                <strong>7가지 정보</strong>
                <span>입력만으로 신규 학원 즉시 구축</span>
            </div>
            <div class="rm-cat-stat">
                <strong>6대 핵심 모듈</strong>
                <span>상담 퍼널부터 수업·첨삭까지 통합</span>
            </div>
            <div class="rm-cat-stat">
                <strong>100% 자체 DB</strong>
                <span>외부 유출 없는 학부모 예약 관리</span>
            </div>
            <div class="rm-cat-stat">
                <strong>전 과목 호환</strong>
                <span>영어·수학·국어논술·입시학원 맞춤화</span>
            </div>
        </div>
    </div>

    <!-- 2. BEFORE vs AFTER -->
    <div class="rm-sec-head">
        <span class="rm-sec-tag">도입 효과 비교</span>
        <h2>일반 홈페이지·무들 기본형 vs 학원 맞춤형 턴키 LMS</h2>
        <p>학부모 상담 전환율부터 입학 후 학생 학습 관리까지 하나의 시스템으로 연결합니다.</p>
    </div>

    <div class="rm-compare-grid">
        <div class="rm-compare-box before">
            <h3 style="color:#64748b;">❌ 기존 학원 운영 방식의 불편함</h3>
            <ul class="rm-compare-list">
                <li>• 홈페이지, 구글 설문지(상담 예약), 레벨테스트, 과제 게시판이 전부 따로 놀아 관리가 분산됨</li>
                <li>• 기본 무들(Moodle) 설치 시 대학용 복잡한 메뉴·영문 표기·수십 개 블록 때문에 학부모와 학생이 길을 잃음</li>
                <li>• 학부모 상담 내역이 외부 스프레드시트에 흩어져 있어 연락 누락 및 개인정보 관리 위험 발생</li>
                <li>• 신규 학원마다 디자인과 강좌 구조를 처음부터 수작업으로 세팅해야 함</li>
            </ul>
        </div>
        <div class="rm-compare-box after">
            <h3 style="color:#1d4ed8;">✅ 리드마스터형 학원 맞춤 LMS 솔루션</h3>
            <ul class="rm-compare-list">
                <li>• <strong>상담 예약 → 온라인 레벨테스트 → 맞춤 반 배정 → 정규 수업·첨삭</strong>이 한 사이트에서 완결</li>
                <li>• 불필요한 모듈 16종·블록 30종 제거 및 <strong>100% 한국어 UI + Noto Sans KR 서체</strong> 기본 탑재</li>
                <li>• 학원 자체 보안 DB(<code>mdl_readmaster_leads</code>) 기반 <strong>원장·교사 전용 예약 관리 대시보드 &amp; 엑셀 출력</strong></li>
                <li>• <code>academy_config.json</code> 설정 파일 하나로 <strong>타 학원 납품 시 5분 내 자동 커스터마이징</strong></li>
            </ul>
        </div>
    </div>

    <!-- 3. 6 CORE MODULES WITH LIVE DEMO LINKS -->
    <div class="rm-sec-head">
        <span class="rm-sec-tag">핵심 제공 기능 (6대 모듈)</span>
        <h2>납품 시 즉시 제공되는 6대 학원 전용 모듈</h2>
        <p>각 카드 하단의 버튼을 클릭하면 현재 구축된 실제 화면을 바로 체험하실 수 있습니다.</p>
    </div>

    <div class="rm-modules-grid">
        <div class="rm-mod-card">
            <div>
                <span class="rm-mod-num">모듈 01 · 브랜드 첫인상</span>
                <h3>🏛️ 맞춤형 실사 배경 &amp; 학원 전용 로그인</h3>
                <p>무들 기본 로고 대신 학원 과목 컨셉(원서 도서관, 수학 스터디 라운지 등)에 맞춘 고해상도 실사 배경과 학원 강점 요약 카드를 배치합니다.</p>
                <ul class="rm-mod-points">
                    <li>✓ 과목별 맞춤 실사 인테리어 이미지 생성·적용</li>
                    <li>✓ 학원 엠블럼 로고 및 핵심 교육 철학 카드 노출</li>
                    <li>✓ 복잡한 비밀번호 제한 해제로 학생·학부모 간편 접속</li>
                </ul>
            </div>
            <a href="<?php echo $loginurl; ?>" class="rm-mod-btn">로그인 화면 라이브 보기 →</a>
        </div>

        <div class="rm-mod-card">
            <div>
                <span class="rm-mod-num">모듈 02 · 커리큘럼 쇼케이스</span>
                <h3>🗺️ 5단계 시각적 커리큘럼 로드맵</h3>
                <p>학원의 전체 교육 과정을 1단계(진단)부터 5단계(심화/입시)까지 한눈에 보여주는 전문 어학원·입시학원 스타일의 커리큘럼 포털입니다.</p>
                <ul class="rm-mod-points">
                    <li>✓ 상단 5단계 스텝 로드맵 &amp; 과정별 실시간 필터 탭</li>
                    <li>✓ 과정별 권장 대상·목표 지수·수업 방식 명세표</li>
                    <li>✓ 강좌별 노트·퀴즈·첨삭과제 수량 배지 자동 집계</li>
                </ul>
            </div>
            <a href="<?php echo $curriculumurl; ?>" class="rm-mod-btn">5단계 커리큘럼 화면 보기 →</a>
        </div>

        <div class="rm-mod-card">
            <div>
                <span class="rm-mod-num">모듈 03 · 신입생 진단 평가</span>
                <h3>🎯 온라인 무료 레벨테스트 &amp; 자동 채점</h3>
                <p>신입생이 학원 방문 전 또는 상담 대기 중에 PC·모바일로 15분 만에 응시하고 즉시 영역별 점수를 확인할 수 있는 온라인 배치고사입니다.</p>
                <ul class="rm-mod-points">
                    <li>✓ 어휘·구문·추론 등 영역별 객관식/단답형 문항 탑재</li>
                    <li>✓ 응시 즉시 자동 채점 및 문항별 상세 해설 피드백</li>
                    <li>✓ 상담 예약 완료 화면에서 원클릭 바로 응시 연동</li>
                </ul>
            </div>
            <a href="<?php echo $leveltesturl; ?>" class="rm-mod-btn">온라인 레벨테스트 보기 →</a>
        </div>

        <div class="rm-mod-card">
            <div>
                <span class="rm-mod-num">모듈 04 · 학부모 상담 퍼널</span>
                <h3>📅 상담 · 레벨테스트 · 간담회 통합 예약</h3>
                <p>분산되어 있던 1:1 학부모 상담, 무료 레벨테스트 신청, 학부모 설명회(간담회) 예약을 하나의 고전환 랜딩 페이지로 통합했습니다.</p>
                <ul class="rm-mod-points">
                    <li>✓ 예약 유형 3종 탭 전환 시 맞춤 상담 주제 자동 변경</li>
                    <li>✓ 간담회 30분 타임라인 안내 &amp; 원장 직통 연락처 카드</li>
                    <li>✓ 학부모 연락처 입력만으로 실시간 예약 처리상태 조회</li>
                </ul>
            </div>
            <a href="<?php echo $bookingurl; ?>" class="rm-mod-btn">통합 상담 예약 화면 보기 →</a>
        </div>

        <div class="rm-mod-card">
            <div>
                <span class="rm-mod-num">모듈 05 · 원장·교사진 전용</span>
                <h3>📋 자체 DB 예약 관리 대시보드 &amp; 엑셀 연동</h3>
                <p>외부 구글 시트 없이 학원 자체 MariaDB에서 모든 학부모 예약 건을 실시간으로 확인하고 처리 상태와 상담 메모를 관리합니다.</p>
                <ul class="rm-mod-points">
                    <li>✓ 전체·신규·연락완료·확정·취소 실시간 통계 카드</li>
                    <li>✓ 지점별·유형별·상태별 필터링 및 원장 상담 메모 저장</li>
                    <li>✓ 전화 문의 수기 등록 &amp; 한글 엑셀(CSV) 원클릭 다운로드</li>
                </ul>
            </div>
            <a href="<?php echo $crmurl; ?>" class="rm-mod-btn">예약 관리 대시보드 보기 →</a>
        </div>

        <div class="rm-mod-card">
            <div>
                <span class="rm-mod-num">모듈 06 · 정규 수업 &amp; 첨삭</span>
                <h3>✍️ 핵심 개념 노트 &amp; 1:1 서술형·에세이 첨삭</h3>
                <p>각 강좌 강의실 내부에 주차별 핵심 리딩/구문 노트, 확인 퀴즈, 그리고 선생님이 직접 첨삭 피드백을 남기는 서술형 과제실이 구성됩니다.</p>
                <ul class="rm-mod-points">
                    <li>✓ 꼭 필요한 7대 핵심 학습 활동만 남긴 깔끔한 강의실</li>
                    <li>✓ 학생 독후감·에세이·서술형 답안 업로드 및 1:1 채점</li>
                    <li>✓ 학생·강사·총괄원장 권한별 깔끔한 메뉴 분리</li>
                </ul>
            </div>
            <a href="<?php echo $curriculumurl; ?>" class="rm-mod-btn">강좌별 강의실 둘러보기 →</a>
        </div>
    </div>

    <!-- 4. DELIVERY PACKAGES -->
    <div class="rm-sec-head">
        <span class="rm-sec-tag">납품 패키지 구성안</span>
        <h2>학원 규모 및 요구사항별 납품 라인업</h2>
        <p>단독 교습소·전문학원부터 다지점 프랜차이즈 본원까지 유연하게 납품할 수 있습니다.</p>
    </div>

    <div class="rm-pkg-grid">
        <div class="rm-pkg-card">
            <div class="rm-pkg-tier">BASIC · 베이직형</div>
            <div class="rm-pkg-title">스마트 학원 학습관 패키지</div>
            <div class="rm-pkg-desc">단일 캠퍼스 학원을 위한 빠른 브랜드 LMS 구축</div>
            <ul class="rm-pkg-list">
                <li>✓ 학원 로고·브랜드 컬러·전용 로그인 배경 적용</li>
                <li>✓ 불필요 기능 80% 경량화 &amp; 100% 한글 UI 적용</li>
                <li>✓ 5단계 커리큘럼 로드맵 포털 기본 세팅</li>
                <li>✓ 관리자·강사·학생 기본 계정 세팅</li>
                <li>✓ 학원 맞춤 초기 대표 강좌 5개 생성</li>
            </ul>
        </div>

        <div class="rm-pkg-card featured">
            <div class="rm-pkg-tier">PRO · 프로형 (추천 · 리드마스터 표준)</div>
            <div class="rm-pkg-title">올인원 상담 퍼널 + 진단 + LMS 패키지</div>
            <div class="rm-pkg-desc">학부모 모객(상담·간담회)부터 레벨테스트·수업까지 완전 통합</div>
            <ul class="rm-pkg-list">
                <li>✓ <strong>베이직형 전체 기능 포함</strong></li>
                <li>✓ <strong>학부모 상담·레벨테스트·간담회 통합 예약 페이지</strong></li>
                <li>✓ <strong>원장·교사용 예약 관리 대시보드(자체 DB + 엑셀 출력)</strong></li>
                <li>✓ <strong>온라인 자동채점 무료 레벨테스트 강좌 구축</strong></li>
                <li>✓ <strong>학부모 연락처 기반 실시간 예약 조회 기능</strong></li>
            </ul>
        </div>

        <div class="rm-pkg-card">
            <div class="rm-pkg-tier">ENTERPRISE · 프랜차이즈·다지점형</div>
            <div class="rm-pkg-title">멀티 캠퍼스 통합 LMS 패키지</div>
            <div class="rm-pkg-desc">본원 및 가맹 분원을 함께 운영하는 학원 브랜드를 위한 패키지</div>
            <ul class="rm-pkg-list">
                <li>✓ <strong>프로형 전체 기능 포함</strong></li>
                <li>✓ 다지점(본원·분원) 선택 예약 및 지점별 필터링 관리</li>
                <li>✓ 네이버 블로그 새 글 자동 연동 전용 테마 적용</li>
                <li>✓ 분원 추가 시 5분 복제 스크립트(<code>provision_academy.php</code>) 제공</li>
                <li>✓ 원장·강사진 운영 교육 및 커스텀 매뉴얼 제공</li>
            </ul>
        </div>
    </div>

    <!-- 5. INTERACTIVE TURNKEY CONFIG GENERATOR -->
    <div class="rm-sec-head">
        <span class="rm-sec-tag">원클릭 납품 워크플로우</span>
        <h2>⚡ 신규 학원 납품용 설정 파일(<code>academy_config.json</code>) 자동 생성기</h2>
        <p>납품할 학원의 기본 정보 7가지를 입력하면 즉시 적용 가능한 설정 코드와 원클릭 실행 명령어가 생성됩니다.</p>
    </div>

    <div class="rm-gen-box">
        <div class="rm-gen-grid">
            <div>
                <div class="rm-gen-field">
                    <label>1. 학원 정식 명칭 (fullname)</label>
                    <input type="text" id="gFullname" value="하이퍼리딩 영어전문학원" oninput="updateConfigPreview()">
                </div>
                <div class="rm-gen-field">
                    <label>2. 학원 약칭 (shortname)</label>
                    <input type="text" id="gShortname" value="하이퍼리딩 학원" oninput="updateConfigPreview()">
                </div>
                <div class="rm-gen-field">
                    <label>3. 전문 과목 및 한 줄 슬로건 (tagline)</label>
                    <input type="text" id="gTagline" value="정밀 레벨 진단부터 1:1 원서·서술형 첨삭까지 완성하는 프리미엄 영어학원" oninput="updateConfigPreview()">
                </div>
                <div class="rm-gen-field">
                    <label>4. 지점명 및 주소 (branch)</label>
                    <input type="text" id="gBranch" value="대치 본원 | 서울시 강남구 도곡로 123 4층" oninput="updateConfigPreview()">
                </div>
                <div class="rm-gen-field">
                    <label>5. 대표 전화번호 &amp; 담당 원장 성함</label>
                    <input type="text" id="gDirector" value="010-1234-5678 | 이현우 대표원장" oninput="updateConfigPreview()">
                </div>
                <div class="rm-gen-field">
                    <label>6. 관리자 아이디 / 초기 비밀번호</label>
                    <input type="text" id="gAdmin" value="hyperadmin / hyper1234" oninput="updateConfigPreview()">
                </div>
                <div class="rm-gen-field">
                    <label>7. 대표 브랜드 컬러 (Primary Color)</label>
                    <select id="gColor" onchange="updateConfigPreview()">
                        <option value="#1E3A8A">로얄 네이비 (#1E3A8A) — 신뢰·입시·어학원</option>
                        <option value="#10301D">딥 포레스트 그린 (#10301D) — 원서도서관·아이비리그</option>
                        <option value="#4C1D95">노블 퍼플 (#4C1D95) — 수학·영재·사고력</option>
                        <option value="#7C2D12">클래식 버건디 (#7C2D12) — 국어·독서논술·인문</option>
                    </select>
                </div>
            </div>

            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <strong style="font-size:0.86rem; color:#0f172a;">생성된 <code>academy_config.json</code> &amp; 실행 명령어</strong>
                    <button type="button" onclick="copyGeneratedConfig()" style="background:#2563eb; color:#fff; border:none; border-radius:8px; padding:0.35rem 0.85rem; font-size:0.78rem; font-weight:700; cursor:pointer;">
                        📋 JSON 복사하기
                    </button>
                </div>
                <pre class="rm-code-out" id="configPreviewOut"></pre>
                <div style="margin-top:0.75rem; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:0.85rem 1rem; font-size:0.8rem; color:#334155;">
                    <strong>🚀 터미널 원클릭 적용 명령어:</strong><br>
                    <code style="color:#1d4ed8; font-weight:700;">docker exec -u www-data readmaster-moodle-web php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json</code>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function buildConfigObject() {
    const fullname = document.getElementById('gFullname').value.trim();
    const shortname = document.getElementById('gShortname').value.trim();
    const tagline = document.getElementById('gTagline').value.trim();
    const branchParts = document.getElementById('gBranch').value.split('|').map(s => s.trim());
    const dirParts = document.getElementById('gDirector').value.split('|').map(s => s.trim());
    const adminParts = document.getElementById('gAdmin').value.split('/').map(s => s.trim());
    const color = document.getElementById('gColor').value;

    const branchName = branchParts[0] || '본원 캠퍼스';
    const branchAddr = branchParts[1] || '';
    const phone = dirParts[0] || '010-0000-0000';
    const directorName = dirParts[1] || '대표원장';
    const adminUser = adminParts[0] || 'admin';
    const adminPass = adminParts[1] || 'academy1234';

    return {
        academy: {
            id: adminUser,
            fullname: fullname,
            shortname: shortname,
            badge: shortname + " · 맞춤형 학습 관리 시스템",
            login_badge: shortname + " 전용 학습관",
            tagline: tagline,
            primary_color: color,
            accent_color: "#F59E0B",
            font_family: "Noto Sans KR"
        },
        accounts: {
            admin_user: adminUser,
            admin_pass: adminPass,
            admin_firstname: "총괄원장",
            admin_lastname: shortname
        },
        branches: [
            {
                name: branchName,
                address: branchAddr,
                phone: phone,
                directors: directorName
            }
        ],
        directors: [
            {
                initial: directorName.slice(0, 1),
                name: directorName,
                role: "전 과정 총괄",
                phone: phone
            }
        ],
        frontpage_steps: [
            { title: "1단계. 정밀 레벨 진단", desc: "영역별 실력 진단 후 수준별 맞춤 반 배정" },
            { title: "2단계. 기초·개념 완성", desc: "핵심 개념 체화 및 주차별 확인 학습" },
            { title: "3단계. 내신·수능 심화", desc: "학교별 기출 및 고난도 유형 완벽 대비" },
            { title: "4단계. 1:1 밀착 첨삭", desc: "개인별 서술형·에세이 1:1 정밀 첨삭" }
        ]
    };
}

function updateConfigPreview() {
    const obj = buildConfigObject();
    document.getElementById('configPreviewOut').textContent = JSON.stringify(obj, null, 2);
}

function copyGeneratedConfig() {
    const text = document.getElementById('configPreviewOut').textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('academy_config.json 설정 코드가 클립보드에 복사되었습니다!');
    });
}

updateConfigPreview();
</script>

<?php
echo $OUTPUT->footer();
