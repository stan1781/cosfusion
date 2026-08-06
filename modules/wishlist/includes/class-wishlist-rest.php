<?php
declare(strict_types=1);

/**
 * 心愿单 REST：tailwindscore/v1（仅父商品）。
 *
 * @package TailwindScore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist REST controller.
 */
class Tailwindscore_Wishlist_Rest {

	public const NAMESPACE = 'tailwindscore/v1';

	/**
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/wishlist',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_wishlist' ),
					'permission_callback' => array( $this, 'require_login' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/wishlist/items',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle_item' ),
					'permission_callback' => array( $this, 'require_login' ),
					'args'                => array(
						'productId' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'require_login' ),
					'args'                => array(
						'productId' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/wishlist/merge',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'merge_items' ),
					'permission_callback' => array( $this, 'require_login' ),
				),
			)
		);
	}

	/**
	 * @return bool
	 */
	public function require_login(): bool {
		return is_user_logged_in();
	}

	/**
	 * @return WP_REST_Response
	 */
	public function get_wishlist(): WP_REST_Response {
		$user_id = get_current_user_id();
		$items   = Tailwindscore_Wishlist_Repository::get_items( $user_id );

		return new WP_REST_Response(
			array(
				'items' => Tailwindscore_Wishlist_Repository::enrich_list( $items ),
				'count' => count( $items ),
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request 请求。
	 * @return WP_REST_Response|WP_Error
	 */
	public function toggle_item( WP_REST_Request $request ) {
		$result = Tailwindscore_Wishlist_Repository::toggle(
			get_current_user_id(),
			array( 'productId' => (int) $request->get_param( 'productId' ) )
		);

		if ( isset( $result['error'] ) && 'invalid_product' === $result['error'] ) {
			return new WP_Error(
				'ts_wishlist_invalid',
				__( 'This product cannot be added to the wishlist.', 'tailwindscore' ),
				array( 'status' => 400 )
			);
		}

		if ( isset( $result['error'] ) && 'limit' === $result['error'] ) {
			return new WP_Error(
				'ts_wishlist_limit',
				__( 'Your wishlist is full. Remove an item to add another.', 'tailwindscore' ),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response(
			array(
				'items' => Tailwindscore_Wishlist_Repository::enrich_list( $result['items'] ),
				'count' => count( $result['items'] ),
				'added' => (bool) $result['added'],
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request 请求。
	 * @return WP_REST_Response
	 */
	public function delete_item( WP_REST_Request $request ): WP_REST_Response {
		$items = Tailwindscore_Wishlist_Repository::remove(
			get_current_user_id(),
			array( 'productId' => (int) $request->get_param( 'productId' ) )
		);

		return new WP_REST_Response(
			array(
				'items' => Tailwindscore_Wishlist_Repository::enrich_list( $items ),
				'count' => count( $items ),
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request 请求。
	 * @return WP_REST_Response
	 */
	public function merge_items( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		$raw  = is_array( $body ) && isset( $body['items'] ) && is_array( $body['items'] )
			? $body['items']
			: array();

		$items = Tailwindscore_Wishlist_Repository::merge( get_current_user_id(), $raw );

		return new WP_REST_Response(
			array(
				'items' => Tailwindscore_Wishlist_Repository::enrich_list( $items ),
				'count' => count( $items ),
			),
			200
		);
	}
}
