<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * ReadMaster Academy Unified Consultation & Briefing Booking Library (Moodle DB Backend)
 *
 * Replaces Google Apps Script (Code.gs) & Google Sheets with native Moodle DB (mdl_readmaster_leads).
 *
 * @package    core
 * @copyright  2026 ReadMaster Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Ensure the mdl_readmaster_leads table exists in Moodle's MariaDB database.
 */
function readmaster_booking_ensure_schema(): void {
    global $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('readmaster_leads');

    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('booking_code', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, '');
        $table->add_field('booking_type', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, 'consultation');
        $table->add_field('parent_name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('student_name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('phone', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '');
        $table->add_field('grade', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '');
        $table->add_field('branch', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '부천 옥길동 본원');
        $table->add_field('session_topic', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        $table->add_field('preferred_time', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '수시 (담당자 확인 후 조율)');
        $table->add_field('concern', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'new');
        $table->add_field('admin_memo', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '통합 상담 예약 페이지');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('idx_branch', XMLDB_INDEX_NOTUNIQUE, ['branch']);
        $table->add_index('idx_booking_type', XMLDB_INDEX_NOTUNIQUE, ['booking_type']);
        $table->add_index('idx_phone', XMLDB_INDEX_NOTUNIQUE, ['phone']);

        $dbman->create_table($table);

        // Seed initial sample leads so the CRM table is immediately ready for testing.
        $now = time();
        $samples = [
            [
                'booking_code' => 'RM-' . date('ymd') . '-101',
                'booking_type' => 'leveltest',
                'parent_name' => '정지영 학부모',
                'student_name' => '김서준',
                'phone' => '010-4821-7790',
                'grade' => '중1',
                'branch' => '부천 옥길동 본원',
                'session_topic' => '초·중등 원서 리딩 & 무료 레벨테스트 3종',
                'preferred_time' => '평일 오후 (16:00~18:00)',
                'concern' => '초등학교 때 ORT는 읽었는데 챕터북(Newbery)으로 넘어가면서 어휘와 요약에 어려움을 겪고 있습니다. 정확한 AR 지수를 진단받고 싶습니다.',
                'status' => 'confirmed',
                'admin_memo' => '10/28(수) 16:30 방문 레벨테스트 확정 (송세훈 부원장 배정)',
                'source' => '무료 레벨테스트 예약 탭',
                'userid' => 0,
                'timecreated' => $now - 86400 * 2,
                'timemodified' => $now - 86400,
            ],
            [
                'booking_code' => 'RM-' . date('ymd') . '-102',
                'booking_type' => 'consultation',
                'parent_name' => '이수민 학부모',
                'student_name' => '박하은',
                'phone' => '010-9134-2258',
                'grade' => '초5',
                'branch' => '부천 옥길동 본원',
                'session_topic' => '초·중등 학부모 맞춤 상담 (원서 북클럽)',
                'preferred_time' => '토요일 오전/오후',
                'concern' => '영어 원서 다독 습관을 잡아주고 싶은데 현재 파닉스 이후 어떤 단계(Magic Tree House vs Wonder)로 시작해야 할지 궁금합니다.',
                'status' => 'contacted',
                'admin_memo' => '1차 통화 완료, 토요일 오전 11시 방문 상담 조율 중',
                'source' => '1:1 학부모 상담 탭',
                'userid' => 0,
                'timecreated' => $now - 43200,
                'timemodified' => $now - 14400,
            ],
            [
                'booking_code' => 'RM-' . date('ymd') . '-103',
                'booking_type' => 'briefing',
                'parent_name' => '김현우 학부모',
                'student_name' => '김도윤',
                'phone' => '010-3389-6104',
                'grade' => '고1',
                'branch' => '춘천 우두동 본점',
                'session_topic' => '고등 수능·입시완성 & 학부모 간담회',
                'preferred_time' => '저녁 7시-8시 (학부모 간담회)',
                'concern' => '모의고사 빈칸추론과 순서배열에서 시간이 부족합니다. 구문 독해(Syntax)와 비문학 추론 훈련 커리큘럼 안내를 받고 싶습니다.',
                'status' => 'new',
                'admin_memo' => '',
                'source' => '학부모 간담회 예약 탭',
                'userid' => 0,
                'timecreated' => $now - 3600,
                'timemodified' => $now - 3600,
            ],
        ];

        foreach ($samples as $sample) {
            $DB->insert_record('readmaster_leads', (object)$sample);
        }
    }
}

/**
 * Normalize phone number to 010-XXXX-XXXX format.
 */
function readmaster_booking_format_phone(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 11) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
    } else if (strlen($digits) === 10) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
    }
    return trim($phone);
}

