# 单品产品保障

单品摘要加购按钮下方的全站政策提示（免运 / 退换 / 送达等），经 Kirki repeater 编辑。

## 行为

| 项 | 说明 |
|----|------|
| 位置 | `woocommerce_single_product_summary` priority 35（加购之后、form 外） |
| 范围 | 全站统一；不做单品 / 分类覆盖 |
| 开关 | `product_assurances_enable`（默认开） |
| 空行 | 文案为空的 repeater 行不渲染；无有效行则不输出 DOM |

## Customizer

路径：外观 → 自定义 → 商品详情页 → **产品保障**

Repeater 每行字段：

| 字段 | 说明 |
|------|------|
| 图标 | 预设：`truck` / `box-return` / `circle-check` / `package` / `gift` / `lock` |
| 文案 | 前台展示文字（默认英文） |
| 链接 URL | 可选 |
| 链接文字 | 可选；有 URL 无 label 时整段文案可点 |

默认三条：Enjoy free shipping；30-Day Free Returns & Exchanges；Estimated delivery in 1–2 days。

## 实现文件

| 层 | 路径 |
|----|------|
| PHP | `inc/woocommerce/product-assurances.php`（经 `inc/bootstrap.php` 加载） |
| 模板 | `template-parts/woocommerce/product-assurances.php` |
| SCSS | `src/scss/woocommerce/_product-assurances.scss`（`woo.scss` `@use`） |
| 图标 | `inc/icons.php` → `tailwindscore_icon()` |

## 维护

1. 新增预设图标时：在 `inc/icons.php` 增加 SVG，并加入 `tailwindscore_product_assurances_icon_choices()`
2. 样式只用 `--ts-*` token，组件内不硬编码色值 / 间距
3. 修改后执行 `npm run build`
