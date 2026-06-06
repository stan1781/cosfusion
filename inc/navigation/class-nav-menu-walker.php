<?php
declare(strict_types=1);

/**
 * Primary navigation Walker (standard dropdown + mega menu extension).
 *
 * Mobile: drill-down panels collected in buffers, wrapped as a horizontal track in walk().
 *
 * @see menu-item-helpers.php for menu CSS class conventions.
 */
class Tailwindscore_Nav_Menu_Walker extends Walker_Nav_Menu {

	/** @var 'desktop'|'mobile' */
	protected string $variant = 'desktop';

	protected bool $in_mega_menu = false;

	/** @var string|null Active mobile sub-panel buffer key (menu-item-{id}). */
	protected ?string $mobile_active_panel_id = null;

	/**
	 * @var array<string, array{title: string, is_mega: bool, html: string}>
	 */
	protected array $mobile_panel_buffers = array();

	/**
	 * @param array{variant?: string}|null $args Walker constructor args.
	 */
	public function __construct( $args = null ) {
		if ( is_array( $args ) && isset( $args['variant'] ) && 'mobile' === $args['variant'] ) {
			$this->variant = 'mobile';
		}
	}

	/**
	 * @param array<int, object> $elements Menu items.
	 * @param mixed              ...$args   Walker args.
	 */
	public function walk( $elements, $max_depth, ...$args ) {
		if ( 'mobile' !== $this->variant ) {
			return parent::walk( $elements, $max_depth, ...$args );
		}

		$this->mobile_panel_buffers   = array();
		$this->mobile_active_panel_id = null;

		$list_items = parent::walk( $elements, $max_depth, ...$args );

		$panels_html = '';

		foreach ( $this->mobile_panel_buffers as $panel_id => $panel ) {
			$panel_classes = 'ts-nav__panel';

			if ( ! empty( $panel['is_mega'] ) ) {
				$panel_classes .= ' ts-nav__panel--mega';
			}

			$panels_html .= sprintf(
				'<div class="%1$s" data-ts-nav-panel="%2$s" data-ts-nav-title="%3$s" role="region" aria-label="%3$s">%4$s</div>',
				esc_attr( $panel_classes ),
				esc_attr( $panel_id ),
				esc_attr( $panel['title'] ),
				$panel['html']
			);
		}

		$root_title = apply_filters(
			'tailwindscore_mobile_nav_root_title',
			__( 'Menu', 'tailwindscore' )
		);

		return sprintf(
			'<div class="ts-nav__viewport"><div class="ts-nav__track" data-ts-nav-track><div class="ts-nav__panel" data-ts-nav-panel="root" data-ts-nav-title="%1$s" role="region" aria-label="%1$s"><ul class="ts-nav__list">%2$s</ul></div>%3$s</div></div>',
			esc_attr( $root_title ),
			$list_items,
			$panels_html
		);
	}

	/**
	 * @param string $output Output buffer.
	 * @return string
	 */
	private function &output_buffer( string &$output ): string {
		if ( null !== $this->mobile_active_panel_id && isset( $this->mobile_panel_buffers[ $this->mobile_active_panel_id ] ) ) {
			return $this->mobile_panel_buffers[ $this->mobile_active_panel_id ]['html'];
		}

		return $output;
	}

	/**
	 * @param object $item Menu item.
	 * @return list<string>
	 */
	private function get_item_classes( object $item, int $depth, object $args ): array {
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		$classes[] = 'ts-nav__item';

		if ( 0 === $depth && tailwindscore_nav_item_has_children( $item, $args ) ) {
			$classes[] = 'ts-nav__item--has-children';
		}

		if ( 0 === $depth && tailwindscore_nav_item_is_mega( $item ) ) {
			$classes[] = 'ts-nav__item--mega';
		}

		$classes = array_filter( array_unique( $classes ) );

		/** @var list<string> $classes */
		$classes = apply_filters( 'tailwindscore_nav_menu_item_classes', $classes, $item, $args, $depth );

		return $classes;
	}

	/**
	 * Chevron-down SVG icon for items that open a dropdown.
	 */
	private function render_chevron_icon(): string {
		return tailwindscore_icon( 'chevron-down', 'ts-nav__chevron' );
	}

