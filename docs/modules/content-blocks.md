# 内容区块（Content Blocks）

运营可维护的全局 HTML + CSS 片段，通过 Flatsome 兼容短代码 `[block id="slug"]` 或 `[block id="123"]` 在页面中引用。

## 实现

| 能力 | 位置 |
|------|------|
| CPT 注册 / 修订限制 | `modules/content-blocks/includes/class-content-block-cpt.php` |
| 后台 metabox / 列表 Shortcode 列 / 预览 | `modules/content-blocks/includes/class-content-block-admin.php` |
| 编辑页预览资源 | `modules/content-blocks/assets/admin-preview.js`、`admin-preview.css` |
| HTML / CSS 规范化 | `modules/content-blocks/includes/class-content-block-content.php` |
| 前台渲染 | `modules/content-blocks/includes/class-content-block-renderer.php` |
| 短代码 `[block]` | `modules/content-blocks/includes/class-content-block-shortcode.php` |
| Page 页面 CSS（正文 `<style>` 抽离） | `modules/content-blocks/includes/class-page-style.php` |
| Bootstrap | `modules/content-blocks/module.php` |

## 后台

**外观 → 内容区块**

- **标题**：给人看的名称
- **Slug**：侧栏可编辑，写入 `post_name`；建议英文短横线（如 `seo-faq-cta`）
- **HTML**：存入 `post_content`，可嵌套主题 / Woo 短代码（如 `[ts_home_hero]`、`[ts_products_carousel]`）
- **CSS**：存入 post meta `_ts_block_css`；可贴纯 CSS 或 `<style>...</style>`
- **代码编辑器**：后台使用 WordPress 内置 CodeMirror 提供 HTML/CSS 语法高亮、行号、自动缩进（不可用时回退原生 textarea）
- **HTML 内 `<style>`**：保存时自动剥离并合并进 CSS 字段；抽取前会遮蔽 HTML 注释，避免注释内的 `<style>` 字面量误删正文
- **HTML 内 `<script>`**：仅具备 `unfiltered_html` 的账号可保存；其它角色会被 `wp_kses_post()` 移除
- **预览**：代码 / 预览 tab；iframe 注入最小 `--ts-*` token；**不**展开短代码、**不**加载主题 TS

## 短代码

```text
[block id="homepage-banner"]
[block id="123"]
```

- 仅已发布区块会在前台输出
- 找不到时：有 `edit_pages` 权限的用户可见英文错误提示，访客无输出

## 修订历史

每区块最多保留 **3** 条修订（`TAILWINDSCORE_BLOCK_REVISIONS_TO_KEEP`）；CSS 随修订同步。

## 前台输出

```html
<div class="content-block content-block--{slug}" data-ts-block="{slug}">
  <!-- HTML，经 do_shortcode -->
</div>
<style><!-- CSS（非空时） --></style>
```

### `wpautop` 与相邻短代码

Page 正文若连续放置多个 `[block]`（中间仅换行），核心 `wpautop` 可能把它们收进同一个 `<p>` 并用 `<br>` 分隔。展开后浏览器修正「`<p>` 内嵌 `<div>`」会留下空的 `<br>` / `<p></p>`（常出现在 `</style>` 下方），造成多余空白。

模块在 `the_content` 上：

1. 优先级 `10.5`：把仅含 `[block]`（及其中 `<br>`）的段落还原为裸短代码
2. 优先级 `12`：清掉紧贴 `.content-block` / 其 `</style>` 的残留空标签

## Page 页面 CSS

编辑 **Page**（经典编辑器 Code 模式可贴整段 HTML）时，主题提供与内容区块同类的 CSS 抽离：

| 项 | 说明 |
|----|------|
| Metabox | 「页面 CSS」 |
| Meta | `_ts_page_css` |
| 保存 | 正文内所有 `<style>…</style>` 自动剥离并合并进该字段；字段本身也支持纯 CSS 或 `<style>` 包裹；抽取时会先遮蔽 HTML 注释 |
| 前台 | `wp_add_inline_style( 'tailwindscore-app', … )`（仅 `is_singular( 'page' )`） |
| 权限 | 与内容区块一致：无 `unfiltered_html` 时正文 `<style>` 可能已被 WP 过滤；CSS 字段保留规则文本 |

推荐落地页仍优先用 **内容区块** + `[block id="…"]`（可复用、有预览）；仅单页一次性 HTML 时可直接贴在 Page 正文，依赖本机制保存 CSS。

## 相关文档

- [首页](../features/homepage.md)
- [模块总览](README.md)
