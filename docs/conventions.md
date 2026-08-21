# 命名与语言约定

TailwindScore 是 **英文站点模板**：前台默认可视文案为英文；项目文档与代码注释使用简体中文。

## 统一前缀 `ts` / `tailwindscore`

主题是**全新制作**、且**不引入 Bootstrap 运行时**。HTML/CSS **类名不加** `ts-`，按 Bootstrap 习惯直写（如 `btn btn-outline-primary`）。`ts` / `tailwindscore` 只用于令牌、行为钩子与 PHP/JS 符号。

| 层级 | 规则 | 示例 |
|------|------|------|
| PHP 函数 | `tailwindscore_*` | `tailwindscore_mod()`, `tailwindscore_cart_url()` |
| PHP 类 | `Tailwindscore_*` | `Tailwindscore_Quick_View_Frontend` |
| PHP 常量 | `TAILWINDSCORE_*` | `TAILWINDSCORE_MODULE_QUICK_VIEW_VERSION` |
| 文本域 / 主题 slug | `tailwindscore` | `__( '…', 'tailwindscore' )` |
| `theme_mod` 键 | 蛇形 | `cart_drawer_coupon_enable` |
| SCSS 变量 | `$ts-*`（保留） | `$ts-primary`, `$ts-spacer-3` |
| CSS 自定义属性 | `--ts-*`（保留） | `--ts-primary` |
| HTML / CSS 类名 | **无** `ts-` 前缀；贴近 Bootstrap | `btn btn-outline-primary`、`site-header`、`nav-link` |
| `data-*` 属性 | `data-ts-*`（保留） | `data-ts-quick-view` |
| JS 全局对象 | `window.tailwindscore*` | `window.tailwindscoreCart` |
| 自定义事件 | `tailwindscore:*` | `tailwindscore:open-cart` |
| SCSS mixin | `ts-up` / `ts-down` 等（保留） | `@include mixins.ts-up(lg)` |

## HTML / CSS 命名风格（贴近 Bootstrap）

本主题**不引入** Bootstrap CSS/JS，也不装 Bootstrap 主题/插件；类名仅为**写法习惯**对齐，例如：

```html
<button class="btn btn-outline-primary btn-lg">…</button>
```

| 习惯 | 本主题 |
|------|--------|
| 基础 + 变体 | `btn btn-primary`、`btn btn-outline-secondary`、`btn btn-sm` |
| 关闭按钮 | `btn-close`（仅图标、无边框；文案用 `screen-reader-text` / `aria-label`；`tailwindscore_render_close_button()`） |
| 子部件连字符 | `nav-link`、`card-body`、`modal-header` |
| 域组件 | `site-header`、`cart-drawer`、`hero-carousel` 等（可读即可） |
| 控件组布局 | `input-group`（贴合）/ `input-inline`（分体）/ `input-stack`（上下）；见 `src/scss/components/_forms.scss` |

说明：

1. **按钮用 `btn`，不要用裸 `button` 当主题类**（WooCommerce 已有 `.button`，双挂时主题样式挂在 `btn` 上即可）。
2. **输入 + 操作按钮**优先用上述三种控件组类，勿再造 `*-subscribe__input-group` 一类重复布局。
3. 禁止引入 Bootstrap 运行时、`data-bs-*`、网格类（`row` / `col-*`）与前台 jQuery；需要栅格时用语义结构或少量布局 utility。
4. `$ts-*`、`--ts-*`、`data-ts-*`、mixin 名 **保持** `ts` 命名空间，勿与类名一并去掉。
5. 元素 `id` 仍可用 `ts-*` 前缀（与类名策略分开），`aria-controls` 等指向这些 id。
6. 存量 BEM（`__` / `--`）可按触达范围逐步改成连字符 / 变体类；不必单开全站重命名工程。

## 语言

| 类型 | 语言 |
|------|------|
| `docs/`、`.cursor/rules/`、模块 `CHANGELOG.md` | **简体中文** |
| 代码注释（**PHP** / TS / SCSS / 构建脚本） | **简体中文** |
| commit message | 英文 conventional commits 为宜；body 可用中文说明 |
| 用户可见前台文案（`__()` / `esc_html__()` / 模板 / JS i18n 默认值） | **英文** |
| Customizer / Kirki 后台标签（`title` / `label` / `description` / `choices`） | **简体中文** |
| 站点语言（期望） | `en_US`（或其它英文变体） |

### 代码注释（含 PHP）

