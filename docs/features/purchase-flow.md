# 购买流程

TailwindScore 采用 **Drawer-first** 购买路径：用户在单品页加购后优先通过头部购物车 Drawer 完成结账前操作，`checkout` 承担正式下单。

## 主路径

```text
单品页加购 → Store API 更新购物车 → 自动打开 Drawer
    → Drawer 内改数量 / 删商品 / 优惠码 → Checkout CTA → 结账页
```

`cart` 页面保留为轻量兜底：可访问、可概览，但不鼓励作为主要编辑界面。

## 架构分层

| 层 | 路径 | 职责 |
|----|------|------|
| 页面模板 | `woocommerce/cart/`、`checkout/` | 页面骨架与 Woo hook |
| 视图片段 | `template-parts/woocommerce/`、`template-parts/header/cart-drawer.php` | Drawer 内容、行项、汇总 |
| 领域 helper | `inc/woocommerce/cart-*.php`、`checkout*.php`、`shipping-rules.php` | URL、配置、服务端渲染 |
| 前端 | `src/ts/cart/`、`src/ts/checkout/`、`src/ts/product/add-to-cart-request.ts` | Store API、通知、DOM 刷新 |

## 已实现能力

- 头部 **购物车 Drawer** 为主要交互界面（数量步进、编辑、小计、Checkout CTA）；数量步进始终为减号（qty→0 仍删行）；独立删除按钮在产品名称行右侧，一点即移除行项
- 单品加购成功后 **自动打开 Drawer**
- 单品 **价格行促销徽章**（`product-price-row.php` + `sale-badge.php`）：价格后显示最大折扣 `-N%` / 金额回退；可变商品固定最大值，不随变体选择更新（画廊已移除默认 sale flash）
- Drawer / 购物车页 **优惠码**（`cart-config.php` 中 drawer coupon 设置、`ui/coupon-form.ts`）
- Drawer / 购物车页 **免运进度条**（`cart-free-shipping-progress.php`；Drawer 行项上方；Cart 页在 `.wc-cart__sidebar` 内与 aside 卡片同级；门槛读 Woo 条件免运 `min_amount`；Store API `extensions.tailwindscore.freeShippingProgress`；`refreshCart()` 后 TS 重绘）
- 购物车页 **产品推荐轮播**（`cart-product-suggestions.php` + 共享引擎 `product-suggestions.php`：Cross-sells → 车内分类 → 人气补齐；共享 [产品轮播](product-carousel.md) 壳层；不进 Drawer；列数在「购物车」Customizer 独立设置，默认手机 `1.5` / 桌面 `3`，**不读**商店全局产品轮播）
- 单品页 **配件搭配**（Cross-sells list 横卡，见 [配件搭配](product-cross-sells.md)）
- **统一通知**接口（`src/ts/cart/notifications.ts`）：支持 toast 与 inline/drawer 反馈
- 购物车行项 **编辑 modal**（`cart-item-edit.ts`）：左固定缩略图 + 右品名/价格/变体选项；匹配变体时联动更新价格与图片（有变体图才换，未选齐时回退初始图）
- 结账页布局与登录 dialog（`checkout-layout.php`、`ui/login-modal.ts`）；modal 密码框带明文切换（`ui/password-visibility.ts`，账户页仍由 `woocommerce.js` 处理）；Remember me 与 Lost password 同一行左右排
- 结账 Payment 区隐藏空的 PPCP `.ppcp-messages`（Pay Later 占位未出文案时避免 flex gap 留白；`_checkout.scss`）
- 结账条款文案 `.woocommerce-terms-and-conditions-wrapper`：`--ts-font-size-xs` + `--ts-text-muted`；内链用 `--ts-link-color` + underline（覆盖全局 `a { color: inherit }`）
- 结账 **Terms / Privacy modal**（`checkout-legal-modals.php` + `legal-modals.ts`）：点击条款或隐私政策链接打开原生 `<dialog>` 展示对应页面全文；正文挂 `.entry__content` + `ts-entry-content-prose`（与 Page / Post 共用）；`body.woocommerce-cart` / `woocommerce-checkout` / `woocommerce-account` 上的 `.entry__content` 仅壳层、不套正文 `a`/列表，避免污染购物车 / 账户页链接与价格；移除 Woo 内联条款 slideToggle；无 JS 时链接仍指向页面 permalink
- 结账订单摘要行项缩略图（`.checkout-line-item__media` 固定 `3.75rem` + `aspect-ratio: 1`）：选择器高于 `.entry__content img`；正文图为 `width: auto; max-width: 100%`（小图保持固有尺寸）
- 购物车 / 结账表单页默认不输出站点页脚（Kirki `footer_hide_on_cart` / `footer_hide_on_checkout`；订单成功页仍显示；见 [站点壳层](site-shell.md)）
- 购物车页 **底部固定操作栏**（`cart-action-bar.php` + `renderCartFixedActionBar`）：Continue shopping + Checkout（含订单总额；CTA 旁实心 `lock` + `.icon--solid`）；侧栏仅 totals / coupon，不重复 CTA；空车不渲染；`body.woocommerce-cart` 预留 `--ts-action-bar-height`
- **订单成功页**（`order-received`）：对齐 Starcore 布局——Hero（可配置标题/副标题）+ 三格 overview stats + 行项摘要/总计 + 侧栏配送方式与地址 + Next steps + Actions；移除 Woo 默认 `woocommerce_order_details_table` 以免重复
- **WhatsApp CTA**（成功页）：号码未配置时不渲染；预填消息支持 `{order_number}` / `{order_total}`（Customizer → 设计 → 联络方式）。**页头顶栏 / FAB 本轮未接入**
- Store API 缓存优化（`store-api-cache.php`）
- Store API nonce：页注入 nonce 视为新鲜（TTL 60s），冷加购默认 **单次 POST**；仅 TTL 过期或 `woocommerce_rest_invalid_nonce` 时才先 `GET /cart`
- **禁用 GET `?add-to-cart=`**：卡片简单品用 `button` + Store API；`tailwindscore_block_get_add_to_cart` 去掉查询参数，避免 bot 扫链加购（单品 POST 表单不受影响）
- 运费规则（`shipping-rules.php`）：达标后隐藏付费配送；门槛读取当前配送 zone 内条件免运的 **Minimum order amount**（`min_amount`），与 Starcore 一致，Customizer（根级「结账」）仅配置开关与要隐藏的 rate ID，不手填门槛金额
- **区域限制**（`checkout-region-restrictions.php`，策略 B）：账单或收货任一为 `US` + `IL`（Illinois）则拒绝下单；经典结账用 `woocommerce_after_checkout_validation`，Store API / Blocks 用 `woocommerce_store_api_checkout_update_order_from_request`；前台 `woocommerce_states` 去掉 Illinois，后台改单仍可选；不改 `form-checkout.php`、不依赖 Shipping Zone
- 可变商品 **未选齐变体**：主加购与 sticky ATC 灰显，文案为 `Select {Attribute}`（首个未选属性）；点击仅滚到对应属性行，不弹 toast / alert（`variation-atc-ready.ts`）
- 可变商品 **选齐变体**：主加购与 sticky ATC 文案均为 `Add to cart`（价格在摘要区 / sticky 价格区展示，不写入按钮）。
- 单品摘要 **加购行**：简单品 `form.cart:not(.variations_form)` 与可变品 `.woocommerce-variation-add-to-cart` 均为 flex；数量固定宽，`.single_add_to_cart_button` 以 `flex: 1 1 auto` 占满剩余宽度（`_product.scss`）。

