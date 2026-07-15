<?php
declare(strict_types=1);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'site-body' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<?php get_template_part( 'template-parts/header/header-bar' ); ?>
	<div class="site-header__scroll-sentinel" aria-hidden="true"></div>
	<header class="site-header" x-data="siteShell()" @keydown.escape.window="closeAll();">
		<div class="container site-header__inner">
			<?php get_template_part( 'template-parts/header/site-branding' ); ?>
			<?php get_template_part( 'template-parts/header/header-actions' ); ?>
			<div class="site-header__nav-inline">
				<?php
				get_template_part(
					'template-parts/navigation/primary-nav',
					null,
					array(
						'nav_id'    => 'primary-nav-desktop',
						'nav_class' => 'nav nav--desktop',
					)
				);
				?>
			</div>
		</div>
		<?php if ( tailwindscore_should_render_header_purchase_panels() ) : ?>
			<?php get_template_part( 'template-parts/header/search-modal' ); ?>
		<?php endif; ?>
		<?php get_template_part( 'template-parts/header/menu-drawer' ); ?>
		<?php if ( tailwindscore_should_render_header_purchase_panels() ) : ?>
			<?php get_template_part( 'template-parts/header/cart-drawer' ); ?>
		<?php endif; ?>
	</header>
