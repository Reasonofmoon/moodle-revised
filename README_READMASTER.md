# 리드마스터 영어독해·원서리딩 학원 (ReadMaster Academy) Moodle

리드마스터 영어독해·원서리딩 전문학원을 위해 커스터마이징된 Moodle LMS 저장소입니다.

## 주요 구성
- `docker/Dockerfile` & `docker-compose.yml`: PHP 8.3 + Apache (`/var/www/html/public` 라우터 설정) + MariaDB 11.4 실행 환경
- `setup_readmaster.php`: 리드마스터 학원 초기 세팅 자동화 CLI 스크립트
  - 한국어 언어팩(`ko`) 및 타임존(`Asia/Seoul`) 설정
  - 리드마스터 학원 전용 메인 히어로 배너 및 Boost 테마 스타일링
  - 5대 정규 커리큘럼 카테고리 및 대표 강좌 5개(신입생 레벨 테스트 퀴즈 포함) 자동 생성
  - 샘플 강사(`teacher1`, `teacher2`) 및 학생(`student1`, `student2`) 계정 생성

## 빠른 실행 가이드

```bash
# 1. 컨테이너 실행
docker compose up -d --build

# 2. Composer 의존성 설치
docker exec -u www-data readmaster-moodle-web composer install --no-dev --classmap-authoritative

# 3. Moodle CLI 설치
docker exec -u www-data readmaster-moodle-web php admin/cli/install.php \
  --lang=en \
  --wwwroot="http://localhost:8080" \
  --dataroot="/var/www/moodledata" \
  --dbtype=mariadb \
  --dbhost=db \
  --dbname=moodle \
  --dbuser=moodleuser \
  --dbpass=readmaster_db_pass \
  --fullname="리드마스터 영어독해·원서리딩 학원 (ReadMaster Academy)" \
  --shortname="ReadMaster" \
  --adminuser=readmaster \
  --adminpass="darlbit123" \
  --adminemail="admin@readmaster.kr" \
  --supportemail="support@readmaster.kr" \
  --non-interactive \
  --agree-license \
  --allow-unstable

# 4. 리드마스터 학원 커리큘럼 및 레벨 테스트 자동 세팅
docker exec -u www-data readmaster-moodle-web php /var/www/html/setup_readmaster.php
```
