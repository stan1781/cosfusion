<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'TAILWINDSCORE_CONTENT_BLOCKS_VERSION', '0.1.0' );
define( 'TAILWINDSCORE_BLOCK_REVISIONS_TO_KEEP', 3 );

require_once __DIR__ . '/includes/class-content-block-cpt.php';
require_once __DIR__ . '/includes/class-content-block-content.php';
require_once __DIR__ . '/includes/class-content-block-renderer.php';
require_once __DIR__ . '/includes/class-content-block-shortcode.php';

Tailwindscore_Content_Block_Cpt::init();
Tailwindscore_Content_Block_Shortcode::init();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-content-block-admin.php';
	Tailwindscore_Content_Block_Admin::init();
}
