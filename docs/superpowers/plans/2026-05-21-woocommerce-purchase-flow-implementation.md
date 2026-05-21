# WooCommerce Purchase Flow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rework the WooCommerce purchase flow so the cart drawer becomes the primary cart surface, the cart page becomes a lightweight fallback, checkout stays focused on order submission, and purchase-flow notifications use a unified toast-capable interface.

**Architecture:** Move purchase-flow rendering out of mixed site helpers into dedicated WooCommerce helpers and shared template parts. Keep Woo page templates as shells, centralize drawer/cart fragment rendering, and let TypeScript modules own Store API interactions plus notification dispatch without owning page structure.

**Tech Stack:** WordPress theme PHP, WooCommerce templates/hooks, TypeScript, Alpine.js, Vite, Store API

---

## File Structure

### Create

- `template-parts/woocommerce/cart-drawer-content.php` - shared drawer body markup
- `template-parts/woocommerce/cart-line-item.php` - reusable cart line-item partial
- `template-parts/woocommerce/cart-summary.php` - shared subtotal and CTA area
- `template-parts/woocommerce/cart-feedback.php` - inline feedback region for drawer/cart warnings
- `inc/woocommerce/cart-view.php` - purchase-flow rendering helpers and template-part entry points
- `inc/woocommerce/cart-config.php` - frontend config payload builders and feature flags
- `src/ts/cart/notifications.ts` - purchase-flow notification contract and dispatch helpers

### Modify

- `functions.php` - require new WooCommerce helper files
- `inc/enqueue.php` - localize purchase-flow config and notification-related settings
- `inc/template-tags.php` - remove purchase-flow-specific rendering logic that moves into Woo files
- `inc/woocommerce/helpers.php` - keep domain helpers focused on URLs, counts, summary copy
- `inc/woocommerce/hooks.php` - preserve wrapper/hooks, wire any new purchase-flow behavior
- `template-parts/header/cart-drawer.php` - reduce to shell plus shared Woo drawer content mount
- `woocommerce/cart/cart.php` - convert to lightweight fallback shell using shared parts
- `woocommerce/checkout/form-checkout.php` - align summary and feedback structure
- `src/ts/cart/index.ts` - integrate shared rendering assumptions, coupon handling, and unified notifications
- `src/ts/product/index.ts` - keep add-to-cart fallback, route success into drawer-first flow and notifier
- `src/ts/checkout/index.ts` - attach any notification/feedback bridge needed for checkout context

### Verify During Implementation

- `npm run typecheck`
- `npm run build`
- PHP lint on each touched PHP file

## Task 1: Move Purchase-Flow Rendering Out of `inc/template-tags.php`

**Files:**
- Create: `inc/woocommerce/cart-view.php`
- Create: `inc/woocommerce/cart-config.php`
- Modify: `functions.php`
- Modify: `inc/template-tags.php`
- Modify: `inc/woocommerce/helpers.php`

- [ ] **Step 1: Write the failing structural expectation**

Document the target boundary in code comments before moving logic:

```php
// Purchase-flow rendering belongs in inc/woocommerce/cart-view.php.
// Generic site helpers stay in inc/template-tags.php.
```

Expected result: after this task, `inc/template-tags.php` no longer owns cart drawer rendering or purchase-flow config assembly.

- [ ] **Step 2: Add the new helper files and load them**

Create `inc/woocommerce/cart-view.php` and `inc/woocommerce/cart-config.php` with strict types and placeholder function signatures that match the moved responsibilities:

```php
<?php
declare(strict_types=1);

function tailwindscore_render_cart_drawer_content(): void {}

function tailwindscore_render_cart_line_item(array $args = array()): void {}

function tailwindscore_render_cart_summary(array $args = array()): void {}

function tailwindscore_render_cart_feedback(array $args = array()): void {}
```

```php
<?php
declare(strict_types=1);

function tailwindscore_purchase_flow_config(): array {
	return array();
}
```

