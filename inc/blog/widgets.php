<?php
declare(strict_types=1);

/**
 * 博客侧栏小工具。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 热门文章小工具（按评论数排序）。
 */
class Tailwindscore_Popular_Posts_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'tailwindscore_popular_posts',
			__( 'Popular Posts', 'tailwindscore' ),
			array(
				'description' => __( 'A list of posts ordered by comment count.', 'tailwindscore' ),
				'classname'   => 'widget_tailwindscore_popular_posts',
			)
		);
	}

	/**
	 * @param array<string, mixed> $args     侧栏参数。
	 * @param array<string, mixed> $instance 实例设置。
	 */
	public function widget( $args, $instance ): void {
		$title = isset( $instance['title'] ) ? (string) $instance['title'] : '';
		$title = $title !== '' ? $title : __( 'Popular posts', 'tailwindscore' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = isset( $instance['number'] ) ? (int) $instance['number'] : 5;
		$posts  = tailwindscore_get_popular_posts( $number );

		if ( array() === $posts ) {
			return;
		}

		echo isset( $args['before_widget'] ) ? $args['before_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( is_string( $title ) && '' !== $title ) {
			$before = isset( $args['before_title'] ) ? (string) $args['before_title'] : '<h2 class="widget-title">';
			$after  = isset( $args['after_title'] ) ? (string) $args['after_title'] : '</h2>';
			echo $before . esc_html( $title ) . $after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<ul class="post-list post-list--compact">';
		foreach ( $posts as $post ) {
			get_template_part(
				'template-parts/content/post-card',
				null,
				array(
					'post'    => $post,
					'variant' => 'compact',
					'tag'     => 'li',
				)
			);
		}
		echo '</ul>';

		echo isset( $args['after_widget'] ) ? $args['after_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array<string, mixed> $instance 当前设置。
	 */
	public function form( $instance ): void {
		$title  = isset( $instance['title'] ) ? (string) $instance['title'] : __( 'Popular posts', 'tailwindscore' );
		$number = isset( $instance['number'] ) ? (int) $instance['number'] : 5;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'tailwindscore' ); ?></label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'tailwindscore' ); ?></label>
			<input
				class="tiny-text"
				id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
				type="number"
				min="1"
				max="12"
				value="<?php echo esc_attr( (string) $number ); ?>"
			/>
		</p>
		<?php
	}

	/**
	 * @param array<string, mixed> $new_instance 新设置。
	 * @param array<string, mixed> $old_instance 旧设置。
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		unset( $old_instance );

		return array(
			'title'  => isset( $new_instance['title'] ) ? sanitize_text_field( (string) $new_instance['title'] ) : '',
			'number' => isset( $new_instance['number'] ) ? max( 1, min( 12, (int) $new_instance['number'] ) ) : 5,
		);
	}
}

/**
 * @return void
 */
function tailwindscore_register_blog_widgets(): void {
	register_widget( Tailwindscore_Popular_Posts_Widget::class );
}
add_action( 'widgets_init', 'tailwindscore_register_blog_widgets' );
