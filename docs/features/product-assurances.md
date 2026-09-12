# 单品产品保障

单品摘要加购按钮下方的全站政策提示（免运 / 退换 / 送达等），经 Kirki repeater 编辑。横排信任条：图标 + 标题 / 副标题。

## 行为

| 项 | 说明 |
|----|------|
| 位置 | `woocommerce_single_product_summary` priority 35（加购之后、form 外） |
| 范围 | 全站统一；不做单品 / 分类覆盖 |
| 开关 | `product_assurances_enable`（默认开） |
| 空行 | 标题与副标题皆空的 repeater 行不渲染；无有效行则不输出 DOM |
| 布局 | 桌面（`md+`）一行等分；手机横滑，`scroll-snap`，约露出 1.5 项；图标约 `2rem`（`calc(base * 2)`） |
| 兼容 | 旧字段 `text` 在 normalize 时迁入 `title` |

## Customizer

路径：外观 → 自定义 → 商品详情页 → **产品保障**

Repeater 每行字段：

| 字段 | 说明 |
|------|------|
| 图标 | 预设：`truck` / `box-return` / `circle-check` / `package` / `gift` / `lock`（多数对齐 [Heroicons](https://heroicons.com/) Outline：`truck` / `arrow-path` / `check-circle` / `cube` / `gift`；`lock` 为 Solid `lock-closed`，模板须加 `.icon--solid`） |
| 标题 | 主行文案（默认英文，`font-weight: 600`） |
| 副标题 | 次行说明（更小、次要色） |
| 链接 URL | 可选 |
| 链接文字 | 可选；有 URL 无 label 时整块 body（标题+副标题）可点 |

默认三条：

| 图标 | 标题 | 副标题 |
|------|------|--------|
| truck | Free Shipping | Enjoy free shipping |
| box-return | 30-Day Returns | Free Returns & Exchanges |
| circle-check | Fast Delivery | Estimated in 1–2 days |

## 实现文件

| 层 | 路径 |
|----|------|
| PHP | `inc/woocommerce/product-assurances.php`（经 `inc/bootstrap.php` 加载） |
| 模板 | `template-parts/woocommerce/product-assurances.php` |
| SCSS | `src/scss/woocommerce/_product-assurances.scss`（`woo.scss` `@use`） |
| 图标 | `inc/icons.php` → `tailwindscore_icon()` |

## 维护

1. 新增预设图标时：优先用 Heroicons Outline 24 path 写入 `inc/icons.php`，并加入 `tailwindscore_product_assurances_icon_choices()`；勿在 SVG 上写内联 `stroke-width`。Solid 图标（如 `lock`）须在包装上加 `.icon--solid`
2. 样式只用 `--ts-*` token，组件内不硬编码色值 / 间距；字重仅 400 / 600
3. 修改后执行 `npm run build`
