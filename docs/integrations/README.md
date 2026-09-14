# 独立插件整合

在 **不合并插件、不迁移业务逻辑到主题** 的前提下，由 TailwindScore 接管独立插件的 **前台 JS/CSS 构建与按页加载**。插件继续负责数据表、AJAX、后台 UI、短代码 SSR。

实现代码：`inc/integrations/`（在 `functions.php` 中按插件 `require_once`）。

## 核心原则

```text
插件：Hooks、AJAX、option、表、短代码 SSR、后台
主题：SCSS 令牌、Vite/TS、按页 enqueue、去 jQuery/Bootstrap
```

**禁止**同一前台能力双开：插件默认 `assets/js/*.js` 与主题 Vite 包同时生效。

配置注入须通过 `wp_add_inline_script` 挂到 `tailwindscore-app`，**禁止** localize 到已被 dequeue 的 handle。

## 整合登记表

| 插件 | 主题文件 | 全局 config | 加载条件 | 策略 | 状态 |
|------|----------|-------------|----------|------|------|
| wc-mailnest | `inc/integrations/wc-mailnest.php` | `wcSubscribeAjax`、`wcMailnestFormAjax` | 弹窗/短代码/联系表单页；前台**非** cart/checkout（结账自动订阅为插件服务端钩子） | 档 B + `src/ts/integrations/mailnest.ts` | ✅ 已完成 |
| wc-order-process-manager | `inc/integrations/wc-order-process-manager.php` | `wcOrderProcessFrontend` | `is_account_page()` | 模板迁主题 + `order-process.ts`；去 Bootstrap Modal | ✅ 已完成 |
| Iconic Linked Variations | `inc/integrations/iconic-linked-variations.php` | — | 按插件条件 | 样式/资源协调 | ✅ 已整合 |
| WP Enhanced Tools（脚本管理） | `inc/integrations/wp-enhanced-tools.php` | — | 插件 Head/Body/Foot 直出 | 滤镜 `wp_enhanced_tools_script_output`：仅 Hotjar idle/交互延迟 | ✅ 已整合 |
| wc-enhanced-user | — | `wcUserEnhancedLogin`、`wcUserEnhancedParams` | 账户页 / 单品积分 | 档 B（待实现） | ⏸ 待做 |
| feed-export | — | — | 仅后台 | 跳过 | — |

### wc-mailnest

- 订阅弹窗、联系表单前台交互
- cart/checkout 通过 `tailwindscore_dequeue_mailnest_assets()` 卸载无关资源（前台 JS/CSS）
- **结账自动订阅**由插件服务端钩子 `woocommerce_checkout_order_processed` 写入（与前台资源卸载无关）；默认直接 `confirmed`，可在「邮件订阅 → 邮件设置」关闭
- 样式：`src/scss/components/_mailnest-subscribe-modal.scss`（分栏 `--split` 在 md 以下限制配图 `max-height: 12rem`，弹窗 `max-height` 与无图一致，避免只见图片）；短代码标题区 `.mailnest-subscribe__header` 左侧为 `envelope` 图标（`inc/icons.php`）
- 插件 SSR 类名须对齐主题约定（**无** `ts-` 前缀）：`mailnest-subscribe*`、`mailnest-contact-form*`、`btn` / `form-control` / `input-group` / `input-inline` / `input-stack`、`field` / `form-layout-*`；行为钩子仍用 `data-ts-mailnest-*`
- 订阅 `form_style` 与全局控件组类名一致：`input-group` / `input-inline` / `input-stack`
- 订阅按钮样式（后台 `button_color`）：仅 `primary` / `secondary` / `outline`（→ `btn` / `btn-secondary` / `btn-outline`）；`form_size` 仅默认或 `sm`（→ `btn-sm`）
- 联系表单 `form_style`：`stacked` → `form-layout-stacked`；`floating` → `form-layout-floating`（字段用 `.field` / `.form-control`；高度与普通 `form-control` 一致）
- 联系表单字段校验：提交 / blur 显示 `data-ts-mailnest-field-error`；字段变为有效时在 `input` / `change` / blur 清除；再次提交前会先清空再只标仍无效的字段
- floating：空态标签居中于控件内；聚焦 / `is-filled` 时标签骑在顶边（`--ts-surface` 背景切开边框）；`date` / `time` / `datetime-local`（及 `month` / `week`）因浏览器空值仍有原生占位，标签始终上浮；点击整块控件调用 `showPicker()`（见 `form-floating.ts`）；stacked 上标签 `for=` 激活不打开选择器

