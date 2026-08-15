# UI 原语

主题级交互辅助（与业务模块解耦）。样式令牌见 [design-tokens.md](../architecture/design-tokens.md)。

| 原语 | 路径 | 说明 |
|------|------|------|
| Button loading | `src/ts/ui/button-loading.ts` | `.btn.is-loading` + spinner |
| Loading host | `src/ts/ui/loading-host.ts` | 区域 `.loading-host.is-loading` |
| Global loading | `src/ts/ui/global-loading.ts` | 全屏遮罩，引用计数 |
| Woo → toast | `src/ts/ui/woo-notices.ts` | 成功/信息 notice 提升为 toast |
| Dialog | `src/ts/ui/dialog.ts` | `hidden` modal / `<dialog>` backdrop |
| Bottom sheet | `src/ts/ui/bottom-sheet.ts` + `ts-bottom-sheet-*` mixins | 底滑面板开闭 |
| Password visibility | `src/ts/ui/password-visibility.ts` | 登录密码明文切换 |

购买流通知策略见 [purchase-flow.md](../features/purchase-flow.md)。
