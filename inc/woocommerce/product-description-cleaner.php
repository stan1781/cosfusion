<?php
declare(strict_types=1);

/**
 * Product Description Cleaner
 * 批量删除产品描述中的表格标签及内容
 */

/**
 * 从 HTML 内容中删除所有 table 标签及其内容
 *
 * @param string $content HTML 内容
 * @return string 清理后的内容
 */
function tws_remove_tables_from_content(string $content): string {
    if (empty($content)) {
        return $content;
    }
    
    // 使用正则表达式删除所有 table 标签及其内容
    // 使用 DOTALL 标志使 . 匹配包括换行符在内的所有字符
    $pattern = '/<table[^>]*>.*?<\/table>/is';
    $cleaned_content = preg_replace($pattern, '', $content);
    
    // 清理可能留下的多余空白
    $cleaned_content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $cleaned_content);
    
    return trim($cleaned_content);
}

/**
 * 批量清理所有产品的描述和简短描述中的表格
 *
 * @param array $options 选项数组
 *   - 'product_ids' (array): 指定要处理的产品 ID，为空则处理所有产品
 *   - 'clean_description' (bool): 是否清理产品描述，默认 true
 *   - 'clean_short_description' (bool): 是否清理简短描述，默认 true
 *   - 'dry_run' (bool): 是否为测试运行（不实际保存），默认 false
 * @return array 处理结果
 */
function tws_bulk_clean_product_descriptions(array $options = []): array {
    $defaults = [
        'product_ids' => [],
        'clean_description' => true,
        'clean_short_description' => true,
        'dry_run' => false,
    ];
    
    $options = wp_parse_args($options, $defaults);
    
    $results = [
        'total' => 0,
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'details' => [],
    ];
    
    // 获取要处理的产品
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ];
    
    if (!empty($options['product_ids'])) {
        $args['post__in'] = $options['product_ids'];
    }
    
    $product_ids = get_posts($args);
    $results['total'] = count($product_ids);
    
    foreach ($product_ids as $product_id) {
        $results['processed']++;
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $results['skipped']++;
            $results['errors'][] = sprintf('产品 ID %d 不存在', $product_id);
            continue;
        }
        
        $updated = false;
        $changes = [];
        
        // 清理产品描述
        if ($options['clean_description']) {
            $description = $product->get_description('edit');
            if (!empty($description) && preg_match('/<table[^>]*>/i', $description)) {
                $cleaned_description = tws_remove_tables_from_content($description);
                if ($cleaned_description !== $description) {
                    $changes['description'] = [
                        'before' => mb_strlen($description),
                        'after' => mb_strlen($cleaned_description),
                        'tables_removed' => preg_match_all('/<table[^>]*>/i', $description),
                    ];
                    
                    if (!$options['dry_run']) {
                        $product->set_description($cleaned_description);
                        $updated = true;
                    }
                }
            }
        }
        
        // 清理简短描述
        if ($options['clean_short_description']) {
            $short_description = $product->get_short_description('edit');
            if (!empty($short_description) && preg_match('/<table[^>]*>/i', $short_description)) {
                $cleaned_short_description = tws_remove_tables_from_content($short_description);
                if ($cleaned_short_description !== $short_description) {
                    $changes['short_description'] = [
                        'before' => mb_strlen($short_description),
                        'after' => mb_strlen($cleaned_short_description),
                        'tables_removed' => preg_match_all('/<table[^>]*>/i', $short_description),
                    ];
                    
                    if (!$options['dry_run']) {
                        $product->set_short_description($cleaned_short_description);
                        $updated = true;
                    }
                }
            }
        }
        
        // 保存更改
        if ($updated) {
            $product->save();
            $results['updated']++;
        }
        
        if (!empty($changes)) {
            $results['details'][$product_id] = [
                'name' => $product->get_name(),
                'changes' => $changes,
            ];
        }
    }
    
    return $results;
}

/**
 * 添加管理菜单
 */
function tws_product_cleaner_admin_menu(): void {
    add_submenu_page(
        'woocommerce',
        '产品描述清理工具',
        '产品描述清理',
        'manage_woocommerce',
        'tws-product-cleaner',
        'tws_product_cleaner_admin_page'
    );
}
add_action('admin_menu', 'tws_product_cleaner_admin_menu');

