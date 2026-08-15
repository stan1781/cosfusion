# 前台性能

主题侧负责 **LCP / CLS / 资源精简 / 无障碍**；产线全页缓存与 CDN 由 **WP Rocket**（及宿主）承担。二者分工明确，避免重复或互相破坏。

## 职责划分

| 层 | 负责 | 不负责 |
|----|------|--------|
| 主题 | Hero/归档 LCP、`loading`/`fetchpriority`、Vite preload、卸冗余 WC/jQuery、Skip link、header 高度 | 全页 HTML 缓存、浏览器缓存头批量策略 |
| WP Rocket | Page Cache、浏览器缓存、Gzip/Brotli、（可选）Delay 第三方追踪脚本 | 改写主题 LCP 图 `src`、延迟主题 `type="module"` 入口 |

主题在 `inc/performance.php` 注册 WP Rocket 排除滤镜（插件未安装时滤镜无害）。购物车有商品时主题已 `nocache_headers()`（见 `inc/woocommerce/store-api-cache.php`），与 Rocket 的 Woo 缓存排除互补。

## 已做的主题优化

| 项 | 位置 | 行为 |
|----|------|------|
| 产品卡主图 loading | `inc/woocommerce/loop.php` | 默认 `lazy`；Shop/分类主查询且无归档 Hero 图时前 4 张 `eager` |
| 首页 Hero | `inc/home/hero-carousel.php`、模板 | 附件 ID → `wp_get_attachment_image( …, 'large' )`；首张 `fetchpriority="high"` + `data-no-lazy`；桌面/手机分流 preload |
| 归档 Hero | `inc/woocommerce/archive-hero.php`、模板 | 装饰性 `<img>`（`large` + srcset），`eager` + `fetchpriority` + `data-no-lazy`；`wp_head` preload |
| 单品图库 LCP | `modules/gallery/includes/class-gallery-data.php` | 首张 `eager` + `fetchpriority`；`wp_head` preload `woocommerce_single`（不含变体/占位） |
| Hotjar 延迟 | `inc/integrations/wp-enhanced-tools.php` | Enhanced Tools 脚本管理直出 Hotjar → idle / 首次交互后再跑 |
| 禁止 `sizes=auto` | Hero / 归档 Hero | `wp_img_tag_add_auto_sizes` 排除 `.hero-carousel__img` / `.wc-archive-hero__img` |
| CLS 占位 | Hero / 产品卡 SCSS | Hero：`--ts-hero-ar*` 或固定 4/3·21/9；产品卡：`--ts-product-image-aspect-ratio` |
| Logo | `site-branding.php` | `width`/`height` + `eager` + `data-no-lazy` |
| 吸顶高度 | `header-sticky.ts` | `ResizeObserver` → `--ts-site-header-height` |
| 资源 hints | `inc/performance.php` | Vite CSS/JS `preload` / `modulepreload`；字体 woff2 preload |
| 脚本精简 | `inc/performance.php` | 卸 WC Blocks、条件卸 jQuery、非关键 CSS print-swap、归因 `defer` |
| WP Rocket 兼容 | `inc/performance.php` | LazyLoad / Delay JS / Defer / 合并 CSS·JS / Lazy Render 排除主题关键资源 |
| Skip link / Swiper a11y | `header.php`、`a11y-i18n.ts` | 见站点壳层与本表上文 |

## WP Rocket 推荐设置（Cosfusion）

### 建议开启

- **Cache → Page Caching**（核心收益；TTFB）
- **Cache → Mobile Cache**（若桌面/手机 HTML 不同再开；主题响应式通常可共用）
- **File Optimization → 仅第三方** 谨慎使用；主题 Vite 产物已 hashed + 压缩，**勿再合并主题 CSS/JS**（主题已通过滤镜排除 `assets/dist/`）
- **Media → 图片 WebP**（可选；也可继续用 `wp-enhanced-tools` 转换器，二选一避免双重处理）

### 建议关闭或严格排除

