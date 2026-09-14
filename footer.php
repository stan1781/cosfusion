<?php
declare(strict_types=1);
?>
<?php if ( function_exists( 'tailwindscore_site_footer_should_render' ) ? tailwindscore_site_footer_should_render() : true ) : ?>
	<footer class="site-footer">
		<?php if ( is_active_sidebar( 'footer-top' ) ) : ?>
			<div class="site-footer__band site-footer__band--top">
				<div class="container site-footer__inner">
					<div class="site-footer__top">
						<?php dynamic_sidebar( 'footer-top' ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="site-footer__band site-footer__band--main">
			<div class="container site-footer__inner">
				<?php get_template_part( 'template-parts/footer/footer-widgets' ); ?>
				<?php get_template_part( 'template-parts/footer/footer-nav' ); ?>
				<?php get_template_part( 'template-parts/footer/footer-info' ); ?>
			</div>
		</div>
	</footer>
<?php endif; ?>
</div>
<?php
$ts_show_toast_root = function_exists( 'WC' )
	|| ( function_exists( 'tailwindscore_should_load_mailnest' ) && tailwindscore_should_load_mailnest() );
if ( $ts_show_toast_root ) :
	?>
	<div class="purchase-flow-toast-root" data-ts-purchase-flow-toast-root aria-live="polite" aria-relevant="additions"></div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
