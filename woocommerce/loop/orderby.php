<?php
/**
 * 归档排序：页内下拉（按钮 + 链接菜单），避免手机原生 select 系统弹层。
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TailwindScore
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

$id_suffix   = wp_unique_id();
$menu_id     = 'ts-archive-orderby-menu-' . $id_suffix;
$current_id  = (string) $orderby;
$short_label = function_exists( 'tailwindscore_archive_orderby_short_label' )
	? tailwindscore_archive_orderby_short_label(
		$current_id,
		isset( $catalog_orderby_options[ $current_id ] ) ? (string) $catalog_orderby_options[ $current_id ] : ''
	)
	: (string) ( $catalog_orderby_options[ $current_id ] ?? $current_id );
?>
<div class="woocommerce-ordering wc-archive-ordering" data-ts-archive-ordering>
	<button
		type="button"
		class="wc-archive-ordering__toggle btn btn-outline"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $menu_id ); ?>"
		aria-haspopup="listbox"
	>
		<span class="wc-archive-ordering__title"><?php echo esc_html__( 'Sort', 'tailwindscore' ); ?></span>
		<span class="wc-archive-ordering__value"><?php echo esc_html( $short_label ); ?></span>
		<span class="icon wc-archive-ordering__chevron" aria-hidden="true"><?php echo function_exists( 'tailwindscore_icon' ) ? tailwindscore_icon( 'chevron-down' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG 来自主题图标 map。 ?></span>
	</button>
	<ul
		id="<?php echo esc_attr( $menu_id ); ?>"
		class="wc-archive-ordering__menu"
		role="listbox"
		aria-label="<?php esc_attr_e( 'Shop order', 'tailwindscore' ); ?>"
		hidden
	>
		<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
			<?php
			$option_id   = (string) $id;
			$is_active   = $option_id === $current_id;
			$menu_label  = function_exists( 'tailwindscore_archive_orderby_menu_label' )
				? tailwindscore_archive_orderby_menu_label( $option_id, (string) $name )
				: (string) $name;
			$href        = function_exists( 'tailwindscore_archive_orderby_url' )
				? tailwindscore_archive_orderby_url( $option_id )
				: add_query_arg( 'orderby', $option_id );
			?>
			<li class="wc-archive-ordering__item" role="none">
				<a
					href="<?php echo esc_url( $href ); ?>"
					class="wc-archive-ordering__option<?php echo $is_active ? ' is-active' : ''; ?>"
					data-ts-archive-nav
					data-ts-archive-nav-type="orderby"
					role="option"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
				><?php echo esc_html( $menu_label ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
