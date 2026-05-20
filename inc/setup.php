<?php
declare(strict_types=1);

function tailwindscore_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'tailwindscore' ),
		)
	);
}
add_action( 'after_setup_theme', 'tailwindscore_setup' );

function tailwindscore_register_sidebars(): void {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'tailwindscore' ),
			'id'            => 'sidebar-1',
			'before_widget' => '<section class="widget ts-card">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

	for ( $index = 1; $index <= 4; $index++ ) {
		register_sidebar(
			array(
				'name'          => sprintf(
					/* translators: %d: footer column number */
					__( 'Footer Column %d', 'tailwindscore' ),
					$index
				),
				'id'            => 'footer-' . $index,
				'before_widget' => '<section class="widget ts-site-footer__widget ts-stack">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title ts-site-footer__title">',
				'after_title'   => '</h2>',
			)
		);
	}
}
add_action( 'widgets_init', 'tailwindscore_register_sidebars' );
