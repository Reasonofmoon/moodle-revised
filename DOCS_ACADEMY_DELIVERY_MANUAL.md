# 학원 맞춤형 올인원 LMS 납품 매뉴얼 & 상품 카탈로그

- **웹 카탈로그 & 설정 생성기 라이브 주소:** `http://localhost:8080/catalog/index.php` (`public/catalog/index.php`)
- **에이전트 자동 구축 스킬:** `.agents/skills/academy-lms-builder/SKILL.md`
- **새 서버 원클릭 DB·인프라 완전 자동화 스크립트:** `init_server.sh`
- **원클릭 프로비저닝 CLI 스크립트:** `provision_academy.php`
- **학원 설정 JSON 파일:** `academy_config.json` (신규 학원용 템플릿: `academy_config.template.json`)

## 1. 완전 초기 상태(빈 서버·빈 DB)에서 1줄 완전 자동 구축

아무것도 설치되지 않은 새 서버이거나 기존 서버에서 다른 학원으로 세팅할 때 아래 **단 한 줄**만 실행하면:
1. 한국어(`ko`) 언어팩 자동 다운로드 및 설치
2. Docker 컨테이너(MariaDB 11.4 + Apache/PHP 8.3) 구동 및 헬스체크
3. `config.php` 미존재 시 자동 생성
4. 빈 DB 감지 시 무들 코어 테이블(`install_database.php`) + 초기 커리큘럼(`setup_readmaster.php`) 자동 설치
5. `academy_config.json` 기반 학원 브랜딩, `Noto Sans KR` 폰트, 관리자 계정, 5단계 커리큘럼, 로그인 좌측 패널, 자체 DB 상담 예약 CRM(`mdl_readmaster_leads`)까지 100% 자동 세팅됩니다.

```bash
./init_server.sh --config=./moodle/academy_config.json --url=http://localhost:8080
```
*(신규 학원 납품 시 기존 샘플 상담 내역을 비우려면 `--reset-leads` 옵션을 추가하세요)*

## 2. 이미 실행 중인 컨테이너에서 학원 설정만 즉시 변경할 때

```bash
docker exec -u www-data readmaster-moodle-web php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json
```

## 3. 핵심 접속 URL 목록

1. **메인 학습관 홈:** `http://localhost:8080/`
2. **브랜드 로그인 페이지:** `http://localhost:8080/login/index.php`
3. **5단계 커리큘럼 로드맵:** `http://localhost:8080/course/index.php`
4. **학부모 상담·레벨테스트·간담회 통합 예약:** `http://localhost:8080/booking/index.php`
5. **원장·교사진 전용 예약 관리 CRM:** `http://localhost:8080/booking/admin.php`
6. **B2B 상품 소개 카탈로그 & 설정 생성기:** `http://localhost:8080/catalog/index.php`
