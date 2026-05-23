# 独立插件 × TailwindScore 资源整合规范

**状态**：长期有效  
**适用主题**：[tailwindscore](D:/www/base/wp-content/themes/tailwindscore)  
**适用插件**：站点级 WooCommerce 扩展（保持 `wp-content/plugins/` 独立安装，不并入主题 `modules/`）

---

## 目的

在**不合并插件、不迁移业务逻辑到主题**的前提下，由主题接管前台 **JS/CSS 的构建与按页加载**，插件继续负责：

- 数据表 / `get_option` / 激活钩子  
- AJAX、`admin-ajax.php` action  
- 短代码、后台 UI、WooCommerce hooks  
- 前台 HTML 结构（或逐步迁到主题模板，但 endpoint 仍在插件）

任何「单个插件逐个改造」的任务开始前，应先阅读本文，并更新文末 **插件整合登记表**。

与 [wc-enhanced-tools 迁移规范](./2026-05-22-wc-enhanced-tools-migration-design.md) 的关系：

| 来源 | 整合策略 |
|------|----------|
| `wc-enhanced-tools` 单品/前台能力 | 迁入 `modules/{slug}`（PHP + Kirki + 主题 TS） |
| 其它独立插件（本表） | **插件逻辑 + 主题呈现**（本文） |
| `review-images` / 买家秀 | 优先 `modules/review-images`；若分期则先按本文拦截资源 |

---

## 核心原则

### 1. 插件管逻辑，主题管呈现

```
插件：Hooks、AJAX、option、表、短代码 SSR、后台
主题：Tailwind、Vite/TS、按页 enqueue、去 jQuery/Bootstrap 依赖
```

**禁止**同一前台能力双开：插件默认 `assets/js/*.js` 与主题 Vite 包同时生效。

### 2. 不必为性能把插件改成 TypeScript

性能来自 **按页加载、合并压缩、去掉多余依赖**，不来自 `.ts` 后缀。主题侧重写交互时使用 `src/ts/integrations/{plugin}.ts` 即可。

### 3. 配置注入不依赖已 dequeue 的句柄

插件若曾用 `wp_localize_script( 'wc-xxx-frontend', ... )`，主题接管后须改为：

- 插件提供 `function wc_*_frontend_config(): array`（推荐），或  
- `apply_filters( 'tailwindscore/{plugin}/frontend_config', array() )`  

由主题在 [inc/enqueue.php](D:/www/base/wp-content/themes/tailwindscore/inc/enqueue.php)（或 `inc/integrations/*.php`）对 `tailwindscore-app` 做 `wp_add_inline_script( ..., 'before' )`。

**禁止** localize 到已被 `wp_dequeue_script` 的 handle（配置会丢失）。

### 4. DOM / 全局变量契约

主题重写 JS 时须保持（或版本化迁移）：

- 插件输出的 **CSS 类名、data 属性**（AJAX 绑定选择器）  
- 原 `wp_localize_script` 暴露的 **全局对象名**（见下表），除非同步改插件 PHP 与所有调用处  

### 5. 后台资源不在此规范范围

`feed-export`、`wc-mailnest` 后台、`wc-enhanced-user` 积分设置页等 **继续用插件自带 admin JS/CSS**，不强制进 Vite。

### 6. 禁止前台 Bootstrap

与 wc-enhanced-tools 迁移规范一致：主题整合时不得新增 `data-bs-*` / Bootstrap Modal。`wc-order-process-manager` 前台现用 Bootstrap Modal，整合时改为主题 `<dialog>` / Alpine（与 checkout 登录 dialog 一致）。

---

## 标准实现（两档，可渐进）

### 档 A — 仅主题拦截（插件零改动或极少改动）

1. 新增 `inc/integrations/{plugin-slug}.php`，在 `functions.php` `require_once`。  
2. `add_action( 'wp_enqueue_scripts', ..., 999 )`（晚于插件默认优先级）：  
   - `wp_dequeue_script` / `wp_dequeue_style`  
   - 必要时 `wp_deregister_*`  
3. 同文件内按条件 enqueue 主题资源（manifest 多入口或 `tailwindscore-app` + 动态 import）。  
4. 用 `wp_add_inline_script` 注入与原插件一致的全局 config。

