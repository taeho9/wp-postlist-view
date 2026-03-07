<?php
namespace WpPostlistView\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    public static function init(): void {
        add_action( 'init', [ self::class, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ self::class, 'add_meta_boxes' ] );
        // 설정 저장
        add_action( 'save_post_plv_view', [ self::class, 'save_meta_boxes' ] );

        add_filter( 'manage_plv_view_posts_columns', [ self::class, 'set_custom_columns' ] );
        add_action( 'manage_plv_view_posts_custom_column', [ self::class, 'custom_column_data' ], 10, 2 );
    }

    public static function register_cpt(): void {
        $labels = [
            'name'               => '포스트 리스트',
            'singular_name'      => '포스트 리스트',
            'menu_name'          => '포스트 리스트',
            'add_new'            => '새로 만들기',
            'add_new_item'       => '새 포스트 리스트 추가',
            'edit_item'          => '포스트 리스트 수정',
            'new_item'           => '새 포스트 리스트',
            'view_item'          => '포스트 리스트 보기',
            'search_items'       => '포스트 리스트 검색',
            'not_found'          => '포스트 리스트를 찾을 수 없습니다.',
            'not_found_in_trash' => '휴지통에 포스트 리스트가 없습니다.'
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false, // 프론트엔드 단독 페이지 없음
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-grid-view',
            'supports'            => [ 'title' ],
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            // [DevSecOps] 보안 권한 검증: 관리자(manage_options)만 접근 가능
            'capabilities'        => [
                'edit_post'          => 'manage_options',
                'read_post'          => 'manage_options',
                'delete_post'        => 'manage_options',
                'edit_posts'         => 'manage_options',
                'edit_others_posts'  => 'manage_options',
                'publish_posts'      => 'manage_options',
                'read_private_posts' => 'manage_options',
            ],
        ];

        register_post_type( 'plv_view', $args );
    }

    public static function add_meta_boxes(): void {
        add_meta_box(
            'plv_settings',
            '포스트 리스트 설정',
            [ self::class, 'render_meta_box' ],
            'plv_view',
            'normal',
            'high'
        );
    }

    public static function render_meta_box( \WP_Post $post ): void {
        // [SAST] CSRF 방어 Nonce 필드
        wp_nonce_field( 'plv_save_meta', 'plv_meta_nonce' );

        $category = get_post_meta( $post->ID, '_plv_category', true ) ?: '0'; // '0' 은 전체
        $count = get_post_meta( $post->ID, '_plv_count', true ) ?: 10;
        $style = get_post_meta( $post->ID, '_plv_style', true ) ?: 'grid';
        $excerpt_length = get_post_meta( $post->ID, '_plv_excerpt_length', true ) ?: 100;
        $thumb_size = get_post_meta( $post->ID, '_plv_thumb_size', true ) ?: '200';
        $excerpt_length = get_post_meta( $post->ID, '_plv_excerpt_length', true ) ?: 100;
        $seo_plugin = get_post_meta( $post->ID, '_plv_seo_plugin', true ) ?: 'slim_seo';
        $show_category = get_post_meta( $post->ID, '_plv_show_category', true ) ?: 'no';
        $show_date = get_post_meta( $post->ID, '_plv_show_date', true ) ?: 'no';
        $disable_responsive = get_post_meta( $post->ID, '_plv_disable_responsive', true ) ?: 'no';
        $thumb_radius = get_post_meta( $post->ID, '_plv_thumb_radius', true ) ?: 'md';
        $title_size = get_post_meta( $post->ID, '_plv_title_size', true ) ?: '1.25rem';
        $title_color = get_post_meta( $post->ID, '_plv_title_color', true ) ?: '#1a202c';
        $title_bold = get_post_meta( $post->ID, '_plv_title_bold', true ) ?: 'yes';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="plv_category">대상 카테고리</label></th>
                <td>
                    <?php
                    // 가이드 요구사항: wp_dropdown_categories 활용
                    wp_dropdown_categories( [
                        'show_option_all' => '전체 (메인페이지용)',
                        'hide_empty'      => 0,
                        'name'            => 'plv_category',
                        'id'              => 'plv_category',
                        'selected'        => $category,
                        'hierarchical'    => 1,
                        'class'           => 'postform',
                    ] );
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="plv_count">출력 개수 (5~20)</label></th>
                <td>
                    <!-- 가이드 요구사항: HTML5 min/max 속성 제한 -->
                    <input type="number" name="plv_count" id="plv_count" value="<?php echo esc_attr( $count ); ?>" min="5" max="20" />
                </td>
            </tr>
            <tr>
                <th><label for="plv_style">레이아웃 스타일</label></th>
                <td>
                    <select name="plv_style" id="plv_style">
                        <option value="grid" <?php selected( $style, 'grid' ); ?>>바둑판형(Grid) - 상단 이미지, 하단 요약</option>
                        <option value="list" <?php selected( $style, 'list' ); ?>>이미지 좌측형(List) - 좌측 이미지, 우측 요약</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="plv_thumb_size">썸네일 크기</label></th>
                <td>
                    <select name="plv_thumb_size" id="plv_thumb_size">
                        <option value="100" <?php selected( $thumb_size, '100' ); ?>>100x100</option>
                        <option value="200" <?php selected( $thumb_size, '200' ); ?>>200x200</option>
                        <option value="300" <?php selected( $thumb_size, '300' ); ?>>300x300</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="plv_excerpt_length">요약 글자수 (멀티바이트 대응)</label></th>
                <td>
                    <input type="number" name="plv_excerpt_length" id="plv_excerpt_length" value="<?php echo esc_attr( $excerpt_length ); ?>" min="10" max="300" />
                </td>
            </tr>
            <tr>
                <th><label for="plv_seo_plugin">요약글 우선순위 SEO 플러그인</label></th>
                <td>
                    <select name="plv_seo_plugin" id="plv_seo_plugin">
                        <option value="none" <?php selected( $seo_plugin, 'none' ); ?>>사용 안 함 (기본 요약/본문 사용)</option>
                        <option value="slim_seo" <?php selected( $seo_plugin, 'slim_seo' ); ?>>Slim SEO</option>
                        <option value="yoast_seo" <?php selected( $seo_plugin, 'yoast_seo' ); ?>>Yoast SEO</option>
                        <option value="rank_math" <?php selected( $seo_plugin, 'rank_math' ); ?>>Rank Math</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>글제목 스타일</th>
                <td>
                    <div style="margin-bottom: 8px;">
                        <label for="plv_title_size" style="display:inline-block; margin-right: 10px;">크기:</label>
                        <input type="text" name="plv_title_size" id="plv_title_size" value="<?php echo esc_attr( $title_size ); ?>" placeholder="예: 1.25rem 또는 20px" style="width: 150px;" />
                    </div>
                    <div style="margin-bottom: 8px;">
                        <label for="plv_title_color" style="display:inline-block; margin-right: 10px;">색상:</label>
                        <input type="color" name="plv_title_color" id="plv_title_color" value="<?php echo esc_attr( $title_color ); ?>" />
                    </div>
                    <div>
                        <label>
                            <input type="checkbox" name="plv_title_bold" value="yes" <?php checked( $title_bold, 'yes' ); ?> />
                            굵게 (Bold)
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>표시 옵션</th>
                <td>
                    <div style="margin-bottom: 8px;">
                        <label for="plv_thumb_radius" style="display:inline-block; margin-right: 10px;">썸네일 둥근 모서리:</label>
                        <select name="plv_thumb_radius" id="plv_thumb_radius">
                            <option value="xs" <?php selected( $thumb_radius, 'xs' ); ?>>매우작게</option>
                            <option value="sm" <?php selected( $thumb_radius, 'sm' ); ?>>작게</option>
                            <option value="md" <?php selected( $thumb_radius, 'md' ); ?>>보통</option>
                            <option value="lg" <?php selected( $thumb_radius, 'lg' ); ?>>크게</option>
                        </select>
                    </div>
                    <label>
                        <input type="checkbox" name="plv_show_category" value="yes" <?php checked( $show_category, 'yes' ); ?> />
                        카테고리 표시
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="plv_show_date" value="yes" <?php checked( $show_date, 'yes' ); ?> />
                        작성일 표시
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="plv_disable_responsive" value="yes" <?php checked( $disable_responsive, 'yes' ); ?> />
                        워드프레스 반응형 이미지 비활성화 (썸네일이 흐릿하게 보일 경우 체크하여 선명도 유지)
                    </label>
                </td>
            </tr>
        </table>
        <p class="description">이 설정을 저장한 후, 목록 화면에서 숏코드를 복사하여 페이지나 글에 붙여넣으세요.</p>
        <?php
    }

    public static function save_meta_boxes( int $post_id ): void {
        if ( ! isset( $_POST['plv_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['plv_meta_nonce'] ), 'plv_save_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // [DevSecOps] 권한 검증: manage_options 통과자만 저장 허용
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // 입력값 검증(Validation) 및 정제(Sanitization)
        if ( isset( $_POST['plv_category'] ) ) {
            $cat = sanitize_text_field( wp_unslash( $_POST['plv_category'] ) );
            update_post_meta( $post_id, '_plv_category', $cat );
        }

        if ( isset( $_POST['plv_count'] ) ) {
            $count = max( 5, min( 20, absint( $_POST['plv_count'] ) ) );
            update_post_meta( $post_id, '_plv_count', $count );
        }

        if ( isset( $_POST['plv_style'] ) ) {
            $style = in_array( $_POST['plv_style'], [ 'grid', 'list' ], true ) ? sanitize_text_field( wp_unslash( $_POST['plv_style'] ) ) : 'grid';
            update_post_meta( $post_id, '_plv_style', $style );
        }

        if ( isset( $_POST['plv_thumb_size'] ) ) {
            $thumb_size = in_array( $_POST['plv_thumb_size'], [ '100', '200', '300' ], true ) ? sanitize_text_field( wp_unslash( $_POST['plv_thumb_size'] ) ) : '200';
            update_post_meta( $post_id, '_plv_thumb_size', $thumb_size );
        }

        if ( isset( $_POST['plv_excerpt_length'] ) ) {
            $length = absint( $_POST['plv_excerpt_length'] );
            update_post_meta( $post_id, '_plv_excerpt_length', $length );
        }

        if ( isset( $_POST['plv_seo_plugin'] ) ) {
            $seo_plugin = in_array( $_POST['plv_seo_plugin'], [ 'none', 'slim_seo', 'yoast_seo', 'rank_math' ], true ) ? sanitize_text_field( wp_unslash( $_POST['plv_seo_plugin'] ) ) : 'slim_seo';
            update_post_meta( $post_id, '_plv_seo_plugin', $seo_plugin );
        }

        $show_category = isset( $_POST['plv_show_category'] ) && $_POST['plv_show_category'] === 'yes' ? 'yes' : 'no';
        update_post_meta( $post_id, '_plv_show_category', $show_category );

        $show_date = isset( $_POST['plv_show_date'] ) && $_POST['plv_show_date'] === 'yes' ? 'yes' : 'no';
        update_post_meta( $post_id, '_plv_show_date', $show_date );

        $disable_responsive = isset( $_POST['plv_disable_responsive'] ) && $_POST['plv_disable_responsive'] === 'yes' ? 'yes' : 'no';
        update_post_meta( $post_id, '_plv_disable_responsive', $disable_responsive );

        if ( isset( $_POST['plv_thumb_radius'] ) ) {
            $radius = in_array( $_POST['plv_thumb_radius'], [ 'xs', 'sm', 'md', 'lg' ], true ) ? sanitize_text_field( wp_unslash( $_POST['plv_thumb_radius'] ) ) : 'md';
            update_post_meta( $post_id, '_plv_thumb_radius', $radius );
        }

        if ( isset( $_POST['plv_title_size'] ) ) {
            $title_size = sanitize_text_field( wp_unslash( $_POST['plv_title_size'] ) );
            update_post_meta( $post_id, '_plv_title_size', $title_size );
        }

        if ( isset( $_POST['plv_title_color'] ) ) {
            // hex 색상인지 확인하는 간단한 검증
            $title_color = sanitize_hex_color( wp_unslash( $_POST['plv_title_color'] ) ) ?: '#1a202c';
            update_post_meta( $post_id, '_plv_title_color', $title_color );
        }

        $title_bold = isset( $_POST['plv_title_bold'] ) && $_POST['plv_title_bold'] === 'yes' ? 'yes' : 'no';
        update_post_meta( $post_id, '_plv_title_bold', $title_bold );
    }

    public static function set_custom_columns( array $columns ): array {
        $columns['shortcode'] = '숏코드';
        return $columns;
    }

    public static function custom_column_data( string $column, int $post_id ): void {
        if ( $column === 'shortcode' ) {
            echo '<code>[plv_view id="' . esc_attr( $post_id ) . '"]</code>';
        }
    }
}
