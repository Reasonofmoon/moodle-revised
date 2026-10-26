<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * ReadMaster Academy Consultation & Briefing CRM Dashboard (Moodle Native DB)
 *
 * Replaces Google Sheets & GAS Dashboard.html with a full CRM inside Moodle.
 *
 * @package    core
 * @copyright  2026 ReadMaster Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once(__DIR__ . '/lib.php');

require_login();
readmaster_booking_ensure_schema();

if (!readmaster_booking_can_manage()) {
    throw new moodle_exception('nopermissions', 'error', '', '상담 예약 CRM 관리');
}

$meta = readmaster_booking_metadata();

// Handle POST actions (status/memo update, manual add, or delete).
$action = optional_param('crm_action', '', PARAM_ALPHA);
$flashmsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    require_sesskey();

    if ($action === 'update') {
        $id = required_param('lead_id', PARAM_INT);
        $status = required_param('status', PARAM_ALPHA);
        $memo = optional_param('admin_memo', '', PARAM_TEXT);

        $lead = $DB->get_record('readmaster_leads', ['id' => $id], '*', MUST_EXIST);
        if (isset($meta['statuses'][$status])) {
            $lead->status = $status;
        }
        $lead->admin_memo = trim($memo);
        $lead->timemodified = time();
        $DB->update_record('readmaster_leads', $lead);
        $flashmsg = "접수번호 [{$lead->booking_code}] ({$lead->parent_name}) 상태 및 상담 메모가 저장되었습니다.";
    } else if ($action === 'delete') {
        $id = required_param('lead_id', PARAM_INT);
        $DB->delete_records('readmaster_leads', ['id' => $id]);
        $flashmsg = "선택한 예약 내역이 삭제되었습니다.";
    } else if ($action === 'create_manual') {
        $record = readmaster_booking_create_lead([
            'booking_type' => optional_param('booking_type', 'consultation', PARAM_ALPHA),
            'parent_name' => required_param('parent_name', PARAM_TEXT),
            'student_name' => optional_param('student_name', '', PARAM_TEXT),
            'phone' => required_param('phone', PARAM_TEXT),
            'grade' => required_param('grade', PARAM_TEXT),
            'branch' => optional_param('branch', '부천 옥길동 본원', PARAM_TEXT),
            'session_topic' => optional_param('session_topic', '전화 문의 수기 접수', PARAM_TEXT),
            'preferred_time' => optional_param('preferred_time', '수시', PARAM_TEXT),
            'concern' => optional_param('concern', '', PARAM_TEXT),
            'source' => '원장/교사 전화 수기 등록',
        ]);
        $flashmsg = "신규 전화 상담 예약 [{$record->booking_code}] 이(가) 등록되었습니다.";
    }
}

// Filters
$fbranch = optional_param('branch', '', PARAM_TEXT);
$ftype = optional_param('type', '', PARAM_ALPHA);
$fstatus = optional_param('status', '', PARAM_ALPHA);
$fsearch = trim(optional_param('q', '', PARAM_TEXT));
$export = optional_param('export', '', PARAM_ALPHA);

$where = ['1=1'];
$params = [];

if ($fbranch !== '') {
    $where[] = 'branch = :branch';
    $params['branch'] = $fbranch;
}
if ($ftype !== '') {
    $where[] = 'booking_type = :btype';
    $params['btype'] = $ftype;
}
if ($fstatus !== '') {
    $where[] = 'status = :status';
    $params['status'] = $fstatus;
}
if ($fsearch !== '') {
    $where[] = '(parent_name LIKE :q1 OR student_name LIKE :q2 OR phone LIKE :q3 OR booking_code LIKE :q4 OR concern LIKE :q5)';
    $like = '%' . $DB->sql_like_escape($fsearch) . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
    $params['q4'] = $like;
    $params['q5'] = $like;
}

$leads = $DB->get_records_select('readmaster_leads', implode(' AND ', $where), $params, 'timecreated DESC');

// Handle CSV Export (UTF-8 with BOM for Excel compatibility, replacing Google Sheets)
if ($export === 'csv') {
    $filename = 'readmaster_bookings_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['접수번호', '접수일시', '예약유형', '지점', '학부모성함', '자녀이름', '연락처', '자녀학년', '희망과정', '희망시간', '궁금한점/고민', '처리상태', '원장상담메모']);
    foreach ($leads as $l) {
        $tlabel = $meta['types'][$l->booking_type]['short'] ?? $l->booking_type;
        $slabel = $meta['statuses'][$l->status]['label'] ?? $l->status;
        fputcsv($out, [
            $l->booking_code,
            date('Y-m-d H:i:s', $l->timecreated),
            $tlabel,
            $l->branch,
            $l->parent_name,
            $l->student_name,
            $l->phone,
            $l->grade,
            $l->session_topic,
            $l->preferred_time,
            $l->concern,
            $slabel,
            $l->admin_memo,
        ]);
    }
    fclose($out);
    exit;
}

