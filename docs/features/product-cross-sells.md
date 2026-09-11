# 单品配件搭配（Cross-sells）

单品主区（media + summary）下方的弱推荐列表，数据源为 WooCommerce **Cross-sells**（配件 / 搭配）。不与主品合并提交。

## 行为

| 项 | 说明 |
|----|------|
| 位置 | 默认 `woocommerce_after_single_product_summary` priority **8**（Tabs 10 / Related 20 之前）；可用 `tailwindscore/product/cross_sells_hook` 改挂载 |
| 形态 | 复用 [产品轮播](product-carousel.md) 壳：`card=list` + `carousel=0`；手机 1 列、桌面 **3** 列；横卡样式与 Recently viewed list 共用 |
| 价格 / 操作 | 标准 `wc-product-card`（价、评分、加购 / QV、心愿单） |
| 数据 | 当前商品 `get_cross_sell_ids()`；无可展示项则不输出 DOM |
| 交互 | 简单品：`ajax_add_to_cart`（Store API → Drawer）；变体：现有 Quick View |
| 开关 | `product_cross_sells_enable`（默认开） |

## Customizer

路径：外观 → 自定义 → 商品详情页 → **配件搭配（Cross-sells）**

| 字段 | 说明 |
|------|------|
| 显示配件搭配 | 开关 |
| 区块标题 | 默认 `Complete the look` |
| 显示数量 | 1–12，默认 6 |

## 实现文件

| 层 | 路径 |
|----|------|
| 共享引擎 | `inc/woocommerce/product-suggestions.php` |
| PDP 渲染 / Kirki | `inc/woocommerce/product-cross-sells.php`（调用 `tailwindscore_render_products_carousel_shell`） |
| 轮播壳 / 循环卡 | `template-parts/woocommerce/product-carousel.php`、`woocommerce/content-product.php` |
| 横卡公共样式 | `src/scss/components/_product-card-list.scss`（经 `app.scss`；Recently viewed / Cross-sells / 短代码 `card=list`） |
| PDP 区块间距 | `src/scss/components/_product-carousel.scss` → `.product-carousel--cross-sells` |

## 与 Related / 购物车

- Related 模块继续排除 upsell + cross-sell，避免页底重复
- 购物车推荐仍用产品轮播壳 + 瀑布取数，见 [购买流程](purchase-flow.md) 与 [产品轮播](product-carousel.md)

## 维护

1. PDP 改版挪位置：过滤 `tailwindscore/product/cross_sells_hook`（`hook` + `priority`），勿写死第二套渲染
2. 横卡视觉改动只改 `_product-card-list.scss`，勿在 Cross-sells / Recently viewed 各写一份
3. 修改后执行 `npm run build`
