<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ts_breadcrumb_items = tailwindscore_entry_breadcrumb_items();
$ts_has_excerpt      = has_excerpt();
$ts_is_post          = is_singular( 'post' );
$ts_header_classes   = 'entry__header';
$ts_categories       = $ts_is_post ? get_the_category() : array();
$ts_primary_category = ( ! empty( $ts_categories ) && $ts_categories[0] instanceof WP_Term )
	? $ts_categories[0]
	: null;

if ( $ts_is_post ) {
	$ts_header_classes .= ' entry__header--post';
}
?>
<header class="<?php echo esc_attr( $ts_header_classes ); ?>">
	<?php if ( count( $ts_breadcrumb_items ) > 1 ) : ?>
		<nav class="entry__breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'tailwindscore' ); ?>">
			<ol class="entry__breadcrumb-list">
				<?php foreach ( $ts_breadcrumb_items as $ts_index => $ts_item ) : ?>
					<?php
					$ts_is_last = $ts_index === array_key_last( $ts_breadcrumb_items );
					?>
					<li class="entry__breadcrumb-item">
						<?php if ( ! $ts_is_last && '' !== $ts_item['url'] ) : ?>
							<a class="entry__breadcrumb-link" href="<?php echo esc_url( $ts_item['url'] ); ?>">
								<?php echo esc_html( $ts_item['label'] ); ?>
							</a>
						<?php else : ?>
							<span class="entry__breadcrumb-current" <?php echo $ts_is_last ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $ts_item['label'] ); ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
	<?php endif; ?>

	<div class="entry__header-body">
		<h1 class="entry__title"><?php the_title(); ?></h1>

		<?php if ( $ts_is_post ) : ?>
			<p class="entry__meta">
				<time class="entry__meta-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
				<?php if ( $ts_primary_category instanceof WP_Term ) : ?>
					<span class="entry__meta-sep" aria-hidden="true">·</span>
					<a class="entry__meta-category" href="<?php echo esc_url( get_category_link( $ts_primary_category->term_id ) ); ?>">
						<?php echo esc_html( $ts_primary_category->name ); ?>
					</a>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( $ts_has_excerpt ) : ?>
			<p class="entry__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $ts_is_post && has_post_thumbnail() ) : ?>
		<figure class="entry__featured">
			<?php
			the_post_thumbnail(
				'large',
				array(
					'class'   => 'entry__featured-image',
					'loading' => 'eager',
				)
			);
			?>
		</figure>
	<?php endif; ?>
</header>
