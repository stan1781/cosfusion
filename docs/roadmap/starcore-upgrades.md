# Starcore → TailwindScore 后续升级评估

首页已对齐 Starcore（Hero Kirki + Content Blocks + Page 短代码）。下表为其余可移植项（**本轮未实现**），排除套餐 / 港送等专属能力。

| 优先级 | 项 | 理由 |
|--------|----|------|
| Done | Kirki theme_mod import/export | 见 [theme-settings-import-export.md](../features/theme-settings-import-export.md) |
| High | Kirki 按域拆分 + `tokens-output.php` | 颜色/字体集中维护 |
| Done | Header bar repeater 轮播 | 见 [site-shell.md](../features/site-shell.md) |
| Done | 导航 submenu 缩略图 | 见 [navigation.md](../features/navigation.md) |
| Done | 导航紧凑 Mega / 扁平多列 / 后台 CSS 类 chips | 见 [navigation.md](../features/navigation.md) |
| Done | Drawer 免运进度条 | 见 [purchase-flow.md](../features/purchase-flow.md) |
| High | `--site-header-height` 同步 | sticky 布局一致 |
| High | UI：global loading、woo-notices→toast、bottom-sheet | 购买流打磨 |
| Medium | 产品轮播 banner + view_all | 营销块 |
| Medium | Section title 组件 | 区块标题一致 |
| Medium | PWA Level 1（无专属券） | 可安装 + 静态缓存 |
| Medium | Checkout 移动步进 / 订单摘要折叠 | 转化 |
| Done | 商店归档 AJAX + 侧栏筛选 | 见 [shop-archive.md](../features/shop-archive.md) |
| Skip | Composite / Coderockz / remote-area / Catering Core | 与 Cosfusion 无关 |

参考：`starcore/docs/architecture/customizer.md`、`starcore/docs/components/ui-primitives.md`。
