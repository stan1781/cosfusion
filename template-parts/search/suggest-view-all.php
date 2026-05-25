<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( empty( $search_url ) || empty( $view_all_text ) ) {
	return;
}
?>
<div class="ts-search-suggest__footer">
	<a class="ts-search-suggest__view-all" href="<?php echo esc_url( $search_url ); ?>">
		<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'search' ); ?></span>
		<span><?php echo esc_html( $view_all_text ); ?></span>
	</a>
</div>