Load them from `functions.php`:

```php
require_once get_template_directory() . '/inc/woocommerce/cart-view.php';
require_once get_template_directory() . '/inc/woocommerce/cart-config.php';
```

- [ ] **Step 3: Move purchase-flow config assembly out of enqueue/template-tags helpers**

Move the array-building logic for frontend cart config into `tailwindscore_purchase_flow_config()` and leave `inc/enqueue.php` responsible only for serialization:

```php
function tailwindscore_purchase_flow_config(): array {
	return array(
		'storeApiUrl' => rest_url( 'wc/store/v1/' ),
		'nonce'       => wp_create_nonce( 'wc_store_api' ),
		'cartUrl'     => tailwindscore_cart_url(),
		'checkoutUrl' => tailwindscore_checkout_url(),
	);
}
```

Expected result: `inc/enqueue.php` calls one helper to fetch config instead of owning business details.

- [ ] **Step 4: Move cart rendering functions out of `inc/template-tags.php`**

Move functions such as cart drawer rendering and related purchase-flow rendering helpers into `inc/woocommerce/cart-view.php`, leaving generic icon/site helpers behind.

Expected result: `inc/template-tags.php` keeps site-wide helpers like icons and search helpers, while purchase-flow rendering lives under `inc/woocommerce/`.

- [ ] **Step 5: Run PHP lint on the touched helper files**

Run:

```bash
php -l functions.php
php -l inc/template-tags.php
php -l inc/woocommerce/helpers.php
php -l inc/woocommerce/cart-view.php
php -l inc/woocommerce/cart-config.php
```

Expected: all files report `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add functions.php inc/template-tags.php inc/woocommerce/helpers.php inc/woocommerce/cart-view.php inc/woocommerce/cart-config.php
git commit -m "refactor: separate purchase flow helpers"
```

## Task 2: Introduce Shared WooCommerce Purchase-Flow Partials

**Files:**
- Create: `template-parts/woocommerce/cart-drawer-content.php`
- Create: `template-parts/woocommerce/cart-line-item.php`
- Create: `template-parts/woocommerce/cart-summary.php`
- Create: `template-parts/woocommerce/cart-feedback.php`
- Modify: `template-parts/header/cart-drawer.php`
- Modify: `inc/woocommerce/cart-view.php`

- [ ] **Step 1: Write the failing reuse target**

Document the intended reuse in the rendering layer:

```php
// The drawer and lightweight cart page must render line items through the same partial.
```

Expected result: the implementation uses shared partials rather than duplicating drawer/cart markup.

- [ ] **Step 2: Create the shared partials**

Create the new template parts with clear input assumptions. Use `get_template_part()` or a local rendering helper to mount:

```php
get_template_part( 'template-parts/woocommerce/cart-line-item' );
get_template_part( 'template-parts/woocommerce/cart-summary' );
get_template_part( 'template-parts/woocommerce/cart-feedback' );
```

Expected result: `cart-drawer-content.php` becomes the drawer body composition root.

- [ ] **Step 3: Reduce the header cart drawer to a shell**

Update `template-parts/header/cart-drawer.php` so it provides the overlay/container structure and delegates the content body to the new WooCommerce drawer partial.

Expected result: header structure stays header-specific; purchase-flow content becomes Woo-specific.

- [ ] **Step 4: Update server-side render entry points**

In `inc/woocommerce/cart-view.php`, create focused rendering entry points that assemble:

```php
function tailwindscore_render_cart_drawer_content(): void {
	get_template_part( 'template-parts/woocommerce/cart-drawer-content' );
}
```

Expected result: the rest of the theme calls rendering functions, not deep template paths.

- [ ] **Step 5: Run PHP lint on the new partials and render helpers**

Run:

