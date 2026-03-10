# Custom Post List View 플러그인 개발 및 분석 문서

## 1. 개요 (Overview)
`Custom Post List View`는 워드프레스 카테고리별 최신 글 목록을 바둑판(Grid) 또는 리스트(List) 형태로 보여주는 숏코드를 생성하는 플러그인입니다. 관리자 페이지에서 다양한 설정(디자인, 표시 옵션, SEO 요약 연동 등)을 시각적으로 구성하고, 생성된 숏코드를 통해 원하는 위치에 포스트 목록을 쉽게 삽입할 수 있습니다.

- **요구 환경:** PHP 8.0 이상, WordPress
- **Text Domain:** `wp-postlist-view`

## 2. 플러그인 구조 (Architecture & Structure)
플러그인은 네임스페이스(`WpPostlistView`)와 PSR-4 기반의 단순화된 오토로더를 사용하여 객체 지향적으로 구성되어 있습니다.

- `wp-postlist-view.php`: 플러그인 메인 파일. 오토로더 설정, 클래스 초기화 및 프론트엔드 스타일 큐(enqueue).
- `css/postlist-style.css`: 프론트엔드에서 사용되는 스타일시트. CSS 변수를 활용하여 동적 스타일링 지원.
- `includes/Admin.php`: 관리자 화면(Custom Post Type, Meta Box) 구현 및 데이터 저장 처리.
- `includes/Render.php`: 프론트엔드 숏코드(`[plv_view]`) 렌더링 및 WP_Query 처리 로직.

## 3. 주요 기능 및 요건 (Key Features & Requirements)

### 3.1 관리자 기능 (Admin.php)
- **커스텀 포스트 타입 (CPT):** `plv_view`라는 비공개(Public: false) 포스트 타입을 등록하여 숏코드 설정 단위로 관리. 관리자(`manage_options` 권한)만 접근 가능.
- **숏코드 칼럼 제공:** 포스트 리스트 목록 화면에서 즉시 사용할 수 있는 숏코드(`[plv_view id="POST_ID"]`) 칼럼을 제공.
- **메타 박스 설정 (Meta Boxes):**
  - **대상 카테고리:** 특정 카테고리 또는 전체 글 선택 (`wp_dropdown_categories` 사용).
  - **출력 개수:** 5~20개 제한.
  - **레이아웃 스타일:** 바둑판형(Grid), 이미지 좌측형(List).
  - **썸네일 크기:** 100x100, 200x200, 300x300.
  - **요약 글자수:** 멀티바이트(한글) 대응 (10~300자).
  - **SEO 플러그인 연동:** Slim SEO, Yoast SEO, Rank Math의 메타 디스크립션을 요약글로 우선 사용.
  - **글제목 스타일:** 크기, 색상, 굵게(Bold) 여부 지정.
  - **표시 옵션:** 썸네일 둥근 모서리(xs, sm, md, lg), 카테고리 표시, 작성일 표시, 페이징(Pagination) 표시, 반응형 이미지 비활성화(선명도 유지용).

### 3.2 프론트엔드 및 렌더링 기능 (Render.php)
- **숏코드 출력:** `[plv_view id="..."]` 숏코드를 통해 설정된 옵션대로 WP_Query를 실행하고 HTML을 렌더링.
- **동적 CSS 변수:** 설정된 썸네일 크기, 제목 스타일 등을 인라인 CSS 변수(`--plv-*`)로 래퍼에 주입하여 스타일시트와 연동.
- **이미지 처리 (Fallback 로직):**
  1. 특성 이미지(Featured Image) 사용.
  2. 반응형 이미지 비활성화 옵션 적용 시 `srcset` 없이 단일 이미지 렌더링.
  3. 특성 이미지가 없을 경우 본문 내 첫 번째 이미지 자동 추출 (`DOMDocument` 활용).
  4. 이미지 추출 실패 시 기본 Fallback 이미지(`images/loading.jpg`) 노출.
- **요약문 처리:**
  - 선택한 SEO 플러그인의 메타 디스크립션을 최우선으로 가져옴.
  - 없을 경우 워드프레스 기본 요약문(`the_excerpt`) 또는 본문(`the_content`) 사용.
  - HTML 태그 제거(`wp_strip_all_tags`) 후 `mb_strimwidth`를 사용하여 한글 깨짐 없이 지정된 글자수로 자름.
- **페이징 처리:** `show_pagination` 옵션 활성화 시 숏코드 하단에 페이지네이션을 표시합니다. 여러 숏코드가 한 화면에 출력되는 충돌을 방지하기 위해 각 숏코드 ID마다 고유한 파라미터(예: `?plv_page_ID=2`)를 생성하여 동작합니다.

### 3.3 스타일링 (postlist-style.css)
- **Grid Layout:** `display: grid` 및 `auto-fill`을 사용하여 반응형 바둑판 배열.
- **List Layout:** `display: flex`를 사용하여 좌측 썸네일, 우측 컨텐츠 구조 생성.
- **모바일 반응형:** 768px 이하 모바일 환경에서는 Grid/List 모두 1열 구조(Stack)로 변경되며, 썸네일 비율을 1:1로 유지하도록 패딩 해킹(`padding-top: 100%`) 적용.
- **애니메이션 및 효과:** Hover 시 부드러운 이동(transform) 및 그림자(box-shadow) 효과 적용.
- **페이징 스타일:** `.plv-pagination` 클래스를 활용하여 Flexbox 기반으로 중앙 정렬 및 활성/호버 상태 디자인 구성.

## 4. 보안 및 성능 고려사항 (Security & Performance)
- **직접 접근 차단:** `if ( ! defined( 'ABSPATH' ) ) exit;` 구문을 모든 파일에 적용.
- **권한 제어:** CPT 등록 및 메타 박스 저장 시 `current_user_can( 'manage_options' )` 검증으로 관리자만 수정 가능.
- **CSRF 방어:** 설정 저장 시 Nonce 검증(`wp_verify_nonce`) 적용.
- **데이터 정제 (Sanitization):** 사용자 입력값 저장 및 출력 시 `sanitize_text_field`, `absint`, `esc_url_raw`, `esc_attr`, `esc_html` 등을 철저히 적용하여 XSS 등 취약점 방지.
- **안전한 XML 파싱:** 본문 이미지 추출 시 `DOMDocument` 경고 메시지를 억제하고 내부 에러 처리를 통해 안정성 확보 (UTF-8 선언 포함).

## 5. 요약 및 개발 진행 현황
현재 플러그인은 핵심 기능이 모두 구현되어 있으며, PHP 8.0 호환성 및 워드프레스 코딩 스탠다드, 보안 가이드라인(SAST, DevSecOps 관련)을 준수하고 있습니다. 이 문서는 현재 코드베이스의 구조와 기능을 명세한 것으로, 향후 기능 추가나 유지보수 시 기준으로 활용될 수 있습니다.
