<?php
namespace WpPostlistView\Includes;

use WP_Query;
use DOMDocument;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Render {

    public static function init(): void {
        add_shortcode( 'plv_view', [ self::class, 'render_shortcode' ] );
    }

    /**
     * 본문에서 첫 번째 이미지 URL 추출 (가이드 요구사항: DOMDocument 사용)
     */
    private static function get_first_image( $post_content ): string {
        $post_content = trim( (string) $post_content );
        if ( empty( $post_content ) ) {
            return '';
        }

        // DOMDocument의 경고 메시지(유효하지 않은 HTML 등) 억제
        $libxml_previous_state = libxml_use_internal_errors( true );
        
        $dom = new DOMDocument();
        // 한글 깨짐 방지를 위해 UTF-8 선언 추가 후 로드
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $post_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        
        libxml_clear_errors();
        libxml_use_internal_errors( $libxml_previous_state );

        $images = $dom->getElementsByTagName( 'img' );
        if ( $images->length > 0 ) {
            $src = $images->item( 0 )->getAttribute( 'src' );
            return esc_url_raw( $src );
        }

        return '';
    }

    /**
     * 숏코드 렌더링 함수
     */
    public static function render_shortcode( $atts ): string {
        $atts = shortcode_atts( [
            'id' => 0,
        ], $atts, 'plv_view' );

        $view_id = absint( $atts['id'] );
        if ( ! $view_id ) {
            return '';
        }

        // 메타 데이터 불러오기
        $category = get_post_meta( $view_id, '_plv_category', true ) ?: '0'; // '0' 은 전체
        $count = get_post_meta( $view_id, '_plv_count', true ) ?: 10;
        $style = get_post_meta( $view_id, '_plv_style', true ) ?: 'grid';
        $excerpt_length = get_post_meta( $view_id, '_plv_excerpt_length', true ) ?: 100;
        $thumb_size = get_post_meta( $view_id, '_plv_thumb_size', true ) ?: '200';
        $show_category = get_post_meta( $view_id, '_plv_show_category', true ) ?: 'no';
        $show_date = get_post_meta( $view_id, '_plv_show_date', true ) ?: 'no';
        $seo_plugin = get_post_meta( $view_id, '_plv_seo_plugin', true ) ?: 'slim_seo';
        $disable_responsive = get_post_meta( $view_id, '_plv_disable_responsive', true ) ?: 'no';
        $thumb_radius = get_post_meta( $view_id, '_plv_thumb_radius', true ) ?: 'md';
        $title_size = get_post_meta( $view_id, '_plv_title_size', true ) ?: '1.25rem';
        $title_color = get_post_meta( $view_id, '_plv_title_color', true ) ?: '#1a202c';
        $title_bold = get_post_meta( $view_id, '_plv_title_bold', true ) ?: 'yes';

        $args = [
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => absint( $count ),
            'ignore_sticky_posts' => 1,
        ];

        // 0이 아닌 경우(특정 카테고리 선택) 카테고리 파라미터 추가
        if ( $category !== '0' ) {
            $args['cat'] = absint( $category );
        }

        $query = new WP_Query( $args );
        ob_start();
        
        $title_weight = $title_bold === 'yes' ? 'bold' : 'normal';
        ?>
        <div class="plv-wrapper plv-style-<?php echo esc_attr( $style ); ?> plv-radius-<?php echo esc_attr( $thumb_radius ); ?>" style="--plv-thumb-size: <?php echo esc_attr( $thumb_size ); ?>px; --plv-title-size: <?php echo esc_attr( $title_size ); ?>; --plv-title-color: <?php echo esc_attr( $title_color ); ?>; --plv-title-weight: <?php echo esc_attr( $title_weight ); ?>;">
            <?php if ( $query->have_posts() ) : ?>
                <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                    <?php
                    $img_html = '';
                    if ( has_post_thumbnail() ) {
                        if ( $disable_responsive === 'yes' ) {
                            // 반응형 비활성화: srcset 없이 단순 img 태그 출력 (항상 large 사이즈 로드)
                            $img_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
                            $img_html = '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( get_the_title() ) . '">';
                        } else {
                            // 기본: 워드프레스 반응형 이미지(srcset, sizes)가 포함된 img 태그 출력
                            $img_html = get_the_post_thumbnail( get_the_ID(), 'large' );
                        }
                    } else {
                        // 특성 이미지가 없으면 본문 첫 이미지 사용 (스마트 미디어 추출)
                        $img_url = self::get_first_image( get_the_content() );
                        
                        if ( empty( $img_url ) ) {
                            // Placeholder Image
                            $img_url = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MDAiIGhlaWdodD0iODAwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJzYW5zLXNlcmlmIiBmb250LXNpemU9IjMyIiBmaWxsPSIjY2VkNGRhIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
                        }
                        $img_html = '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( get_the_title() ) . '">';
                    }

                    // 가이드 요구사항: mb_strimwidth를 사용하여 한글 깨짐 방지 및 정확한 요약문 생성
                    // 모든 태그를 제거한 순수 텍스트 추출 후 글자 수 자르기
                    
                    $seo_desc = '';
                    if ( $seo_plugin === 'slim_seo' ) {
                        $slim_seo_data = get_post_meta( get_the_ID(), 'slim_seo', true );
                        $seo_desc = is_array( $slim_seo_data ) && ! empty( $slim_seo_data['description'] ) ? $slim_seo_data['description'] : '';
                    } elseif ( $seo_plugin === 'yoast_seo' ) {
                        $seo_desc = get_post_meta( get_the_ID(), '_yoast_wpseo_metadesc', true );
                    } elseif ( $seo_plugin === 'rank_math' ) {
                        $seo_desc = get_post_meta( get_the_ID(), 'rank_math_description', true );
                    }

                    // 기본 요약 및 본문
                    $raw_content = wp_strip_all_tags( get_the_content() );
                    $raw_excerpt = has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : $raw_content;
                    
                    if ( ! empty( $seo_desc ) ) {
                        $raw_excerpt = wp_strip_all_tags( $seo_desc );
                    }

                    $excerpt = mb_strimwidth( $raw_excerpt, 0, absint( $excerpt_length ), '...', 'UTF-8' );
                    ?>
                    <div class="plv-item">
                        <div class="plv-thumb">
                            <a href="<?php echo esc_url( get_permalink() ); ?>">
                                <?php echo $img_html; ?>
                            </a>
                        </div>
                        <div class="plv-content">
                            <h3 class="plv-title">
                                <a href="<?php echo esc_url( get_permalink() ); ?>">
                                    <?php echo esc_html( get_the_title() ); ?>
                                </a>
                            </h3>
                            <?php if ( $show_category === 'yes' || $show_date === 'yes' ) : ?>
                                <div class="plv-meta">
                                    <?php if ( $show_category === 'yes' ) : ?>
                                        <span class="plv-category">
                                            <?php
                                            $categories = get_the_category();
                                            if ( ! empty( $categories ) ) {
                                                echo esc_html( $categories[0]->name );
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show_category === 'yes' && $show_date === 'yes' ) : ?>
                                        <span class="plv-meta-sep"> | </span>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show_date === 'yes' ) : ?>
                                        <span class="plv-date"><?php echo esc_html( get_the_date() ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="plv-excerpt">
                                <!-- HTML이 제거된 텍스트이므로 esc_html 사용 -->
                                <p><?php echo esc_html( $excerpt ); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p><?php esc_html_e( '게시물이 없습니다.', 'wp-postlist-view' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
