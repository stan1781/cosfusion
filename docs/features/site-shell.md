# 站点壳层

站点壳层包含头部、主导航、页脚、搜索与移动端导航体验，是各页面共享的基础框架。

## 实现文件

| 区域 | PHP | 样式 | 交互 |
|------|-----|------|------|
| 公告栏 | `inc/navigation/header-bar.php`、`template-parts/header/header-bar.php` | `src/scss/components/_header.scss` | `src/ts/site/header-bar.ts` |
| 吸顶头部 | — | 同上 | `src/ts/site/header-sticky.ts`（compact + `--ts-site-header-height`） |
| 主导航 | `inc/navigation/nav.php`、`class-nav-menu-walker.php` | `src/scss/components/_navigation.scss` | `src/ts/site/mobile-nav.ts`、`menu-drawer-panel.ts` |
| 移动壳层 | `inc/navigation/mobile-shell.php` | `src/scss/components/_drawers.scss` | Alpine + TS |
| 移动底栏 | — | `src/scss/components/_mobile-tab-bar.scss` | `src/ts/site/mobile-tab-bar.ts` |
| 单品底部 sticky 购买条 | `template-parts/woocommerce/product-sticky-bar.php` | `src/scss/components/_mobile-shell.scss` | `src/ts/product/sticky-bar.ts` |
| 页脚 | `inc/navigation/footer-nav.php` | `src/scss/components/_footer.scss` | — |
| 搜索 | `inc/woocommerce/ajax-search.php` | 搜索相关组件 SCSS | `src/ts/search/` |

模板入口：`header.php`、`footer.php`、`template-parts/header/`、`template-parts/footer/`。

## 无障碍与吸顶

- **Skip link**：`header.php` 在 `wp_body_open` 后输出「Skip to content」→ `#primary`；聚焦时可见（`.skip-link:focus`）。商城 `<main>` 由 `tailwindscore_wc_wrapper_open()` 带 `id="primary"`。
- **吸顶高度**：`header-sticky.ts` 用 `ResizeObserver` 把 `.site-header` 实测高度写入 `--ts-site-header-height`，供归档侧栏 / 文章侧栏 / 手机端账户导航 sticky `top`；compact 切换后同步更新。
- **Logo**：`loading="eager"` + `decoding="async"` + 固有宽高，降低 CLS。
- 性能总览见 [前台性能](../architecture/performance.md)。

## 顶部公告栏（Kirki）

可选顶栏位于 sticky `.site-header` 之前，随页面滚动离开；主页头保持粘性。Customizer 路径：页头 → **顶部公告栏**。

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `header_bar_enable` | toggle | `true` | 显示顶栏 |
| `header_bar_scheme` | radio-buttonset | `light` | `dark`（主色底）/ `light`（弱化底） |
| `header_bar_autoplay_delay` | slider 2500–8000 | `4000` | 多条时垂直轮播间隔（ms） |
| `header_bar_items` | repeater | 一条默认英文文案 | 每行 `text`、`link_url`、`link_label`；空文案行前台略过 |

行为：

- **1 条**：静态输出
- **≥2 条**：`src/ts/site/header-bar.ts` 初始化垂直 Swiper（`rewind`）；`prefers-reduced-motion` 时关闭 autoplay
- 链接缺 label 时前台 fallback：`Learn more`
- 旧版单条 `header_bar_text` 迁移桥接已于 2026-08-06 移除（生产已确认无依赖）；`header_bar_items` 为唯一数据源，尚未设置时回退默认一条

## 响应式行为

- **`< lg`**：汉堡菜单打开移动抽屉；可选底部 Tab 栏（购物车、账户等快捷入口）
- **`≥ lg`**：横向 inline 主导航；下拉 / Mega 菜单悬停或键盘聚焦展开
- 头部支持吸顶策略（`header-sticky.ts`）

断点约定见 [设计令牌](../architecture/design-tokens.md)。

## 页脚

页脚通过 Kirki 配置（Customizer → **页脚**）：

| theme_mod | 类型 | 默认 | 说明 |
|-----------|------|------|------|
| `footer_hide_on_cart` | toggle | `true` | 购物车页不输出 `.site-footer` |
| `footer_hide_on_checkout` | toggle | `true` | 结账表单页不输出 `.site-footer`（`order-received` 仍显示） |
| `footer_nav_alignment` | radio | `center` | 导航水平对齐：`left` / `center` / `right` |
| `footer_payment_image` | image | — | 支付方式图标 |

隐藏页脚时 `body` 带 `ts-site-footer-hidden`。实现：`inc/navigation/footer-nav.php`（`tailwindscore_site_footer_should_render()`）、`footer.php`。

对齐修饰符类：`.site-footer__nav--align-{left|center|right}`。Widget 区域与版权信息见模板片段。

## 联络方式（WhatsApp）

Customizer **设计 → 联络方式**（`inc/contact/whatsapp.php`、`inc/config/kirki/contact.php`）：

| theme_mod | 说明 |
|-----------|------|
| `contact_whatsapp_number` | E.164 数字（不含 +） |
| `contact_whatsapp_display` | 展示号码（可选） |
| `contact_whatsapp_message_default` | 通用预填 |
| `contact_whatsapp_message_order` | 订单场景预填（`{order_number}` / `{order_total}`） |

成功页 CTA 见 [购买流程](purchase-flow.md)。**页头顶栏 / FAB 入口本轮未接入**（以后可再挂）。

## 搜索

- 全站搜索表单与头部搜索入口
- AJAX 实时搜索（`tailwindscoreAjaxSearch` 配置注入）
- 与 WooCommerce 产品搜索集成
- 搜索弹层（`template-parts/header/search-modal.php`）可在输入框下方展示 chip 引导：
  - **热门搜索**（`search_modal_hot_terms`）：每行一个关键词；点击跳转商品搜索结果页
  - **热门分类**（`search_modal_hot_categories`）：每行一个 `product_cat` 名称或 slug；解析成功后点击直达该分类归档
  - 两者默认均为空；未配置时整块 chips 不渲染（见 Customizer → Header → 搜索弹层）
  - 实现：`inc/navigation/mobile-shell.php`、`template-parts/search/modal-chips.php`

## 交互技术边界

| 类型 | 技术 | 示例 |
|------|------|------|
| 轻量壳层 UI | Alpine.js + 少量 TS | 抽屉开合、手风琴、modal 状态 |
| 商城业务流程 | TypeScript | 购物车 Drawer、变体、结账 |

不把 Alpine 用于复杂商城状态同步。

## 相关文档

- [主导航](navigation.md)
- [设计令牌](../architecture/design-tokens.md)
