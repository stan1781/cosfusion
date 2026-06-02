<?php
declare(strict_types=1);

set_query_var( 'ts_search_form_variant', 'command' );
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
		class="ts-site-search-modal__dialog ts-site-search-modal__dialog--command"
		role="dialog"
		aria-modal="true"
		aria-label="<?php esc_attr_e( 'Search products', 'tailwindscore' ); ?>"
		@click.outside="closeSearch()"
	>
		<?php get_search_form(); ?>
		<?php get_template_part( 'template-parts/search/modal-chips' ); ?>
	</div>
</div>
<?php
set_query_var( 'ts_search_form_variant', '' );