```bash
php -l template-parts/header/cart-drawer.php
php -l template-parts/woocommerce/cart-drawer-content.php
php -l template-parts/woocommerce/cart-line-item.php
php -l template-parts/woocommerce/cart-summary.php
php -l template-parts/woocommerce/cart-feedback.php
php -l inc/woocommerce/cart-view.php
```

Expected: all files report `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add template-parts/header/cart-drawer.php template-parts/woocommerce/cart-drawer-content.php template-parts/woocommerce/cart-line-item.php template-parts/woocommerce/cart-summary.php template-parts/woocommerce/cart-feedback.php inc/woocommerce/cart-view.php
git commit -m "refactor: add shared purchase flow partials"
```

## Task 3: Convert the Cart Page into a Lightweight Fallback

**Files:**
- Modify: `woocommerce/cart/cart.php`
- Modify: `template-parts/woocommerce/cart-line-item.php`
- Modify: `template-parts/woocommerce/cart-summary.php`
- Modify: `template-parts/woocommerce/cart-feedback.php`
- Modify: `inc/woocommerce/cart-view.php`

- [ ] **Step 1: Write the failing behavior target**

Document the intended page role:

```php
// The cart page is a fallback summary/edit surface, not the primary cart workflow.
```

Expected result: the cart page favors summary and checkout progression over heavy editing ceremony.

- [ ] **Step 2: Replace the current cart page composition**

Update `woocommerce/cart/cart.php` to use the shared purchase-flow pieces instead of relying on the default cart-table-heavy path as the primary structure.

Expected result: the cart page remains functional, but visually and structurally feels lighter than the drawer-centric path.

- [ ] **Step 3: Keep WooCommerce compatibility points**

Retain necessary Woo hooks and form semantics so direct cart visits and cart updates still work when JS enhancement is absent.

Expected result: no regression in non-enhanced cart behavior.

- [ ] **Step 4: Ensure CTA hierarchy pushes checkout**

Make the checkout CTA the dominant action and demote the cart page as a stopping point.

Expected result: a direct visual path from cart fallback to checkout.

- [ ] **Step 5: Run PHP lint on the cart-related files**

Run:

```bash
php -l woocommerce/cart/cart.php
php -l template-parts/woocommerce/cart-line-item.php
php -l template-parts/woocommerce/cart-summary.php
php -l template-parts/woocommerce/cart-feedback.php
php -l inc/woocommerce/cart-view.php
```

Expected: all files report `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add woocommerce/cart/cart.php template-parts/woocommerce/cart-line-item.php template-parts/woocommerce/cart-summary.php template-parts/woocommerce/cart-feedback.php inc/woocommerce/cart-view.php
git commit -m "refactor: lighten cart page flow"
```

## Task 4: Align Checkout Summary and Feedback Boundaries

**Files:**
- Modify: `woocommerce/checkout/form-checkout.php`
- Modify: `template-parts/woocommerce/cart-summary.php`
- Modify: `template-parts/woocommerce/cart-feedback.php`
- Modify: `src/ts/checkout/index.ts`

- [ ] **Step 1: Write the failing behavior target**

Document the checkout rule:

```php
// Checkout keeps blocking validation and payment errors in context instead of routing them through toast.
```

Expected result: checkout adopts the purchase-flow language without becoming a cart editor.

- [ ] **Step 2: Restructure checkout summary markup**

Update `woocommerce/checkout/form-checkout.php` so order summary framing, headings, and feedback regions align with the rest of the purchase flow.

Expected result: visual and content hierarchy match the drawer/cart system while preserving Woo checkout behavior.

- [ ] **Step 3: Add checkout-specific feedback bridge**

Update `src/ts/checkout/index.ts` only as needed to tag or expose checkout feedback regions for the unified notification layer without replacing native form-level validation.

Expected result: checkout errors stay inline/page-bound; non-blocking enhancements can still use the shared notifier when appropriate.

- [ ] **Step 4: Run PHP lint and TypeScript typecheck**

Run:

```bash
php -l woocommerce/checkout/form-checkout.php
npm run typecheck
```

