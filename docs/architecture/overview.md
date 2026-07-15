# 架构总览

## 项目定位

TailwindScore 是面向 WooCommerce 商城的 WordPress 主题基线（**英文站点模板**），目标是在不依赖 Bootstrap/jQuery 的前提下提供：

- 清晰的 PHP 模板与 `template-parts/` 分层
- 统一的设计令牌（`--ts-*`）与语义化 CSS 类（`.ts-*`）
- TypeScript 驱动的商城交互（购物车 Drawer、变体选择、图库等）
- 从 `wc-enhanced-tools` 迁入的可配置主题模块（Kirki `theme_mod`）

前台默认可视文案为英文；文档与代码注释为简体中文。详见 [命名与语言约定](../conventions.md)。

## 技术栈

| 层 | 技术 |
|----|------|
| 后端 | PHP 8+、WordPress、WooCommerce |
| 样式 | SCSS → CSS 变量、`ts-up`/`ts-down` 断点 mixin |
| 脚本 | TypeScript（Vite 构建）、Alpine.js（轻量壳层交互） |
| 图库 | Swiper、PhotoSwipe |
| 配置 | Kirki Framework（`theme_mod`） |
| 构建 | Vite 6、Sass、manifest 资源清单 |

## 目录结构

```text
tailwindscore/
├── assets/dist/          # Vite 构建产物 + manifest.json
├── docs/                 # 项目文档（本目录）
├── inc/
│   ├── bootstrap.php     # 统一 require（functions.php 仅引导此处）
│   ├── config/           # Kirki 注册与 helpers
│   ├── navigation/       # 头部、移动壳层、页脚导航
│   ├── woocommerce/      # 商城模板逻辑、购买流程、结账
│   ├── integrations/     # 独立插件前台资源整合
│   ├── homepage.php      # 首页 Kirki 与区块 helper
│   └── enqueue.php       # 资源加载与运行时 config 注入
├── modules/              # 可开关主题模块（gallery、variations 等）
├── src/
│   ├── scss/             # app.scss / woo.scss / 组件样式
│   └── ts/               # app.ts 及按域划分的 TS 模块
├── template-parts/       # 可复用标记片段
├── woocommerce/          # WooCommerce 模板覆盖
├── front-page.php        # 首页模板
└── functions.php         # 仅 require inc/bootstrap.php
```

## 分层边界

| 层 | 职责 | 路径示例 |
|----|------|----------|
| 页面模板 | 页面级骨架、Woo hook 编排 | `woocommerce/*.php`、`front-page.php` |
| 视图片段 | 可跨页面复用的 HTML 结构 | `template-parts/` |
| 领域 helper | URL、配置载荷、服务端渲染入口 | `inc/woocommerce/`、`inc/navigation/` |
| 主题模块 | 可 Kirki 开关的独立业务能力 | `modules/{slug}/` |
| TypeScript | 用户交互、Store API、DOM 同步 | `src/ts/` |
| 样式 | 语义类 + 设计令牌 | `src/scss/components/`、`src/scss/woocommerce/` |

### 模板书写约定

- PHP 模板优先使用 **语义类名**（`.ts-site-header`、`.ts-nav`），不把颜色/按钮/表单视觉堆成 Tailwind 原子类。
- 允许少量 **布局型** utility（`grid`、`flex`、`hidden`、`lg:block` 等），视觉风格由令牌与组件 SCSS 承担。
- 禁止 Bootstrap 类、`data-bs-*`、jQuery 前台依赖。

### 配置注入

前台运行时配置通过 `inc/enqueue.php` 合并注入，避免多个 inline script 互相覆盖：

| 全局对象 | 用途 |
|----------|------|
| `window.tailwindscoreCart` | 购买流程、Drawer、Store API |
| `window.tailwindscoreProduct` | 单品页：图库、变体、Size Guide |
| `window.tailwindscoreQuickView` | 归档页 Quick View |
| `window.tailwindscoreAccountAuth` | 登录/注册 |
| `window.tailwindscoreAjaxSearch` | 实时搜索 |
| `window.wcOrderProcessFrontend` | 订单流程（确认收货等） |

模块可通过 `tailwindscore_runtime_config_parts` 过滤器追加片段。

## WooCommerce 整合策略

1. 主题 `woo.scss` 接管 WooCommerce 前台默认样式。
2. 可变商品保留隐藏 `select[name^="attribute_"]` 与 `wc-add-to-cart-variation`；自定义变体 UI 是「皮肤」，通过写 select + `trigger('change')` 驱动 WC 核心。
3. 变体图仅由 `modules/gallery` + `gallery-variations.ts` 处理，不在 variations 模块重复实现。
4. 购买主路径：`product → cart drawer → checkout`；`cart` 页为轻量兜底。

## 与 Bootscore 的关系

见 [bootscore-mapping.md](bootscore-mapping.md)：保留 `inc/`、`template-parts/`、`woocommerce/` 结构思路，替换 Bootstrap/jQuery 为 SCSS 令牌 + TypeScript。

## 相关文档

- [设计令牌](design-tokens.md)
- [构建系统](build-system.md)
- [主题模块](../modules/README.md)
- [独立插件整合](../integrations/README.md)
