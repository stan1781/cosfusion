# TailwindScore 文档

TailwindScore 是基于 PHP、SCSS 设计令牌、TypeScript、Alpine.js 与 WooCommerce 的 WordPress 商城主题（**英文站点模板**）。本目录文档与代码注释使用简体中文；前台默认可视文案为英文。

## 快速导航

| 文档 | 说明 |
|------|------|
| [命名与语言约定](conventions.md) | PHP/JS 前缀、无 `ts-` 的 Bootstrap 风格类名、令牌 `--ts-*`、注释与文档语言、前台英文默认值 |
| [开发入门](development/getting-started.md) | 环境要求、构建命令、Vite 开发模式 |
| [架构总览](architecture/overview.md) | 目录结构、技术栈、分层边界 |
| [设计令牌](architecture/design-tokens.md) | SCSS/CSS 变量、断点、组件样式规范 |
| [构建系统](architecture/build-system.md) | Vite 入口、资源清单、按页加载、release 打包 |
| [Bootscore 映射](architecture/bootscore-mapping.md) | 与 Bootscore 的继承与替换关系 |

## 功能文档

| 文档 | 说明 |
|------|------|
| [站点壳层](features/site-shell.md) | 头部、页脚、搜索、移动端导航 |
| [主导航](features/navigation.md) | 下拉菜单、Mega 菜单、移动端钻取抽屉 |
| [购买流程](features/purchase-flow.md) | Drawer-first 购物车、结账、通知策略 |
| [首页](features/homepage.md) | 静态 Page + 短代码；Hero Kirki + Content Blocks |
| [首页 Hero](features/home-hero.md) | `[ts_home_hero]` 图轮播（full / peek、srcset、LCP preload） |
| [产品轮播](features/product-carousel.md) | `[ts_products_carousel]`、购物车 / 404 共享 Swiper 壳层 |
| [单品商品画廊](features/product-gallery.md) | 主图 Swiper、桌面竖向缩略图均分与居中、PhotoSwipe |
| [产品详情 Tab](features/product-detail-tabs.md) | 响应式描述 / Size Guide / 评论布局 |
| [产品保障](features/product-assurances.md) | 单品加购下方政策提示（Kirki repeater） |
| [配件搭配 Cross-sells](features/product-cross-sells.md) | 单品紧凑列表；购物车瀑布见购买流程 |
| [商店 / 分类归档](features/shop-archive.md) | AJAX 筛选导航、无限滚动、桌面侧栏 / 移动端 Filters+Sort 各半 + 左侧 Drawer |
| [博客文章页](features/blog-post.md) | singular post：阅读栏、相关文章、Post Sidebar（桌面列 / 移动 Drawer） |
| [404 页面](features/not-found.md) | Hero、快捷导航卡片、人气商品轮播 |
| [主题设置导入/导出](features/theme-settings-import-export.md) | Kirki theme_mod 白名单 JSON；预览与本机快照 |

## 模块与整合

| 文档 | 说明 |
|------|------|
| [主题模块](modules/README.md) | `modules/` 目录能力与登记表 |
| [内容区块](modules/content-blocks.md) | CPT `ts_block` 与 `[block]` |
| [心愿单](modules/wishlist.md) | 访客收藏、登录合并、Account 列表 |
| [独立插件整合](integrations/README.md) | 插件逻辑 + 主题前台资源 |
| [后续升级评估](roadmap/starcore-upgrades.md) | 可从 Starcore 继续移植的非套餐能力 |
| [冗余精简路线图](roadmap/redundancy-cleanup.md) | 去重 / 遗留修剪 / 文件拆分；分阶段 + 验收门禁（与升级评估分开） |

## 历史文档（归档）

设计与实施过程中的原始 spec/plan 保留在 [archive/superpowers/](archive/superpowers/)，供追溯决策背景。日常开发以本文档集为准。

| 归档 | 说明 |
|------|------|
| [wc-enhanced-tools 迁移](archive/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md) | 插件能力迁入 `modules/` 的原始规范 |
| [产品轮播壳层](archive/superpowers/specs/2026-07-15-product-carousel-design.md) | 短代码 / 首页 / 购物车共享 Swiper 设计 |
| [插件资源整合](archive/superpowers/specs/2026-05-23-plugin-theme-asset-integration-design.md) | 独立插件前台 JS/CSS 整合原始规范 |
| [站点壳层与令牌设计](archive/superpowers/specs/2026-05-19-site-shell-and-theme-tokens-design.md) | 设计令牌与壳层原始方案 |
| [购买流程设计](archive/superpowers/specs/2026-05-21-woocommerce-purchase-flow-design.md) | Drawer-first 购买路径原始设计 |

## 文档维护约定

1. **实现变更时同步更新**对应 `docs/features/` 或 `docs/modules/` 文档（简体中文）。
2. **模块变更**写入 `modules/{slug}/CHANGELOG.md`，并更新 [modules/README.md](modules/README.md) 登记表。
3. **插件整合变更**更新 [integrations/README.md](integrations/README.md) 登记表。
4. **设计令牌**以 `src/scss/abstracts/_variables.scss` 为唯一真源；文档描述规则，不重复罗列全部数值。
5. 归档目录中的 spec/plan **只读追溯**，新内容写入正式文档而非继续堆叠在 `archive/`。
6. **语言**：命名与前台文案规则见 [conventions.md](conventions.md)；勿把中文写入前台默认字符串。

## AI 编辑器（Cursor）

项目已配置 `.cursor/rules/project-docs.mdc`（`alwaysApply: true`）：AI 会话在改代码前应优先阅读 `docs/README.md` 及上表对应文档；涉及文案或注释时必读 [conventions.md](conventions.md)。SCSS/TS 另受 `.cursor/rules/scss-design-tokens.mdc` 约束。
