# Starcore → TailwindScore 后续升级评估

首页已对齐 Starcore（Hero Kirki + Content Blocks + Page 短代码）。下表为其余可移植项，排除套餐 / 港送等专属能力。

执行中的转化路线见 [b2c-conversion.md](b2c-conversion.md)（一阶一验）。

| 优先级 | 项 | 理由 |
|--------|----|------|
| Done | Kirki theme_mod import/export | 见 [theme-settings-import-export.md](../features/theme-settings-import-export.md) |
| Skip | Kirki 按域拆分 + `tokens-output.php` | 暂不做 CSS 变量映射；颜色仍以 SCSS `--ts-*` 为真源 |
| Done | Header bar repeater 轮播 | 见 [site-shell.md](../features/site-shell.md) |
| Done | 导航 submenu 缩略图 | 见 [navigation.md](../features/navigation.md) |
| Done | 导航紧凑 Mega / 扁平多列 / 后台 CSS 类 chips | 见 [navigation.md](../features/navigation.md) |
| Done | Drawer 免运进度条 | 见 [purchase-flow.md](../features/purchase-flow.md) |
| Done | `--site-header-height` 同步 | 见 [site-shell.md](../features/site-shell.md)、[performance.md](../architecture/performance.md) |
| Done | 归档 LCP + 循环图 loading + skip-link + Swiper a11y / reduced-motion | 见 [performance.md](../architecture/performance.md) |
| Done | UI：global loading、woo-notices→toast、bottom-sheet | 见 [b2c-conversion.md](b2c-conversion.md)、[ui-primitives.md](../components/ui-primitives.md) |
| Skip | 产品轮播 banner + view_all | Cosfusion 不需要；轮播保持 title + 导航 |
| Done | Section title 组件 | 见 [section-title.md](../features/section-title.md) |
| Done | PWA Level 1（无专属券） | 见 [pwa.md](../features/pwa.md)；默认关 |
| Done | 订单摘要折叠 | Shopify 风格单页；见 [purchase-flow.md](../features/purchase-flow.md) |
| Skip | Checkout 移动步进 | 欧美站保持 Shopify 单页结账，不移植步进 |
| Done | 商店归档 AJAX + 侧栏筛选 | 见 [shop-archive.md](../features/shop-archive.md) |
| Skip | Composite / Coderockz / remote-area / Catering Core | 与 Cosfusion 无关 |
| Skip | 缺货到货通知 | 1688 一件代发模式暂不做 |
| Skip | 积分 / wc-enhanced-user 前台整合 | 暂无扩展计划 |

参考：`starcore/docs/architecture/customizer.md`、`starcore/docs/components/ui-primitives.md`、`starcore/docs/architecture/performance.md`。
