#!/usr/bin/env bash
set -e

echo "================================================================="
echo "  Academy LMS Container Auto-Bootstrap (docker-entrypoint.sh)"
echo "================================================================="

# 1. Ensure /var/www/moodledata directories and permissions
mkdir -p /var/www/moodledata/lang /var/www/moodledata/temp /var/www/moodledata/cache
chown -R www-data:www-data /var/www/moodledata || true

# 2. Auto-install Composer dependencies if vendor/autoload.php is missing
if [ ! -f /var/www/html/vendor/autoload.php ] && [ -f /var/www/html/composer.json ]; then
    echo "[Bootstrap] vendor/autoload.php 미존재 -> Composer 의존성 자동 설치 중..."
    su -s /bin/bash www-data -c "cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction"
fi

# 3. Auto-download Korean (ko) language pack if missing
if [ ! -f /var/www/moodledata/lang/ko/moodle.php ]; then
    echo "[Bootstrap] 한국어 언어팩(ko) 자동 다운로드 및 설치 중..."
    curl -fsSL https://download.moodle.org/download.php/direct/langpack/5.0/ko.zip -o /tmp/ko.zip || true
    if [ -f /tmp/ko.zip ]; then
        unzip -q -o /tmp/ko.zip -d /var/www/moodledata/lang/
        rm -f /tmp/ko.zip
        chown -R www-data:www-data /var/www/moodledata/lang
    fi
fi

# 4. Run Zero-Touch DB & Academy Provisioning
if [ -f /var/www/html/provision_academy.php ]; then
    echo "[Bootstrap] DB 상태 점검 및 학원 맞춤형 자동 프로비저닝 실행..."
    su -s /bin/bash www-data -c "php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json" || echo "[경고] 초기 프로비저닝 스크립트 실행 중 일부 경고가 발생했습니다."
fi

echo "================================================================="
echo "  Academy LMS Ready! Starting Apache Web Server..."
echo "================================================================="

exec apache2-foreground "$@"
