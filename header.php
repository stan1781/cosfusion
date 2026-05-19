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
	<header class="ts-site-header">
		<div class="ts-container ts-site-header__inner">
			<?php get_template_part( 'template-parts/header/site-branding' ); ?>
			<?php get_template_part( 'template-parts/navigation/primary-nav' ); ?>
		</div>
	</header>

