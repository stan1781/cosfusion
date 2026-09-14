<?php
declare(strict_types=1);

/**
 * My Account — Wishlist endpoint.
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() || ! class_exists( 'Tailwindscore_Wishlist_Repository' ) ) {
	return;
}

$user_id = get_current_user_id();
$items   = Tailwindscore_Wishlist_Repository::enrich_list(
	Tailwindscore_Wishlist_Repository::get_items( $user_id )
);
?>
<div class="wishlist-account" data-ts-wishlist-account>
	<header class="wishlist-account__header">
		<h2 class="wishlist-account__title"><?php esc_html_e( 'Wishlist', 'tailwindscore' ); ?></h2>
		<p class="wishlist-account__count" data-ts-wishlist-count>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of wishlist items */
					_n( '%d item', '%d items', count( $items ), 'tailwindscore' ),
					count( $items )
				)
			);
			?>
		</p>
	</header>

	<?php if ( empty( $items ) ) : ?>
		<div class="wishlist-account__empty" data-ts-wishlist-empty>
			<p><?php esc_html_e( 'Your wishlist is empty.', 'tailwindscore' ); ?></p>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					<?php esc_html_e( 'Continue shopping', 'tailwindscore' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<ul class="wishlist-account__list" data-ts-wishlist-list>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$product_id     = (int) ( $item['productId'] ?? 0 );
				$is_variable    = ! empty( $item['isVariable'] );
				$can_add_direct = ! empty( $item['canAddDirect'] );
				$name           = (string) ( $item['name'] ?? '' );
				$permalink      = (string) ( $item['permalink'] ?? '#' );
				?>
				<li
					class="wishlist-account__item"
					data-ts-wishlist-row
					data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
				>
					<div class="wishlist-account__media">
						<a href="<?php echo esc_url( $permalink ); ?>">
							<?php echo wp_kses_post( (string) ( $item['image'] ?? '' ) ); ?>
						</a>
					</div>
					<div class="wishlist-account__info">
						<a class="wishlist-account__name" href="<?php echo esc_url( $permalink ); ?>">
							<?php echo esc_html( $name ); ?>
						</a>
						<div class="wishlist-account__price">
							<?php echo wp_kses_post( (string) ( $item['priceHtml'] ?? '' ) ); ?>
						</div>
					</div>
					<div class="wishlist-account__actions">
						<?php if ( $is_variable ) : ?>
							<button
								type="button"
								class="btn btn-primary btn-sm"
								data-ts-quick-view
								data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
							>
								<?php esc_html_e( 'Quick view', 'tailwindscore' ); ?>
							</button>
						<?php elseif ( $can_add_direct ) : ?>
							<button type="button" class="btn btn-primary btn-sm" data-ts-wishlist-add-cart>
								<?php esc_html_e( 'Add to cart', 'tailwindscore' ); ?>
							</button>
						<?php else : ?>
							<a class="btn btn-outline-primary btn-sm" href="<?php echo esc_url( $permalink ); ?>">
								<?php esc_html_e( 'View product', 'tailwindscore' ); ?>
							</a>
						<?php endif; ?>
						<button
							type="button"
							class="btn btn-outline btn-sm wishlist-account__remove"
							data-ts-wishlist-remove
							aria-label="<?php esc_attr_e( 'Remove from wishlist', 'tailwindscore' ); ?>"
						>
							<span class="icon" aria-hidden="true"><?php echo tailwindscore_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG 来自主题图标 map。 ?></span>
						</button>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