**缺点**：插件若全站 enqueue，会先注册再被 dequeue；应用档 B 避免浪费。

### 档 B — 插件配合（推荐终态）

**插件侧**（每个插件改造时添加）：

```php
// 默认 true，主题 filter 为 false
if ( apply_filters( 'wc_mailnest_enqueue_frontend_assets', true ) ) {
    wp_enqueue_script( 'wc-mailnest-frontend', ... );
}

/**
 * 供主题 inline 的配置（替代 localize 到已移除的 handle）
 */
function wc_mailnest_frontend_config(): array {
    return array(
        'subscribe' => array( /* 原 wcSubscribeAjax 字段 */ ),
        'form'      => array( /* 原 wcMailnestFormAjax 字段 */ ),
    );
}
```

**主题侧**：

```php
add_filter( 'wc_mailnest_enqueue_frontend_assets', '__return_false' );

add_action( 'wp_enqueue_scripts', function () {
    if ( ! tailwindscore_should_load_mailnest() ) {
        return;
    }
    // enqueue 主题 chunk + inline config
}, 1000 );
```

Filter 命名约定：`{plugin_slug}_enqueue_frontend_assets` 或 `tailwindscore/dequeue/{plugin_slug}`（二选一，每个插件统一一种，登记在表内）。

---

## 主题目录约定（规划）

```
inc/integrations/
  wc-mailnest.php
  wc-enhanced-user.php
  wc-order-process-manager.php
  README.md                 # 句柄清单速查（可选，与本文登记表同步）

src/ts/integrations/
  mailnest.ts               # 订阅弹窗、联系表单
  account-login.ts          # Ajax 登录
  points-redeem.ts          # 单品积分兑换
  order-process.ts          # 确认收货等

src/scss/integrations/
  _mailnest.scss
  _order-process.scss
```

Vite [vite.config.ts](D:/www/base/wp-content/themes/tailwindscore/vite.config.ts) 可增加 `rollupOptions.input` 独立入口，或仅在 `app.ts` 内 `import()` 动态分包——按体积与是否全站需要选择。

配置合并入口优先扩展 `tailwindscore_localize_app_script()`；仅 account 页需要的 config 可在 `inc/integrations/*.php` 按页追加 inline。

---

## 插件整合登记表

改造某个插件时：**更新「状态」列**，并在插件仓库或主题 `CHANGELOG` 记一笔。

| 插件 | 业务归属 | 前台句柄（dequeue 目标） | 全局 config（须保持或迁移） | 主题加载条件 | 整合策略 | 状态 |
|------|----------|--------------------------|----------------------------|--------------|----------|------|
| [wc-mailnest](D:/www/base/wp-content/plugins/wc-mailnest) | 插件 | `wc-mailnest-frontend`（CSS+JS） | `wcSubscribeAjax`、`wcMailnestFormAjax` | 弹窗开启 / 含订阅短代码 / 联系表单页；**非** cart/checkout | 档 B + `integrations/mailnest.ts` | ⏸ 待做 |
| [wc-enhanced-user](D:/www/base/wp-content/plugins/wc-enhanced-user) | 插件 | `wc-user-login-ajax` | `wcUserEnhancedLogin` | `is_account_page()` && 未登录 | 档 B + `account-login.ts` | ⏸ 待做 |
| 同上 | 插件 | `wc-points-redeem` | `wcUserEnhancedParams` | `is_product()` && 积分功能开启 | 档 B + `points-redeem.ts` | ⏸ 待做 |
| 同上 | 插件 | `registration-form`（若 enqueue） | — | 注册页 | 可仅 dequeue（文件已空） | ⏸ 待做 |
| [wc-order-process-manager](D:/www/base/wp-content/plugins/wc-order-process-manager) | 插件 | `wc-order-process-frontend`（CSS+JS） | `wcOrderProcessFrontend` | `is_account_page()` | 模板迁主题 `woocommerce/` + 去 Bootstrap + `order-process.ts` | ⏸ 待做 |
| [feed-export](D:/www/base/wp-content/plugins/feed-export) | 插件 | —（仅后台） | — | 不适用 | 不整合前台 | — 跳过 |
| [wc-order-process-manager](D:/www/base/wp-content/plugins/wc-order-process-manager) | 插件 | 插件 `woocommerce_locate_template` | — | My Account | 业务留插件；模板逐步迁 `themes/tailwindscore/woocommerce/`，**停用**插件抢模板 | ⏸ 待做 |