/**
 * 管理页面
 */
function tws_product_cleaner_admin_page(): void {
    // 检查权限
    if (!current_user_can('manage_woocommerce')) {
        wp_die('您没有权限访问此页面');
    }
    
    $results = null;
    $message = '';
    
    // 处理表单提交
    if (isset($_POST['tws_clean_products']) && check_admin_referer('tws_clean_products_action', 'tws_clean_products_nonce')) {
        $options = [
            'clean_description' => isset($_POST['clean_description']),
            'clean_short_description' => isset($_POST['clean_short_description']),
            'dry_run' => isset($_POST['dry_run']),
        ];
        
        // 如果指定了产品 ID
        if (!empty($_POST['product_ids'])) {
            $product_ids = array_map('intval', explode(',', $_POST['product_ids']));
            $options['product_ids'] = array_filter($product_ids);
        }
        
        $results = tws_bulk_clean_product_descriptions($options);
        
        if ($options['dry_run']) {
            $message = sprintf(
                '<div class="notice notice-info"><p><strong>测试运行完成</strong>：找到 %d 个产品，其中 %d 个包含表格（未实际修改）</p></div>',
                $results['total'],
                count($results['details'])
            );
        } else {
            $message = sprintf(
                '<div class="notice notice-success"><p><strong>清理完成</strong>：共处理 %d 个产品，更新了 %d 个产品</p></div>',
                $results['processed'],
                $results['updated']
            );
        }
    }
    
    ?>
    <div class="wrap">
        <h1>产品描述清理工具</h1>
        <p>此工具可以批量删除产品描述中的所有表格（table 标签及其内容）。</p>
        
        <?php echo $message; ?>
        
        <div class="card">
            <h2>清理选项</h2>
            <form method="post" action="">
                <?php wp_nonce_field('tws_clean_products_action', 'tws_clean_products_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">清理范围</th>
                        <td>
                            <label>
                                <input type="checkbox" name="clean_description" value="1" checked>
                                产品描述（详细描述）
                            </label><br>
                            <label>
                                <input type="checkbox" name="clean_short_description" value="1" checked>
                                简短描述
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">产品 ID</th>
                        <td>
                            <input type="text" name="product_ids" class="regular-text" placeholder="留空处理所有产品，或输入用逗号分隔的产品 ID">
                            <p class="description">例如：123,456,789。留空则处理所有产品。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">测试模式</th>
                        <td>
                            <label>
                                <input type="checkbox" name="dry_run" value="1">
                                仅测试，不实际修改数据
                            </label>
                            <p class="description">建议首次运行时勾选此选项，查看会影响哪些产品。</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="tws_clean_products" class="button button-primary">
                        开始清理
                    </button>
                </p>
            </form>
        </div>
        
        <?php if ($results && !empty($results['details'])): ?>
            <div class="card" style="margin-top: 20px;">
                <h2>处理详情</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>产品 ID</th>
                            <th>产品名称</th>
                            <th>修改内容</th>
                            <th>删除的表格数</th>
                            <th>字符变化</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['details'] as $product_id => $detail): ?>
                            <tr>
                                <td><?php echo esc_html($product_id); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>" target="_blank">
                                        <?php echo esc_html($detail['name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $fields = [];
                                    if (isset($detail['changes']['description'])) {
                                        $fields[] = '产品描述';
                                    }
                                    if (isset($detail['changes']['short_description'])) {
                                        $fields[] = '简短描述';
                                    }
                                    echo esc_html(implode('、', $fields));
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $total_tables = 0;
                                    foreach ($detail['changes'] as $change) {
                                        $total_tables += $change['tables_removed'];
                                    }
                                    echo esc_html($total_tables);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    foreach ($detail['changes'] as $field => $change) {
                                        printf(
                                            '%s: %d → %d 字符<br>',
                                            $field === 'description' ? '描述' : '简短描述',
                                            $change['before'],
                                            $change['after']
                                        );
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if ($results && !empty($results['errors'])): ?>
            <div class="card" style="margin-top: 20px;">
                <h2>错误信息</h2>
                <ul>
                    <?php foreach ($results['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .card {
            max-width: 100%;
            padding: 20px;
        }
        .form-table th {
            width: 200px;
        }
    </style>
    <?php
}
