# Header And Footer Template Boundaries Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduce template sprawl by splitting `header.php` along real UI responsibilities, simplify the footer template by removing one low-value partial, and fix the small footer fallback defects discovered during review.

**Architecture:** Keep `header.php` as the site-shell entry point that owns only document structure and the shell state container, then move each overlay or action group into a focused template part. Keep footer composition shallow: retain parts that have their own rendering logic, but inline the trivial `footer-top` wrapper back into `footer.php`.

**Tech Stack:** WordPress theme PHP, WooCommerce theme integration, existing template-part structure

---

### Task 1: Record the current template boundaries

**Files:**
- Modify: `docs/superpowers/plans/2026-05-21-header-footer-template-boundaries.md`
- Review: `header.php`
- Review: `footer.php`
- Review: `template-parts/footer/footer-top.php`
- Review: `template-parts/footer/footer-info.php`
- Review: `template-parts/footer/footer-widgets.php`

- [ ] **Step 1: Confirm the current responsibilities before editing**

Record these current observations in the working notes for execution:
- `header.php` currently owns header chrome, action buttons, desktop nav, search modal, mobile menu drawer, and cart drawer.
- `footer-top.php` is a thin wrapper around a single sidebar and has low standalone value.
- `footer-info.php` owns meaningful branching logic and should stay independent.
- `footer-widgets.php` owns the footer column loop and should stay independent.

### Task 2: Split header actions into focused template parts

**Files:**
- Create: `template-parts/header/header-actions.php`
- Modify: `header.php`

- [ ] **Step 1: Create the header actions partial**

Move the action-button group from `header.php` into `template-parts/header/header-actions.php`, keeping:
- search toggle button
- account link
- conditional cart toggle button
- menu toggle button

The partial should continue using the existing helpers and Alpine bindings already present in `header.php`.

- [ ] **Step 2: Replace the inline action markup in `header.php`**

Call:

```php
<?php get_template_part( 'template-parts/header/header-actions' ); ?>
```

from the current action-button location so `header.php` keeps the same structure with less local markup.

### Task 3: Split the header overlays by behavior

**Files:**
- Create: `template-parts/header/search-modal.php`
- Create: `template-parts/header/menu-drawer.php`
- Create: `template-parts/header/cart-drawer.php`
- Modify: `header.php`

- [ ] **Step 1: Create the search modal partial**

Move the search dialog block into `template-parts/header/search-modal.php` unchanged in behavior, preserving:
- `x-cloak`
- `x-show="searchOpen"`
- close buttons and `@click.self`
- `get_search_form()`

- [ ] **Step 2: Create the menu drawer partial**

Move the mobile menu drawer block into `template-parts/header/menu-drawer.php`, preserving:
- drawer open state bindings
- close controls
- `template-parts/navigation/primary-nav` call with mobile args

- [ ] **Step 3: Create the cart drawer partial**

Move the WooCommerce cart drawer block into `template-parts/header/cart-drawer.php`, preserving:
- the `function_exists( 'wc_get_cart_url' )` guard
- summary text
- Store API config attributes
- `tailwindscore_render_cart_drawer()`

- [ ] **Step 4: Reduce `header.php` to shell composition**

After extraction, keep `header.php` responsible only for:
- document opening and body wrapper
- shell state container on `<header>`
- branding partial
- header actions partial
- inline desktop navigation include
- the three extracted overlay partials

### Task 4: Simplify footer composition

**Files:**
- Modify: `footer.php`
- Delete: `template-parts/footer/footer-top.php`

- [ ] **Step 1: Inline the footer-top sidebar check**

Replace:

```php
<?php get_template_part( 'template-parts/footer/footer-top' ); ?>
```

with the direct sidebar guard and wrapper markup now contained in that file.

- [ ] **Step 2: Remove the obsolete footer-top partial**

Delete `template-parts/footer/footer-top.php` after confirming `footer.php` now owns that trivial wrapper directly.

### Task 5: Fix the two review issues found during planning

**Files:**
- Modify: `template-parts/footer/footer-info.php`
- Modify: `header.php`

- [ ] **Step 1: Repair the default footer fallback text**

Replace the corrupted copyright prefix in `template-parts/footer/footer-info.php` with a clean ASCII-safe string such as:

```php
esc_html__( 'Copyright %1$s %2$s. All rights reserved.', 'tailwindscore' )
```

- [ ] **Step 2: Remove the unused cart-count variable**

Delete the unused:

```php
<?php $ts_cart_count = tailwindscore_cart_count(); ?>
```

from `header.php` so the file no longer suggests a dependency it does not use.

### Task 6: Verify template behavior after the refactor

**Files:**
- Review: `header.php`
- Review: `footer.php`
- Review: `template-parts/header/header-actions.php`
- Review: `template-parts/header/search-modal.php`
- Review: `template-parts/header/menu-drawer.php`
- Review: `template-parts/header/cart-drawer.php`
- Review: `template-parts/footer/footer-info.php`
- Review: `template-parts/footer/footer-widgets.php`

- [ ] **Step 1: Run PHP lint on the touched templates**

Run:

```bash
php -l header.php
php -l footer.php
php -l template-parts/header/header-actions.php
php -l template-parts/header/search-modal.php
php -l template-parts/header/menu-drawer.php
php -l template-parts/header/cart-drawer.php
php -l template-parts/footer/footer-info.php
php -l template-parts/footer/footer-widgets.php
```

Expected: `No syntax errors detected` for every file.

- [ ] **Step 2: Review the diff for boundary correctness**

Run:

```bash
git diff -- header.php footer.php template-parts/header template-parts/footer/footer-info.php template-parts/footer/footer-widgets.php
```

Expected:
- `header.php` shrinks materially
- only `footer-top.php` disappears from footer partials
- no behavior changes beyond the copyright text fix and the unused variable removal

- [ ] **Step 3: Check working tree scope**

Run:

```bash
git status --short
```

Expected: only the intended header/footer template files and this plan document are changed.
