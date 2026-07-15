<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ts_breadcrumb_items = tailwindscore_entry_breadcrumb_items();
$ts_has_excerpt      = has_excerpt();
?>
<header class="entry__header">
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

		<?php if ( $ts_has_excerpt ) : ?>
			<p class="entry__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</div>
</header>
