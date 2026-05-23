<?php
declare(strict_types=1);
?>
<div
	class="ts-site-search-modal"
	x-cloak
	x-show="searchOpen"
	x-transition.opacity
	@click.self="closeSearch()"
>
	<button class="ts-site-search-modal__backdrop" type="button" @click="closeSearch()" aria-label="<?php esc_attr_e( 'Close search', 'tailwindscore' ); ?>"></button>
	<div
		id="ts-site-search-panel"
		class="ts-site-search-modal__dialog ts-stack"
		role="dialog"
		aria-modal="true"
		aria-labelledby="ts-site-search-title"
		@click.outside="closeSearch()"
	>
		<div class="ts-site-search-modal__header">
			<h2 id="ts-site-search-title" class="ts-site-search-modal__title"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></h2>
			<?php
			tailwindscore_render_close_button(
				__( 'Close search', 'tailwindscore' ),
				array(
					'attrs' => array(
						'@click' => 'closeSearch()',
					),
				)
			);
			?>
		</div>
		<?php get_search_form(); ?>
	</div>
</div>