## 通知策略

| 场景 | 承载方式 |
|------|----------|
| 加购成功、优惠码成功、删除成功 | **toast**（短时） |
| WooCommerce `.woocommerce-message` / `.woocommerce-info`（非结账校验） | **toast**（`woo-notices.ts` 提升后移除原 banner） |
| 库存不足、数量被修正、Drawer 内无效优惠码 | **Drawer 内联**持久反馈 |
| 结账表单校验、支付失败 | **checkout 页面 inline**（不提升为 toast） |

Toast 贴顶（对齐 Starcore）：桌面（`≥ md`）右上限宽卡片；手机（`< md`）顶部全宽贴视口顶，浅底延伸进 `safe-area-inset-top`（padding 在 toast 自身，root 不垫顶以免出现顶缝），圆角仅底部。入场 / 离场共用 `is-entering` / `is-leaving` 从上滑入、向上滑出。外观为类型浅底色（`color-mix` 约 14% 混入 success/warning/danger/info）+ 同色系文字、无边框、保留阴影，无标题栏；圆形 `.purchase-flow-toast__close` 桌面溢出右上角，手机贴齐右上（safe-area 下、无 transform）。手机底部有 `.purchase-flow-toast__handle` pill，向上拖过阈值关闭（拖拽中暂停自动关闭）。底部进度条（`purchase-flow-toast__progress`）与自动关闭时长（4200ms）同步（CSS `scaleX` 动画）。

