<?php
declare(strict_types=1);

/**
 * 博客归档页头：面包屑、标题、可选 term / 作者说明。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

$ts_breadcrumb_items = tailwindscore_entry_breadcrumb_items();
$ts_title            = tailwindscore_get_archive_title();
$ts_description      = get_the_archive_description();
?>
<header class="blog-archive__header">
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

	<div class="blog-archive__header-body">
		<h1 class="blog-archive__title"><?php echo esc_html( $ts_title ); ?></h1>
		<?php if ( is_string( $ts_description ) && '' !== trim( wp_strip_all_tags( $ts_description ) ) ) : ?>
			<div class="blog-archive__description">
				<?php echo wp_kses_post( $ts_description ); ?>
			</div>
		<?php endif; ?>
	</div>
</header>
