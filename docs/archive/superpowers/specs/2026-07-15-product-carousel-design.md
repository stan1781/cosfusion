# 产品轮播壳层设计（2026-07-15）

> 归档：已拍板方案。日常以 `docs/features/product-carousel.md` 为准。

## 范围

从 starcore 移植共享产品 Swiper 壳层到 TailwindScore：

- 短代码 `[ts_products_carousel]`
- 首页 Featured Products 改用同一壳层
- 购物车页推荐轮播（不进 Drawer）

刻意不做：404 推荐、`sc_products_carousel` 别名、`type=composite|simple`、推荐 HTML 的动态 REST 刷新。

## 架构（方案 2）

```text
[ts_products_carousel] ──┐
首页 Featured Products ──┼──► parse/query ──► render_shell ──► template
购物车推荐 (Drawer/页) ──┘                              │
                                                       ▼
                                         src/ts/shop/product-carousel.ts
```

- PHP：`inc/woocommerce/product-carousel.php` + `cart-product-suggestions.php`
- 模板：`template-parts/woocommerce/product-carousel.php`（`product-carousel` / `data-ts-*`）
- 样式：`_product-carousel.scss`；购物车用 `.cart-suggestions` modifier
- TS：通用 `initProductCarousels()`；首页仅保留 Hero

## 短代码属性

| 属性 | 默认 | 说明 |
|------|------|------|
| `category` | `''` | 逗号分隔 slug 或 term ID |
| `limit` | `8` | 1–24 |
| `columns_mobile` | `2.5` | 1–6，0.5 步进 |
| `columns_desktop` | `4` | 同上 |
| `columns_wide` | `''` | 可选；空则无 wide 断点 |
| `gap` | `16` | 0–48 px |
| `orderby` | `date` | `date` / `popularity` / `menu_order` / `title` |
| `order` | `DESC` | `ASC` / `DESC` |
| `title` | `''` | 空则只留导航位 |

Filter：`tailwindscore_products_carousel_query_args`。

## 首页

- 调用 `tailwindscore_render_products_carousel_shell()`（经 homepage helper）
- Kirki 控制标题、列数（含 wide）、间距
- `homepage_products_shortcode` 支持 `[products]` / `[ts_products_carousel]` 属性解析
- 默认 shortcode：`[ts_products_carousel limit="8" orderby="date"]`

## 购物车推荐

- Kirki：enable、title、limit、orderby、category_id（默认 0 = 不限分类）
- 列数：mobile `2.5` / desktop `3`
- 排除车内 product/variation ID
- **仅购物车页**挂载；SSR + `tailwindscoreCart.productSuggestionsHtml`；购物车页 Store API 刷新后重插静态 HTML 并 `initProductCarousels`（不进 Drawer）

## 错误 / 降级

- 无 WC / 无结果 → 空字符串
- Swiper 失败 → 回退普通列表
- 单 slide → 不启 Swiper
- 空车或推荐关闭 → 不输出区块
