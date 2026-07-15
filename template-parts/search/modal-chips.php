<?php
declare(strict_types=1);

$ts_hot_terms      = tailwindscore_search_modal_hot_terms();
$ts_hot_categories = tailwindscore_search_modal_hot_categories();

if ( empty( $ts_hot_terms ) && empty( $ts_hot_categories ) ) {
	return;
}
?>
<div class="search-modal-chips">
	<?php if ( ! empty( $ts_hot_terms ) ) : ?>
		<section class="search-modal-chips__group" aria-label="<?php esc_attr_e( 'Popular searches', 'tailwindscore' ); ?>">
			<h3 class="search-modal-chips__heading"><?php esc_html_e( 'Popular searches', 'tailwindscore' ); ?></h3>
			<ul class="search-modal-chips__list" role="list">
				<?php foreach ( $ts_hot_terms as $ts_term ) : ?>
					<li class="search-modal-chips__item" role="listitem">
						<a class="search-modal-chips__chip" href="<?php echo esc_url( tailwindscore_search_modal_term_url( $ts_term ) ); ?>">
							<?php echo esc_html( $ts_term ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $ts_hot_categories ) ) : ?>
		<section class="search-modal-chips__group" aria-label="<?php esc_attr_e( 'Popular categories', 'tailwindscore' ); ?>">
			<h3 class="search-modal-chips__heading"><?php esc_html_e( 'Popular categories', 'tailwindscore' ); ?></h3>
			<ul class="search-modal-chips__list" role="list">
				<?php foreach ( $ts_hot_categories as $ts_category ) : ?>
					<li class="search-modal-chips__item" role="listitem">
						<a class="search-modal-chips__chip" href="<?php echo esc_url( get_term_link( $ts_category ) ); ?>">
							<?php echo esc_html( $ts_category->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
