# 학원 맞춤형 올인원 LMS 납품 매뉴얼 & 상품 카탈로그

- **웹 카탈로그 & 설정 생성기 라이브 주소:** `http://localhost:8080/catalog/index.php` (`public/catalog/index.php`)
- **에이전트 자동 구축 스킬:** `.agents/skills/academy-lms-builder/SKILL.md`
- **원클릭 프로비저닝 CLI 스크립트:** `provision_academy.php`
- **학원 설정 JSON 파일:** `academy_config.json` (신규 학원용 템플릿: `academy_config.template.json`)

## 1. 타 학원 납품 시 1줄 실행 명령어

`academy_config.json`의 학원명, 약칭, 슬로건, 지점/연락처, 원장명, 관리자 ID/PW를 수정한 뒤 아래 명령어를 실행하면 전체 LMS가 새 학원 정보로 즉시 프로비저닝됩니다:

```bash
docker exec -u www-data readmaster-moodle-web php /var/www/html/provision_academy.php --config=/var/www/html/academy_config.json
```

## 2. 핵심 접속 URL 목록

1. **메인 학습관 홈:** `http://localhost:8080/`
2. **브랜드 로그인 페이지:** `http://localhost:8080/login/index.php`
3. **5단계 커리큘럼 로드맵:** `http://localhost:8080/course/index.php`
4. **학부모 상담·레벨테스트·간담회 통합 예약:** `http://localhost:8080/booking/index.php`
5. **원장·교사진 전용 예약 관리 CRM:** `http://localhost:8080/booking/admin.php`
6. **B2B 상품 소개 카탈로그 & 설정 생성기:** `http://localhost:8080/catalog/index.php`
