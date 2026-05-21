<?php
declare(strict_types=1);
?>
<div class="ts-site-menu-drawer" x-cloak :data-open="menuOpen ? 'true' : 'false'">
	<button class="ts-site-menu-drawer__backdrop" type="button" @click="closeMenu()" aria-label="<?php esc_attr_e( 'Close menu', 'tailwindscore' ); ?>"></button>
	<div id="ts-site-menu-panel" class="ts-site-menu-drawer__panel ts-stack" role="dialog" aria-modal="true" aria-labelledby="ts-site-menu-title">
		<div class="ts-site-menu-drawer__header">
			<h2 id="ts-site-menu-title" class="ts-site-menu-drawer__title"><?php esc_html_e( 'Menu', 'tailwindscore' ); ?></h2>
			<button class="ts-icon-button ts-site-menu-drawer__close" type="button" @click="closeMenu()" aria-label="<?php esc_attr_e( 'Close menu', 'tailwindscore' ); ?>">
				<span class="ts-icon" aria-hidden="true"><?php echo tailwindscore_icon( 'close' ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'tailwindscore' ); ?></span>
			</button>
		</div>
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
