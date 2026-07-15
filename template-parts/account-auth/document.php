<?php
declare(strict_types=1);

/**
 * 账户认证界面的精简 HTML 文档（无站点 header/footer）。
 *
 * @package TailwindScore
 *
 * @var array<string, mixed> $args 模板参数。
 */

defined( 'ABSPATH' ) || exit;

$content_callback = null;

if ( isset( $args['content_callback'] ) && is_callable( $args['content_callback'] ) ) {
	$content_callback = $args['content_callback'];
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'account-auth-surface' ); ?>>
<?php wp_body_open(); ?>
<main id="primary" class="account-auth-root">
	<?php
	if ( $content_callback ) {
		call_user_func( $content_callback );
	}
	?>
</main>
<div class="purchase-flow-toast-root" data-ts-purchase-flow-toast-root aria-live="polite" aria-relevant="additions"></div>
<?php wp_footer(); ?>
</body>
</html>
