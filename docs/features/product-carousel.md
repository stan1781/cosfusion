# 产品轮播

共享产品列表 Swiper 壳层，供短代码、首页精选商品、购物车推荐与 404 人气商品复用。

## 短代码

```text
[ts_products_carousel category="" limit="8" columns_mobile="2.5" columns_desktop="4" columns_wide="" gap="16" orderby="date" order="DESC" title=""]
```

| 属性 | 默认 | 说明 |
|------|------|------|
| `category` | 空 | 逗号分隔的 `product_cat` slug 或 term ID |
| `limit` | `8` | 1–24 |
| `columns_mobile` | `2.5` | 1–6，0.5 步进 |
| `columns_desktop` | `4` | md 断点及以上 |
| `columns_wide` | 空 | 可选；设值后在 xl 断点生效 |
| `gap` | `16` | 间距 px，0–48 |
| `orderby` | `date` | `date` / `popularity` / `menu_order` / `title` |
| `order` | `DESC` | `ASC` / `DESC` |
| `title` | 空 | 区块标题；空则仅保留导航位 |

查询可过滤：`tailwindscore_products_carousel_query_args`。

## 首页

首页为静态 Page，直接在正文放入短代码即可，例如：

```text
[ts_products_carousel limit="8" orderby="date" title="New arrivals"]
```

也可嵌在内容区块 `[block]` 内。不再经 Customizer「首页展示」包装。

## 购物车推荐

- Customizer → Cart：开关、标题、数量、补齐层排序（**已废弃**全局分类 ID）
- 取数瀑布（`product-suggestions.php`）：① 车内商品 Cross-sells 合并 → ② 车内商品所属分类 → ③ 人气（或配置 orderby）补齐到上限；空则隐藏
- 挂载：**仅购物车页**行项列表下方（不进 Drawer）
- 可变品按钮走 Quick View（Customizer → 产品 → 快速查看 → 生效页面含「购物车页」）
- 运行时：`window.tailwindscoreCart.productSuggestionsHtml`；购物车页 Store API 重绘后再次 `initProductCarousels`
- 排除购物车内已有 product / variation ID

## 单品 Cross-sells

见 [配件搭配](product-cross-sells.md)。PDP 使用**紧凑行列表**（非本轮播壳）；购物车推荐仍用本壳层。

## 404 人气商品

- Customizer → 404 页面：开关、标题、数量、排序、分类 ID（`0` = 不限）
- 挂载：404 页快捷导航下方
- 复用同一壳层与全局 `initProductCarousels`；详见 [404 页面](not-found.md)

## 前台资源

| 层 | 路径 |
|----|------|
| PHP | `inc/woocommerce/product-carousel.php`、`product-suggestions.php`、`cart-product-suggestions.php`、`inc/not-found.php` |
| 模板 | `template-parts/woocommerce/product-carousel.php` |
| TS | `src/ts/shop/product-carousel.ts`（`app.ts` 全局初始化） |
| SCSS | `src/scss/components/_product-carousel.scss` |

选择器：`[data-ts-product-carousel]`、`data-ts-columns-mobile|desktop|wide`、`data-ts-gap`。

## 降级行为

- WooCommerce 未启用或无可见产品 → 不输出
- 仅 1 个产品 → 不启 Swiper
- Swiper 初始化失败 → 回退普通 `ul.products` 列表

## 相关文档

- [首页](homepage.md)
- [购买流程](purchase-flow.md)
- [配件搭配](product-cross-sells.md)
- 设计归档：`docs/archive/superpowers/specs/2026-07-15-product-carousel-design.md`