	/**
	 * @param object $item Menu item.
	 */
	private function render_link( object $item, int $depth, object $args, string $link_class ): string {
		$classes = array( $link_class );

		if ( $depth > 0 ) {
			$classes[] = 'ts-nav__link--child';
		}

		if ( ! empty( $item->classes ) && is_array( $item->classes ) ) {
			if ( in_array( 'current-menu-item', $item->classes, true ) ) {
				$classes[] = 'ts-nav__link--current';
			}
		}

		$has_children = tailwindscore_nav_item_has_children( $item, $args );

		// Add modifier class when the link has a dropdown, for CSS targeting.
		if ( 0 === $depth && $has_children ) {
			$classes[] = 'ts-nav__link--has-children';
		}

		$atts           = array();
		$atts['href']   = ! empty( $item->url ) ? $item->url : '';
		$atts['class']  = implode( ' ', array_filter( $classes ) );
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';

		if ( in_array( 'ts-nav__link--current', $classes, true ) ) {
			$atts['aria-current'] = 'page';
		}

		$attributes = '';

		foreach ( $atts as $attr => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$value       = 'href' === $attr ? esc_url( $value ) : esc_attr( $value );
			$attributes .= ' ' . $attr . '="' . $value . '"';
		}

		$title       = apply_filters( 'the_title', $item->title, $item->ID );
		$badge       = 0 === $depth ? tailwindscore_nav_item_badge_html( $item, 'top' ) : tailwindscore_nav_item_badge_html( $item, 'inline' );
		$link_before = isset( $args->link_before ) ? $args->link_before : '';
		$link_after  = isset( $args->link_after ) ? $args->link_after : '';

		// Append chevron icon for top-level items with a dropdown (desktop only).
		$chevron = ( 0 === $depth && $has_children && 'desktop' === $this->variant )
			? $this->render_chevron_icon()
			: '';

		return '<a' . $attributes . '>' . $badge . $link_before . esc_html( $title ) . $link_after . $chevron . '</a>';
	}

	/**
	 * @param list<string> $item_classes Item classes.
	 */
	private function render_mobile_drill_button( object $item, array $item_classes, string $id_attr ): string {
		$panel_id = 'menu-item-' . $item->ID;
		$title    = apply_filters( 'the_title', $item->title, $item->ID );
		$url      = ! empty( $item->url ) ? $item->url : '';
		$classes  = array( 'ts-nav__link', 'ts-nav__link--drill' );

		if ( tailwindscore_nav_item_is_mega( $item ) ) {
			$classes[] = 'ts-nav__link--drill-mega';
		}

		$badge = tailwindscore_nav_item_badge_html( $item, 'top' );

		return '<li' . $id_attr . ' class="' . esc_attr( implode( ' ', $item_classes ) ) . '">'
			. '<button type="button" class="' . esc_attr( implode( ' ', $classes ) ) . '"'
			. ' data-ts-nav-panel-trigger data-ts-nav-panel="' . esc_attr( $panel_id ) . '"'
			. ' data-ts-nav-title="' . esc_attr( $title ) . '"'
			. ' data-ts-nav-url="' . esc_url( $url ) . '"'
			. ' aria-haspopup="true" aria-expanded="false">'
			. $badge
			. esc_html( $title )
			. '</button></li>';
	}

