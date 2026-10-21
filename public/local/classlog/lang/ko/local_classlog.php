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
 * Korean language strings.
 *
 * @package    local_classlog
 * @copyright  2026 ReadMaster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
$string['sessiondeleted'] = '수업을 삭제했습니다.';
$string['sessions'] = '수업일지';
$string['sessiontime'] = '수업 일시';
$string['show'] = '보기';
$string['status_absent'] = '결석';
$string['status_excused'] = '사유결석';
$string['status_late'] = '지각';
$string['status_present'] = '출석';
