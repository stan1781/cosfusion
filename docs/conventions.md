# 命名与语言约定

TailwindScore 是 **英文站点模板**：前台默认可视文案为英文；项目文档与代码注释使用简体中文。

## 统一前缀 `ts` / `tailwindscore`

| 层级 | 规则 | 示例 |
|------|------|------|
| PHP 函数 | `tailwindscore_*` | `tailwindscore_mod()`, `tailwindscore_cart_url()` |
| PHP 类 | `Tailwindscore_*` | `Tailwindscore_Quick_View_Frontend` |
| PHP 常量 | `TAILWINDSCORE_*` | `TAILWINDSCORE_MODULE_QUICK_VIEW_VERSION` |
| 文本域 / 主题 slug | `tailwindscore` | `__( '…', 'tailwindscore' )` |
| `theme_mod` 键 | 蛇形 | `cart_drawer_coupon_enable` |
| SCSS 变量 | `$ts-*` | `$ts-primary`, `$ts-spacer-3` |
| CSS 自定义属性 | `--ts-*` | `--ts-primary` |
| HTML 类名 | `.ts-*` 语义类 | `.ts-site-header`, `.ts-cart-drawer` |
| `data-*` 属性 | `data-ts-*` | `data-ts-quick-view` |
| JS 全局对象 | `window.tailwindscore*` | `window.tailwindscoreCart` |
| 自定义事件 | `tailwindscore:*` | `tailwindscore:open-cart` |

## 语言

| 类型 | 语言 |
|------|------|
| `docs/`、`.cursor/rules/`、模块 `CHANGELOG.md` | **简体中文** |
| 代码注释（PHP / TS / SCSS / 构建脚本） | **简体中文** |
| commit message | 英文 conventional commits 为宜；body 可用中文说明 |
| 用户可见前台文案（`__()` / `esc_html__()` / 模板 / JS i18n 默认值） | **英文** |
| 站点语言（期望） | `en_US`（或其它英文变体） |

### 前台文案规则

1. **源码中的默认可视字符串必须是英文**（含 Kirki 标签、toast、空购物车、Quick View 等）。
2. 一律经 WordPress i18n 包裹（`__( 'English string', 'tailwindscore' )`），便于后续语言包翻译。
3. **禁止**把中文（简/繁）写进主题源码作为前台默认输出。
4. TS 侧 fallback（如 `config?.i18n.x ?? '…'`）同样使用英文。
5. 不在主题内做中英自动转换；多语言交给 WPML / TranslatePress / `.po` 等。

### AI / 生成时注意

- 新增或改写说明文字、注释、文档 → 简体中文。
- 新增或改写按钮文案、通知、`aria-label`、表单 label → 英文默认值 + 文本域 `tailwindscore`。

## 配置与模板

- 配置：`theme_mod` + `tailwindscore_mod()`；新站禁止读插件 `wc_*` option。
- 模板：语义类 `.ts-*`；禁止 Bootstrap / `data-bs-*` / 前台 jQuery。
- 断点与令牌：以 `src/scss/abstracts/_variables.scss` 为真源；样式用 `ts-up` / `ts-down`，禁止裸写无关断点。

## 文件数量

- **`functions.php`**：只引导 `inc/bootstrap.php`；禁止继续堆叠 `require_once`。
- **PHP 加载**：新增/调整主题核心文件时改 [`inc/bootstrap.php`](../inc/bootstrap.php)，保持加载顺序清晰。
- **`inc/woocommerce/`**：不足约 40 行的钩子/helper 优先并入邻接文件，避免无限微文件增殖。
- **SCSS**：同域相关样式优先合并（tokens + base + 少量 components）；避免每个 UI 碎片单独一个文件。
- **独立可开关业务**仍放 `modules/{slug}/`，不把全部能力拍平进 `inc/`。
- **`template-parts/`**：仅当调用处唯一且极短时才内联；多处复用的片段保持独立。

## 禁止

- 前台默认可视字符串使用中文
- 文档或注释默认改用英文（专有名词、API 符号除外）
- 新增 Bootstrap / 前台 jQuery 依赖
- 组件 SCSS 硬编码与令牌无关的 hex（应 `var(--ts-*)`）
- 在 `functions.php` 新增 `require_once`