	/**
	 * @param string $output Nav output.
	 * @param object $item   Menu item.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$args         = is_object( $args ) ? $args : (object) $args;
		$target       = &$this->output_buffer( $output );
		$is_mega      = tailwindscore_nav_item_is_mega( $item );
		$has_children = tailwindscore_nav_item_has_children( $item, $args );
		$item_classes = $this->get_item_classes( $item, $depth, $args );
		$class_names  = implode( ' ', array_map( 'esc_attr', $item_classes ) );
		$item_id      = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
		$id_attr      = $item_id ? ' id="' . esc_attr( $item_id ) . '"' : '';

		// Mobile drill-down: top-level items with children open a slide panel.
		if ( 0 === $depth && 'mobile' === $this->variant && $has_children ) {
			$panel_id = 'menu-item-' . $item->ID;
			$title    = apply_filters( 'the_title', $item->title, $item->ID );

			$output .= $this->render_mobile_drill_button( $item, $item_classes, $id_attr );

			$this->mobile_active_panel_id           = $panel_id;
			$this->mobile_panel_buffers[ $panel_id ] = array(
				'title'   => $title,
				'is_mega' => $is_mega,
				'html'    => '',
			);

			if ( $is_mega ) {
				$this->in_mega_menu = true;
			}

			return;
		}

		// Mega menu — top level (desktop).
		if ( 0 === $depth && $is_mega ) {
			$this->in_mega_menu = true;
			$target            .= '<li' . $id_attr . ' class="' . esc_attr( $class_names ) . '">';
			$target            .= $this->render_link( $item, $depth, $args, 'ts-nav__link' );
			return;
		}

		// Mega — column (depth 1).
		if ( $this->in_mega_menu && 1 === $depth ) {
			$target .= '<div class="ts-nav__mega-col">';
			$target .= $this->render_link( $item, $depth, $args, 'ts-nav__mega-heading' );
			return;
		}

		// Mega — link (depth 2).
		if ( $this->in_mega_menu && 2 === $depth ) {
			$target .= '<li class="ts-nav__mega-list-item">';
			$target .= $this->render_link( $item, $depth, $args, 'ts-nav__mega-link' );
			return;
		}

		// Standard list item.
		$before      = isset( $args->before ) ? $args->before : '';
		$after       = isset( $args->after ) ? $args->after : '';
		$item_output = $before . $this->render_link( $item, $depth, $args, 'ts-nav__link' ) . $after;
		$item_output = apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );

		$target .= '<li' . $id_attr . ' class="' . esc_attr( $class_names ) . '">';
		$target .= $item_output;
	}

	/**
	 * @param string $output Nav output.
	 * @param object $item   Menu item.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$target  = &$this->output_buffer( $output );
		$is_mega = tailwindscore_nav_item_is_mega( $item );

		if ( 0 === $depth && 'mobile' === $this->variant && null !== $this->mobile_active_panel_id ) {
			if ( $is_mega ) {
				$this->in_mega_menu = false;
			}
			$this->mobile_active_panel_id = null;
			return;
		}

		if ( 0 === $depth && $is_mega && $this->in_mega_menu ) {
			$this->in_mega_menu = false;
			$target            .= '</li>';
			return;
		}

		if ( $this->in_mega_menu && 1 === $depth ) {
			$target .= '</div>';
			return;
		}

		if ( $this->in_mega_menu && 2 === $depth ) {
			$target .= '</li>';
			return;
		}

		$target .= '</li>';
	}

	/**
	 * @param string $output Nav output.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$target = &$this->output_buffer( $output );
		$indent = str_repeat( "\t", $depth );

		if ( $this->in_mega_menu && 0 === $depth ) {
			if ( 'mobile' === $this->variant ) {
				$target .= "\n{$indent}<div class=\"ts-nav__mega-panel ts-nav__mega-panel--mobile\">\n";
				return;
			}
			$target .= "\n{$indent}<div class=\"ts-nav__mega-panel\">\n";
			$target .= "{$indent}<div class=\"ts-nav__mega-grid\">\n";
			return;
		}

		if ( $this->in_mega_menu && 1 === $depth ) {
			$target .= "\n{$indent}<ul class=\"ts-nav__mega-list\">\n";
			return;
		}

		$submenu_class = 'ts-nav__submenu sub-menu';
		if ( isset( $args->sub_menu_class ) && is_string( $args->sub_menu_class ) ) {
			$submenu_class = $args->sub_menu_class;
		}

		$target .= "\n{$indent}<ul class=\"" . esc_attr( $submenu_class ) . "\">\n";
	}

	/**
	 * @param string $output Nav output.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$target = &$this->output_buffer( $output );
		$indent = str_repeat( "\t", $depth );

		if ( $this->in_mega_menu && 0 === $depth ) {
			if ( 'mobile' === $this->variant ) {
				$target .= "{$indent}</div>\n";
				return;
			}
			$target .= "{$indent}</div>\n{$indent}</div>\n";
			return;
		}

		if ( $this->in_mega_menu && 1 === $depth ) {
			$target .= "{$indent}</ul>\n";
			return;
		}

		$target .= "{$indent}</ul>\n";
	}
}
