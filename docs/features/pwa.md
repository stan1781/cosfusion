# PWA Level 1

可安装 Progressive Web App（无专属优惠券）。默认 **关闭**；产线在 Customizer → PWA 开启。

## 能力

| 项 | 说明 |
|----|------|
| Manifest | `uploads/ts-pwa/manifest.json`（不可写时回退 REST） |
| Service Worker | `/ts-sw.js` rewrite；仅 CacheFirst 主题 `assets/dist` 静态资源；导航始终网络 |
| Install UI | bottom-sheet（`pwa-install`）；Kirki「Show install prompt」默认关 |

## 实现

- PHP：`inc/pwa.php`、`inc/config/kirki/pwa.php`
- TS：`src/ts/pwa/register.ts`、`src/ts/pwa/sw.ts`（Vite 出口固定 `sw.js`）
- 构建：`npm run build` 含 `generate-pwa-precache.mjs`；图标 `npm run pwa:icons`

开启后请到 **设置 → 固定链接** 保存一次以刷新 rewrite（或切换主题触发）。
