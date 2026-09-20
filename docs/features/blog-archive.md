# 博客归档列表

分类 / 标签 / 日期 / 作者归档使用 [`archive.php`](../../archive.php)：统一 **grid** 精简卡（缩略图 + 标题 + 日期）；有小工具时显示通用 `sidebar-1`。

## 模板层级

```
archive.php
  └── content-shell[--has-sidebar]
        ├── content-main.blog-archive
        │     ├── blog-archive-header（面包屑 / 标题 / description）
        │     ├── ul.blog-archive__grid → post-card--grid
        │     └── the_posts_pagination
        └── sidebar.php（sidebar-1）
```

| 文件 | 职责 |
|------|------|
| `archive.php` | 博客归档入口、网格循环、分页、侧栏 |
| `template-parts/content/blog-archive-header.php` | 面包屑、干净归档标题、可选说明 |
| `template-parts/content/post-card.php` | `grid`（及相关 `card` / `compact`） |
| `inc/template-tags.php` | `tailwindscore_get_archive_title()`、归档面包屑 |
| `src/scss/components/_blog-archive.scss` | 页头 / 网格 / 分页 |

## 卡片变体

| variant | 用途 | 内容 |
|---------|------|------|
| `grid` | 归档列表 | 中图 + 标题（2 行截断）+ 日期 |
| `card` / `compact` | 相关文章 / Popular Posts | 不变 |

## 网格列数

| 断点 | 无侧栏 | 有 `sidebar-1` |
|------|--------|----------------|
| `< md` | 1 | 1 |
| `md`–`lg` | 2 | 2 |
| `lg+` | 3 | 2 |

## 行为要点

1. **全部条目**进入同一网格，无单独 featured 横卡。
2. **空归档**：英文 empty-state（与 `index.php` 同文案）。
3. **标题**：`tailwindscore_get_archive_title()`，无 WordPress 默认 “Category:” 前缀。
4. **分页**：`the_posts_pagination()`，文案 Previous / Next；每页 **12** 篇（2×3 网格公倍数，见 `tailwindscore_blog_archive_posts_per_page`）。

## 不在范围

博客 Posts 首页（`is_home()`）、搜索结果、AJAX / 无限滚动、文章 Post Sidebar Drawer、商店 / Woo 归档、摘要 / 作者 / 阅读时长 / 分类 chip、Kirki 列数配置。

相关单篇布局见 [博客文章页](blog-post.md)。
