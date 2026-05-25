<?php
declare(strict_types=1);

$ts_variant = get_query_var( 'ts_search_form_variant', '' );
$ts_command = 'command' === $ts_variant;

$ts_ajax_search_active = function_exists( 'tailwindscore_ajax_search_is_active' ) && tailwindscore_ajax_search_is_active();
$ts_placeholder        = $ts_ajax_search_active
	? tailwindscore_ajax_search_placeholder()
	: tailwindscore_search_placeholder_text();

$form_class = 'ts-search-form';
if ( $ts_command ) {
	$form_class .= ' ts-search-form--command';
}
?>
<form role="search" method="get" class="<?php echo esc_attr( $form_class ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $ts_ajax_search_active ? ' data-ts-ajax-search' : ''; ?>>
	<label class="screen-reader-text" for="ts-search-field"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></label>
	<div class="ts-search-form__wrap">
		<?php if ( $ts_command ) : ?>
			<div class="ts-command-search-field">
				<span class="ts-command-search-field__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'search' ); ?></span>
				<input
					id="ts-search-field"
					class="ts-command-search-field__input"
					type="search"
					name="s"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					placeholder="<?php echo esc_attr( $ts_placeholder ); ?>"
					autocomplete="off"
					<?php echo $ts_ajax_search_active ? ' aria-controls="ts-search-suggest" aria-expanded="false" aria-autocomplete="list"' : ''; ?>
				>
				<?php tailwindscore_render_search_hidden_fields(); ?>
			</div>
		<?php else : ?>
			<div class="ts-input-group ts-input-group--inset-end">
				<input id="ts-search-field" class="ts-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( $ts_placeholder ); ?>" autocomplete="off"<?php echo $ts_ajax_search_active ? ' aria-controls="ts-search-suggest" aria-expanded="false" aria-autocomplete="list"' : ''; ?>>
				<?php tailwindscore_render_search_hidden_fields(); ?>
				<button class="ts-icon-button" type="submit" aria-label="<?php echo esc_attr( tailwindscore_search_submit_text() ); ?>">
					<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'search' ); ?></span>
					<span class="screen-reader-text"><?php echo esc_html( tailwindscore_search_submit_text() ); ?></span>
				</button>
			</div>
		<?php endif; ?>
		<?php if ( $ts_ajax_search_active ) : ?>
			<div id="ts-search-suggest" class="ts-search-suggest" role="listbox" aria-live="polite" hidden></div>
		<?php endif; ?>
	</div>
</form>
