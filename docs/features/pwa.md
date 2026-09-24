# PWA Level 1

可安装 Progressive Web App（无专属优惠券）。默认 **关闭**；产线在 Customizer → PWA 开启。

## 能力

| 项 | 说明 |
|----|------|
| Manifest | `uploads/ts-pwa/manifest.json`（不可写时回退 REST） |
| Service Worker | 注册 URL 为 `/?tailwindscore_pwa_asset=sw`（稳定 200）；`/ts-sw.js` rewrite 保留；仅 CacheFirst 主题 `assets/dist` 静态资源；导航始终网络 |
| Install UI | 底部固定提示条（`.pwa-install`，对齐 Starcore `.sc-pwa-install`）；**无全屏遮罩**，不阻断页面浏览；Kirki「Show install prompt」默认关 |
| 图标 | Kirki 保存后写入 **`uploads/ts-pwa/icon-*.png`**（与 manifest 同目录）；主题 `assets/pwa/` 仅为本地 `npm run pwa:icons` 占位（**git 忽略、不随主题包发布**） |

## 实现

- PHP：`inc/pwa.php`、`inc/config/kirki/pwa.php`
- TS：`src/ts/pwa/register.ts`、`src/ts/pwa/sw.ts`（Vite 出口固定 `sw.js`）
- 样式：`src/scss/components/_pwa-install.scss`
- 构建：`npm run build` 含 `generate-pwa-precache.mjs`；图标 `npm run pwa:icons`

开启后请到 **设置 → 固定链接** 保存一次以刷新 rewrite（或切换主题触发）。

## 安装提示

需 Kirki「Show install prompt」开启；默认关闭。前台由 `tsPwa.installPromptEnable` 门控。

- **Android Chrome**：`beforeinstallprompt` + 底部提示条（可继续滚动/点击页面；点「Not now」写入 sessionStorage 本会话不再显示）
- **iOS Safari**：仅显示操作说明（系统无自动安装窗）
- **勿**使用 bottom-sheet + overlay：遮罩会强制用户先点安装/稍后才能浏览

## 装置 OS（订单来源增强）

与 PWA 安装无关：展示时解析订单已有字段 `customer_user_agent`（`$order->get_customer_user_agent()`），粗分为 iOS / Android / macOS / Windows / Linux；不写新 meta、不依赖插件。历史订单只要 UA 已存即可立刻显示。

| 位置 | 表现 |
|------|------|
| 订单列表 **Origin** 列 | 来源下方追加一行（`iOS` 等） |
| 订单详情账单地址下方 | 「Device: iOS」等 |

无 UA（如后台手动建单）或不识别时不输出。实现：`inc/woocommerce/order-device-os.php`。

## 排障

| 现象 | 常见原因 |
|------|----------|
| 安装提示不出现 | SW 未注册成功；Kirki「Show install prompt」关闭；已点「Not now」（同会话 sessionStorage） |
| `/ts-sw.js` 有 JS 但状态码 404 | 本站主机常见；注册已改走 query URL；勿把 `swUrl` 改回 `/ts-sw.js` |
| `themes/.../assets/pwa/icon-192.png` 404 | 正常：该目录不发布；正式图标在 `uploads/ts-pwa/`。部署本修复后，到 Customizer → PWA **再保存一次** 触发生成 |
| 图标已换仍不可安装 | 清 CDN / 浏览器缓存；确认 `uploads/ts-pwa/icon-192.png` 可访问且为 192×192 |
| Cursor / Electron 内嵌页 | 通常不触发安装事件；请用本机 Chrome 或 Android Chrome 测 |
| 提示挡住浏览 | 旧版含 `.pwa-install-overlay`；需部署含非遮罩 toast 的 `assets/dist` |

自定义安装条依赖浏览器 `beforeinstallprompt`（Chrome / Edge / Android）。桌面 Chrome 也可能只在地址栏显示 ⊕ 安装图标，而不立刻弹自定义条。
