<?php
declare(strict_types=1);

$menu_title = __( 'Menu', 'tailwindscore' );
?>
<div
	class="ts-site-menu-drawer"
	x-cloak
	:data-open="menuOpen ? 'true' : 'false'"
	:aria-hidden="menuOpen ? 'false' : 'true'"
	:inert="!menuOpen"
>
	<button
		class="ts-site-menu-drawer__backdrop"
		type="button"
		@click="closeMenu()"
		aria-label="<?php esc_attr_e( 'Close menu', 'tailwindscore' ); ?>"
	></button>
	<div
		id="ts-site-menu-panel"
		class="ts-site-menu-drawer__panel"
		role="dialog"
		aria-modal="true"
		aria-labelledby="ts-site-menu-title"
		@transitionend="onMenuDrawerTransitionEnd($event)"
	>
		<div
			class="ts-site-menu-drawer__content"
			x-data='mobileNav(<?php echo wp_json_encode( array( 'rootTitle' => $menu_title ) ); ?>)'
			@tailwindscore:mobile-menu-reset.window="reset()"
		>
			<div class="ts-site-menu-drawer__header">
				<div class="ts-site-menu-drawer__header-main">
					<button
						type="button"
						class="ts-site-menu-drawer__back ts-nav__back"
						x-show="canGoBack"
						x-cloak
						@click="goBack()"
						aria-label="<?php esc_attr_e( 'Back', 'tailwindscore' ); ?>"
					>
						<span aria-hidden="true">&larr;</span>
					</button>
					<h2 id="ts-site-menu-title" class="ts-site-menu-drawer__title" x-text="headerTitle"><?php echo esc_html( $menu_title ); ?></h2>
				</div>
				<?php
				tailwindscore_render_close_button(
					__( 'Close menu', 'tailwindscore' ),
					array(
						'attrs' => array(
							'@click' => 'closeMenu()',
						),
					)
				);
				?>
			</div>
			<div class="ts-site-menu-drawer__nav" x-ref="navViewport">
				<?php
				get_template_part(
					'template-parts/navigation/primary-nav',
					null,
					array(
						'nav_id'    => 'ts-primary-nav-mobile',
						'nav_class' => 'ts-nav ts-nav--mobile',
					)
				);
				?>
			</div>
		</div>
	</div>
</div>