1. **文件头、函数/类/钩子 docblock、行内 `//` 说明一律简体中文**（`inc/`、`modules/`、主题根与模板覆盖中的 PHP 均适用）。
2. **标识符、钩子名、API 符号、路径、类名保持英文原文**，可嵌在中文句子里（如「经 `tailwindscore_mod()` 读取配置」）。
3. **例外（保持英文）**：
   - WordPress i18n：`/* translators: … */` 注释（供 pot 扫描与译者，勿改成中文）
   - 第三方 / 上游许可头若原文为英文，可原样保留
4. **禁止**新增英文说明性注释；改动既有文件时顺带把接触到的英文注释改为中文（不必单开巨型纯翻译 PR，但不得以「历史英文」为由继续加英文注释）。

### 前台文案规则

1. **源码中会输出到前台的默认可视字符串必须是英文**（toast、空购物车、Quick View、Kirki 字段 `default` 等）。
2. 一律经 WordPress i18n 包裹（`__( 'English string', 'tailwindscore' )`），便于后续语言包翻译。
3. **禁止**把中文（简/繁）写进主题源码作为前台默认输出。
4. TS 侧 fallback（如 `config?.i18n.x ?? '…'`）同样使用英文。
5. 不在主题内做中英自动转换；多语言交给 WPML / TranslatePress / `.po` 等。

### Customizer / Kirki 后台文案

1. Panel / section 的 `title`、`description`，字段的 `label`、`description`，以及 `choices` / `row_label` 使用**简体中文**。
2. 仍用 `esc_html__( '…', 'tailwindscore' )` 包裹。
3. **字段 `default`（会落到前台）保持英文**，与前台文案规则一致。

### AI / 生成时注意

- 新增或改写说明文字、注释（含 **PHP docblock**）、文档 → 简体中文。
- 新增或改写按钮文案、通知、`aria-label`、表单 label → 英文默认值 + 文本域 `tailwindscore`。
- 新增或改写 Kirki 后台 `label` / `description` / `choices` → 简体中文；`default` 保持英文。
- 勿将 `/* translators: … */` 改成中文。

## 配置与模板

- 配置：`theme_mod` + `tailwindscore_mod()`；新站禁止读插件 `wc_*` option。
- 模板：无 `ts-` 前缀的语义类（如 `btn btn-outline-primary`）；禁止 Bootstrap 运行时 / `data-bs-*` / 前台 jQuery。
- 断点与令牌：以 `src/scss/abstracts/_variables.scss` 为真源；样式用 `ts-up` / `ts-down` mixin，禁止裸写无关断点。
- **字重**：自托管字体一般只加载 **400** / **600**；SCSS 禁止 `font-weight: 500`（会假粗）；需要强调用 `600`。`700` 仅当当前字体族清单确有该 face（如 Playfair Display）。详见 `docs/architecture/design-tokens.md` 与 `.cursor/rules/scss-design-tokens.mdc`。

## 文件数量

- **`functions.php`**：只引导 `inc/bootstrap.php`；禁止继续堆叠 `require_once`。
- **PHP 加载**：新增/调整主题核心文件时改 [`inc/bootstrap.php`](../inc/bootstrap.php)，保持加载顺序清晰。
- **`inc/woocommerce/`**：不足约 40 行的钩子/helper 优先并入邻接文件，避免无限微文件增殖。
- **SCSS**：同域相关样式优先合并（tokens + base + 少量 components）；避免每个 UI 碎片单独一个文件。
- **独立可开关业务**仍放 `modules/{slug}/`，不把全部能力拍平进 `inc/`。
- **`template-parts/`**：仅当调用处唯一且极短时才内联；多处复用的片段保持独立。

## 禁止

- 前台默认可视字符串使用中文
- 文档或**说明性注释**默认改用英文（专有名词、API 符号、`/* translators: … */` 除外）
- 在 PHP（`inc/`、`modules/`、模板）中新增英文 docblock / `//` 说明
- 新增 Bootstrap / 前台 jQuery 依赖，或使用 `data-bs-*` / Bootstrap 网格
- 用裸类名 `button` 充当主题按钮类（应用 `btn`，与 WC `.button` 区分）
- 去掉 `$ts-*` / `--ts-*` / `data-ts-*` / mixin 上的 `ts` 命名空间
- 组件 SCSS 硬编码与令牌无关的 hex（应 `var(--ts-*)`）
- 在 `functions.php` 新增 `require_once`