### 已知问题（改造时一并修复）

| 问题 | 位置 | 修复 |
|------|------|------|
| checkout/cart 未卸掉 mailnest | [inc/woocommerce/checkout.php](D:/www/base/wp-content/themes/tailwindscore/inc/woocommerce/checkout.php) 使用 `mailnest-frontend` | 改为 `wc-mailnest-frontend` 或改用语义化 `tailwindscore_dequeue_mailnest_assets()` |
| mailnest 全站 enqueue | 插件 `Subscribe_Form` / `Contact_Form` | 插件加条件 enqueue 或主题 filter 关默认 + 主题按页加载 |

---

## 不宜用本文、应走 `modules/` 的能力

| 能力 | 原因 |
|------|------|
| `wc-enhanced-tools`：gallery、variations、related-products | 已迁或按 [迁移规范](./2026-05-22-wc-enhanced-tools-migration-design.md) 迁 |
| `review-images` + `Showcase_Gallery` | 与单品评论/图库强耦合；整块 `modules/review-images` + TS |
| 未来 size-guide | Kirki + 单品模板 + TS |

---

## 单插件改造检查清单

复制本节作为每个插件任务的 PR / 会话检查项。

### 准备

- [ ] 阅读本文与插件主文件、前台 `enqueue` 类  
- [ ] 在登记表注明负责人与目标档（A 或 B）  
- [ ] 列出所有前台 handle、依赖（jquery 等）、全局变量名  

### 插件改动（档 B）

- [ ] 增加 `apply_filters( '{slug}_enqueue_frontend_assets', true )` 包裹前台 enqueue  
- [ ] 抽出 `*_frontend_config()`，字段与原 `wp_localize_script` 一致  
- [ ] 不修改 AJAX action 名、nonce 名、表名、option 键（除非单独迁移任务）  
- [ ] 后台 enqueue 不动  

### 主题改动

- [ ] 新增 `inc/integrations/{slug}.php` 并在 `functions.php` require  
- [ ] dequeue / filter 关闭插件前台资源（优先级 ≥ 999）  
- [ ] 实现 `tailwindscore_should_load_{slug}()` 条件函数  
- [ ] TS/SCSS 进 Vite；`npm run typecheck` && `npm run build`  
- [ ] config 注入 `tailwindscore-app`（或独立 manifest 入口）  
- [ ] 模板/类名与插件 HTML 契约一致；去掉 Bootstrap 依赖（如有）  

### 验证

- [ ] 目标页面：功能正常（AJAX、nonce、UI）  
- [ ] 非目标页面：Network 无插件前台 JS/CSS、无主题 integration chunk（若设计为不加载）  
- [ ] cart/checkout：无 mailnest 等无关脚本  
- [ ] 插件 deactivate：仅业务消失，无主题 JS 报错（或主题检测 `class_exists`）  
- [ ] 更新本文 **插件整合登记表** 状态为 ✅  

---

## 新会话 / AI 工作指引

1. 确认任务属于 **独立插件整合** 还是 **wc-enhanced-tools → modules**（后者读迁移规范，不读本文）。  
2. 打开本文 **插件整合登记表**，只改一个插件的一行/多行。  
3. 优先实现档 B（filter + config 函数），避免长期依赖 dequeue 竞态。  
4. 完成后更新登记表状态，并写主题或插件 `CHANGELOG`（若有）。  
5. 不要为「整合输出」合并多个无关插件为一个 PHP 包；插件数量与部署独立性问题不在本文范围。

---

## 参考

- [wc-enhanced-tools 迁移设计规范](./2026-05-22-wc-enhanced-tools-migration-design.md)  
- [inc/enqueue.php](D:/www/base/wp-content/themes/tailwindscore/inc/enqueue.php) — `tailwindscore-app`、manifest  
- [inc/woocommerce/checkout.php](D:/www/base/wp-content/themes/tailwindscore/inc/woocommerce/checkout.php) — 现有 dequeue 示例  
