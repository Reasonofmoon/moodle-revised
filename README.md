# 🎓 학원 맞춤형 올인원 LMS & 상담 예약 CRM 패키지 (ReadMaster Standard Edition)

본 저장소(`moodle-revised`)는 **Moodle 6.0 코어를 한국 학원 환경에 맞게 경량화**하고, **5단계 학원 커리큘럼 포털**, **온라인 레벨테스트(자동 채점·해설)**, **학부모 상담·간담회 통합 예약 퍼널**, **원장님 전용 자체 DB 상담 관리 CRM(`mdl_readmaster_leads`)**, 그리고 **타 학원 1분 납품용 자동 프로비저닝 엔진(`init_server.sh` / `provision_academy.php`)**을 하나로 통합한 턴키(Turnkey) 패키지입니다.

---

## ✨ 핵심 특징

1. **100% 한국어 UI & `Noto Sans KR` 표준 타이포그래피**
   - 로그인 화면, 상단 내비게이션, 5단계 커리큘럼, 상담 예약, 관리자 CRM 등 전 화면 한국어 현지화 및 Google Fonts `Noto Sans KR` 기본 탑재.
2. **원클릭 완전 자동 설치 (`./init_server.sh`)**
   - Collaborator나 신규 서버 배포자가 저장소를 `git clone` 받은 뒤 `./init_server.sh` 한 줄만 실행하면 **Docker 컨테이너 빌드 → MariaDB 11.4 생성 → 한국어 언어팩 설치 → Composer 의존성 설치 → 빈 DB 자동 감지 및 무들 코어 테이블 생성 → 초기 강좌·퀴즈 시딩 → 학원 브랜딩 & 예약 CRM 테이블 생성**까지 전 과정이 무인으로 완료됩니다.
3. **외부 시트 없는 무들 자체 DB 상담 예약 & CRM (`public/booking/`)**
   - 학부모 1:1 상담, 무료 레벨테스트, 학부모 간담회 예약이 무들 자체 MariaDB(`mdl_readmaster_leads` 테이블)에 실시간 저장되며, 연락처 기반 예약 조회 및 원장님 전용 CRM 대시보드(상태 변경, 상담 메모, 엑셀/CSV 다운로드)를 제공합니다.
4. **타 학원 1분 커스터마이징 & 납품 (`academy_config.json`)**
   - `academy_config.json` 파일 하나만 수정하거나 웹 카탈로그(`http://localhost:8080/catalog/index.php`)에서 JSON을 생성해 프로비저닝 스크립트를 돌리면 어느 학원(영어, 수학, 국어·논술, 입시 등)이든 즉시 맞춤 세팅됩니다.

---

## 🛠️ 1. 사전 준비 사항 (Prerequisites)

로컬 PC 또는 서버에 아래 두 가지만 설치되어 있으면 됩니다 (PHP, Apache, MariaDB를 호스트에 직접 설치할 필요가 없습니다).

- **Git** (`git --version`)
- **Docker & Docker Compose v2** (`docker --version`, `docker compose version`)
- **권장 사양**: 여유 메모리(RAM) 4GB 이상, 디스크 여유 공간 5GB 이상, 기본 포트 `8080` 사용 가능 환경

---

## 🚀 2. Collaborator 빠른 설치 가이드 (Zero-Touch 1줄 설치)

아무것도 세팅되지 않은 새 PC나 서버에서 아래 **3줄**을 순서대로 실행하세요:

```bash
# 1) 저장소 클론
git clone https://github.com/Reasonofmoon/moodle-revised.git
cd moodle-revised

# 2) 실행 권한 부여 후 원클릭 완전 자동 구축 실행
chmod +x init_server.sh docker/docker-entrypoint.sh
./init_server.sh
```

### 💡 `./init_server.sh`가 내부에서 자동으로 처리하는 작업
1. **`moodledata/` 디렉터리 생성 및 한국어(`ko`) 언어팩 자동 다운로드**
2. **Docker 컨테이너 구동**:
   - `readmaster-moodle-db` (`mariadb:11.4`, `utf8mb4_unicode_ci`)
   - `readmaster-moodle-web` (`php:8.3-apache`, Moodle 6.0 라우터 및 필수 확장 모듈 완비)
3. **Composer 의존성 자동 설치** (`vendor/autoload.php` 미존재 시 자동 실행)
4. **`config.php` 자동 생성** (`config.php` 미존재 시 DB 연결 정보 및 라우터 설정 자동 기입)
5. **빈 DB 자동 감지 및 코어 설치**:
   - DB에 `mdl_config` 테이블이 없으면 자동으로 `admin/cli/install_database.php`와 `setup_readmaster.php`를 실행해 코어 테이블 + 5단계 커리큘럼 + 온라인 레벨테스트 문항을 생성합니다.
