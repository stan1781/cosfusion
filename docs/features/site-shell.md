# 站点壳层

站点壳层包含头部、主导航、页脚、搜索与移动端导航体验，是各页面共享的基础框架。

## 实现文件

| 区域 | PHP | 样式 | 交互 |
|------|-----|------|------|
| 头部栏 | `inc/navigation/header-bar.php` | `src/scss/components/_navigation.scss` | `src/ts/site/header-sticky.ts` |
| 主导航 | `inc/navigation/nav.php`、`class-nav-menu-walker.php` | 同上 | `src/ts/site/mobile-nav.ts`、`menu-drawer-panel.ts` |
| 移动壳层 | `inc/navigation/mobile-shell.php` | `src/scss/components/_drawers.scss` | Alpine + TS |
| 移动底栏 | — | `src/scss/components/_mobile-tab-bar.scss` | `src/ts/site/mobile-tab-bar.ts` |
| 页脚 | `inc/navigation/footer-nav.php` | `src/scss/components/_footer.scss` | — |
| 搜索 | `inc/woocommerce/ajax-search.php` | 搜索相关组件 SCSS | `src/ts/search/` |

模板入口：`header.php`、`footer.php`、`template-parts/header/`、`template-parts/footer/`。

## 响应式行为

- **`< lg`**：汉堡菜单打开移动抽屉；可选底部 Tab 栏（购物车、账户等快捷入口）
- **`≥ lg`**：横向 inline 主导航；下拉 / Mega 菜单悬停或键盘聚焦展开
- 头部支持吸顶策略（`header-sticky.ts`）

断点约定见 [设计令牌](../architecture/design-tokens.md)。

## 页脚

页脚通过 Kirki 配置：

- 导航水平对齐：`left` / `center` / `right`（`footer_nav_alignment`）
- 支付方式图标 repeater
- Widget 区域与版权信息

对齐修饰符类：`.site-footer__nav--align-{left|center|right}`。

## 搜索

- 全站搜索表单与头部搜索入口
- AJAX 实时搜索（`tailwindscoreAjaxSearch` 配置注入）
- 与 WooCommerce 产品搜索集成

## 交互技术边界

| 类型 | 技术 | 示例 |
|------|------|------|
| 轻量壳层 UI | Alpine.js + 少量 TS | 抽屉开合、手风琴、modal 状态 |
| 商城业务流程 | TypeScript | 购物车 Drawer、变体、结账 |

不把 Alpine 用于复杂商城状态同步。

## 相关文档

- [主导航](navigation.md)
- [设计令牌](../architecture/design-tokens.md)
