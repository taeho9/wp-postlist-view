<?php
/**
 * Plugin Name: Custom Post List View
 * Description: 카테고리별 최신 글 목록을 바둑판(카드) 또는 리스트 형태로 보여주는 숏코드 생성 플러그인
 * Version: 1.0.0
 * Author: taeho@blogger.pe.kr
 * Text Domain: wp-postlist-view
 * Requires PHP: 8.0
 */

namespace WpPostlistView;

// [SAST] 직접적인 파일 접근 차단 (보안)
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PLV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader 구현 (PSR-4 기반 단순화 버전)
 * 클래스가 요청될 때 자동으로 해당하는 파일을 찾아 인클루드 합니다.
 */
spl_autoload_register( function ( string $class ) {
    // 프로젝트의 네임스페이스 프리픽스
    $prefix = __NAMESPACE__ . '\\';
    
    // 네임스페이스 프리픽스에 해당하는 기본 디렉토리
    $base_dir = PLV_PLUGIN_DIR;
    
    // 요청된 클래스가 이 플러그인의 프리픽스를 사용하는지 확인
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    
    // 프리픽스를 제외한 상대 클래스 이름 가져오기
    $relative_class = substr( $class, $len );
    
    $file_path = str_replace( '\\', '/', $relative_class );
    
    // Linux 등 대소문자를 구분하는 환경을 위해 Includes 네임스페이스를 includes 폴더로 매핑
    if ( strpos( $file_path, 'Includes/' ) === 0 ) {
        $file_path = 'includes/' . substr( $file_path, 9 );
    }
    
    // 네임스페이스 구분자(\)를 디렉토리 구분자(/)로 바꾸고 .php 확장자 추가
    $file = $base_dir . $file_path . '.php';
    
    // 파일이 존재하면 불러오기
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * 플러그인 초기화 함수
 */
function init(): void {
    Includes\Admin::init();
    Includes\Render::init();
}
// 플러그인 로드 후 객체 초기화 실행
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

/**
 * 프론트엔드 스타일 로드
 */
function enqueue_styles(): void {
    wp_enqueue_style( 'plv-style', PLV_PLUGIN_URL . 'css/postlist-style.css', [], '1.0.0' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_styles' );