不把阻塞型结账错误只做成短暂 toast。

### 全局 loading

- `src/ts/ui/global-loading.ts`：全屏 `.global-loading` 引用计数遮罩（`setGlobalLoading` / `withGlobalLoading`）
- 已接入：购物车行项编辑打开 / 保存；**Quick View 取 HTML 只用弹层内 loading**（可关闭，不挡 Esc / 关按钮）
- **不**替代结账页 `checkout-processing` / `.loading-host` 区域遮罩

### Bottom sheet 原语

- TS：`src/ts/ui/bottom-sheet.ts`；SCSS mixin：`ts-bottom-sheet-panel` / `ts-bottom-sheet-panel-open` / `ts-bottom-sheet-overlay-shell`
- 首个业务消费者：PWA install 提示（见 [b2c-conversion.md](../roadmap/b2c-conversion.md) Phase 4）

前端 API 形态（`notifications.ts`）：

```ts
notify({
  type: 'success' | 'info' | 'warning' | 'error',
  message: string,
  surface: 'toast' | 'inline' | 'drawer' | 'page',
  context?: string,
});
```

## 运行时配置

`window.tailwindscoreCart` 由 `inc/enqueue.php` 注入，来源 `tailwindscore_purchase_flow_config()`（`inc/woocommerce/cart-config.php`）。

## 错误处理

- Store API 失败须向用户展示有上下文的反馈，不可静默失败
- JS 增强路径失败时，原生表单提交仍须可用
- WooCommerce 未启用时头部渲染安全降级

## Customizer

主题购买流程相关设置已提升为一级菜单（不再挂在原生 WooCommerce panel）：

| 路径 | 内容 |
|------|------|
| 购物车（根级） | Drawer 优惠码、免运进度、购物车页推荐等 |
| 结账页面（根级） | 运费规则；订单成功页 Hero / Next steps |
| 设计 → 联络方式 | E.164 号码、展示号码、通用/订单预填消息（成功页 CTA；无 Header 入口） |

## 验证清单

```bash
npm run typecheck
npm run build
```

手动验证：

1. 单品加购 → Drawer 自动打开
2. Drawer 内数量更新、删除商品
3. 优惠码成功/失败反馈位置正确
4. Checkout CTA 跳转正常（购物车页底部固定栏；Drawer 内 CTA）
5. 结账页阻塞型校验错误可见
6. Toast：桌面右上卡片；手机顶部全宽；向上拖动手柄可关闭
7. 订单成功：Hero / stats / 行项 / 地址 / next steps；配置 WhatsApp 后出现 CTA，清空号码后消失；失败单仍显示 Pay；无重复 Woo 订单详情表
8. 购物车有货：底部固定栏可见、侧栏无 Checkout、改数量后栏内总额更新；空车无底栏

## 相关文档

- 原始设计（归档）：[archive/superpowers/specs/2026-05-21-woocommerce-purchase-flow-design.md](../archive/superpowers/specs/2026-05-21-woocommerce-purchase-flow-design.md)
- 实现文件：`woocommerce/checkout/thankyou.php`、`order-received.php`、`inc/woocommerce/thankyou.php`、`inc/contact/whatsapp.php`、`template-parts/woocommerce/order-success/`、`src/scss/woocommerce/_thankyou.scss`