Expected: PHP lint passes and `tsc --noEmit` exits successfully.

- [ ] **Step 5: Commit**

```bash
git add woocommerce/checkout/form-checkout.php template-parts/woocommerce/cart-summary.php template-parts/woocommerce/cart-feedback.php src/ts/checkout/index.ts
git commit -m "refactor: align checkout purchase flow feedback"
```

## Task 5: Add a Unified Purchase-Flow Notification Interface

**Files:**
- Create: `src/ts/cart/notifications.ts`
- Modify: `src/ts/cart/index.ts`
- Modify: `src/ts/product/index.ts`
- Modify: `src/ts/checkout/index.ts`
- Modify: `inc/enqueue.php`
- Modify: `inc/woocommerce/cart-config.php`

- [ ] **Step 1: Write the failing interface contract**

Create the notification type definitions first:

```ts
export type NotificationSurface = 'toast' | 'inline' | 'drawer' | 'page';

export type NotificationType = 'success' | 'info' | 'warning' | 'error';

export type PurchaseFlowNotification = {
	type: NotificationType;
	message: string;
	surface: NotificationSurface;
	context?: string;
};
```

Expected result: cart/product/checkout code can target the same notification contract.

- [ ] **Step 2: Implement the notification module**

Build `src/ts/cart/notifications.ts` with a single export such as:

```ts
export function notify(notification: PurchaseFlowNotification) {
	// route to toast, drawer region, inline region, or page region
}
```

Expected result: action handlers no longer write ad hoc feedback directly without a shared abstraction.

- [ ] **Step 3: Add frontend config for notification support**

Expose any needed selectors, capability flags, or copy through `tailwindscore_purchase_flow_config()` and localize them in `inc/enqueue.php`.

Expected result: the notifier has stable server-defined hooks for drawer/page surfaces.

- [ ] **Step 4: Route product and cart actions through the notifier**

Update:

- add-to-cart success -> `toast`, then open drawer
- coupon success -> `toast`
- invalid coupon -> `drawer`
- stock/quantity correction -> `drawer`
- blocking checkout errors -> remain `inline` or `page`

Expected result: purchase-flow feedback rules match the approved spec.

- [ ] **Step 5: Run TypeScript typecheck**

Run:

```bash
npm run typecheck
```

Expected: `tsc --noEmit` exits successfully.

- [ ] **Step 6: Commit**

```bash
git add src/ts/cart/notifications.ts src/ts/cart/index.ts src/ts/product/index.ts src/ts/checkout/index.ts inc/enqueue.php inc/woocommerce/cart-config.php
git commit -m "feat: add unified purchase flow notifications"
```

## Task 6: Extend the Drawer to Own Coupon and Cart-State Feedback

**Files:**
- Modify: `template-parts/woocommerce/cart-drawer-content.php`
- Modify: `template-parts/woocommerce/cart-feedback.php`
- Modify: `src/ts/cart/index.ts`
- Modify: `inc/woocommerce/cart-config.php`
- Modify: `inc/woocommerce/cart-view.php`

- [ ] **Step 1: Write the failing behavior target**

Document the drawer role:

```php
// The drawer is the primary place for cart edits, coupon entry, and cart-state feedback.
```

Expected result: drawer content includes the UI needed for the approved standard feature set.

- [ ] **Step 2: Add coupon entry and feedback regions**

Update the drawer content partial to include a coupon form area and a persistent feedback mount point associated with cart state.

Expected result: coupon failures and stock corrections have a stable in-drawer home.

- [ ] **Step 3: Implement cart-side coupon and feedback handling**

Extend `src/ts/cart/index.ts` to submit coupon actions through the available Woo/Store path, refresh drawer state, and emit notifications to the correct surface.

Expected result: coupon application works without forcing a cart-page detour.

- [ ] **Step 4: Verify degraded behavior**

Ensure the drawer still renders a sensible non-enhanced state when JS is unavailable or a request fails.

