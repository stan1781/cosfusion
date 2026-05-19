<?php
declare(strict_types=1);
?>
<form role="search" method="get" class="ts-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="ts-search-field"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></label>
	<input id="ts-search-field" class="ts-input ts-search-form__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search the site', 'tailwindscore' ); ?>">
	<button class="ts-button" type="submit"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></button>
</form>
