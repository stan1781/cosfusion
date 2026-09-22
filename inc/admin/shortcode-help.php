<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * 编辑页侧栏「主题短代码」速查（post / page / ts_block）。
 */

/**
 * 可展示速查的 post type。
 *
 * @return list<string>
 */
function tailwindscore_shortcode_help_post_types(): array {
	return array( 'post', 'page', 'ts_block' );
}

/**
 * 运营侧短代码目录（不含 [block]；Hero 不列可选覆盖属性）。
 *
 * @return list<array{
 *   tag: string,
 *   label: string,
 *   blurb: string,
 *   example: string,
 *   attrs: list<array{name: string, desc: string}>
 * }>
 */
function tailwindscore_shortcode_help_catalog(): array {
	return array(
		array(
			'tag'     => 'ts_products_carousel',
			'label'   => __( '产品轮播', 'tailwindscore' ),
			'blurb'   => __( '按分类或排序输出产品列表（默认 Swiper 轮播）。', 'tailwindscore' ),
			'example' => '[ts_products_carousel limit="8" orderby="date" title="New arrivals"]',
			'attrs'   => array(
				array(
					'name' => 'category',
					'desc' => __( '逗号分隔的 product_cat slug 或 term ID；空为全部', 'tailwindscore' ),
				),
				array(
					'name' => 'limit',
					'desc' => __( '数量，1–24，默认 8', 'tailwindscore' ),
				),
				array(
					'name' => 'card',
					'desc' => __( 'grid（竖卡，默认）或 list（横卡；list 强制关轮播；未指定列数时默认手机 1 / 桌面 2）', 'tailwindscore' ),
				),
				array(
					'name' => 'carousel',
					'desc' => __( '1 轮播（默认）或 0 静态网格；全断点生效', 'tailwindscore' ),
				),
				array(
					'name' => 'columns_mobile / columns_desktop / columns_wide',
					'desc' => __( '可见列数；未写则用 Customizer「商店/归档 → 产品轮播」全局默认（1.5 / 3 / 4）；list 未指定时手机 1 / 桌面 2', 'tailwindscore' ),
				),
				array(
					'name' => 'gap',
					'desc' => __( '间距 px，0–48；未写则手机 0.5rem / 桌面 1rem', 'tailwindscore' ),
				),
				array(
					'name' => 'orderby / order',
					'desc' => __( 'orderby：date / popularity / menu_order / title；order：ASC / DESC', 'tailwindscore' ),
				),
				array(
					'name' => 'title',
					'desc' => __( '区块标题；空则仅保留导航位', 'tailwindscore' ),
				),
			),
		),
		array(
			'tag'     => 'ts_recently_viewed',
			'label'   => __( 'Recently viewed', 'tailwindscore' ),
			'blurb'   => __( '访客最近浏览的商品列表（默认轮播）。', 'tailwindscore' ),
			'example' => '[ts_recently_viewed limit="8" title="Recently viewed" exclude="0"]',
			'attrs'   => array(
				array(
					'name' => 'limit',
					'desc' => __( '数量，默认 8', 'tailwindscore' ),
				),
				array(
					'name' => 'title',
					'desc' => __( '区块标题；空则用主题默认文案', 'tailwindscore' ),
				),
				array(
					'name' => 'exclude',
					'desc' => __( '排除的商品 ID，默认 0', 'tailwindscore' ),
				),
				array(
					'name' => 'card',
					'desc' => __( 'grid（默认）或 list（横卡；list 强制关轮播；列数可用 columns_*）', 'tailwindscore' ),
				),
				array(
					'name' => 'carousel',
					'desc' => __( '1（默认）或 0；list 时强制为 0', 'tailwindscore' ),
				),
				array(
					'name' => 'columns_mobile / columns_desktop / columns_wide',
					'desc' => __( '可选；list 默认 1 / 3，grid 未写则用全局产品轮播默认', 'tailwindscore' ),
				),
				array(
					'name' => 'gap',
					'desc' => __( '可选间距 px；未写则手机 0.5rem / 桌面 1rem', 'tailwindscore' ),
				),
			),
		),
		array(
			'tag'     => 'ts_home_hero',
			'label'   => __( '首页 Hero', 'tailwindscore' ),
			'blurb'   => __( '首页 Hero 轮播；幻灯片与布局在 外观 → 自定义 → 首页 → Hero Banner。', 'tailwindscore' ),
			'example' => '[ts_home_hero]',
			'attrs'   => array(),
		),
	);
}

