# PWA Level 1

可安装 Progressive Web App（无专属优惠券）。默认 **关闭**；产线在 Customizer → PWA 开启。

## 能力

| 项 | 说明 |
|----|------|
| Manifest | `uploads/ts-pwa/manifest.json`（不可写时回退 REST） |
| Service Worker | 注册 URL 为 `/?tailwindscore_pwa_asset=sw`（稳定 200）；`/ts-sw.js` rewrite 保留；仅 CacheFirst 主题 `assets/dist` 静态资源；导航始终网络 |
| Install UI | bottom-sheet（`pwa-install`）；Kirki「Show install prompt」默认关 |
| 图标 | `assets/pwa/icon-192.png` / `icon-512.png` 必须为 **正方形** 且尺寸与文件名一致，否则 Chrome 不发 `beforeinstallprompt` |

## 实现

- PHP：`inc/pwa.php`、`inc/config/kirki/pwa.php`
- TS：`src/ts/pwa/register.ts`、`src/ts/pwa/sw.ts`（Vite 出口固定 `sw.js`）
- 构建：`npm run build` 含 `generate-pwa-precache.mjs`；图标 `npm run pwa:icons`

开启后请到 **设置 → 固定链接** 保存一次以刷新 rewrite（或切换主题触发）。

## 排障

| 现象 | 常见原因 |
|------|----------|
| 安装提示不出现 | SW 未注册成功；Kirki「Show install prompt」关闭；或 overlay 未取消 `hidden`（见 `register.ts` mount） |
| `/ts-sw.js` 有 JS 但状态码 404 | 本站主机常见；注册已改走 query URL；勿把 `swUrl` 改回 `/ts-sw.js` |
| 图标已换仍不可安装 | 清 CDN / 浏览器缓存；确认 PNG 为 192×192 与 512×512 |
| Cursor / Electron 内嵌页 | 通常不触发安装事件；请用本机 Chrome 或 Android Chrome 测 |
| DOM 里有 `.pwa-install` 但看不见 | overlay 仍带 `hidden`；需部署含修复的 `assets/dist` 前端包 |

自定义 bottom sheet 依赖浏览器 `beforeinstallprompt`（Chrome / Edge / Android）。桌面 Chrome 也可能只在地址栏显示 ⊕ 安装图标，而不立刻弹自定义条。