### wc-order-process-manager

- 确认收货等 My Account 前台流程
- Bootstrap Modal 改为主题 `<dialog>` / TS
- Woo 模板优先使用 `themes/tailwindscore/woocommerce/`

### WP Enhanced Tools（脚本管理）

- 插件 `Script_Manager` 在 Head / Body / Foot 直出管理员粘贴的 HTML（option `wc_enhanced_tools_scripts`）
- 输出前经滤镜 `wp_enhanced_tools_script_output`（`$content`, `$location`）
- 主题仅延迟含 Hotjar 的 `<script>`（idle / 首次交互）；GTM、gtag、`noscript` 原样
- GTM **容器内** 的 Hotjar 主题改不到，须在 GTM 设触发延迟
- 与 WP Rocket Delay JS：**同一段 Hotjar 勿叠两次**（二选一）

详见 [前台性能](../architecture/performance.md)。

### wc-enhanced-user（待做）

| 句柄 | 场景 |
|------|------|
| `wc-user-login-ajax` | 未登录账户页 Ajax 登录 |
| `wc-points-redeem` | 单品页积分兑换 |
| `registration-form` | 注册页（可仅 dequeue 空文件） |

计划 TS：`src/ts/integrations/account-login.ts`、`points-redeem.ts`。

## 标准实现流程

### 档 B（推荐）

**插件侧：**

```php
if ( apply_filters( 'wc_mailnest_enqueue_frontend_assets', true ) ) {
    wp_enqueue_script( 'wc-mailnest-frontend', ... );
}

function wc_mailnest_frontend_config(): array {
    return array( /* 原 localize 字段 */ );
}
```

**主题侧：**

```php
add_filter( 'wc_mailnest_enqueue_frontend_assets', '__return_false' );
// enqueue 主题资源 + wp_add_inline_script 注入 config
```

### 档 A（过渡）

`wp_enqueue_scripts` 优先级 ≥ 999  dequeue 插件资源 + 主题 enqueue。长期应升级到档 B。

## 单插件改造检查清单

### 准备

- [ ] 阅读插件主文件与前台 enqueue 类
- [ ] 列出所有前台 handle、依赖、全局变量名
- [ ] 在本文登记表注明目标档（A 或 B）

### 插件（档 B）

- [ ] `apply_filters( '{slug}_enqueue_frontend_assets', true )` 包裹前台 enqueue
- [ ] 抽出 `*_frontend_config()`，字段与原 localize 一致
- [ ] 不修改 AJAX action、nonce、表名、option 键

### 主题

- [ ] 新增 `inc/integrations/{slug}.php` 并在 `functions.php` require
- [ ] 实现 `tailwindscore_should_load_{slug}()` 条件函数
- [ ] TS/SCSS 进 Vite；`npm run typecheck && npm run build`
- [ ] config 注入 `tailwindscore-app` 或 `tailwindscore_runtime_config_parts`
- [ ] 去掉 Bootstrap 依赖（如有）

### 验证

- [ ] 目标页面功能正常（AJAX、nonce、UI）
- [ ] 非目标页面 Network 无插件前台 JS/CSS
- [ ] cart/checkout 无无关脚本
- [ ] 插件 deactivate 后主题无 JS 报错
- [ ] 更新本登记表状态

## 不宜用本文、应走 modules/

| 能力 | 原因 |
|------|------|
| wc-enhanced-tools：gallery、variations、size-guide 等 | 已迁入 `modules/` |
| review-images | 与单品评论强耦合，整块在 `modules/review-images` |

## 归档

完整规范：[archive/superpowers/specs/2026-05-23-plugin-theme-asset-integration-design.md](../archive/superpowers/specs/2026-05-23-plugin-theme-asset-integration-design.md)
