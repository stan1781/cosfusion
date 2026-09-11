# 产品轮播

共享产品列表壳层（默认 Swiper），供短代码、首页精选商品、购物车推荐、404 人气商品与单品 Related 复用。短代码可关轮播或改用 list 横卡。

## 短代码

```text
[ts_products_carousel category="" limit="8" card="grid" carousel="1" columns_mobile="2.5" columns_desktop="4" columns_wide="" gap="16" orderby="date" order="DESC" title=""]
```

| 属性 | 默认 | 说明 |
|------|------|------|
| `category` | 空 | 逗号分隔的 `product_cat` slug 或 term ID |
| `limit` | `8` | 1–24 |
| `card` | `grid` | `grid` 竖卡；`list` 横卡（强制 `carousel=0`）；列数见 `columns_*` |
| `carousel` | `1` | `1` 启用 Swiper；`0` 静态布局（全断点，含 PC） |
| `columns_mobile` | `2.5`（list 未指定时为 `1`） | 1–6；轮播为 slidesPerView，静态/list 取整为 CSS grid 列 |
| `columns_desktop` | `4`（list 未指定时为 `2`） | md 断点及以上 |
| `columns_wide` | 空 | 可选；设值后在 xl 断点生效 |
| `gap` | `16` | 间距 px，0–48 |
| `orderby` | `date` | `date` / `popularity` / `menu_order` / `title` |
| `order` | `DESC` | `ASC` / `DESC` |
| `title` | 空 | 区块标题（[`section-title`](section-title.md)）；空则仅保留导航位 |

### 展示组合

| `card` | `carousel` | 结果 |
|--------|------------|------|
| `grid` | `1` | 竖卡 + Swiper（默认） |
| `grid` | `0` | 竖卡 + CSS grid（无箭头/分页） |
| `list` | `0`（或传入 `1` 被强制） | 横卡 + CSS grid；手机默认 1 列、桌面默认 2 列（可用 `columns_*` 覆盖）；卡片 `padding: --ts-spacer-2`，操作区在 footer 右侧，小型非圆形按钮 |

非法值回退 `grid` + `1`。壳层标记：`data-ts-card`、`data-ts-carousel`；修饰类 `product-carousel--static`、`product-carousel--card-list`。

不支持短代码 `banner` / `view_all`（Cosfusion 不做）。

查询可过滤：`tailwindscore_products_carousel_query_args`。

编辑页侧栏速查见 [主题短代码](shortcode-help.md)。

## 首页

首页为静态 Page，直接在正文放入短代码即可，例如：

```text
[ts_products_carousel limit="8" orderby="date" title="New arrivals"]
[ts_products_carousel limit="8" carousel="0" columns_desktop="3" title="Shop the edit"]
[ts_products_carousel limit="6" card="list" title="Featured"]
```

也可嵌在内容区块 `[block]` 内。不再经 Customizer「首页展示」包装。

## 购物车推荐

- Customizer → 购物车：开关、标题、数量、补齐层排序（**已废弃**全局分类 ID）
- 取数瀑布（`product-suggestions.php`）：① 车内商品 Cross-sells 合并 → ② 车内商品所属分类 → ③ 人气（或配置 orderby）补齐到上限；空则隐藏
- 挂载：**仅购物车页**行项列表下方（不进 Drawer）
- 可变品按钮走 Quick View（Customizer → 商店/归档 → 快速查看 → 生效页面含「购物车页」）
- 运行时：`window.tailwindscoreCart.productSuggestionsHtml`；购物车页 Store API 重绘后再次 `initProductCarousels`
- 排除购物车内已有 product / variation ID
- **不**暴露短代码 `card` / `carousel`（保持默认轮播）

## 单品 Related

- Customizer → 商品详情页 → 相关产品：`product_related_carousel`（默认开）
- 开启：`modules/related-products` 输出共享轮播壳（`section.product-carousel.related.products`）；ID 仍走相关产品引擎 + `wc_get_related_products` 过滤链
- 关闭：恢复 WooCommerce 默认网格 `woocommerce_output_related_products()`
- 总开关 `product_related_enabled` 关闭时不劫持输出
- 移动 2.5 列 / 桌面 4 列；标题文案与 WC 一致（`Related products`）
- **不**支持短代码 banner / view_all；**不**改默认 `card` / `carousel`

## 单品 Cross-sells

见 [配件搭配](product-cross-sells.md)。PDP 复用本壳层 `card=list`（静态横卡，桌面 3 列）；购物车推荐仍用本壳层默认轮播。

## 404 人气商品

- Customizer → 404 页面：开关、标题、数量、排序、分类 ID（`0` = 不限）
- 挂载：404 页快捷导航下方
- 复用同一壳层与全局 `initProductCarousels`；详见 [404 页面](not-found.md)

## 前台资源

| 层 | 路径 |
|----|------|
| PHP | `inc/woocommerce/product-carousel.php`、`product-suggestions.php`、`cart-product-suggestions.php`、`inc/not-found.php`、`modules/related-products/` |
| 模板 | `template-parts/woocommerce/product-carousel.php` |
| TS | `src/ts/shop/product-carousel.ts`（`app.ts` 全局初始化；`data-ts-carousel="0"` 时跳过 Swiper） |
| SCSS | `src/scss/components/_product-carousel.scss`；横卡 `card=list` → `_product-card-list.scss` |

选择器：`[data-ts-product-carousel]`、`data-ts-card`、`data-ts-carousel`、`data-ts-columns-mobile|desktop|wide`、`data-ts-gap`。

导航箭头（`.product-carousel__nav`）尺寸为 `--ts-control-min-height`（与表单控件同高），图标 `--ts-spacer-3`。

## 降级行为

- WooCommerce 未启用或无可见产品 → 不输出
- 仅 1 个产品 + 轮播开启 → 不启 Swiper
- `carousel="0"` → 任意数量都不启 Swiper
- Swiper 初始化失败 → 回退普通 `ul.products` 列表

## 相关文档

- [首页](homepage.md)
- [购买流程](purchase-flow.md)
- [配件搭配](product-cross-sells.md)
- [Recently viewed](recently-viewed.md)
- 设计归档：`docs/archive/superpowers/specs/2026-07-15-product-carousel-design.md`
