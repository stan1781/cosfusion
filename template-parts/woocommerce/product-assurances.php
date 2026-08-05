<?php
declare(strict_types=1);

/**
 * 单品保障列表模板。
 *
 * @package TailwindScore
 *
 * @var array<string, mixed> $args {
 *     @type array<int, array{icon: string, text: string, link_url: string, link_label: string}> $items
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
		$text       = (string) ( $item['text'] ?? '' );
		$link_url   = (string) ( $item['link_url'] ?? '' );
		$link_label = (string) ( $item['link_label'] ?? '' );
		$has_link   = '' !== $link_url;
		?>
		<li class="product-assurances__item">
			<span class="product-assurances__icon icon" aria-hidden="true">
				<?php echo tailwindscore_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<span class="product-assurances__body">
				<?php if ( $has_link && '' === $link_label ) : ?>
					<a class="product-assurances__text product-assurances__link" href="<?php echo esc_url( $link_url ); ?>">
						<?php echo esc_html( $text ); ?>
					</a>
				<?php else : ?>
					<span class="product-assurances__text"><?php echo esc_html( $text ); ?></span>
					<?php if ( $has_link && '' !== $link_label ) : ?>
						<a class="product-assurances__link" href="<?php echo esc_url( $link_url ); ?>">
							<?php echo esc_html( $link_label ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</span>
		</li>
	<?php endforeach; ?>
</ul>
