---
inclusion: fileMatch
fileMatchPattern: ['**/*.scss']
---

# SCSS 设计 Token 规范

## 唯一真源

- `src/scss/abstracts/_variables.scss` — 所有 `$ts-*` 字面量（含 rem）
- `src/scss/abstracts/_root.scss` — 仅导出组件需要的 `--ts-*`（断点/抽屉宽等用 Sass，不进 `:root`）
- `src/scss/abstracts/_mixins.scss` — 表单/动效 mixin

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

## 禁止

- 组件内 hex / rgb / 裸 rem·px（与 token 等价的）
- `var(--ts-*, #fallback)` 带色值 fallback
- 组件内新建 `:root { --ts-… }`
- 为单一用途新增 token（先用 `color-mix` / `calc()` 或已有 token）
- 新增与 `surface` / `text` 同值的重复色（如 `background`、`on-inverse`）

## 示例

```scss
// ❌
font-size: 14px;
gap: 0.375rem;
box-shadow: 0 12px 40px rgb(0 0 0 / 12%);

// ✅
font-size: var(--ts-font-size-sm);
gap: var(--ts-space-sm);
box-shadow: var(--ts-shadow-lg);
```

## 扩展 token 前

1. 查上表是否已有合适阶梯
2. 仍不够再在 `_variables.scss` 增加，并同步 `_root.scss`
3. 禁止在组件 SCSS 临时写死