$stats = readmaster_booking_get_stats($fbranch);

$site = get_site();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/booking/admin.php'));
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('limitedwidth rm-crm-page');
$PAGE->set_title('상담 · 레벨테스트 · 간담회 예약 CRM 관리 | ' . $site->shortname);
$PAGE->set_heading('상담 · 간담회 예약 CRM 관리');

$bookingformurl = (new moodle_url('/booking/index.php'))->out(false);
$csvurl = (new moodle_url('/booking/admin.php', [
    'branch' => $fbranch,
    'type' => $ftype,
    'status' => $fstatus,
    'q' => $fsearch,
    'export' => 'csv',
]))->out(false);

echo $OUTPUT->header();
?>

<style>
.rm-crm-page #page-header {
    display: none !important;
}
.rm-crm-page #region-main {
    border: none !important;
    padding: 0 !important;
    background: transparent !important;
}
.rm-crm-wrap {
    font-family: 'Pretendard', 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0f172a;
    padding-bottom: 3rem;
}

/* Header Banner */
.rm-crm-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border-radius: 18px;
    padding: 2rem 2.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.25rem;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.14);
}
.rm-crm-header h1 {
    font-size: 1.65rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.3rem;
}
.rm-crm-header p {
    font-size: 0.88rem;
    color: #94a3b8;
    margin: 0;
}
.rm-crm-header-btns {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}
.rm-crm-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.65rem 1.1rem;
    border-radius: 10px;
    font-size: 0.86rem;
    font-weight: 700;
    text-decoration: none !important;
    cursor: pointer;
    border: none;
}
.rm-crm-btn.gold {
    background: #f59e0b;
    color: #0f172a !important;
}
.rm-crm-btn.outline {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #ffffff !important;
}

/* KPI Grid */
.rm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.9rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) {
    .rm-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.rm-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}
.rm-kpi-num {
    font-size: 1.85rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 0.25rem;
}
.rm-kpi-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
}

/* Filter Bar */
.rm-filter-bar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
}
.rm-filter-bar select,
.rm-filter-bar input[type="text"] {
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    padding: 0.55rem 0.85rem;
    font-size: 0.86rem;
    color: #0f172a;
    background: #f8fafc;
}
.rm-filter-bar input[type="text"] {
    flex: 1;
    min-width: 220px;
}

/* Lead Cards / Table */
.rm-lead-list {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}
.rm-lead-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.35rem 1.5rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    display: grid;
    grid-template-columns: 1.3fr 1.6fr 1.2fr;
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 980px) {
    .rm-lead-card {
        grid-template-columns: 1fr;
    }
}
.rm-lead-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 0.55rem;
}
.rm-badge {
    font-size: 0.74rem;
    font-weight: 800;
    padding: 0.22rem 0.65rem;
    border-radius: 999px;
}
.rm-lead-name {
    font-size: 1.1rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.rm-lead-meta {
    font-size: 0.84rem;
    color: #475569;
    line-height: 1.6;
}
.rm-lead-concern {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.75rem 0.9rem;
    font-size: 0.83rem;
    color: #334155;
    margin-top: 0.5rem;
    line-height: 1.55;
}
.rm-lead-actions {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.95rem;
}
.rm-lead-actions select,
.rm-lead-actions textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.5rem 0.7rem;
    font-size: 0.83rem;
    background: #ffffff;
    margin-bottom: 0.5rem;
}
.rm-lead-actions textarea {
    min-height: 58px;
    resize: vertical;
}
.rm-save-btn {
    background: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 0.95rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
}
.rm-del-btn {
    background: transparent;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 0.45rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
}
</style>

