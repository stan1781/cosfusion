<?php
declare(strict_types=1);
?>
	<footer class="ts-site-footer">
		<div class="ts-container ts-site-footer__inner">
			<?php if ( is_active_sidebar( 'footer-top' ) ) : ?>
				<div class="ts-site-footer__top">
					<?php dynamic_sidebar( 'footer-top' ); ?>
				</div>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/footer/footer-widgets' ); ?>
			<?php get_template_part( 'template-parts/footer/footer-info' ); ?>
		</div>
	</footer>
</div>
<?php if ( function_exists( 'WC' ) ) : ?>
	<div class="ts-purchase-flow-toast-root" data-ts-purchase-flow-toast-root aria-live="polite" aria-relevant="additions"></div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
