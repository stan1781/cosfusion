<?php
declare(strict_types=1);

/**
 * 文章卡片（相关网格 / 侧栏紧凑列表 / 归档 grid）。
 *
 * @package TailwindScore
 *
 * @var array{post?: WP_Post, variant?: string, tag?: string}|null $args
 */

defined( 'ABSPATH' ) || exit;

$ts_post    = isset( $args['post'] ) && $args['post'] instanceof WP_Post ? $args['post'] : null;
$ts_variant = isset( $args['variant'] ) ? sanitize_key( (string) $args['variant'] ) : 'card';
$ts_tag     = isset( $args['tag'] ) ? sanitize_key( (string) $args['tag'] ) : 'article';

if ( ! $ts_post instanceof WP_Post ) {
	return;
}

if ( ! in_array( $ts_variant, array( 'card', 'compact', 'grid' ), true ) ) {
	$ts_variant = 'card';
}

if ( ! in_array( $ts_tag, array( 'article', 'li', 'div' ), true ) ) {
	$ts_tag = 'article';
}

$ts_permalink = get_permalink( $ts_post );
$ts_title     = get_the_title( $ts_post );
$ts_classes   = array( 'post-card' );

if ( 'card' !== $ts_variant ) {
	$ts_classes[] = 'post-card--' . $ts_variant;
}

$ts_show_date = 'compact' !== $ts_variant;

if ( 'compact' === $ts_variant ) {
	$ts_thumb_size = 'thumbnail';
} else {
	$ts_thumb_size = 'medium';
}
?>
<<?php echo esc_attr( $ts_tag ); ?> class="<?php echo esc_attr( implode( ' ', $ts_classes ) ); ?>">
	<a class="post-card__link" href="<?php echo esc_url( is_string( $ts_permalink ) ? $ts_permalink : '' ); ?>">
		<?php if ( has_post_thumbnail( $ts_post ) ) : ?>
			<span class="post-card__media">
				<?php
				echo get_the_post_thumbnail(
					$ts_post,
					$ts_thumb_size,
					array(
						'class'   => 'post-card__image',
						'loading' => 'lazy',
						'alt'     => '',
					)
				);
				?>
			</span>
		<?php else : ?>
			<span class="post-card__media post-card__media--placeholder" aria-hidden="true"></span>
		<?php endif; ?>
		<span class="post-card__body">
			<span class="post-card__title"><?php echo esc_html( $ts_title ); ?></span>
			<?php if ( $ts_show_date ) : ?>
				<time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $ts_post ) ); ?>">
					<?php echo esc_html( get_the_date( '', $ts_post ) ); ?>
				</time>
			<?php endif; ?>
		</span>
	</a>
</<?php echo esc_attr( $ts_tag ); ?>>