/**
 * 注册侧栏 Meta Box。
 */
function tailwindscore_shortcode_help_register_meta_boxes(): void {
	foreach ( tailwindscore_shortcode_help_post_types() as $post_type ) {
		add_meta_box(
			'ts-shortcode-help',
			__( '主题短代码', 'tailwindscore' ),
			'tailwindscore_shortcode_help_render_meta_box',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'tailwindscore_shortcode_help_register_meta_boxes' );

/**
 * @param WP_Post $post 当前编辑对象（未使用）。
 */
function tailwindscore_shortcode_help_render_meta_box( WP_Post $post ): void {
	unset( $post );
	?>
	<p class="description ts-shortcode-help__intro">
		<?php esc_html_e( '粘贴到正文或内容区块 HTML 中即可；点击下方代码可全选复制。', 'tailwindscore' ); ?>
	</p>
	<?php foreach ( tailwindscore_shortcode_help_catalog() as $item ) : ?>
		<div class="ts-shortcode-help__item">
			<p class="ts-shortcode-help__label">
				<strong><?php echo esc_html( $item['label'] ); ?></strong>
				<code><?php echo esc_html( '[' . $item['tag'] . ']' ); ?></code>
			</p>
			<p class="description ts-shortcode-help__blurb"><?php echo esc_html( $item['blurb'] ); ?></p>
			<textarea
				readonly
				class="large-text code ts-shortcode-help__example"
				rows="2"
				onclick="this.select();"
			><?php echo esc_textarea( $item['example'] ); ?></textarea>
			<?php if ( array() !== $item['attrs'] ) : ?>
				<ul class="ts-shortcode-help__attrs">
					<?php foreach ( $item['attrs'] as $attr ) : ?>
						<li class="ts-shortcode-help__attr">
							<code class="ts-shortcode-help__attr-name"><?php echo esc_html( $attr['name'] ); ?></code>
							<span class="ts-shortcode-help__attr-desc"><?php echo esc_html( $attr['desc'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	<?php
}

/**
 * 仅在匹配编辑屏注入少量样式。
 */
function tailwindscore_shortcode_help_enqueue_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->post_type, tailwindscore_shortcode_help_post_types(), true ) ) {
		return;
	}

	wp_add_inline_style(
		'wp-admin',
		'
		.ts-shortcode-help__intro { margin-top: 0; }
		.ts-shortcode-help__item { margin: 0 0 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #dcdcde; }
		.ts-shortcode-help__item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: 0; }
		.ts-shortcode-help__label { margin: 0 0 0.25rem; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: baseline; }
		.ts-shortcode-help__blurb { margin: 0 0 0.5rem; }
		.ts-shortcode-help__example { width: 100%; max-width: 100%; box-sizing: border-box; background: #f6f7f7; font-family: Consolas, Monaco, monospace; font-size: 12px; }
		.ts-shortcode-help__attrs { margin: 0.5rem 0 0; padding: 0; list-style: none; font-size: 11px; line-height: 1.45; }
		.ts-shortcode-help__attr { margin: 0 0 0.45rem; padding: 0; }
		.ts-shortcode-help__attr:last-child { margin-bottom: 0; }
		.ts-shortcode-help__attr-name { display: inline-block; max-width: 100%; margin: 0 0 0.15rem; word-break: break-word; white-space: normal; font-size: 11px; }
		.ts-shortcode-help__attr-desc { display: block; color: #50575e; }
		'
	);
}
add_action( 'admin_enqueue_scripts', 'tailwindscore_shortcode_help_enqueue_assets' );
