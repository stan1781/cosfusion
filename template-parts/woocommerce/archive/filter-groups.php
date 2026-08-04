<?php
declare(strict_types=1);

/**
 * 归档筛选 chip 分组（桌面侧栏 / 移动端 Drawer 共用）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$context = tailwindscore_archive_get_context();
$groups  = $context['sidebar_groups'];

if ( ! is_array( $groups ) || array() === $groups ) {
	return;
}
?>
<div class="wc-archive-sidebar__groups">
	<?php foreach ( $groups as $group ) : ?>
		<?php
		if ( ! is_array( $group ) || empty( $group['items'] ) || ! is_array( $group['items'] ) ) {
			continue;
		}

		$group_label = isset( $group['label'] ) ? (string) $group['label'] : '';
		$group_id    = isset( $group['id'] ) ? sanitize_html_class( (string) $group['id'] ) : 'group';
		$group_type  = isset( $group['type'] ) ? (string) $group['type'] : 'taxonomy';
		$is_attribute = 'attribute' === $group_type;
		?>
		<div class="wc-archive-sidebar__group wc-archive-sidebar__group--<?php echo esc_attr( $group_id ); ?>">
			<?php if ( '' !== $group_label ) : ?>
				<h3 class="wc-archive-sidebar__group-title"><?php echo esc_html( $group_label ); ?></h3>
			<?php endif; ?>

			<ul class="wc-archive-sidebar__list" role="list">
				<?php foreach ( $group['items'] as $item ) : ?>
					<?php
					if ( ! is_array( $item ) ) {
						continue;
					}

					$url       = isset( $item['url'] ) ? (string) $item['url'] : '';
					$name      = isset( $item['name'] ) ? (string) $item['name'] : '';
					$is_active = ! empty( $item['is_active'] );

					if ( '' === $url || '' === $name ) {
						continue;
					}

					$link_class = 'wc-archive-sidebar__link';

					if ( $is_active ) {
						$link_class .= ' is-active';
					}
					?>
					<li class="wc-archive-sidebar__item">
						<a
							class="<?php echo esc_attr( $link_class ); ?>"
							href="<?php echo esc_url( $url ); ?>"
							data-ts-archive-nav
							data-ts-archive-nav-type="<?php echo esc_attr( $is_attribute ? 'attribute' : 'taxonomy' ); ?>"
							<?php echo $is_attribute ? ' rel="nofollow"' : ''; ?>
							<?php echo $is_active ? ' aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( $name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>
