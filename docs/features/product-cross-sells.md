# 单品配件搭配（Cross-sells）

单品摘要加购附近的弱推荐迷你轮播，数据源为 WooCommerce **Cross-sells**（配件 / 搭配）。不与主品合并提交。

## 行为

| 项 | 说明 |
|----|------|
| 位置 | 默认 `woocommerce_single_product_summary` priority **32**（ATC 30 与 Assurances 35 之间）；可用 `tailwindscore/product/cross_sells_hook` 改挂载 |
| 数据 | 当前商品 `get_cross_sell_ids()`；无可展示项则不输出 DOM |
| 交互 | 简单品：循环卡 AJAX 加购（Store API → Drawer）；变体：现有 Quick View |
| 开关 | `product_cross_sells_enable`（默认开） |

## Customizer

路径：外观 → 自定义 → WooCommerce → **配件搭配（Cross-sells）**

| 字段 | 说明 |
|------|------|
| 显示配件搭配 | 开关 |
| 区块标题 | 默认 `Complete the look` |
| 显示数量 | 1–12，默认 6 |

## 实现文件

| 层 | 路径 |
|----|------|
| 共享引擎 | `inc/woocommerce/product-suggestions.php` |
| PDP 渲染 / Kirki | `inc/woocommerce/product-cross-sells.php` |
| 轮播壳 | `inc/woocommerce/product-carousel.php` + `template-parts/woocommerce/product-carousel.php` |
| SCSS | `src/scss/components/_product-carousel.scss`（`.product-cross-sells`） |

## 与 Related / 购物车

- Related 模块继续排除 upsell + cross-sell，避免页底重复
- 购物车推荐瀑布见 [购买流程](purchase-flow.md) 与 [产品轮播](product-carousel.md)

## 维护

1. PDP 改版挪位置：过滤 `tailwindscore/product/cross_sells_hook`（`hook` + `priority`），勿写死第二套渲染
2. 样式只用 `--ts-*` token
3. 修改后执行 `npm run build`
