<?php
declare(strict_types=1);

/**
 * 单品保障列表模板。
 *
 * @package TailwindScore
 *
 * @var array<string, mixed> $args {
 *     @type array<int, array{icon: string, title: string, subtitle: string, link_url: string, link_label: string}> $items
 * }
 */

defined( 'ABSPATH' ) || exit;

$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();

if ( array() === $items ) {
	return;
}
?>
<ul class="product-assurances">
	<?php foreach ( $items as $item ) : ?>
		<?php
		$icon       = (string) ( $item['icon'] ?? 'truck' );
		$title      = (string) ( $item['title'] ?? '' );
		$subtitle   = (string) ( $item['subtitle'] ?? '' );
		$link_url   = (string) ( $item['link_url'] ?? '' );
		$link_label = (string) ( $item['link_label'] ?? '' );
		$has_link   = '' !== $link_url;
		$wrap_body  = $has_link && '' === $link_label;
		?>
		<li class="product-assurances__item">
			<span class="product-assurances__icon icon" aria-hidden="true">
				<?php echo tailwindscore_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<?php if ( $wrap_body ) : ?>
				<a class="product-assurances__body product-assurances__link" href="<?php echo esc_url( $link_url ); ?>">
					<?php if ( '' !== $title ) : ?>
						<span class="product-assurances__title"><?php echo esc_html( $title ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $subtitle ) : ?>
						<span class="product-assurances__subtitle"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<span class="product-assurances__body">
					<?php if ( '' !== $title ) : ?>
						<span class="product-assurances__title"><?php echo esc_html( $title ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $subtitle ) : ?>
						<span class="product-assurances__subtitle"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
					<?php if ( $has_link && '' !== $link_label ) : ?>
						<a class="product-assurances__link" href="<?php echo esc_url( $link_url ); ?>">
							<?php echo esc_html( $link_label ); ?>
						</a>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
