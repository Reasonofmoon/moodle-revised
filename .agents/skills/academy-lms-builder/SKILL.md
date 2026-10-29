---
name: academy-lms-builder
description: >-
  Turnkey B2B Academy Moodle LMS provisioning and customization workflow.
  Use this skill whenever the user asks to customize, clone, or deliver the
  ReadMaster-style Moodle LMS for another academy (e.g., entering academy name,
  subject, branches, directors, admin credentials, or curriculum to automatically
  build a branded Korean Academy LMS).
---

# Academy LMS Turnkey Builder Skill (`academy-lms-builder`)

이 스킬은 **리드마스터(ReadMaster) 학원형 Moodle LMS 패키지**를 새로운 학원(영어·수학·국어·논술·입시 등)에 납품할 때, **7가지 핵심 정보**만 입력받아 단 몇 분 만에 브랜드 UI, 로그인 배경 실사 이미지, 5단계 커리큘럼 로드맵, 무들 자체 DB 기반 통합 상담·간담회 예약 CRM까지 완벽하게 세팅하는 자동화 워크플로우입니다.

---

## 1. 신규 학원 납품용 필수 입력 정보 (7대 항목)

사용자가 새 학원 세팅을 요청할 때 아래 정보 중 누락된 핵심 항목이 있으면 확인하거나 기본값을 제안합니다:

1. **학원 기본 정보**: 정식 학원명(`fullname`), 약칭(`shortname`), 한 줄 슬로건(`tagline`)
2. **전문 과목 / 교육 분야**: 예) 영어 원서·독해, 중고등 수학·과학, 국어·독서논술, 종합 입시학원
3. **지점 및 연락처**: 지점명(`name`), 주소(`address`), 대표 전화번호(`phone`)
4. **원장·부원장 정보**: 성함(`name`), 담당 부서/역할(`role`), 직통 연락처(`phone`)
5. **관리자 계정**: 관리자 아이디(`admin_user`) 및 초기 비밀번호(`admin_pass`)
6. **브랜드 컬러 & 로그인 배경 컨셉**: 메인 컬러(`primary_color`, 기본 `#1E3A8A`), 포인트 컬러(`accent_color`, 기본 `#F59E0B`), 로그인 좌측 실사 배경 테마(예: 원서 도서관, 프리미엄 스터디 카페, 클래식 강의실)
7. **단계별 대표 커리큘럼 & 상담 항목**: 5단계 커리큘럼 트랙 및 상담/레벨테스트/간담회 선택 주제

---

## 2. 자동 구축 실행 단계 (Step-by-Step Runbook)

### Step 1: `academy_config.json` 작성
템플릿 파일([academy_config.template.json](file:///home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/academy_config.template.json))을 참고하여 입력받은 학원 정보를 [academy_config.json](file:///home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/academy_config.json)에 저장합니다.

### Step 2: 로그인 페이지 전용 실사 배경 이미지 생성 (`image-generator` 서브에이전트)
학원의 과목 특성에 맞는 고해상도 실사 인테리어 사진(예: 원서 도서관, 수학·과학 스터디 라운지, 프리미엄 독서실)을 `image-generator` 서브에이전트로 생성한 뒤 아래 두 경로에 복사합니다:
- `/home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/public/theme/boost/pix/login_background.jpg`
- `/home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/public/theme/readmaster/pix/login_background.jpg`

또한 [login_panel.mustache](file:///home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/public/theme/boost/templates/core/login_panel.mustache)의 좌측 소개 배지·학원명·설명을 해당 학원 정보에 맞게 업데이트합니다.

### Step 3: 원클릭 프로비저닝 스크립트 실행 (`provision_academy.php`)
Docker 컨테이너 내부에서 자동 세팅 엔진([provision_academy.php](file:///home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/provision_academy.php))을 실행합니다:

```bash
docker exec -u www-data readmaster-moodle-web php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json
```

### Step 4: 커리큘럼(`public/course/index.php`) 및 강좌 데이터 맞춤화 (선택)
학원의 과목이 영어독해 외 다른 과목(수학, 국어논술 등)인 경우:
- [public/course/index.php](file:///home/reasonofmoon/claude-project/tutorial-agy/moodle/moodle/public/course/index.php)의 `$trackmeta` 및 `$coursebadges` 배열을 해당 과목의 5단계 트랙에 맞게 업데이트합니다.
- DB의 `mdl_course_categories` 및 `mdl_course` 강좌명/요약을 해당 학원 커리큘럼으로 갱신하고 `purge_all_caches()`를 실행합니다.

### Step 5: 동작 검증 (Verification)
아래 핵심 페이지들이 모두 HTTP 200을 반환하고 한글 UI 및 `Noto Sans KR` 폰트가 정상 출력되는지 확인합니다:
1. 메인 학습관 홈: `http://localhost:8080/`
2. 브랜드 로그인 페이지: `http://localhost:8080/login/index.php`
3. 5단계 커리큘럼 로드맵: `http://localhost:8080/course/index.php`
4. 학부모 통합 상담·레벨테스트·간담회 예약: `http://localhost:8080/booking/index.php`
5. 원장·교사진 전용 예약 관리 CRM: `http://localhost:8080/booking/admin.php`
6. B2B 솔루션 소개 카탈로그: `http://localhost:8080/catalog/index.php`
