<?php
declare(strict_types=1);
?>
<form role="search" method="get" class="ts-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="ts-search-field"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></label>
	<div class="ts-input-group ts-input-group--inset-end">
		<input id="ts-search-field" class="ts-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( tailwindscore_search_placeholder_text() ); ?>">
		<?php tailwindscore_render_search_hidden_fields(); ?>
		<button class="ts-icon-button" type="submit" aria-label="<?php echo esc_attr( tailwindscore_search_submit_text() ); ?>">
			<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'search' ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( tailwindscore_search_submit_text() ); ?></span>
		</button>
	</div>
</form>
