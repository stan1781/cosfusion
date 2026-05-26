<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-billing-fields">
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<?php
	tailwindscore_render_checkout_field_group(
		$checkout,
		'billing',
		tailwindscore_get_checkout_billing_layout()
	);
	?>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