6. **학원 맞춤형 프로비저닝 (`provision_academy.php`)**:
   - `academy_config.json`에 정의된 학원명, 컬러, 폰트, 관리자/강사/학생 계정, 로그인 패널, 로고마크, 상담 예약 테이블(`mdl_readmaster_leads`)을 동기화합니다.

> **다른 포트나 실제 도메인으로 설치하고 싶을 때:**
> ```bash
> MOODLE_PORT=8090 ./init_server.sh --url=http://localhost:8090
> # 또는 실서버 도메인 연결 시:
> ./init_server.sh --url=https://lms.myacademy.kr
> ```

---

## 🌐 3. 주요 접속 주소 (URL) 및 기본 계정 정보

설치가 완료되면 브라우저에서 아래 주소로 즉시 접속할 수 있습니다:

| 구분 | 접속 URL (기본 로컬 기준) | 주요 기능 설명 |
| :--- | :--- | :--- |
| **메인 학습관 홈** | `http://localhost:8080/` | 학원 히어로 배너, 4단계 핵심 학습 프로세스, 공지 및 강좌 진입 |
| **브랜드 로그인 화면** | `http://localhost:8080/login/index.php` | 실사 원서 도서관 배경 + 학원 브랜딩 패널 + 100% 한글 로그인 폼 |
| **5단계 전체 커리큘럼** | `http://localhost:8080/course/index.php` | 학원 5단계 정규 과정 카드 UI 및 강좌 바로가기 |
| **온라인 레벨테스트** | `http://localhost:8080/course/view.php?id=7` | 신입생 영어 독해 진단 평가 (응시 즉시 자동 채점 + 해설 + 반 배정표) |
| **학부모 상담·예약 퍼널** | `http://localhost:8080/booking/index.php` | 1:1 학부모 상담 / 무료 레벨테스트 / 학부모 간담회 예약 및 내 예약 조회 |
| **원장님 예약 관리 CRM** | `http://localhost:8080/booking/admin.php` | 예약 통계 KPI, 검색 필터, 상태 변경, 원장 메모 저장, 전화 문의 수기 등록, CSV 추출 |
| **B2B 상품 카탈로그** | `http://localhost:8080/catalog/index.php` | 타 학원 납품용 상품 소개서 & 실시간 `academy_config.json` 설정 생성기 |

### 🔐 기본 로그인 계정

| 역할 | 아이디 (ID) | 비밀번호 (PW) | 비고 |
| :--- | :--- | :--- | :--- |
| **총괄 관리자 (원장)** | `readmaster` | `darlbit123` | 모든 강좌·사용자·사이트 설정 및 예약 CRM 전체 권한 |
| **담임 강사 1** | `teacher1` | `ReadMaster1!` | 강좌 콘텐츠 편집, 과제 채점, 예약 CRM 관리 권한 |
| **담임 강사 2** | `teacher2` | `ReadMaster1!` | 원서 리딩 클럽 및 에세이 첨삭 강사 계정 |
| **샘플 학생 1** | `student1` | `ReadMaster1!` | 레벨테스트 응시, 강좌 수강, 과제 제출 테스트용 |
| **샘플 학생 2** | `student2` | `ReadMaster1!` | 초등 파닉스·원서 리딩반 수강 학생 테스트용 |

### 🗄️ 데이터베이스(MariaDB) 기본 접속 정보
- **컨테이너명**: `readmaster-moodle-db` (`mariadb:11.4`)
- **DB 호스트 (컨테이너 내부)**: `db` (포트 `3306`)
- **데이터베이스명**: `moodle` (테이블 접두어: `mdl_`, 상담 예약 테이블: `mdl_readmaster_leads`)
- **DB 사용자 / 비밀번호**: `moodleuser` / `readmaster_db_pass` (Root 비밀번호: `readmaster_root_pass`)

---

## 🏢 4. 다른 학원에 납품하거나 커스터마이징하는 방법

새로운 학원(예: 대치 하이엔드 수학학원, 에이플러스 영어학원 등)으로 브랜딩과 정보를 바꾸려면 아래 두 가지 방법 중 편한 방법을 사용하세요.

### 방법 A. 웹 설정 생성기 또는 `academy_config.json` 수정 후 1줄 명령 실행
1. 브라우저에서 `http://localhost:8080/catalog/index.php` 하단의 **실시간 납품 설정 생성기**에 새 학원 정보를 입력해 `academy_config.json`을 생성하거나, 루트 폴더의 `academy_config.json`(또는 `academy_config.template.json`)을 직접 수정합니다.
2. 아래 명령어를 실행하면 3초 만에 사이트 전체가 새 학원으로 전환됩니다:

```bash
# 기존 샘플 상담 내역까지 깨끗하게 비우고 새 학원으로 세팅할 때 (--reset-leads)
./init_server.sh --config=./academy_config.json --reset-leads

# 또는 이미 컨테이너가 떠 있는 상태에서 즉시 반영만 할 때
docker exec -u www-data readmaster-moodle-web php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json --reset-leads
```

### 방법 B. AI 에이전트(Antigravity / Claude) 스킬 활용
본 저장소에는 `.agents/skills/academy-lms-builder/SKILL.md` 스킬과 `AGENTS.md` 지침이 내장되어 있습니다. 에이전트에게 아래와 같이 요청하면 설정 파일 작성부터 배경 이미지 생성, 프로비저닝까지 자동으로 수행합니다:
> *"이번에 납품할 곳은 '대치 프라임 수학학원'이야. 원장은 이정훈 원장(010-1234-5678), 지점은 대치 본원이고 관리자 계정은 primemath / prime1234 로 해서 학원 LMS 전체 세팅해줘."*

---

## 🔧 5. 자주 쓰는 운영 & 유지보수 명령어 (Troubleshooting)

```bash
# 1) 컨테이너 상태 및 실시간 로그 확인
docker compose ps
docker compose logs -f moodle

# 2) 무들 전체 캐시 및 테마 캐시 강제 초기화
docker exec -u www-data readmaster-moodle-web php /var/www/html/admin/cli/purge_caches.php

# 3) 데이터베이스 전체 백업 (SQL 덤프)
docker exec readmaster-moodle-db mariadb-dump -u moodleuser -preadmaster_db_pass moodle > backup_academy_lms.sql

# 4) 데이터베이스 복원
cat backup_academy_lms.sql | docker exec -i readmaster-moodle-db mariadb -u moodleuser -preadmaster_db_pass moodle

# 5) 컨테이너 재시작 / 중지
docker compose restart
docker compose down
```
*(주의: `docker compose down -v`처럼 `-v` 옵션을 주면 DB 볼륨 `db_data`가 초기화되므로, DB를 완전히 새로 밀고 처음부터 재설치할 때만 `-v`를 사용하세요.)*

---

## 📁 6. 주요 디렉터리 및 파일 구조

```text
.
├── init_server.sh                  # [핵심] 원클릭 서버·DB·커리큘럼·CRM 완전 자동 구축 스크립트
├── provision_academy.php           # [핵심] academy_config.json 기반 Zero-Touch DB & UI 프로비저닝 엔진
├── setup_readmaster.php            # 초기 5단계 커리큘럼, 6개 강좌, 레벨테스트 문항 자동 시딩 스크립트
├── academy_config.json             # 현재 적용된 학원 맞춤형 통합 설정 파일
├── academy_config.template.json    # 타 학원 신규 납품용 설정 템플릿
├── docker-compose.yml              # MariaDB 11.4 + PHP 8.3/Apache 컨테이너 오케스트레이션
├── docker/
│   ├── Dockerfile                  # Moodle 6.0 전용 PHP 8.3 + Apache + Composer 이미지 정의
│   └── docker-entrypoint.sh        # 컨테이너 부팅 시 언어팩·Composer·프로비저닝 자동 실행 엔트리포인트
├── public/
│   ├── booking/                    # 자체 DB(mdl_readmaster_leads) 기반 학부모 상담·레벨테스트·간담회 예약 및 CRM
│   │   ├── index.php               # 학부모용 통합 예약 & 내 예약 조회 페이지 (/booking/index.php)
│   │   ├── admin.php               # 원장·강사용 상담 예약 CRM 대시보드 (/booking/admin.php)
│   │   ├── api.php                 # 예약 생성·조회·상태/메모 업데이트 REST API
│   │   └── lib.php                 # mdl_readmaster_leads 스키마 자동 생성 및 비즈니스 로직
│   ├── catalog/
│   │   └── index.php               # B2B 상품 카탈로그 & 실시간 설정 JSON 생성기 (/catalog/index.php)
│   └── theme/
│       ├── boost/                  # 커스텀 학원 로그인 패널, 원서도서관 배경, 커리큘럼 UI 스타일
│       └── readmaster/             # 리드마스터 전용 브랜드 테마
└── .agents/skills/
    └── academy-lms-builder/        # 타 학원 자동 커스터마이징 AI 에이전트 스킬
```