<div class="rm-crm-wrap">

    <!-- HEADER -->
    <div class="rm-crm-header">
        <div>
            <h1>📋 리드마스터 상담 · 레벨테스트 · 간담회 CRM</h1>
            <p>기존 Google Sheets(Code.gs)를 대체하여 무들 자체 DB(<code>mdl_readmaster_leads</code>)에서 실시간으로 예약을 관리합니다.</p>
        </div>
        <div class="rm-crm-header-btns">
            <button type="button" class="rm-crm-btn gold" onclick="document.getElementById('manualAddBox').style.display = (document.getElementById('manualAddBox').style.display === 'none' ? 'block' : 'none')">
                + 전화 문의 수기 등록
            </button>
            <a href="<?php echo $csvurl; ?>" class="rm-crm-btn outline">📥 엑셀(CSV) 내보내기</a>
            <a href="<?php echo $bookingformurl; ?>" class="rm-crm-btn outline">🔗 학부모 예약 페이지 보기 →</a>
        </div>
    </div>

    <?php if ($flashmsg !== ''): ?>
    <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; padding:0.9rem 1.2rem; border-radius:12px; margin-bottom:1.25rem; font-weight:700; font-size:0.9rem;">
        ✅ <?php echo s($flashmsg); ?>
    </div>
    <?php endif; ?>

    <!-- MANUAL ADD COLLAPSIBLE FORM -->
    <div id="manualAddBox" style="display:none; background:#ffffff; border:2px solid #bfdbfe; border-radius:16px; padding:1.5rem; margin-bottom:1.5rem;">
        <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:0.85rem;">📞 전화/방문 상담 수기 등록</h3>
        <form method="post" action="<?php echo (new moodle_url('/booking/admin.php'))->out(false); ?>" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.75rem; align-items:end;">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <input type="hidden" name="crm_action" value="create_manual">
            <div>
                <label style="font-size:0.78rem; font-weight:700;">예약 유형</label>
                <select name="booking_type" class="form-control">
                    <option value="consultation">1:1 학부모 상담</option>
                    <option value="leveltest">무료 레벨테스트</option>
                    <option value="briefing">학부모 간담회</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700;">지점</label>
                <select name="branch" class="form-control">
                    <option value="부천 옥길동 본원">부천 옥길동 본원</option>
                    <option value="춘천 우두동 본점">춘천 우두동 본점</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700;">학부모 성함 *</label>
                <input type="text" name="parent_name" class="form-control" required placeholder="홍길동">
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700;">자녀 이름</label>
                <input type="text" name="student_name" class="form-control" placeholder="홍지민">
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700;">연락처 *</label>
                <input type="text" name="phone" class="form-control" required placeholder="010-1234-5678">
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700;">학년 *</label>
                <input type="text" name="grade" class="form-control" required placeholder="예: 초5 / 중2">
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700;">희망 과정</label>
                <input type="text" name="session_topic" class="form-control" value="전화 상담 및 레벨테스트 예약">
            </div>
            <div>
                <button type="submit" class="rm-save-btn" style="width:100%; padding:0.62rem;">DB에 즉시 등록</button>
            </div>
        </form>
    </div>

    <!-- KPI ROW -->
    <div class="rm-kpi-grid">
        <div class="rm-kpi-card">
            <div class="rm-kpi-num" style="color:#0f172a;"><?php echo (int)$stats['total']; ?></div>
            <div class="rm-kpi-label">전체 예약 접수</div>
        </div>
        <div class="rm-kpi-card">
            <div class="rm-kpi-num" style="color:#d97706;"><?php echo (int)$stats['new']; ?></div>
            <div class="rm-kpi-label">🆕 신규 대기</div>
        </div>
        <div class="rm-kpi-card">
            <div class="rm-kpi-num" style="color:#2563eb;"><?php echo (int)$stats['contacted']; ?></div>
            <div class="rm-kpi-label">📞 1차 연락 완료</div>
        </div>
        <div class="rm-kpi-card">
            <div class="rm-kpi-num" style="color:#059669;"><?php echo (int)$stats['confirmed']; ?></div>
            <div class="rm-kpi-label">✅ 상담/간담회 확정</div>
        </div>
        <div class="rm-kpi-card">
            <div class="rm-kpi-num" style="color:#dc2626;"><?php echo (int)$stats['cancelled']; ?></div>
            <div class="rm-kpi-label">❌ 예약 취소</div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <form method="get" action="<?php echo (new moodle_url('/booking/admin.php'))->out(false); ?>" class="rm-filter-bar">
        <select name="branch" onchange="this.form.submit()">
            <option value="">전체 지점</option>
            <?php foreach (array_keys($meta['branches']) as $bname): ?>
            <option value="<?php echo s($bname); ?>" <?php echo ($fbranch === $bname) ? 'selected' : ''; ?>>
                <?php echo s($bname); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="type" onchange="this.form.submit()">
            <option value="">전체 예약 유형</option>
            <?php foreach ($meta['types'] as $tkey => $tinfo): ?>
            <option value="<?php echo s($tkey); ?>" <?php echo ($ftype === $tkey) ? 'selected' : ''; ?>>
                <?php echo s($tinfo['icon'] . ' ' . $tinfo['short']); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="status" onchange="this.form.submit()">
            <option value="">전체 처리 상태</option>
            <?php foreach ($meta['statuses'] as $skey => $sinfo): ?>
            <option value="<?php echo s($skey); ?>" <?php echo ($fstatus === $skey) ? 'selected' : ''; ?>>
                <?php echo s($sinfo['label']); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" value="<?php echo s($fsearch); ?>" placeholder="학부모 성함, 자녀 이름, 연락처, 접수번호 검색...">
        <button type="submit" class="rm-save-btn">검색</button>
        <?php if ($fbranch !== '' || $ftype !== '' || $fstatus !== '' || $fsearch !== ''): ?>
        <a href="<?php echo (new moodle_url('/booking/admin.php'))->out(false); ?>" style="font-size:0.82rem; color:#64748b; text-decoration:none; font-weight:700;">초기화</a>
        <?php endif; ?>
    </form>

    <!-- LEAD LIST -->
    <div class="rm-lead-list">
        <?php if (empty($leads)): ?>
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:3rem; text-align:center; color:#64748b;">
            조회 조건에 해당하는 상담/간담회 예약 내역이 없습니다.
        </div>
        <?php else: ?>
            <?php foreach ($leads as $lead):
                $tinfo = $meta['types'][$lead->booking_type] ?? $meta['types']['consultation'];
                $sinfo = $meta['statuses'][$lead->status] ?? $meta['statuses']['new'];
            ?>
            <div class="rm-lead-card">
                <!-- Col 1: Applicant Info -->
                <div>
                    <div class="rm-lead-badges">
                        <span class="rm-badge" style="background:#f1f5f9; color:#334155; font-family:monospace;">
                            <?php echo s($lead->booking_code); ?>
                        </span>
                        <span class="rm-badge" style="background:<?php echo $tinfo['badge_bg']; ?>; color:<?php echo $tinfo['badge_color']; ?>;">
                            <?php echo $tinfo['icon'] . ' ' . s($tinfo['short']); ?>
                        </span>
                        <span class="rm-badge" style="background:<?php echo $sinfo['bg']; ?>; color:<?php echo $sinfo['color']; ?>;">
                            <?php echo s($sinfo['label']); ?>
                        </span>
                    </div>
                    <div class="rm-lead-name">
                        <?php echo s($lead->parent_name); ?>
                        <?php if (!empty($lead->student_name)): ?>
                            <span style="font-size:0.88rem; font-weight:600; color:#475569;">(자녀: <?php echo s($lead->student_name); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="rm-lead-meta">
                        📞 <a href="tel:<?php echo s($lead->phone); ?>" style="font-weight:800; color:#2563eb; text-decoration:none;"><?php echo s($lead->phone); ?></a><br>
                        🎓 학년: <strong><?php echo s($lead->grade); ?></strong> · 🏫 지점: <strong><?php echo s($lead->branch); ?></strong><br>
                        🕒 접수일시: <?php echo date('Y-m-d H:i', $lead->timecreated); ?>
                    </div>
                </div>

                <!-- Col 2: Topic, Preferred Time & Concern -->
                <div>
                    <div style="font-size:0.86rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
                        📌 희망 과정: <?php echo s($lead->session_topic); ?>
                    </div>
                    <div style="font-size:0.82rem; color:#475569; margin-bottom:0.4rem;">
                        ⏰ 희망 시간대: <strong><?php echo s($lead->preferred_time); ?></strong> · 유입경로: <?php echo s($lead->source); ?>
                    </div>
                    <?php if (!empty($lead->concern)): ?>
                    <div class="rm-lead-concern">
                        <strong>💬 학부모 사전 질문 / 자녀 고민:</strong><br>
                        <?php echo nl2br(s($lead->concern)); ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:0.8rem; color:#94a3b8; font-style:italic;">사전 기재된 고민/질문 없음</div>
                    <?php endif; ?>
                </div>

                <!-- Col 3: Status & Admin Memo Update -->
                <div class="rm-lead-actions">
                    <form method="post" action="<?php echo (new moodle_url('/booking/admin.php'))->out(false); ?>">
                        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                        <input type="hidden" name="crm_action" value="update">
                        <input type="hidden" name="lead_id" value="<?php echo (int)$lead->id; ?>">

                        <label style="font-size:0.76rem; font-weight:800; color:#334155; display:block; margin-bottom:0.25rem;">처리 상태 변경</label>
                        <select name="status">
                            <?php foreach ($meta['statuses'] as $skey => $st): ?>
                            <option value="<?php echo s($skey); ?>" <?php echo ($lead->status === $skey) ? 'selected' : ''; ?>>
                                <?php echo s($st['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <label style="font-size:0.76rem; font-weight:800; color:#334155; display:block; margin-bottom:0.25rem;">원장 / 교사 상담 메모</label>
                        <textarea name="admin_memo" placeholder="통화 내용, 방문 확정 일시, 배정 예정 반 등을 기록하세요."><?php echo s($lead->admin_memo); ?></textarea>

                        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
                            <button type="submit" class="rm-save-btn">💾 상태·메모 저장</button>
                            <button type="submit" name="crm_action" value="delete" class="rm-del-btn"
                                    onclick="return confirm('이 예약 내역을 삭제하시겠습니까?');">삭제</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php
echo $OUTPUT->footer();
