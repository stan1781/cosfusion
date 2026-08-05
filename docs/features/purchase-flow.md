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

- 头部 **购物车 Drawer** 为主要交互界面（数量步进、编辑、小计、Checkout CTA）；数量为 1 时减号切换为删除图标，再点即移除行项（无独立删除按钮）
- 单品加购成功后 **自动打开 Drawer**
- Drawer / 购物车页 **优惠码**（`cart-config.php` 中 drawer coupon 设置、`ui/coupon-form.ts`）
- Drawer / 购物车页 **免运进度条**（`cart-free-shipping-progress.php`；Drawer 行项上方；Cart 页在 `.wc-cart__sidebar` 内与 aside 卡片同级；门槛读 Woo 条件免运 `min_amount`；Store API `extensions.tailwindscore.freeShippingProgress`；`refreshCart()` 后 TS 重绘）
- 购物车页 **产品推荐轮播**（`cart-product-suggestions.php`，共享 [产品轮播](product-carousel.md) 壳层；不进 Drawer）
- **统一通知**接口（`src/ts/cart/notifications.ts`）：支持 toast 与 inline/drawer 反馈
- 购物车行项 **编辑 modal**（`cart-item-edit.ts`）
- 结账页布局与登录 dialog（`checkout-layout.php`、`ui/login-modal.ts`）
- 结账 Payment 区隐藏空的 PPCP `.ppcp-messages`（Pay Later 占位未出文案时避免 flex gap 留白；`_checkout.scss`）
- 结账条款文案 `.woocommerce-terms-and-conditions-wrapper`：`--ts-font-size-xs` + `--ts-text-muted`；内链用 `--ts-link-color` + underline（覆盖全局 `a { color: inherit }`）
- Store API 缓存优化（`store-api-cache.php`）
- Store API nonce：页注入 nonce 视为新鲜（TTL 60s），冷加购默认 **单次 POST**；仅 TTL 过期或 `woocommerce_rest_invalid_nonce` 时才先 `GET /cart`
- 运费规则（`shipping-rules.php`）：达标后隐藏付费配送；门槛读取当前配送 zone 内条件免运的 **Minimum order amount**（`min_amount`），与 Starcore 一致，Customizer 仅配置开关与要隐藏的 rate ID，不手填门槛金额
- 可变商品 **未选齐变体**：主加购与 sticky ATC 灰显，文案为 `Select {Attribute}`（首个未选属性）；点击仅滚到对应属性行，不弹 toast / alert（`variation-atc-ready.ts`）
- 可变商品 **选齐变体**：主加购与 sticky ATC 文案均为 `Add to cart`（价格在摘要区 / sticky 价格区展示，不写入按钮）。

## 通知策略

| 场景 | 承载方式 |
|------|----------|
| 加购成功、优惠码成功、删除成功 | **toast**（短时） |
| 库存不足、数量被修正、Drawer 内无效优惠码 | **Drawer 内联**持久反馈 |
| 结账表单校验、支付失败 | **checkout 页面 inline** |

Toast 底部有与自动关闭时长（4200ms）同步的倒计时进度条（`purchase-flow-toast__progress`，CSS `scaleX` 动画）。

不把阻塞型结账错误只做成短暂 toast。

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

## 验证清单

```bash
npm run typecheck
npm run build
```

手动验证：

1. 单品加购 → Drawer 自动打开
2. Drawer 内数量更新、删除商品
3. 优惠码成功/失败反馈位置正确
4. Checkout CTA 跳转正常
5. 结账页阻塞型校验错误可见

## 相关文档

- 原始设计（归档）：[archive/superpowers/specs/2026-05-21-woocommerce-purchase-flow-design.md](../archive/superpowers/specs/2026-05-21-woocommerce-purchase-flow-design.md)
