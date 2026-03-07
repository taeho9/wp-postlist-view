WordPress 'PostList' 플러그인: 고성능 멀티 인스턴스 뷰 시스템 설계안
1. 개요 및 인프라 환경본 플러그인은 기존의 무거운 'ContentsView'를 대체하여, 사용자가 정의한 여러 개의 포스트 목록 뷰를 생성하고 관리할 수 있는 경량화된 솔루션을 목표로 한다.
서버 환경: Ubuntu Linux, Apache2, PHP-FPM 8.3 (JIT 활성 권장)
개발 표준: PHP 8.3 OOP, PSR-4 오토로딩, 워드프레스 코딩 표준(WPCS) 준수 
보안 원칙: 3단계 보안 모델(Sanitize input, Validate logic, Escape output) 적용 
2. 데이터 구조 및 관리 시스템 (CRUD)
각 'PostList' 설정은 독립적인 인스턴스로 관리되며, 워드프레스의 wp\_options 또는 커스텀 포스트 타입(CPT)을 통해 저장된다.
2.1 관리자 화면 (Admin UI)기능: 새로운 포스트리스트 생성, 기존 설정 수정 및 삭제.
    설정 항목: 대상 카테고리: 전체 최신글 또는 특정 카테고리 선택 (wp\_dropdown\_categories 활용)
    출력 개수: 5개 ~ 20개 범위 (HTML5 min/max 속성으로 제한) 
    레이아웃 스타일: '바둑판형(Grid)' 또는 '이미지 좌측형(List)' 선택.
    요약 글자수: 멀티바이트(한글) 대응 절삭 길이 설정.
    숏코드 생성: 각 인스턴스별로 `` 형태의 고유 코드를 제공하여 페이지/슬러그 주소에 노출.
3. 핵심 엔진 로직 (PHP 8.3 최적화)
3.1 동적 쿼리 및 캐싱
WP\_Query: 숏코드 호출 시 저장된 설정값에 따라 동적으로 글 목록을 호출한다. 
Transients API: 데이터베이스 부하를 줄이기 위해 각 인스턴스 결과를 12시간 동안 캐싱하며, 새 글 작성(save\_post) 시 캐시를 자동 무효화한다. 
3.2 스마트 미디어 추출 
(Image Parsing)로직: 글의 '특성 이미지'가 없을 경우, 본문(get\_the\_content) 내의 첫 번째 <img> 태그를 DOMDocument 클래스를 사용하여 안전하게 파싱하여 추출한다. 
수식:$$\\text{Thumbnail} = \\text{FeaturedImage} \\parallel \\text{ExtractFirstIMG}(\\text{Content})$$
3.3 한글 요약문 처리
PHP 8.3의 mb\_substr 또는 mb\_strimwidth를 사용하여 한글 깨짐 없이 사용자가 지정한 글자수만큼 정확히 요약문을 생성한다.
4. 프론트엔드 레이아웃 설계 (CSS Grid \& Flexbox)
4.1 타입 A: 바둑판 카드형 (Grid Layout)구조: 대표 이미지가 상단, 제목/요약이 하단에 위치하는 카드 형태. CSS: display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;4.2 타입 B: 리스트형 (Flex Layout)구조: 대표 이미지가 왼쪽, 제목/요약이 오른쪽인 수평 배치 형태. CSS: display: flex; align-items: flex-start;를 기본으로 하며, 모바일 환경에서는 flex-direction: column;으로 반응형 대응.
5. DevSecOps 보안 가이드라인
SQL Injection 방어: 모든 데이터베이스 쿼리는 $wpdb->prepare()를 거쳐야 한다. 
XSS 방어: 브라우저 출력 시 esc\_html(), esc\_url(), esc\_attr()을 엄격히 적용한다. 
권한 검증: 관리자 화면 로직은 current\_user\_can('manage\_options')를 통해 인가된 사용자만 접근 가능하도록 제한한다. 

