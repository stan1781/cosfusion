<?php
declare(strict_types=1);

/**
 * Site Kit Google 登录插槽。
 *
 * @package TailwindScore
 *
 * @var string $placement after_login|before_form
 */

defined( 'ABSPATH' ) || exit;

$placement = isset( $placement ) ? sanitize_key( (string) $placement ) : 'after_login';

$show = 'before_form' === $placement
	? tailwindscore_account_auth_show_register_google()
	: tailwindscore_account_auth_show_google();

if ( ! $show ) {
	return;
}
?>
<div class="ts-account-auth__google-wrap">
	<?php if ( 'after_login' === $placement ) : ?>
		<div class="ts-account-auth__divider" aria-hidden="true">
			<span><?php esc_html_e( 'or', 'tailwindscore' ); ?></span>
		</div>
	<?php endif; ?>

	<div class="ts-account-auth__google">
		<?php
		do_action(
			'googlesitekit_render_sign_in_with_google_button',
			array(
				'class' => 'ts-account-auth__google-button',
				'text'  => 'continue_with',
				'shape' => 'pill',
			)
		);
		?>
	</div>

	<?php if ( 'before_form' === $placement ) : ?>
		<div class="ts-account-auth__divider" aria-hidden="true">
			<span><?php esc_html_e( 'or', 'tailwindscore' ); ?></span>
		</div>
	<?php endif; ?>
</div>