/**
 * Metadata definitions for booking types, statuses, branches, and grades.
 */
function readmaster_booking_metadata(): array {
    return [
        'types' => [
            'consultation' => [
                'label' => '1:1 학부모 맞춤 상담',
                'short' => '1:1 상담',
                'badge_bg' => '#eff6ff',
                'badge_color' => '#1d4ed8',
                'icon' => '👩‍🏫',
                'desc' => '자녀의 현재 영어·독서 수준과 목표에 맞춰 원장·부원장이 1:1로 학습 로드맵을 설계합니다.',
            ],
            'leveltest' => [
                'label' => 'RMLI 무료 레벨테스트 & 진단',
                'short' => '무료 레벨테스트',
                'badge_bg' => '#ecfdf5',
                'badge_color' => '#047857',
                'icon' => '🎯',
                'desc' => '어휘·구문·추론 역량 및 AR/Lexile 지수를 정밀 측정하고 수준별 최적 반을 배정합니다.',
            ],
            'briefing' => [
                'label' => '학부모 간담회 예약 (선착순 20명)',
                'short' => '학부모 간담회',
                'badge_bg' => '#fffbeb',
                'badge_color' => '#b45309',
                'icon' => '🎤',
                'desc' => '30분 핵심 브리핑으로 초등 원서읽기부터 중등 내신, 고등 수능까지의 문해력 로드맵을 안내합니다.',
            ],
        ],
        'statuses' => [
            'new' => [
                'label' => '🆕 신규 접수',
                'bg' => '#fef3c7',
                'color' => '#b45309',
                'border' => '#fde68a',
            ],
            'contacted' => [
                'label' => '📞 연락 완료',
                'bg' => '#dbeafe',
                'color' => '#1d4ed8',
                'border' => '#bfdbfe',
            ],
            'confirmed' => [
                'label' => '✅ 일정 확정',
                'bg' => '#d1fae5',
                'color' => '#047857',
                'border' => '#a7f3d0',
            ],
            'cancelled' => [
                'label' => '❌ 예약 취소',
                'bg' => '#fee2e2',
                'color' => '#b91c1c',
                'border' => '#fecaca',
            ],
        ],
        'branches' => [
            '부천 옥길동 본원' => [
                'address' => '경기도 부천시 옥길로 113 상림프라자 5층 리드마스터 학원',
                'phone' => '010-3368-7873',
                'directors' => '김민희 원장 (중·고등) · 송세훈 부원장 (초등)',
            ],
            '춘천 우두동 본점' => [
                'address' => '강원특별자치도 춘천시 우두동 리드마스터 학원 본점',
                'phone' => '010-3368-7873',
                'directors' => '김민희 원장 · 송세훈 부원장',
            ],
        ],
        'grades' => [
            '초등' => ['초1~2', '초3', '초4', '초5', '초6'],
            '중등' => ['중1', '중2', '중3'],
            '고등' => ['고1', '고2', '고3'],
        ],
        'topics' => [
            'consultation' => [
                '초·중등 원서 리딩 클럽 상담 (Magic Tree House ~ Newbery)',
                '초등 파닉스 & 기초 리딩 입문 상담 (ORT / Sight Words)',
                '중등 내신 & 구문 독해(Syntax) 집중 상담',
                '고등 수능 1등급 학술 비문학 & 입시 로드맵 상담',
                'NIE 영자신문 & Critical Essay 라이팅 첨삭 상담',
            ],
            'leveltest' => [
                '온라인/방문 통합 무료 레벨테스트 (어휘·구문·추론 + AR 지수 진단)',
                '초등 파닉스·원서 리딩 배치 고사 (AR 1.5 ~ 4.5)',
                '중·고등 내신/수능 구문·비문학 진단 고사 (Lexile 700L ~ 1250L)',
            ],
            'briefing' => [
                '초·중등 학부모 간담회 — 원서 다독에서 내신 서술형까지 (저녁 7시)',
                '중·고등 학부모 간담회 — 수능 1등급 독해 루틴 & 입시 전략 (저녁 7시)',
                '수시 개별 간담회 요청 (원장 1:1 브리핑)',
            ],
        ],
        'times' => [
            '평일 오후 (14:00 ~ 18:00)',
            '평일 저녁 (18:00 ~ 21:00)',
            '토요일 오전/오후 (10:00 ~ 16:00)',
            '간담회 정규 일정 (저녁 19:00 ~ 20:00)',
            '수시 (담당자 확인 후 전화 조율)',
        ],
    ];
}

