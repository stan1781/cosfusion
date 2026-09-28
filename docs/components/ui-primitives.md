# UI 原语

主题级交互辅助（与业务模块解耦）。样式令牌见 [design-tokens.md](../architecture/design-tokens.md)。

| 原语 | 路径 | 说明 |
|------|------|------|
| Button loading | `src/ts/ui/button-loading.ts` | `.btn.is-loading` + spinner；主色按钮 spinner 用 `--ts-on-primary`；`.btn-icon` 用 `currentColor`。Woo 主色 loading 在 `_forms.scss` 末尾 `color: transparent !important`（只藏字、保留背景），压过同文件 button base |
| Loading host | `src/ts/ui/loading-host.ts` | 区域 `.loading-host.is-loading` |
| Global loading | `src/ts/ui/global-loading.ts` | 全屏遮罩，引用计数 |
| Woo → toast | `src/ts/ui/woo-notices.ts` | 成功/信息 notice 提升为 toast |
| Tooltip | `src/ts/ui/tooltip.ts` + `components/_tooltip.scss` | 单例气泡；`data-ts-tooltip` 委托（产品组 / 归档 swatch） |
| Dialog | `src/ts/ui/dialog.ts` | `hidden` modal / `<dialog>` backdrop |
| Bottom sheet | `src/ts/ui/bottom-sheet.ts` + `ts-bottom-sheet-*` mixins | 底滑面板开闭 |
| Password visibility | `src/ts/ui/password-visibility.ts` | 登录密码明文切换 |

### Tooltip

- 任意元素加 `data-ts-tooltip="Label"`；`app.ts` 全局 `initTooltip()`，hover / focus 显示，Esc / 滚动隐藏。
- 勿与原生 `title` 同用，避免双气泡。
- 已接入：产品组 PDP 缩略图（Style term）、归档 `wc-product-card__swatch`。

购买流通知策略见 [purchase-flow.md](../features/purchase-flow.md)。
