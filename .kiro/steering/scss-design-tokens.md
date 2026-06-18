---
inclusion: fileMatch
fileMatchPattern: ['**/*.scss', '**/*.ts']
---

# SCSS 设计 Token 规范

## 唯一真源

- `src/scss/abstracts/_variables.scss` — 所有 `$ts-*` 字面量（含 rem 断点）
- `src/scss/abstracts/_root.scss` — 仅导出组件需要的 `--ts-*`（断点/抽屉宽等用 Sass，不进 `:root`）
- `src/scss/abstracts/_mixins.scss` — 断点 mixin（`ts-up` / `ts-down`）、表单/动效 mixin
- `src/ts/breakpoints.ts` — JS `matchMedia` 与 SCSS 断点对齐

**改风格时优先只改 `_variables.scss`**，不要在各组件里逐个改数值。

## Token 清单（精简后）

| 类别 | Token |
|------|--------|
| 中性色 | `surface`、`surface-muted`、`text`、`text-muted`、`border`、`border-strong` |
| 语义色 | `primary` / `primary-foreground`（由 text/surface 派生，勿重复写 hex） |
| UI 字号 | `--ts-font-size-base` (1rem)、`sm` (0.875rem)、`xs` (0.8125rem) |
| 字号 / 行高 | `--ts-font-size-*`、`--ts-line-height`（单一值；间距用 padding） |
| 间距 | `--ts-space-2xs` … `3xl`（8 级，rem） |
| 圆角 | `--ts-radius-sm`、`md`、`full` |
| 阴影 | `--ts-shadow-sm`、`md`、`lg` |
| 遮罩 | `--ts-colors-overlay`、`overlay-dark` |
| 图标尺寸 | 直接用 `--ts-font-size-xl` / `lg` / `sm`，勿再加 icon-* |
| 表单 | `--ts-form-*`（由间距/字号引用，勿重复定义） |
| 断点 | `$ts-breakpoint-sm` … `2xl`（仅 `_variables.scss` 定义；组件用 mixin） |

## 响应式断点

断点名称与 Tailwind 惯例一致，数值以 rem 为真源（16px root）：

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
- `ts-up` → `@media (width >= …)`；`ts-down` → `@media (width < …)`，与 mixin 成对、无重叠

### 组件 SCSS 禁止

- 裸写 `@media (min-width: …)`、`@media (max-width: …)`、`@media (width >= …)` 并填入断点 px/rem
- 在组件里直接写 `@media (min-width: $ts-breakpoint-md)`（应走 mixin，不要绕过）
- `1023.98px` 等缝隙 hack（用 `ts-down(lg)` 代替 `max-width: 1023.98px`）

### 例外（特性查询，不是断点）

以下可保留在组件内直接写 `@media`：

- `(hover: hover)` / `(hover: none)`
- `(prefers-reduced-motion: reduce)`

布局宽度（如 `width: min(100%, 48rem)`）是尺寸 token，不是断点媒体查询，不受上述 mixin 约束。

### TypeScript 对齐

`matchMedia` / Swiper `breakpoints` 等运行时逻辑须与 SCSS 断点一致：

- 使用 `src/ts/breakpoints.ts` 的 `TS_MEDIA_UP` / `TS_MEDIA_DOWN`（或导出的 `TS_BREAKPOINT_*_REM`）
- 禁止硬编码 `'(min-width: 1024px)'`、`768`、`'48rem'` 等断点字符串/像素值
- PHP 向 JS 注入像素断点时，注释或配置键应标明对应名称（如 `md`、`xl`），并与 `_variables.scss` 同步

## 禁止

- 组件内 hex / rgb / 裸 rem·px（与 token 等价的）
- `var(--ts-*, #fallback)` 带色值 fallback
- 组件内新建 `:root { --ts-… }`
- 为单一用途新增 token（先用 `color-mix` / `calc()` 或已有 token）
- 新增与 `surface` / `text` 同值的重复色（如 `background`、`on-inverse`）
- 组件内裸断点媒体查询（见上文「响应式断点」）

## 示例

```scss
// ❌ 裸值
font-size: 14px;
gap: 0.375rem;
box-shadow: 0 12px 40px rgb(0 0 0 / 12%);

// ✅ token
font-size: var(--ts-font-size-sm);
gap: var(--ts-space-sm);
box-shadow: var(--ts-shadow-lg);

// ❌ 裸断点
@media (min-width: 1024px) { ... }
@media (max-width: 1023.98px) { ... }
@media (width >= 48rem) { ... }
@media (min-width: $ts-breakpoint-md) { ... }

// ✅ mixin
@use "../abstracts/mixins";

@include mixins.ts-up(md) { ... }
@include mixins.ts-down(lg) { ... }
```

```ts
// ❌
const mq = window.matchMedia('(min-width: 1024px)');

// ✅
import { TS_MEDIA_UP } from '../breakpoints';

const mq = window.matchMedia(TS_MEDIA_UP.lg);
```

## 扩展 token 前

1. 查上表是否已有合适阶梯
2. 仍不够再在 `_variables.scss` 增加，并同步 `_root.scss` 与 `src/ts/breakpoints.ts`
3. 禁止在组件 SCSS / TS 临时写死
