<?php
if (!defined('ABSPATH')) exit;

/**
 * WooCommerce ????????? - ?????????
 * ????????????????????????????????? metabox ?????
 */
class Tailwindscore_Review_Images_Admin {
    private $options;

    public function __construct() {
        $this->init_options();
        $this->init_hooks();
    }

    private function init_options() {
        $this->options = array(
            'enabled' => Tailwindscore_Review_Images_Settings::is_enabled(),
        );
    }

    private function init_hooks() {
        if (!$this->options['enabled']) {
            // ???????????????????????????????????????
        }
        add_action('admin_menu', array($this, 'register_tools_page'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        add_action('add_meta_boxes_comment', array($this, 'add_comment_images_metabox'));
        add_action('edit_comment', array($this, 'save_comment_images'), 10, 1);

        add_filter('comment_text', array($this, 'append_review_images_to_comment'), 20, 2);
        
        // ?????????????????
        // ????????????????
        add_filter('bulk_actions-edit-comments', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-comments', array($this, 'handle_bulk_actions'), 10, 3);
        
        // WooCommerce???????????????
        // ???admin_enqueue_scripts???????????????????????????????????
        add_action('admin_init', array($this, 'handle_product_reviews_bulk_actions'), 5);
        
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // ??????????
        // ???????? WooCommerce ????????? 'verified' meta ???
        // ??? wc_review_is_from_verified_owner() ??????????????????? true
        // WooCommerce ?????????????????????????????????
        
        // ?????????????author?????????????????????
        add_filter('comment_author', array($this, 'modify_comment_author_column'), 10, 2);
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('The review images feature requires the WooCommerce plugin to work.', 'tailwindscore');
        echo '</p></div>';
    }

    public function register_settings() {
        register_setting('wc_review_images_settings', 'wc_review_images_settings', array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        $sanitized['enabled'] = isset($input['enabled']) ? 1 : 0;
        $sanitized['max_images'] = isset($input['max_images']) ? absint($input['max_images']) : 6;
        if (isset($input['max_file_size'])) {
            $raw_value = $input['max_file_size'];
            $numeric = is_numeric($raw_value) ? (float) $raw_value : 5;
            // ???????????????????????????????????????????????? MB
            if ($numeric > 20) {
                // ???????????????????????????? 20MB ??????????????????????
                $maybe_bytes = (int) $numeric;
                if ($maybe_bytes > 1048576) {
                    $maybe_mb = (int) round($maybe_bytes / 1048576);
                    if ($maybe_mb >= 1 && $maybe_mb <= 20) {
                        $numeric = $maybe_mb;
                    }
                }
            }
            // ????????? MB????????? 1 - 20 MB
            $max_mb = (int) round($numeric);
            if ($max_mb < 1) { $max_mb = 1; }
            if ($max_mb > 20) { $max_mb = 20; }
            $sanitized['max_file_size'] = $max_mb * 1048576;
        } else {
            $sanitized['max_file_size'] = 5 * 1048576;
        }
        $sanitized['max_width'] = isset($input['max_width']) ? absint($input['max_width']) : 1000;
        $sanitized['max_height'] = isset($input['max_height']) ? absint($input['max_height']) : 1000;
        $sanitized['thumbnail_size'] = isset($input['thumbnail_size']) ? sanitize_text_field($input['thumbnail_size']) : 'thumbnail';
        return $sanitized;
    }

    public function admin_enqueue_scripts($hook) {
        // ?????????????????????????????????????????????????
        if (!$this->options['enabled']) {
            return;
        }
        
        // ????????????????????????WooCommerce?????????
        $is_comments_page = ($hook === 'edit-comments.php');
        $is_product_reviews_page = (
            $hook === 'product_page_product-reviews' || 
            ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'product' && isset($_GET['page']) && $_GET['page'] === 'product-reviews')
        );
        
        if ($is_comments_page || $is_product_reviews_page) {
            wp_add_inline_style('wp-admin', '
                .review-images-admin-inline {
                    margin-top: 10px;
                }
                .review-images-admin-inline a {
                    transition: opacity 0.2s ease;
                }
                .review-images-admin-inline a:hover {
                    opacity: 0.8;
                }
                .review-images-admin-inline img {
                    cursor: pointer;
                }
            ');
            
            // ???????????????JavaScript
            wp_enqueue_script(
                'wc-review-images-bulk-js',
                get_template_directory_uri() . '/modules/review-images/assets/admin/review-images-bulk.js',
                array('jquery'),
                TAILWINDSCORE_MODULE_REVIEW_IMAGES_VERSION,
                true
            );
            
            // ??????????????????JavaScript
            $countries = $this->get_country_list();
            wp_localize_script('wc-review-images-bulk-js', 'wcReviewImagesBulk', array(
                'countries' => $countries,
                'select_country' => __( '请选择国家', 'tailwindscore' ),
                'cancel'         => __( '取消', 'tailwindscore' ),
                'confirm'        => __( '确认', 'tailwindscore' ),
                'bulk_set_country_action' => 'wc_bulk_set_country'
            ));
        }
        if ($hook === 'comment.php') {
            wp_enqueue_media();
            wp_enqueue_script(
                'wc-review-images-admin-js',
                get_template_directory_uri() . '/modules/review-images/assets/admin/review-images-admin.js',
                array('jquery'),
                TAILWINDSCORE_MODULE_REVIEW_IMAGES_VERSION,
                true
            );
            wp_localize_script('wc-review-images-admin-js', 'wcReviewImagesAdmin', array(
                'select_images' => __('Select images', 'tailwindscore'),
                'remove' => __('Remove (delete on save)', 'tailwindscore')
            ));
        }
    }

    public function register_tools_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Review images tools', 'tailwindscore' ),
            __( 'Review images', 'tailwindscore' ),
            'manage_woocommerce',
            'ts-review-images-tools',
            array( $this, 'render_tools_page' )
        );
    }

    public function render_tools_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'tailwindscore' ) );
        }
        if ( isset( $_POST['cleanup_temp_review_images'] ) ) {
            check_admin_referer( 'wc_review_images_cleanup', '_wpnonce_cleanup' );
            $deleted = $this->cleanup_unsubmitted_review_images();
            if ( is_wp_error( $deleted ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $deleted->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Deleted %d unlinked review images.', 'tailwindscore' ), (int) $deleted ) ) . '</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Review images tools', 'tailwindscore' ); ?></h1>
            <p><?php esc_html_e( 'Configure review image uploads in Appearance ? Customize ? Review images.', 'tailwindscore' ); ?></p>
            <div class="card" style="max-width:800px;padding:16px;">
                <h2><?php esc_html_e( 'Maintenance', 'tailwindscore' ); ?></h2>
                <p><?php esc_html_e( 'Delete uploaded images that were never attached to a submitted review.', 'tailwindscore' ); ?></p>
                <form method="post">
                    <?php wp_nonce_field( 'wc_review_images_cleanup', '_wpnonce_cleanup' ); ?>
                    <input type="hidden" name="cleanup_temp_review_images" value="1">
                    <?php submit_button( __( 'Delete unlinked review images', 'tailwindscore' ), 'delete' ); ?>
                </form>
            </div>
        </div>
        <?php
    }


    private function cleanup_unsubmitted_review_images() {
        global $wpdb;
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT pm1.post_id
                 FROM {$wpdb->postmeta} pm1
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm1.post_id AND p.post_type = %s
                 LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = pm1.post_id AND pm2.meta_key = %s
                 WHERE pm1.meta_key = %s AND pm1.meta_value = %s AND (pm2.meta_value IS NULL OR pm2.meta_value = '')",
                'attachment', '_review_comment_id', '_review_image', '1'
            )
        );
        if (empty($ids)) {
            return 0;
        }
        $deleted = 0;
        foreach ($ids as $attachment_id) {
            if (wp_delete_attachment((int) $attachment_id, true)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function append_review_images_to_comment($comment_text, $comment) {
        if (!is_admin() || !$comment) {
            return $comment_text;
        }
        if ($comment->comment_type !== 'review') {
            return $comment_text;
        }
        $comment_id = $comment->comment_ID;
        $image_ids = get_comment_meta($comment_id, '_review_images', true);
        if (empty($image_ids) || !is_array($image_ids)) {
            return $comment_text;
        }
        $image_ids = array_filter($image_ids, function($id) {
            if (empty($id) || !is_numeric($id)) {
                return false;
            }
            $attachment = get_post($id);
            return $attachment && wp_attachment_is_image($id);
        });
        if (empty($image_ids)) {
            return $comment_text;
        }
        $image_ids = array_values($image_ids);
        $images_html = '<div class="review-images-admin-inline" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">';
        foreach ($image_ids as $image_id) {
            $thumbnail_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            $full_url = wp_get_attachment_image_url($image_id, 'full');
            if (!$thumbnail_url) {
                $thumbnail_url = $full_url;
            }
            if ($thumbnail_url) {
                $images_html .= '<a href="' . esc_url($full_url ? $full_url : $thumbnail_url) . '" target="_blank" title="Click to view full size" style="display: inline-block; text-decoration: none;">';
                $images_html .= '<img src="' . esc_url($thumbnail_url) . '" alt="Review Image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; display: block;">';
                $images_html .= '</a>';
            }
        }
        $images_html .= '</div>';
        return $comment_text . $images_html;
    }

    public function add_comment_images_metabox() {
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'comment') {
            return;
        }
        $comment_id = isset($_GET['c']) ? absint($_GET['c']) : 0;
        if (!$comment_id) {
            return;
        }
        $comment = get_comment($comment_id);
        if (!$comment || $comment->comment_type !== 'review') {
            return;
        }
        add_meta_box(
            'wc_review_images_metabox',
            __('Review Images', 'tailwindscore'),
            array($this, 'render_comment_images_metabox'),
            null,
            'normal',
            'default'
        );
    }

    public function render_comment_images_metabox($comment) {
        if (!$comment || $comment->comment_type !== 'review') {
            echo '<p>' . esc_html__('Only available for product reviews.', 'tailwindscore') . '</p>';
            return;
        }
        $comment_id = $comment->comment_ID;
        $image_ids = get_comment_meta($comment_id, '_review_images', true);
        if (!is_array($image_ids)) {
            $image_ids = array();
        }
        wp_nonce_field('save_review_images_metabox', '_review_images_metabox_nonce');
        $ids_string = implode(',', array_map('absint', $image_ids));
        
        // ??????
        // ????? WooCommerce ????????? verified meta ???
        $is_verified = (bool) get_comment_meta($comment_id, 'verified', true);
        
        // ????????????????? US
        $country_code = get_comment_meta($comment_id, '_review_country_code', true);
        if (empty($country_code)) {
            $country_code = 'US';
        }
        ?>
        <div id="wc-review-images-admin">
            <p class="description"><?php echo esc_html__('Manage images attached to this review. Removing will delete files on save.', 'tailwindscore'); ?></p>
            <div id="wc-review-images-admin-list" style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($image_ids as $img_id): $thumb = wp_get_attachment_image_url($img_id, 'thumbnail'); ?>
                    <div class="wc-review-image-item" data-id="<?php echo esc_attr($img_id); ?>" style="position:relative;width:80px;height:80px;">
                        <img src="<?php echo esc_url($thumb ? $thumb : wp_get_attachment_image_url($img_id, 'full')); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border:1px solid #ddd;border-radius:4px;">
                        <button type="button" class="button-link delete-review-image" title="<?php echo esc_attr__('Remove (delete on save)', 'tailwindscore'); ?>" style="position:absolute;top:2px;right:4px;color:#a94442;">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p>
                <button type="button" class="button" id="wc-review-images-admin-add"><?php echo esc_html__('Add Images', 'tailwindscore'); ?></button>
            </p>
            <input type="hidden" id="wc-review-images-admin-ids" name="_review_images_ids" value="<?php echo esc_attr($ids_string); ?>">
            
            <hr style="margin: 20px 0;">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="verified_buyer"><?php esc_html_e( '显示认证买家标识', 'tailwindscore' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="verified_buyer" id="verified_buyer" value="1" <?php checked( $is_verified, true ); ?>>
                            <?php esc_html_e( '勾选后，此评论将显示“认证买家”标识', 'tailwindscore' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( '无论用户是否真正购买过产品，都可以手动标记为认证买家。', 'tailwindscore' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="review_country_code"><?php esc_html_e( '国家标识', 'tailwindscore' ); ?></label>
                    </th>
                    <td>
                        <select name="review_country_code" id="review_country_code" class="regular-text">
                            <?php
                            $countries = $this->get_country_list();
                            foreach ($countries as $code => $name) {
                                $selected = selected($country_code, $code, false);
                                echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( '选择评论者所在国家，将在前台显示国旗图标和国家简称。', 'tailwindscore' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function save_comment_images($comment_id) {
        if (!isset($_POST['_review_images_metabox_nonce']) || !wp_verify_nonce($_POST['_review_images_metabox_nonce'], 'save_review_images_metabox')) {
            return;
        }
        if (!current_user_can('edit_comment', $comment_id)) {
            return;
        }
        $comment = get_comment($comment_id);
        if (!$comment || $comment->comment_type !== 'review') {
            return;
        }
        $old_ids = get_comment_meta($comment_id, '_review_images', true);
        if (!is_array($old_ids)) {
            $old_ids = array();
        }
        $new_ids = array();
        if (isset($_POST['_review_images_ids'])) {
            $raw = sanitize_text_field(wp_unslash($_POST['_review_images_ids']));
            if ($raw !== '') {
                $parts = array_map('trim', explode(',', $raw));
                foreach ($parts as $pid) {
                    $aid = absint($pid);
                    if ($aid && get_post_type($aid) === 'attachment' && wp_attachment_is_image($aid)) {
                        $new_ids[] = $aid;
                    }
                }
            }
        }
        $new_ids = array_values(array_unique($new_ids));

        $to_remove = array_diff($old_ids, $new_ids);
        if (!empty($to_remove)) {
            foreach ($to_remove as $rid) {
                $rid = absint($rid);
                if ($rid) {
                    wp_delete_attachment($rid, true);
                }
            }
        }
        $added = array_diff($new_ids, $old_ids);
        if (!empty($added)) {
            foreach ($added as $aid) {
                $aid = absint($aid);
                if ($aid) {
                    update_post_meta($aid, '_review_comment_id', $comment_id);
                    update_post_meta($aid, '_review_image', true);
                }
            }
        }
        if (!empty($new_ids)) {
            update_comment_meta($comment_id, '_review_images', $new_ids);
        } else {
            delete_comment_meta($comment_id, '_review_images');
        }
        
        // ?????????
        // ???? WooCommerce ????????? 'verified' meta ???
        // ??? wc_review_is_from_verified_owner() ????????????????????????
        if (isset($_POST['verified_buyer']) && $_POST['verified_buyer'] == '1') {
            // ?? WooCommerce ????????? verified meta ? 1
            update_comment_meta($comment_id, 'verified', '1');
        } else {
            // ??????????????????? verified meta??? WooCommerce ?????????????
            delete_comment_meta($comment_id, 'verified');
        }
        
        // ?????????
        if (isset($_POST['review_country_code'])) {
            $country_code = sanitize_text_field(wp_unslash($_POST['review_country_code']));
            $countries = $this->get_country_list();
            // ???????????????????
            if (array_key_exists($country_code, $countries)) {
                update_comment_meta($comment_id, '_review_country_code', $country_code);
            } else {
                // ?????????????????????? US
                update_comment_meta($comment_id, '_review_country_code', 'US');
            }
        } else {
            // ?????????????????????? US
            update_comment_meta($comment_id, '_review_country_code', 'US');
        }
    }
    
    /**
     * ????WooCommerce???????????????????????
     * ???????????WooCommerce????????????????????????????????????
     */
    public function handle_product_reviews_bulk_actions() {
        // ????WooCommerce??????????????
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'product' || !isset($_GET['page']) || $_GET['page'] !== 'product-reviews') {
            return;
        }
        
        // ??????????????????????????
        $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';
        if ($action === '-1') {
            $action = isset($_REQUEST['action2']) ? sanitize_text_field(wp_unslash($_REQUEST['action2'])) : '';
        }
        
        // ???????????????????????????????
        if ($action !== 'wc_bulk_verify_buyer' && $action !== 'wc_bulk_unverify_buyer' && $action !== 'wc_bulk_set_country') {
            return;
        }
        
        // ???nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-product-reviews')) {
            return;
        }
        
        // ???????????????ID??WooCommerce????delete_comments[]?????????
        $comment_ids = isset($_REQUEST['delete_comments']) ? array_map('absint', (array) $_REQUEST['delete_comments']) : array();
        if (empty($comment_ids)) {
            return;
        }
        
        // ???????????????
        $redirect_to = admin_url('edit.php?post_type=product&page=product-reviews');
        $redirect_to = $this->handle_bulk_actions($redirect_to, $action, $comment_ids);
        
        // ???????
        wp_safe_redirect($redirect_to);
        exit;
    }
    
    /**
     * ?????????????????????????????
     * @param array $actions ????????????????????
     * @return array ???????????????????????????????????????
     */
    public function add_bulk_actions($actions) {
        $actions['wc_bulk_verify_buyer'] = __( '标记为认证买家', 'tailwindscore' );
        $actions['wc_bulk_unverify_buyer'] = __( '取消认证买家', 'tailwindscore' );
        $actions['wc_bulk_set_country'] = __( '批量设置国家', 'tailwindscore' );
        return $actions;
    }

    /**
     * ??????????????????
     * @param string $redirect_to ???????URL
     * @param string $action ???????????????????
     * @param array $comment_ids ??????????ID?????
     * @return string ???????URL????????????????
     */
    public function handle_bulk_actions($redirect_to, $action, $comment_ids) {
        // ????????????
        if ($action !== 'wc_bulk_verify_buyer' && $action !== 'wc_bulk_unverify_buyer' && $action !== 'wc_bulk_set_country') {
            return $redirect_to;
        }

        // ????????
        if (!current_user_can('moderate_comments')) {
            return $redirect_to;
        }

        // ???nonce?????????????????????WooCommerce???????????
        $nonce_verified = false;
        if (isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-comments')) {
            $nonce_verified = true;
        } elseif (isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-posts')) {
            // WooCommerce?????????????????bulk-posts nonce
            $nonce_verified = true;
        } elseif (isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-product-reviews')) {
            // WooCommerce?????????????bulk-product-reviews nonce
            $nonce_verified = true;
        }
        
        if (!$nonce_verified) {
            return $redirect_to;
        }

        $processed = 0;
        $skipped = 0;

        if ($action === 'wc_bulk_verify_buyer') {
            // ????????????
            foreach ($comment_ids as $comment_id) {
                $comment_id = absint($comment_id);
                if (!$comment_id) {
                    $skipped++;
                    continue;
                }

                $comment = get_comment($comment_id);
                if (!$comment || $comment->comment_type !== 'review') {
                    $skipped++;
                    continue;
                }

                // ??????????
                update_comment_meta($comment_id, 'verified', '1');
                $processed++;
            }

            $redirect_to = add_query_arg(array(
                'wc_bulk_verified' => $processed,
                'wc_bulk_skipped' => $skipped
            ), $redirect_to);

        } elseif ($action === 'wc_bulk_unverify_buyer') {
            // ??????????????
            foreach ($comment_ids as $comment_id) {
                $comment_id = absint($comment_id);
                if (!$comment_id) {
                    $skipped++;
                    continue;
                }

                $comment = get_comment($comment_id);
                if (!$comment || $comment->comment_type !== 'review') {
                    $skipped++;
                    continue;
                }

                // ??????????????
                delete_comment_meta($comment_id, 'verified');
                $processed++;
            }

            $redirect_to = add_query_arg(array(
                'wc_bulk_unverified' => $processed,
                'wc_bulk_skipped' => $skipped
            ), $redirect_to);

        } elseif ($action === 'wc_bulk_set_country') {
            // ????????????
            $country_code = isset($_REQUEST['bulk_country_code']) ? sanitize_text_field(wp_unslash($_REQUEST['bulk_country_code'])) : '';

            // ?????????
            $countries = $this->get_country_list();
            if (empty($country_code) || !array_key_exists($country_code, $countries)) {
                // ???????????????????????????????
                $redirect_to = add_query_arg(array(
                    'wc_bulk_error' => 'invalid_country'
                ), $redirect_to);
                return $redirect_to;
            }

            foreach ($comment_ids as $comment_id) {
                $comment_id = absint($comment_id);
                if (!$comment_id) {
                    $skipped++;
                    continue;
                }

                $comment = get_comment($comment_id);
                if (!$comment || $comment->comment_type !== 'review') {
                    $skipped++;
                    continue;
                }

                // ????????
                update_comment_meta($comment_id, '_review_country_code', $country_code);
                $processed++;
            }

            $redirect_to = add_query_arg(array(
                'wc_bulk_country_set' => $processed,
                'wc_bulk_country_code' => $country_code,
                'wc_bulk_skipped' => $skipped
            ), $redirect_to);
        }

        return $redirect_to;
    }

    /**
     * ???????????????????????
     */
    public function admin_notices() {
        if (isset($_GET['wc_bulk_verified']) || isset($_GET['wc_bulk_skipped'])) {
            $verified = isset($_GET['wc_bulk_verified']) ? absint($_GET['wc_bulk_verified']) : 0;
            $skipped = isset($_GET['wc_bulk_skipped']) ? absint($_GET['wc_bulk_skipped']) : 0;
            
            if ($verified > 0) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo sprintf( esc_html__( '已成功标记 %d 条评论为认证买家。', 'tailwindscore' ), $verified );
                if ( $skipped > 0 ) {
                    echo ' ' . sprintf( esc_html__( '跳过了 %d 条非产品评论。', 'tailwindscore' ), $skipped );
                }
                echo '</p></div>';
            }
        }

        if (isset($_GET['wc_bulk_unverified']) || isset($_GET['wc_bulk_skipped'])) {
            $unverified = isset($_GET['wc_bulk_unverified']) ? absint($_GET['wc_bulk_unverified']) : 0;
            $skipped = isset($_GET['wc_bulk_skipped']) ? absint($_GET['wc_bulk_skipped']) : 0;
            
            if ($unverified > 0) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo sprintf( esc_html__( '已成功取消 %d 条评论的认证买家标识。', 'tailwindscore' ), $unverified );
                if ( $skipped > 0 ) {
                    echo ' ' . sprintf( esc_html__( '跳过了 %d 条非产品评论。', 'tailwindscore' ), $skipped );
                }
                echo '</p></div>';
            }
        }

        if (isset($_GET['wc_bulk_country_set']) || isset($_GET['wc_bulk_error'])) {
            if (isset($_GET['wc_bulk_error']) && $_GET['wc_bulk_error'] === 'invalid_country') {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo esc_html__( '错误：无效的国家代码。', 'tailwindscore' );
                echo '</p></div>';
            } elseif (isset($_GET['wc_bulk_country_set'])) {
                $set = absint($_GET['wc_bulk_country_set']);
                $country_code = isset($_GET['wc_bulk_country_code']) ? sanitize_text_field(wp_unslash($_GET['wc_bulk_country_code'])) : '';
                $skipped = isset($_GET['wc_bulk_skipped']) ? absint($_GET['wc_bulk_skipped']) : 0;
                
                if ($set > 0) {
                    $countries = $this->get_country_list();
                    $country_name = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
                    
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    echo sprintf( esc_html__( '已成功为 %d 条评论设置国家为：%s。', 'tailwindscore' ), $set, esc_html( $country_name ) );
                    if ( $skipped > 0 ) {
                        echo ' ' . sprintf( esc_html__( '跳过了 %d 条非产品评论。', 'tailwindscore' ), $skipped );
                    }
                    echo '</p></div>';
                }
            }
        }
    }

    /**
     * ?????????????
     * @return array ?????? => ??????
     */
    public function get_country_list() {
        return array(
            'US' => __('United States', 'tailwindscore'),
            'CA' => __('Canada', 'tailwindscore'),
            'GB' => __('United Kingdom', 'tailwindscore'),
            'AU' => __('Australia', 'tailwindscore'),
            'CN' => __('China', 'tailwindscore'),
            'JP' => __('Japan', 'tailwindscore'),
            'KR' => __('South Korea', 'tailwindscore'),
            'SG' => __('Singapore', 'tailwindscore'),
            'DE' => __('Germany', 'tailwindscore'),
            'FR' => __('France', 'tailwindscore'),
            'ES' => __('Spain', 'tailwindscore'),
            'IT' => __('Italy', 'tailwindscore'),
            'BR' => __('Brazil', 'tailwindscore'),
            'MX' => __('Mexico', 'tailwindscore'),
            'IN' => __('India', 'tailwindscore'),
            'RU' => __('Russia', 'tailwindscore'),
        );
    }
    
    /**
     * ???????????author??????????????????????????
     * @param string $author ????????????
     * @param int $comment_id ???ID
     * @return string ???????????????????????????????????????
     */
    public function modify_comment_author_column($author, $comment_id = 0) {
        // ????????????
        if (!is_admin()) {
            return $author;
        }
        
        // ?????????comment_id??????????????????????
        if (!$comment_id && isset($GLOBALS['comment']) && is_object($GLOBALS['comment'])) {
            $comment_id = $GLOBALS['comment']->comment_ID;
        }
        
        if (!$comment_id) {
            return $author;
        }
        
        // ???????????????
        $screen = get_current_screen();
        
        // ????????????????????????????????????WooCommerce???????????
        $is_comments_page = false;
        if ($screen) {
            // ??????????
            if ($screen->base === 'edit-comments' || $screen->id === 'edit-comments') {
                $is_comments_page = true;
            }
            // WooCommerce?????????
            if ($screen->id === 'product_page_product-reviews' || 
                ($screen->base === 'edit' && isset($_GET['post_type']) && $_GET['post_type'] === 'product' && isset($_GET['page']) && $_GET['page'] === 'product-reviews')) {
                $is_comments_page = true;
            }
        }
        
        // ?????????????????????????????????????
        if (!$is_comments_page) {
            return $author;
        }
        
        // ??????????
        $comment = get_comment($comment_id);
        if (!$comment || $comment->comment_type !== 'review') {
            return $author;
        }
        
        // ???????????????
        $is_verified = (bool) get_comment_meta($comment_id, 'verified', true);
        
        // ???????????
        $country_code = get_comment_meta($comment_id, '_review_country_code', true);
        $countries = $this->get_country_list();
        $country_name = isset($countries[$country_code]) ? $countries[$country_code] : '';
        
        // ????????????
        $badges = array();
        
        // ????????
        if ($is_verified) {
            $badges[] = '<span style="display: inline-block; background: #46b450; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 5px; vertical-align: middle;" title="' . esc_attr__( '认证买家', 'tailwindscore' ) . '">&#10003; ' . esc_html__( '认证', 'tailwindscore' ) . '</span>';
        }
        
        // ??????
        if (!empty($country_name)) {
            $badges[] = '<span style="display: inline-block; background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 5px; vertical-align: middle;" title="' . esc_attr( sprintf( __( '国家：%s', 'tailwindscore' ), $country_name ) ) . '">' . esc_html( $country_code ) . '</span>';
        }
        
        // ???????????????????????????????????
        if (empty($badges)) {
            return $author;
        }
        
        // ???????????? + ????
        return $author . implode('', $badges);
    }
    
}


