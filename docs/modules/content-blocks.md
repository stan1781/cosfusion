# 内容区块（Content Blocks）

运营可维护的全局 HTML + CSS 片段，通过 Flatsome 兼容短代码 `[block id="slug"]` 或 `[block id="123"]` 在页面中引用。

## 实现

| 能力 | 位置 |
|------|------|
| CPT 注册 / 修订限制 | `modules/content-blocks/includes/class-content-block-cpt.php` |
| 后台 metabox / 列表 Shortcode 列 / 预览 | `modules/content-blocks/includes/class-content-block-admin.php` |
| 编辑页预览资源 | `modules/content-blocks/assets/admin-preview.js`、`admin-preview.css` |
| 前台渲染 | `modules/content-blocks/includes/class-content-block-renderer.php` |
| 短代码 `[block]` | `modules/content-blocks/includes/class-content-block-shortcode.php` |
| Bootstrap | `modules/content-blocks/module.php` |

## 后台

**外观 → 内容区块**

- **标题**：给人看的名称
- **Slug**：侧栏可编辑，写入 `post_name`；建议英文短横线（如 `seo-faq-cta`）
- **HTML**：存入 `post_content`，可嵌套主题 / Woo 短代码（如 `[ts_home_hero]`、`[ts_products_carousel]`）
- **CSS**：存入 post meta `_ts_block_css`；可贴纯 CSS 或 `<style>...</style>`
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

## 相关文档

- [首页](../features/homepage.md)
- [模块总览](README.md)