/**
 * Create a new consultation/booking lead in Moodle's DB.
 */
function readmaster_booking_create_lead(array $data): stdClass {
    global $DB, $USER;

    readmaster_booking_ensure_schema();

    $now = time();
    $randomsuffix = str_pad((string)random_int(100, 999), 3, '0', STR_PAD_LEFT);
    $bookingcode = 'RM-' . date('ymd', $now) . '-' . $randomsuffix;

    $record = new stdClass();
    $record->booking_code = $bookingcode;
    $record->booking_type = clean_param($data['booking_type'] ?? 'consultation', PARAM_ALPHA);
    if (!in_array($record->booking_type, ['consultation', 'leveltest', 'briefing'], true)) {
        $record->booking_type = 'consultation';
    }
    $record->parent_name = trim(clean_param($data['parent_name'] ?? '', PARAM_TEXT));
    $record->student_name = trim(clean_param($data['student_name'] ?? '', PARAM_TEXT));
    $record->phone = readmaster_booking_format_phone(clean_param($data['phone'] ?? '', PARAM_TEXT));
    $record->grade = trim(clean_param($data['grade'] ?? '', PARAM_TEXT));
    $record->branch = trim(clean_param($data['branch'] ?? '부천 옥길동 본원', PARAM_TEXT));
    $record->session_topic = trim(clean_param($data['session_topic'] ?? '', PARAM_TEXT));
    $record->preferred_time = trim(clean_param($data['preferred_time'] ?? '수시 (담당자 확인 후 전화 조율)', PARAM_TEXT));
    $record->concern = trim(clean_param($data['concern'] ?? '', PARAM_TEXT));
    $record->status = 'new';
    $record->admin_memo = '';
    $record->source = trim(clean_param($data['source'] ?? '통합 상담 예약 페이지', PARAM_TEXT));
    $record->userid = (!empty($USER->id) && !isguestuser()) ? (int)$USER->id : 0;
    $record->timecreated = $now;
    $record->timemodified = $now;

    $id = $DB->insert_record('readmaster_leads', $record);
    $record->id = $id;
    return $record;
}

/**
 * Check if current user can manage bookings (Admin, Manager, or Teacher).
 */
function readmaster_booking_can_manage(): bool {
    global $USER, $DB;

    if (!isloggedin() || isguestuser()) {
        return false;
    }
    if (is_siteadmin()) {
        return true;
    }
    $sysctx = context_system::instance();
    if (has_capability('moodle/site:config', $sysctx) || has_capability('moodle/category:manage', $sysctx)) {
        return true;
    }
    // Check if user has editingteacher or teacher role in any context.
    $sql = "SELECT COUNT(ra.id)
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = :userid
               AND r.shortname IN ('manager', 'editingteacher', 'teacher')";
    return $DB->count_records_sql($sql, ['userid' => $USER->id]) > 0;
}

/**
 * Fetch aggregate KPI statistics from mdl_readmaster_leads.
 */
function readmaster_booking_get_stats(string $branch = ''): array {
    global $DB;
    readmaster_booking_ensure_schema();

    $params = [];
    $where = '1=1';
    if ($branch !== '') {
        $where .= ' AND branch = :branch';
        $params['branch'] = $branch;
    }

    $records = $DB->get_records_select('readmaster_leads', $where, $params);
    $stats = [
        'total' => 0,
        'new' => 0,
        'contacted' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'consultation' => 0,
        'leveltest' => 0,
        'briefing' => 0,
    ];

    foreach ($records as $r) {
        $stats['total']++;
        if (isset($stats[$r->status])) {
            $stats[$r->status]++;
        }
        if (isset($stats[$r->booking_type])) {
            $stats[$r->booking_type]++;
        }
    }

    return $stats;
}
