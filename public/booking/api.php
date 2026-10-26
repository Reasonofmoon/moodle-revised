<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * ReadMaster Booking & CRM REST/JSON Endpoint (Moodle Native DB Replacement for GAS Code.gs)
 *
 * @package    core
 * @copyright  2026 ReadMaster Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../config.php');
require_once(__DIR__ . '/lib.php');

header('Content-Type: application/json; charset=utf-8');

readmaster_booking_ensure_schema();

// Support both JSON body (like funnel.html / book.html / briefing.html) and standard form POST.
$rawbody = file_get_contents('php://input');
$jsonpayload = [];
if (!empty($rawbody)) {
    $decoded = json_decode($rawbody, true);
    if (is_array($decoded)) {
        $jsonpayload = $decoded;
    }
}

$action = $_REQUEST['action'] ?? ($jsonpayload['action'] ?? 'create');

try {
    if ($action === 'create') {
        $parentname = trim((string)($jsonpayload['parent_name'] ?? $jsonpayload['parentName'] ?? $_POST['parent_name'] ?? $_POST['parentName'] ?? ''));
        $studentname = trim((string)($jsonpayload['student_name'] ?? $jsonpayload['studentName'] ?? $_POST['student_name'] ?? ''));
        $phone = trim((string)($jsonpayload['phone'] ?? $_POST['phone'] ?? ''));
        $grade = trim((string)($jsonpayload['grade'] ?? $_POST['grade'] ?? ''));
        $branch = trim((string)($jsonpayload['branch'] ?? $_POST['branch'] ?? '부천 옥길동 본원'));
        $bookingtype = trim((string)($jsonpayload['booking_type'] ?? $_POST['booking_type'] ?? 'consultation'));
        $sessiontopic = trim((string)($jsonpayload['session_topic'] ?? $jsonpayload['session'] ?? $jsonpayload['interest'] ?? $_POST['session_topic'] ?? ''));
        $preferredtime = trim((string)($jsonpayload['preferred_time'] ?? $_POST['preferred_time'] ?? '수시 (담당자 확인 후 전화 조율)'));
        $concern = trim((string)($jsonpayload['concern'] ?? $jsonpayload['question'] ?? $_POST['concern'] ?? ''));
        $source = trim((string)($jsonpayload['source'] ?? $_POST['source'] ?? '통합 상담 예약 페이지'));

        if ($parentname === '' || $phone === '' || $grade === '') {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => '학부모 성함, 연락처, 자녀 학년은 필수 입력 항목입니다.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $record = readmaster_booking_create_lead([
            'booking_type' => $bookingtype,
            'parent_name' => $parentname,
            'student_name' => $studentname,
            'phone' => $phone,
            'grade' => $grade,
            'branch' => $branch,
            'session_topic' => $sessiontopic,
            'preferred_time' => $preferredtime,
            'concern' => $concern,
            'source' => $source,
        ]);

        echo json_encode([
            'status' => 'ok',
            'id' => $record->id,
            'booking_code' => $record->booking_code,
            'parent_name' => $record->parent_name,
            'phone' => $record->phone,
            'grade' => $record->grade,
            'branch' => $record->branch,
            'booking_type' => $record->booking_type,
            'session_topic' => $record->session_topic,
            'preferred_time' => $record->preferred_time,
            'created_at' => date('Y-m-d H:i', $record->timecreated),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'lookup') {
        global $DB;
        $phone = readmaster_booking_format_phone(trim((string)($jsonpayload['phone'] ?? $_REQUEST['phone'] ?? '')));
        if ($phone === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => '연락처를 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $records = $DB->get_records('readmaster_leads', ['phone' => $phone], 'timecreated DESC');
        $meta = readmaster_booking_metadata();
        $results = [];
        foreach ($records as $r) {
            $statusinfo = $meta['statuses'][$r->status] ?? $meta['statuses']['new'];
            $typeinfo = $meta['types'][$r->booking_type] ?? $meta['types']['consultation'];
            $results[] = [
                'booking_code' => $r->booking_code,
                'type_label' => $typeinfo['label'],
                'parent_name' => $r->parent_name,
                'student_name' => $r->student_name,
                'grade' => $r->grade,
                'branch' => $r->branch,
                'session_topic' => $r->session_topic,
                'preferred_time' => $r->preferred_time,
                'status' => $r->status,
                'status_label' => $statusinfo['label'],
                'created_at' => date('Y-m-d H:i', $r->timecreated),
            ];
        }

        echo json_encode([
            'status' => 'ok',
            'count' => count($results),
            'entries' => $results,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update_lead') {
        global $DB;
        if (!readmaster_booking_can_manage()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => '관리 권한이 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require_sesskey();

        $id = (int)($jsonpayload['id'] ?? $_POST['id'] ?? 0);
        $status = trim((string)($jsonpayload['status'] ?? $_POST['status'] ?? 'new'));
        $memo = trim((string)($jsonpayload['admin_memo'] ?? $_POST['admin_memo'] ?? ''));

        $record = $DB->get_record('readmaster_leads', ['id' => $id], '*', MUST_EXIST);
        if (in_array($status, ['new', 'contacted', 'confirmed', 'cancelled'], true)) {
            $record->status = $status;
        }
        $record->admin_memo = $memo;
        $record->timemodified = time();
        $DB->update_record('readmaster_leads', $record);

        echo json_encode([
            'status' => 'ok',
            'id' => $record->id,
            'new_status' => $record->status,
            'timemodified' => date('Y-m-d H:i', $record->timemodified),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '알 수 없는 요청입니다.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
