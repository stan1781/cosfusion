# 单品配件搭配（Cross-sells）

单品摘要加购附近的弱推荐**紧凑行列表**，数据源为 WooCommerce **Cross-sells**（配件 / 搭配）。不与主品合并提交。

## 行为

| 项 | 说明 |
|----|------|
| 位置 | 默认 `woocommerce_single_product_summary` priority **32**（ATC 30 与 Assurances 35 之间）；可用 `tailwindscore/product/cross_sells_hook` 改挂载 |
| 形态 | 竖排 list：小图 + 名/价 + 操作；非轮播（配件数量通常少，轮播性价比低） |
| 数据 | 当前商品 `get_cross_sell_ids()`；无可展示项则不输出 DOM |
| 交互 | 简单品：`ajax_add_to_cart`（Store API → Drawer）；变体：现有 Quick View |
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
| 模板 | `template-parts/woocommerce/product-cross-sells.php`、`product-cross-sell-item.php` |
| SCSS | `src/scss/woocommerce/_product-cross-sells.scss`（经 `woo.scss`） |

## 与 Related / 购物车

- Related 模块继续排除 upsell + cross-sell，避免页底重复
- 购物车推荐仍用产品轮播壳 + 瀑布取数，见 [购买流程](purchase-flow.md) 与 [产品轮播](product-carousel.md)

## 维护

1. PDP 改版挪位置：过滤 `tailwindscore/product/cross_sells_hook`（`hook` + `priority`），勿写死第二套渲染
2. 样式只用 `--ts-*` token
3. 修改后执行 `npm run build`
