<?php
declare(strict_types=1);
?>
<div class="ts-site-menu-drawer" x-cloak :data-open="menuOpen ? 'true' : 'false'">
	<button class="ts-site-menu-drawer__backdrop" type="button" @click="closeMenu()" aria-label="<?php esc_attr_e( 'Close menu', 'tailwindscore' ); ?>"></button>
	<div id="ts-site-menu-panel" class="ts-site-menu-drawer__panel ts-stack" role="dialog" aria-modal="true" aria-labelledby="ts-site-menu-title">
		<div class="ts-site-menu-drawer__header">
			<h2 id="ts-site-menu-title" class="ts-site-menu-drawer__title"><?php esc_html_e( 'Menu', 'tailwindscore' ); ?></h2>
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
