---
inclusion: fileMatch
fileMatchPattern: ['**/*.scss', '**/*.ts']
---

# SCSS 设计 Token 规范

结构对齐 Starcore；颜色保留本站；非颜色数值与 Starcore 一致。详见 `docs/architecture/design-tokens.md`。

## 唯一真源

- `src/scss/abstracts/_variables.scss` — 所有 `$ts-*` 字面量（含 rem 断点）
- `src/scss/abstracts/_root.scss` — 导出组件需要的 `--ts-*`（断点用 Sass + mixin，不进 `:root`）
- `src/scss/abstracts/_mixins.scss` — 断点 mixin（`ts-up` / `ts-down`）、表单/动效 mixin
- `src/ts/breakpoints.ts` — JS `matchMedia` 与 SCSS 断点对齐

**改风格时优先只改 `_variables.scss`**，不要在各组件里逐个改数值。

## Token 清单

| 类别 | Token |
|------|--------|
| 品牌 / Surface / 文本 | `primary`/`on-primary`、`secondary`/`on-secondary`、`surface`/`surface-alt`/`surface-muted`、`text-primary`/`text-secondary`/`text-muted` |
| 边框 | `border-color`、`border-color-strong` |
| 反馈 / 遮罩 | `info`/`success`/`warning`/`danger`、`overlay`/`overlay-dark` |
| 间距 | `--ts-spacer-0` … `--ts-spacer-5`（BS spacer 倍数） |
| 圆角 | `--ts-border-radius-sm`、`--ts-border-radius`、`--ts-border-radius-lg`、`--ts-border-radius-pill` |
| 字号 / 行高 | `--ts-font-size-xs`…`lg`、`--ts-line-height-base` |
| 字体栈 | `--ts-font-body`、`--ts-font-heading` |
| 阴影 | `--ts-shadow-sm`、`md`、`lg` |
| 布局 | `--ts-container-max`、`--ts-drawer-width` |
| z-index | `--ts-z-sticky`、`panel`、`drawer`、`modal`、`toast` |
| 控件 | `--ts-control-min-height`、`--ts-form-padding-*`、`--ts-btn-padding-*`、`--ts-form-floating-*` |
| 断点 | `$ts-breakpoint-sm` … `2xl`（仅 Sass；组件用 mixin） |

图标尺寸直接用 `--ts-font-size-lg` / `sm` 等，勿再加 `icon-*` token。

## 响应式断点

| 名称 | Sass 变量 | 默认换算 |
|------|-----------|----------|
| `sm` | `$ts-breakpoint-sm` | 40rem (640px) |
| `md` | `$ts-breakpoint-md` | 48rem (768px) |
| `lg` | `$ts-breakpoint-lg` | 64rem (1024px) |
| `xl` | `$ts-breakpoint-xl` | 80rem (1280px) |
| `2xl` | `$ts-breakpoint-2xl` | 96rem (1536px) |

### 组件 SCSS 必须

- 使用 `@include mixins.ts-up($name)` / `@include mixins.ts-down($name)`
- 文件顶部 `@use "../abstracts/mixins";`（路径按目录层级调整）
- `ts-up` → `@media (width >= …)`；`ts-down` → `@media (width < …)`

### 组件 SCSS 禁止

- 裸写 `@media (min-width: …)` / `@media (max-width: …)` 并填入断点 px/rem
- 绕过 mixin 直接用 `$ts-breakpoint-*`
- `1023.98px` 等缝隙 hack（用 `ts-down(lg)`）

### 例外（特性查询）

- `(hover: hover)` / `(hover: none)`
- `(prefers-reduced-motion: reduce)`

### TypeScript 对齐

- 使用 `src/ts/breakpoints.ts` 的 `TS_MEDIA_UP` / `TS_MEDIA_DOWN`
- 禁止硬编码 `'(min-width: 1024px)'`、`768` 等

## 禁止

- 组件内 hex / rgb / 裸 rem·px（与 token 等价的）
- `var(--ts-*, #fallback)` 带色值 fallback
- 组件内新建 `:root { --ts-… }`
- 为单一用途新增 token（先用 `color-mix` / `calc()` 或已有 token）
- 引入套餐站专用 aspect-ratio 全局 token

## 示例

```scss
font-size: var(--ts-font-size-sm);
gap: var(--ts-spacer-1);
box-shadow: var(--ts-shadow-lg);
border-radius: var(--ts-border-radius-sm);
color: var(--ts-text-primary);

@use "../abstracts/mixins";

@include mixins.ts-up(md) { ... }
@include mixins.ts-down(lg) { ... }
```

```ts
import { TS_MEDIA_UP } from '../breakpoints';
const mq = window.matchMedia(TS_MEDIA_UP.lg);
```

## 扩展 token 前

1. 查上表是否已有合适阶梯
2. 仍不够再在 `_variables.scss` 增加，并同步 `_root.scss` 与 `src/ts/breakpoints.ts`
3. 禁止在组件 SCSS / TS 临时写死
