# 博客文章页

`is_singular( 'post' )` 使用 [`single.php`](../../single.php)：干净标题区、约 `42rem` 阅读栏、正文下方相关文章，以及可选的 **Post Sidebar**（桌面内联列 / `< lg` 右侧 Drawer）。

## 模板层级

```
single.php
  └── content-shell(--has-post-sidebar)
        ├── content-main
        │     ├── post-sidebar-toggle（仅 < lg）
        │     ├── article.entry--post
        │     └── post-related
        ├── sidebar-post.php（小工具；< lg 作 Drawer 面板）
        └── post-sidebar-drawer（backdrop）
```

| 文件 | 职责 |
|------|------|
| `single.php` | 文章专用布局入口 |
| `sidebar-post.php` | `dynamic_sidebar( 'sidebar-post' )` |
| `template-parts/content/entry-header.php` | 面包屑、标题、meta、可选 lead / 特色图 |
| `template-parts/content/post-related.php` | 同主分类相关文章（默认 3） |
| `template-parts/content/post-card.php` | 卡片 / 紧凑列表项 |
| `template-parts/content/post-sidebar-toggle.php` | 移动端打开侧栏 |
| `template-parts/content/post-sidebar-drawer.php` | Drawer 遮罩 |
| `inc/blog/queries.php` | 相关 / 热门查询 |
| `inc/blog/widgets.php` | Popular Posts 小工具 |
| `src/ts/site/post-sidebar-drawer.ts` | Drawer 开合 |
| `src/scss/components/_post-sidebar.scss` | 分栏 / Drawer / 卡片 |

无独立相关产品；普通 Page 仍走 `page.php`，不使用 `sidebar-post`。博客分类 / 标签 / 日期 / 作者列表见 [博客归档列表](blog-archive.md)。

## 侧栏

1. 外观 → 小工具 → 将 **Popular Posts**（或其它小工具）拖入 **Post Sidebar**。
2. `tailwindscore_has_post_sidebar()`：仅 singular post 且该区域有小工具时为真。
3. **`lg` 及以上**：右侧内联列（`content-shell--has-sidebar`）。
4. **`< lg`**：侧栏不叠在 main 下方；点 **Sidebar** 打开右侧 Drawer；backdrop / 关闭钮 / Esc 关闭。侧栏 DOM 只渲染一份。

Popular Posts：按 `comment_count` 排序；可配置标题与数量（默认 5）。

## 相关文章

- 取当前文章主分类，`post__not_in` 排除自身，按日期，limit 3。
- 无结果时不输出整块。
- 区块标题默认英文：`Related posts`。

## 标题区 / 阅读栏

singular Page 与 Post **共用干净标题带**（无边框 / 渐变 / 色条卡片）：面包屑为句式大小写、字重 400；`h1.entry__title` 共享。Post 另有 meta（日期 · 分类）与可选特色图。

| 表面 | 正文宽度 | 说明 |
|------|----------|------|
| Post（`.entry--post`） | `$ts-entry-measure: 42rem` 居中 | 阅读栏 |
| 普通 Page | container 全宽 | 不加 measure |
| cart / checkout / account | — | `.entry__content` 仅壳层，不继承 prose（避免泄漏到 Woo UI） |

正文排版由共享 `ts-entry-content-prose`（Page / Post / 结账法律 modal）负责；正文壳带 `ts-content-root`，供特稿 scoped CSS 挂载（见 [Singular Scoped CSS](../roadmap/singular-scoped-css.md)）。

## 特稿 CSS（可选）

侧栏勾选 **Feature layout** 后可编辑「文章 CSS」；存 `_ts_singular_css`，前台 inline。普通文章勿开。复杂可复用模块仍用内容区块。

## 不在范围

相关产品、Tags / 评论小工具、阅读量统计、通用 `sidebar-1` 改造。