| 功能 | 建议 | 原因 |
|------|------|------|
| LazyLoad（图片） | **关**，或仅开并依赖主题排除 | 会把 `src`→`data-src`，破坏 Hero/归档 LCP、`fetchpriority`、主题已写的 `loading` |
| LazyLoad for CSS background images | **关** | 归档曾用背景图；现已改 `<img>`，仍无必要 |
| Delay JavaScript Execution | 可开，但必须排除主题 | 延迟 `app` module 会拖垮 Alpine 壳层 / Hero Swiper / Drawer；主题已排除 `assets/dist/` 与 `type="module"` |
| Load JavaScript deferred | 勿作用于主题 module | 同上 |
| Remove Unused CSS (RUCSS) | **慎用 / 先关** | 易裁掉抽屉、Mega、AJAX 归档后才出现的类；若开启须把主题 safelist 测全 |
| Delay Images / 与 LazyLoad 同类 | **关** | 同 LazyLoad |

主题自动注入的排除（无需在后台重复手填，但后台可再加第三方脚本）：

- LazyLoad 属性：`data-no-lazy`、`fetchpriority="high"`、`hero-carousel__img`、`wc-archive-hero__img`、`branding__logo`
- Delay / Defer / 合并 JS·CSS：`/themes/tailwindscore/assets/dist/`、`/assets/fonts/`
- Lazy Render：`hero-carousel`、`wc-archive-hero`、`site-header`、`site-header-bar`

### 缓存与购买流

- Cart / Checkout / My Account：依赖 WP Rocket 内置 WooCommerce 兼容（默认不缓存）
- 主题在 `woocommerce_items_in_cart` Cookie 存在时对 HTML 发 `nocache_headers()`
- Store API `/wc/store/*`：主题强制 `Cache-Control: no-store`（全页缓存插件不得缓存 JSON）

部署主题新 CSS/JS 后：在 Rocket 清一次缓存（或 `rocket_clean_domain()`），因文件名带 hash，通常仅需清 HTML。

## 测量注意

- **本机 TTFB**（无 Rocket / 冷 PHP）不能当产线 KPI；产线对比须在 **WP Rocket 已开 + 缓存已预热** 下测。
- 产线优先看：TTFB（Rocket）、LCP（主题 Hero）、折下卡片是否抢带宽。
- 建议工具：PSI、Chrome DevTools、Rocket 预加载后的 RUM。

### 本机快速自检（主题层，可不启 Rocket）

1. **首页**：Hero 首张 `fetchpriority=high` + `data-no-lazy`；`<head>` 分流 preload。
2. **商店**：有 Hero 时卡片主图 `lazy`；LCP 为 Hero `<img>`。
3. **单品**：`<head>` 有主图 `preload`（`woocommerce_single`）；占位图页无。
4. **无障碍**：Skip to content → `#primary`；`prefers-reduced-motion` 下 Hero 不自动播。
5. 启用 Rocket 后复查：Hero `src` 未被改成 `data-src`；首屏交互（菜单/购物车）无需先点击才加载主题 JS。
6. Enhanced Tools 贴 Hotjar：首屏为 `text/plain` / delay-src，交互或约 4s 后才请求 `static.hotjar.com`；GTM 片段原样。

## 明确不做

- 另装 a3 Lazy Load、Smush Delay Images 等与主题 / Rocket 抢图加载的插件
- 在 Rocket 里对主题 `assets/dist` 开 Delay JS / 合并（滤镜已排除；勿在后台强行移除排除）
- 套餐 / 港送 / PWA 专属券等 Starcore 专属能力
- 默认延迟 GTM / gtag（转化与 pageview 会丢）

## 后续可选

- 字体 preload 收窄为正文 400
- `content-visibility: auto`（先量 CLS；本轮不做）
- GTM **容器内** Hotjar：须在 GTM 设触发延迟（主题只处理 Enhanced Tools 直出片段）
- 同一段 Hotjar：**要么**主题 idle，**要么** Rocket Delay JS，勿叠两次

## 相关文档

- [首页 Hero](../features/home-hero.md)
- [商店归档](../features/shop-archive.md)
- [单品商品画廊](../features/product-gallery.md)
- [站点壳层](../features/site-shell.md)
- [独立插件整合](../integrations/README.md)
- [构建系统](build-system.md)
