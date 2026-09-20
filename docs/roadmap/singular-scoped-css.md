# Singular Scoped CSS

Page / 特稿 Post 的内容区 scoped CSS：HTML 与 CSS 分栏存储，供人工编辑与 WooPIM 同步。

## 存储模型

| 对象 | HTML | CSS |
|------|------|-----|
| Page | `post_content` | `_ts_singular_css`（读兼容旧 `_ts_page_css`） |
| Post（特稿） | `post_content` | `_ts_singular_css` |
| `ts_block` | `post_content` | `_ts_block_css` |

禁止将 `<style>` 留在 `post_content` 作为真源；保存时抽离至 meta。前台 `wp_add_inline_style( 'tailwindscore-app', … )`。

内容根 class：`ts-content-root`（与 `.entry__content` 同节点；Full Width 模板仅包 `ts-content-root`）。生成 CSS 选择器须挂在 `.ts-content-root` 下，并优先使用 `--ts-*`。

## 相关 meta

| Key | 用途 |
|-----|------|
| `_ts_singular_css` | 纯 CSS |
| `_ts_page_css` | 旧 Page CSS（只读兼容） |
| `_ts_feature_layout` | Post 特稿开关（`1` 时显示文章 CSS metabox） |
| `_ts_layout_template` | 版式 ID（WooPIM：`prose` / `feature-post` / `landing-page`） |
| `_ts_content_css_mode` | `none` \| `scoped` |

## WooPIM

伴侣插件 ≥ **0.3.0** 经 Bridge 读写 `contentCss` / `layoutTemplate` / `contentCssMode`。WooPIM 侧说明见该仓库 `docs/modules/content.md` 与 `wordpress-plugin/woopim-bridge/README.md`。

主题实现：`modules/content-blocks/includes/class-singular-style.php`。内容区块文档：[content-blocks.md](../modules/content-blocks.md)。

## 非目标

- 本阶段不把 `ts_block` 纳入 WooPIM 推送主流程
- AI 不得重写 `:root` 品牌色或壳层选择器
