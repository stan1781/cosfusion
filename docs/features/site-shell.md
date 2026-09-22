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
- **吸顶高度**：`header-sticky.ts` 用 `ResizeObserver` 把 `.site-header` 实测高度写入 `--ts-site-header-height`，供归档侧栏 / 文章侧栏 sticky `top`；compact 切换后同步更新。
- **账户页（`< lg`）**：无站点 `mobile-tab-bar`；`.wc-account__nav` 固定底部（对齐 Tab 栏视觉），`.main` 预留 `--ts-account-nav-bar-height` + safe-area；抽屉/overlay 打开时隐藏。`≥ lg` 为左侧栏：`.wc-account__nav` sticky（`top` = `--ts-site-header-height` + spacer）；`.woocommerce-MyAccount-navigation` 成卡（`--ts-surface` / `--ts-border-radius-lg` / `--ts-shadow-sm`），顶部 `.wc-account-nav__user`（头像 + Welcome back + 姓名 + View profile → `edit-account`）；`< lg` 隐藏用户信息区，底栏仅菜单项。Dashboard / Orders / View Order 内容区为 grid + `gap`；空的 `.woocommerce-notices-wrapper`（无 message/info/error）`display: none`，避免无通知时仍占一行间距。
- **Logo**：`loading="eager"` + `decoding="async"` + 固有宽高，降低 CLS。
- 性能总览见 [前台性能](../architecture/performance.md)。

## 顶部公告栏（Kirki）

可选顶栏位于 sticky `.site-header` 之前，随页面滚动离开；主页头保持粘性。Customizer 路径：页头 → **顶部公告栏**。`dark` 方案使用全局 `--ts-gradient-brand`（粉 → 品牌紫 → violet）；字号 `sm`、字重 `600`，保证渐变底上白字可读。`light` 方案为 `surface-muted` 底 + 正文色。

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `header_bar_enable` | toggle | `true` | 显示顶栏 |
| `header_bar_scheme` | radio-buttonset | `light` | `dark`（`--ts-gradient-brand`）/ `light`（弱化底） |
| `header_bar_autoplay_delay` | slider 2500–8000 | `4000` | 多条时垂直轮播间隔（ms） |
| `header_bar_items` | repeater | 一条默认英文文案 | 每行 `text`、`link_url`、`link_label`；空文案行前台略过 |

行为：

- **1 条**：静态输出
- **≥2 条**：`src/ts/site/header-bar.ts` 初始化垂直 Swiper（`rewind`）；`prefers-reduced-motion` 时关闭 autoplay
- 链接缺 label 时前台 fallback：`Learn more`
- 旧版单条 `header_bar_text` 迁移桥接已于 2026-08-06 移除（生产已确认无依赖）；`header_bar_items` 为唯一数据源，尚未设置时回退默认一条

## 页头购物车图标（Kirki）

Customizer 路径：页头 → **页头图标**。影响页头操作区与移动底栏购物车入口（商品卡加购等仍用 `cart`）。

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `header_cart_icon` | radio-buttonset | `bag` | `bag`（Heroicons shopping-bag）/ `cart`（shopping-cart） |

实现：`tailwindscore_header_cart_icon()`（`inc/navigation/header-bar.php`）、`inc/icons.php` 的 `bag` / `cart`。

## 响应式行为

- **`< lg`**：汉堡菜单打开移动抽屉；可选底部 Tab 栏（购物车、账户等快捷入口）
- **有底栏时（`body.has-mobile-tab-bar`，`< lg`）**：页头隐藏搜索与账户图标（与底栏入口重复）；购物车与菜单按钮保留。无底栏页（单品 / 购物车 / 结账 / 账户等）页头入口仍显示
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

### 双 Band 全宽背景

`.site-footer` 拆成两个全宽带（背景画在 band 上，内容各自包 `.container`）：

| Band | 类 | 默认背景 | 内容 |
|------|-----|----------|------|
| Top | `.site-footer__band--top` | `--ts-surface-muted` | `.site-footer__top`（`footer-top` widget；未启用则不输出该 band） |
| Main | `.site-footer__band--main` | `--ts-surface` | `.site-footer__content` + `.site-footer__nav` + `.site-footer__info` |

样式：`src/scss/components/_footer.scss`（band 背景）；壳层边框仍在 `_header.scss` 的 `.site-footer`。换色只改上述 token，不新增 footer 专用色。

### 列区菜单（Navigation Menu widget）

`.site-footer__content` 内 WP 菜单 widget（`ul.menu`）独立样式，不与主题位置 `.site-footer__nav` 共用：

- 标题：`.site-footer__title`（`base` / `600` / `text-primary`）
- 链接：`sm`、`text-secondary`；hover / focus-visible 为 `primary`
- 列表：去 bullet，纵向 stack，子菜单左缩进 `spacer-3`

**移动端折叠**（`< lg`）：`src/ts/site/footer-widgets.ts` 将带菜单的列 widget 标题增强为按钮；默认全部收起，可各自展开（非互斥）。`≥ lg` 常开并隐藏 chevron。无 JS 时菜单保持展开可见。

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
- **SKU 直达**：表单提交时，若关键词与可公开的简单商品 / 可变商品父级 `_sku` 精确匹配，则 302 到产品详情；未命中、变体 SKU、草稿或目录不可见则仍走搜索结果页。实现：`inc/woocommerce/search-sku-redirect.php`。AJAX 实时建议不因此改跳转。
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
