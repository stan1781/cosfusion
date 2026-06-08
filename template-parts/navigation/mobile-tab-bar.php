<?php
declare(strict_types=1);

$ts_new_url    = tailwindscore_new_arrivals_url();
$ts_tab_count  = class_exists( 'WooCommerce' ) ? 5 : 4;
$ts_tab_style  = '--ts-mobile-tab-count: ' . (int) $ts_tab_count;
?>
<nav class="ts-mobile-tab-bar" style="<?php echo esc_attr( $ts_tab_style ); ?>" aria-label="<?php esc_attr_e( 'Primary mobile', 'tailwindscore' ); ?>">
	<ul class="ts-mobile-tab-bar__list">
		<li class="ts-mobile-tab-bar__item">
			<a
				class="ts-mobile-tab-bar__link<?php echo is_front_page() ? ' ts-mobile-tab-bar__link--current' : ''; ?>"
				href="<?php echo esc_url( home_url( '/' ) ); ?>"
				<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>
			>
				<span class="ts-mobile-tab-bar__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'home' ); ?></span>
				<span class="ts-mobile-tab-bar__label"><?php esc_html_e( 'Home', 'tailwindscore' ); ?></span>
			</a>
		</li>
		<li class="ts-mobile-tab-bar__item">
			<?php
			$ts_is_new = is_page( 'new-arrivals' ) || ( function_exists( 'is_shop' ) && is_shop() && isset( $_GET['orderby'] ) && 'date' === sanitize_key( wp_unslash( (string) $_GET['orderby'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<a
				class="ts-mobile-tab-bar__link<?php echo $ts_is_new ? ' ts-mobile-tab-bar__link--current' : ''; ?>"
				href="<?php echo esc_url( $ts_new_url ); ?>"
				<?php echo $ts_is_new ? ' aria-current="page"' : ''; ?>
			>
				<span class="ts-mobile-tab-bar__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'sparkles' ); ?></span>
				<span class="ts-mobile-tab-bar__label"><?php esc_html_e( 'New', 'tailwindscore' ); ?></span>
			</a>
		</li>
		<li class="ts-mobile-tab-bar__item">
			<button
				class="ts-mobile-tab-bar__link ts-mobile-tab-bar__link--button"
				type="button"
				data-ts-mobile-tab-search
				aria-label="<?php esc_attr_e( 'Search', 'tailwindscore' ); ?>"
			>
				<span class="ts-mobile-tab-bar__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'search' ); ?></span>
				<span class="ts-mobile-tab-bar__label"><?php esc_html_e( 'Search', 'tailwindscore' ); ?></span>
			</button>
		</li>
		<li class="ts-mobile-tab-bar__item">
			<button
				class="ts-mobile-tab-bar__link ts-mobile-tab-bar__link--button"
				type="button"
				data-ts-mobile-tab-cart
				aria-label="<?php esc_attr_e( 'Cart', 'tailwindscore' ); ?>"
			>
				<span class="ts-mobile-tab-bar__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'cart' ); ?></span>
				<span class="ts-mobile-tab-bar__badge-slot" data-ts-cart-badge>
					<?php tailwindscore_render_cart_badge(); ?>
				</span>
				<span class="ts-mobile-tab-bar__label"><?php esc_html_e( 'Cart', 'tailwindscore' ); ?></span>
			</button>
		</li>		
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<li class="ts-mobile-tab-bar__item">
				<?php if ( is_user_logged_in() ) : ?>
					<a
						class="ts-mobile-tab-bar__link"
						href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
						aria-label="<?php echo esc_attr( tailwindscore_mobile_tab_account_aria_label() ); ?>"
					>
						<span class="ts-mobile-tab-bar__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'user' ); ?></span>
						<span class="ts-mobile-tab-bar__badge-slot" data-ts-account-badge>
							<?php tailwindscore_render_mobile_tab_account_badge(); ?>
						</span>
						<span class="ts-mobile-tab-bar__label"><?php esc_html_e( 'Account', 'tailwindscore' ); ?></span>
					</a>
				<?php else : ?>
					<button
						class="ts-mobile-tab-bar__link ts-mobile-tab-bar__link--button"
						type="button"
						data-ts-login-modal-open
						aria-controls="ts-account-login-modal"
						aria-label="<?php echo esc_attr( tailwindscore_mobile_tab_account_aria_label() ); ?>"
					>
						<span class="ts-mobile-tab-bar__icon ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'user' ); ?></span>
						<span class="ts-mobile-tab-bar__label"><?php esc_html_e( 'Account', 'tailwindscore' ); ?></span>
					</button>
				<?php endif; ?>
			</li>
		<?php endif; ?>
	</ul>
</nav>
