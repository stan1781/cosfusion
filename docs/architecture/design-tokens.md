# 设计令牌

TailwindScore 使用 **SCSS 变量 → `:root` CSS 变量 → 组件 `var(--ts-*)`** 的单向数据流。结构与命名对齐 Starcore；颜色字面量保留本站时尚风，其余非颜色数值与 Starcore 一致。

## 唯一真源

| 文件 | 职责 |
|------|------|
| `src/scss/abstracts/_variables.scss` | 所有 `$ts-*` 字面量（颜色、间距、断点 rem 等） |
| `src/scss/abstracts/_root.scss` | 将 `$ts-*` 导出为 `:root` 上的 `--ts-*` |
| `src/scss/abstracts/_mixins.scss` | `ts-up` / `ts-down` 断点 mixin、表单/动效 mixin、`ts-price-html` / `ts-price-sale` 价格展示 |
| `src/ts/breakpoints.ts` | JS `matchMedia` 与 SCSS 断点对齐 |

**改风格时优先只改 `_variables.scss`**，不要在各组件里逐个改数值。断点与抽屉宽等可用 Sass 直接引用，不必全部进 `:root`。

## 令牌类别

| 类别 | 示例 token |
|------|------------|
| 品牌色 | `primary` / `on-primary`、`secondary` / `on-secondary`、`link-color` |
| Surface | `surface`、`surface-alt`、`surface-muted` |
| 文本 | `text-primary`、`text-secondary`、`text-muted` |
| 边框 | `border-color`、`border-color-strong`（本站扩展） |
| 反馈色 | `info`、`success`、`warning`、`danger` |
| 遮罩 | `overlay`、`overlay-dark` |
| 间距 | `$ts-spacer` + `$ts-spacers`（0–5）→ `--ts-spacer-0` … `--ts-spacer-5` |
| 圆角 | `--ts-border-radius-sm`、`--ts-border-radius`、`--ts-border-radius-lg`、`--ts-border-radius-pill` |
| 字号 / 行高 | `--ts-font-size-xs` … `lg`、`--ts-line-height-base` |
| 字体栈 | `--ts-font-body`、`--ts-font-heading`（typography 模块可覆盖） |
| 阴影 | `--ts-shadow-sm`、`md`、`lg` |
| 布局 | `--ts-container-max`、`$ts-drawer-width` / `--ts-drawer-width` |
| z-index | `--ts-z-sticky`、`panel`、`drawer`、`modal`、`toast` |
| 控件 | `--ts-control-min-height`、`--ts-form-padding-*`、`--ts-btn-padding-*`；floating 表单另有 `--ts-form-floating-*` |

单页尺寸（hero 高度、search 最大宽、section 垂直间距等）**不进全局 token**，写在对应组件局部 Sass 变量中。

## 响应式断点

断点名称与常见 Tailwind 惯例一致，数值以 **rem** 为真源（16px root）：

| 名称 | Sass 变量 | 默认换算 |
|------|-----------|----------|
| `sm` | `$ts-breakpoint-sm` | 40rem (640px) |
| `md` | `$ts-breakpoint-md` | 48rem (768px) |
| `lg` | `$ts-breakpoint-lg` | 64rem (1024px) |
| `xl` | `$ts-breakpoint-xl` | 80rem (1280px) |
| `2xl` | `$ts-breakpoint-2xl` | 96rem (1536px) |

### 移动优先约定

| 视口 | 约定 |
|------|------|
| `< md` | 手机默认样式：单列、抽屉导航、全宽控件 |
| `md`～`lg` 以下 | 大屏手机/小平板；仅在需要时加 `md` 断点 |
| `lg` 及以上 | 桌面主断点：inline 导航、页头布局切换 |
| `xl` / `2xl` | 按页面渐进增强列数、间距、超宽留白 |

**设计基准**：桌面视觉稿以 `2xl` 为主要参考画布；实现仍从无媒体查询的手机样式写起，通过 `min-width` 向上叠加。

### 组件 SCSS 必须

```scss
@use "../abstracts/mixins";

@include mixins.ts-up(lg) {
  /* 桌面布局 */
}
```

- 使用 `@include mixins.ts-up($name)` / `ts-down($name)`
- `ts-up` → `@media (width >= …)`；`ts-down` → `@media (width < …)`
- 样式属性写 `var(--ts-text-primary)` 等 CSS 变量

### 组件 SCSS 禁止

- 裸写 `@media (min-width: 1024px)` 等断点 px/rem
- `1023.98px` 等缝隙 hack（用 `ts-down(lg)` 代替）
- 在 `@media` 中使用 `var(--ts-breakpoint-*)`（浏览器不支持）
- 组件内 hex/rgb/裸 rem·px（与 token 等价的）
- `var(--ts-*, #fallback)` 带色值 fallback
- 组件内新建 `:root { --ts-… }`

### 例外（特性查询）

以下可保留在组件内直接写 `@media`：

- `(hover: hover)` / `(hover: none)`
- `(prefers-reduced-motion: reduce)`

### TypeScript 对齐

```ts
import { TS_MEDIA_UP } from '../breakpoints';

const mq = window.matchMedia(TS_MEDIA_UP.lg);
```

Swiper `breakpoints`、手风琴/Tab 切换等运行时逻辑须与 SCSS 断点一致，禁止硬编码 `'(min-width: 1024px)'` 或 `768`。

## Token 提升规则

**适合进入 `_variables.scss`：**

- 跨多个组件复用
- 未来换肤或品牌替换需要
- 调整频率高、值得集中管理

**不应默认进入全局令牌：**

- 单个区域的一次性尺寸（如 header 高度、hero 高度、某面板宽度）
- 只在一个组件出现一次的布局细节
- 套餐站专用比例（如商品图 aspect-ratio）

## 与站点壳层、商城的关系

- **Header / 主导航**：移动壳 `< lg`，桌面 inline 导航 `≥ lg`
- **WooCommerce**：结账/账户可在 `lg` 启用多列；商品网格在 `md`/`xl` 按需加分列
- **产品详情 Tab**：手风琴 `< md`，两列网格 `≥ md`
- **容器宽度**：`--ts-container-max` 在较宽视口内居中

## 价格 HTML（`get_price_html`）

促销价展示集中在 mixin，避免单品 / 卡片 / sticky bar / widget 各写一份 `del`/`ins`：

| Mixin | 用途 |
|-------|------|
| `ts-price-sale($del-font-size)` | 仅原价/特价标记样式；布局另有约束时用 |
| `ts-price-html($font-size, $del-font-size)` | 行内布局 + 促销样式；包在 `.price` 或直接包 HTML 的容器上 |

调用方只传字号档位（如单品 `lg`/`sm`，sticky `sm`/`xs`）。

## 扩展令牌流程

1. 查现有阶梯是否已有合适 token
2. 在 `_variables.scss` 增加，并同步 `_root.scss` 与 `src/ts/breakpoints.ts`（若涉及断点）
3. 禁止在组件 SCSS/TS 临时写死

## 相关文件

- Cursor 规则：`.cursor/rules/scss-design-tokens.mdc`（与本文档同步，供 AI/编辑器引用）
- 归档设计说明：[archive/superpowers/specs/2026-05-19-site-shell-and-theme-tokens-design.md](../archive/superpowers/specs/2026-05-19-site-shell-and-theme-tokens-design.md)