Expected result: the purchase flow degrades safely instead of hiding critical cart state.

- [ ] **Step 5: Run TypeScript typecheck and PHP lint**

Run:

```bash
npm run typecheck
php -l template-parts/woocommerce/cart-drawer-content.php
php -l template-parts/woocommerce/cart-feedback.php
php -l inc/woocommerce/cart-config.php
php -l inc/woocommerce/cart-view.php
```

Expected: all checks pass.

- [ ] **Step 6: Commit**

```bash
git add template-parts/woocommerce/cart-drawer-content.php template-parts/woocommerce/cart-feedback.php src/ts/cart/index.ts inc/woocommerce/cart-config.php inc/woocommerce/cart-view.php
git commit -m "feat: extend drawer cart workflow"
```

## Task 7: Preserve Drawer-First Add-to-Cart Behavior on Product Pages

**Files:**
- Modify: `src/ts/product/index.ts`
- Modify: `src/ts/cart/index.ts`
- Modify: `template-parts/header/cart-drawer.php`

- [ ] **Step 1: Write the failing behavior target**

Document the user path:

```ts
// Successful product add-to-cart should keep the user in flow by opening the cart drawer.
```

Expected result: product-page add-to-cart remains drawer-first after the refactor.

- [ ] **Step 2: Keep progressive enhancement fallback**

Retain the current pattern where failed enhanced submission falls back to native form submission.

Expected result: the user is not blocked if JS enhancement or Store API handling fails.

- [ ] **Step 3: Confirm the drawer open event still works with the new shell**

Ensure `tailwindscore:open-cart` still targets the updated header drawer shell and opens the correct surface after successful add-to-cart.

Expected result: the refactor does not break the primary add-to-cart path.

- [ ] **Step 4: Run TypeScript typecheck**

Run:

```bash
npm run typecheck
```

Expected: `tsc --noEmit` exits successfully.

- [ ] **Step 5: Commit**

```bash
git add src/ts/product/index.ts src/ts/cart/index.ts template-parts/header/cart-drawer.php
git commit -m "refactor: preserve drawer first add to cart flow"
```

## Task 8: Full Verification Pass

**Files:**
- Verify only

- [ ] **Step 1: Run PHP lint across all touched PHP files**

Run:

```bash
php -l functions.php
php -l inc/enqueue.php
php -l inc/template-tags.php
php -l inc/woocommerce/helpers.php
php -l inc/woocommerce/hooks.php
php -l inc/woocommerce/cart-view.php
php -l inc/woocommerce/cart-config.php
php -l template-parts/header/cart-drawer.php
php -l template-parts/woocommerce/cart-drawer-content.php
php -l template-parts/woocommerce/cart-line-item.php
php -l template-parts/woocommerce/cart-summary.php
php -l template-parts/woocommerce/cart-feedback.php
php -l woocommerce/cart/cart.php
php -l woocommerce/checkout/form-checkout.php
```

Expected: every file reports `No syntax errors detected`.

- [ ] **Step 2: Run TypeScript verification**

Run:

```bash
npm run typecheck
```

Expected:

```text
> tailwindscore@0.1.0 typecheck
> tsc --noEmit
```

- [ ] **Step 3: Run build verification**

Run:

```bash
npm run build
```

Expected: Vite build completes and writes updated assets into `assets/dist`.

If the environment blocks `esbuild` process spawning, record that explicitly and do not claim build success without evidence.

- [ ] **Step 4: Manually verify purchase-flow behavior**

Verify in a browser:

- single-product add-to-cart opens drawer
- drawer quantity updates work
- drawer remove works
- drawer coupon success shows toast
- drawer coupon failure shows drawer-bound feedback
- cart page remains functional as lightweight fallback
- checkout shows blocking validation in context

Expected result: the primary customer path is `product -> drawer -> checkout`.

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "test: verify purchase flow refactor"
```
