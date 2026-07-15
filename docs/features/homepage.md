# 首页

首页由 `front-page.php` 渲染，区块内容与开关通过 Kirki **Customizer** 配置，逻辑集中在 `inc/homepage.php`。

## 配置入口

**外观 → 自定义 → Homepage**（具体面板名以 Kirki 注册为准）

配置存储为 `theme_mod`，通过 `tailwindscore_mod()` 读取（`inc/config/kirki/helpers.php`）。

## 主要区块

首页支持以下可配置区块（以 Kirki repeater / toggle 控制显示与内容）：

| 区块 | 说明 |
|------|------|
| Hero | 主视觉横幅（图/文/CTA） |
| Shop by Occasion | 场合分类网格 |
| Trending Collections | 趋势系列网格 |
| Featured Products | 共享产品轮播壳层（见 [产品轮播](product-carousel.md)） |
| Shop by Color | 按颜色购物（`inc/woocommerce/homepage-shop-by-color.php`） |
| 其他营销区 | 以 `inc/homepage.php` 中 Kirki 字段为准 |

默认值 helper 示例：

- `tailwindscore_homepage_default_products_shortcode()` → `[ts_products_carousel limit="8" orderby="date"]`
- `tailwindscore_homepage_default_occasion_items()` → 场合网格默认项

## 前端交互

`src/ts/home/` 仅处理 Hero Swiper。产品列表轮播由 `src/ts/shop/product-carousel.ts` 统一初始化。

## 样式

首页区块使用 `.home-*` 语义类；精选商品区块复用 `.product-carousel.home-products`（见 `_product-carousel.scss` / `_homepage.scss`），遵循 [设计令牌](../architecture/design-tokens.md)。

## 维护说明

1. 新增首页区块时：在 `inc/homepage.php` 注册 Kirki 字段 + helper，在 `front-page.php` 引用 template part。
2. 避免在模板中硬编码营销文案；默认值放在 helper 函数中便于翻译。
3. 产品列表优先经 `tailwindscore_homepage_render_products_carousel()` / `[ts_products_carousel]`，勿再为首页单独写一套 Swiper 骨架。

## 相关文件

- `front-page.php`
- `inc/homepage.php`
- `inc/woocommerce/product-carousel.php`
- `inc/woocommerce/homepage-shop-by-color.php`
- `template-parts/woocommerce/product-carousel.php`
