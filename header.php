<?php
declare(strict_types=1);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ts-site-body' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="ts-site">
	<header class="ts-site-header" x-data="siteShell()" @keydown.escape.window="closeAll();">
		<div class="ts-container ts-site-header__inner">
			<?php get_template_part( 'template-parts/header/site-branding' ); ?>
			<?php get_template_part( 'template-parts/header/header-actions' ); ?>
			<div class="ts-site-header__nav-inline">
				<?php
				get_template_part(
					'template-parts/navigation/primary-nav',
					null,
					array(
						'nav_id'    => 'ts-primary-nav-desktop',
						'nav_class' => 'ts-nav ts-nav--desktop',
					)
				);
				?>
			</div>
		</div>
		<?php get_template_part( 'template-parts/header/search-modal' ); ?>
		<?php get_template_part( 'template-parts/header/menu-drawer' ); ?>
		<?php get_template_part( 'template-parts/header/cart-drawer' ); ?>
	</header>
