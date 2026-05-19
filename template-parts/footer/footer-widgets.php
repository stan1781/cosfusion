<?php
declare(strict_types=1);
?>
<div class="ts-site-footer__content">
	<div class="ts-site-footer__identity ts-stack">
		<p class="ts-site-footer__title"><?php echo esc_html( tailwindscore_site_name() ); ?></p>
		<p class="ts-site-footer__meta"><?php esc_html_e( 'Built on WordPress and ready for WooCommerce.', 'tailwindscore' ); ?></p>
	</div>
	<div class="ts-site-footer__search">
		<?php get_search_form(); ?>
	</div>
</div>
