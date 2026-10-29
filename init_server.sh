#!/usr/bin/env bash
# ==============================================================================
#  학원 맞춤형 LMS (Moodle 6.0 + 자체 DB 상담 예약 CRM) 원클릭 완전 자동 구축 스크립트
#  Usage:
#    ./init_server.sh [--config=/path/to/academy_config.json] [--url=http://localhost:8080] [--reset-leads]
# ==============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "${SCRIPT_DIR}/docker-compose.yml" ]; then
    WORKSPACE_DIR="${SCRIPT_DIR}"
    MOODLE_DIR="${SCRIPT_DIR}/moodle"
elif [ -f "${SCRIPT_DIR}/../docker-compose.yml" ]; then
    WORKSPACE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
    MOODLE_DIR="${SCRIPT_DIR}"
else
    WORKSPACE_DIR="${SCRIPT_DIR}"
    MOODLE_DIR="${SCRIPT_DIR}"
fi

CONFIG_FILE="${MOODLE_DIR}/academy_config.json"
SITE_URL="${MOODLE_WWWROOT:-http://localhost:8080}"
RESET_LEADS_FLAG=""

for arg in "$@"; do
    case $arg in
        --config=*)
            CONFIG_FILE="${arg#*=}"
            shift
            ;;
        --url=*)
            SITE_URL="${arg#*=}"
            shift
            ;;
        --reset-leads)
            RESET_LEADS_FLAG="--reset-leads"
            shift
            ;;
        --help|-h)
            echo "사용법: ./init_server.sh [--config=./moodle/academy_config.json] [--url=http://localhost:8080] [--reset-leads]"
            exit 0
            ;;
    esac
done

echo "======================================================================"
echo " 🚀 학원 맞춤형 LMS (DB + 커리큘럼 + 상담 CRM) 완전 자동 구축 시작"
echo "======================================================================"
echo " - 워크스페이스 경로 : ${WORKSPACE_DIR}"
echo " - 설정 파일 경로    : ${CONFIG_FILE}"
echo " - 접속 도메인(URL)  : ${SITE_URL}"
echo "======================================================================"

# 1. moodledata 디렉터리 및 한국어 언어팩 준비
mkdir -p "${WORKSPACE_DIR}/moodledata/lang" "${WORKSPACE_DIR}/moodledata/temp" "${WORKSPACE_DIR}/moodledata/cache"
if [ ! -f "${WORKSPACE_DIR}/moodledata/lang/ko/moodle.php" ]; then
    echo "[1/4] 한국어(ko) 언어팩 다운로드 및 설치 중..."
    curl -fsSL https://download.moodle.org/download.php/direct/langpack/5.0/ko.zip -o /tmp/moodle_ko.zip || true
    if [ -f /tmp/moodle_ko.zip ]; then
        unzip -q -o /tmp/moodle_ko.zip -d "${WORKSPACE_DIR}/moodledata/lang/"
        rm -f /tmp/moodle_ko.zip
    fi
else
    echo "[1/4] 한국어(ko) 언어팩 확인 완료"
fi

# 2. Docker 컨테이너(MariaDB 11.4 + Apache/PHP 8.3) 구동
if [ -f "${WORKSPACE_DIR}/docker-compose.yml" ]; then
    echo "[2/4] Docker 컨테이너(MariaDB 11.4 + Web) 시작 중..."
    cd "${WORKSPACE_DIR}"
    docker compose up -d
else
    echo "[2/4] 실행 중인 readmaster-moodle-web 컨테이너 확인 중..."
fi

# 3. MariaDB 헬스체크 대기
echo "[3/4] 데이터베이스(readmaster-moodle-db) 준비 상태 확인 중..."
for i in {1..30}; do
    if docker exec readmaster-moodle-db healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then
        echo "      -> MariaDB 11.4 정상 연결 확인 완료!"
        break
    fi
    sleep 2
done

# 4. 학원 설정 파일 복사 및 원클릭 프로비저닝 엔진 실행 (빈 DB일 경우 코어 테이블 자동 설치 포함)
if [ "$(realpath "${CONFIG_FILE}")" != "$(realpath "${MOODLE_DIR}/academy_config.json")" ]; then
    cp "${CONFIG_FILE}" "${MOODLE_DIR}/academy_config.json"
fi

echo "[4/4] 학원 맞춤형 DB·커리큘럼·상담 CRM 자동 프로비저닝 실행 중..."
docker exec -e MOODLE_WWWROOT="${SITE_URL}" -u www-data readmaster-moodle-web \
    php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json ${RESET_LEADS_FLAG}

echo ""
echo "======================================================================"
echo " ✅ 학원 맞춤형 LMS 구축 및 DB 세팅이 100% 완료되었습니다!"
echo "======================================================================"
echo " 🌐 메인 홈 화면          : ${SITE_URL}/"
echo " 🔐 로그인 화면           : ${SITE_URL}/login/index.php"
echo " 📚 5단계 학원 커리큘럼   : ${SITE_URL}/course/index.php"
echo " 📅 학부모 상담·예약 페이지: ${SITE_URL}/booking/index.php"
echo " 📋 원장님 예약 관리 CRM  : ${SITE_URL}/booking/admin.php"
echo " 📖 B2B 상품 카탈로그     : ${SITE_URL}/catalog/index.php"
echo "======================================================================"
