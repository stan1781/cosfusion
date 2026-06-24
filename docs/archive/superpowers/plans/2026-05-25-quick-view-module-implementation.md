# Quick View 模块实施记录

**日期**：2026-05-25  
**模块**：`modules/quick-view` v0.1.0  
**设计**：[2026-05-22-wc-enhanced-tools-migration-design.md](../specs/2026-05-22-wc-enhanced-tools-migration-design.md)

## 已实现

- PHP：`Tailwindscore_Quick_View_Settings`、`Tailwindscore_Quick_View_Ajax`、`Tailwindscore_Quick_View_Frontend`
- Kirki：`product_quick_view_enabled`、`product_quick_view_target_pages`
- 模板：`content-product-quick-view.php`、`variable-quick-view.php`、`quick-view-modal.php`
- TS：`shop/quick-view.ts`、`shop/quick-view-variations.ts`、`product/add-to-cart-request.ts`（与单品加购共用）
- 样式：`components/_quick-view-modal.scss`

## 验证

```bash
npm run build
npx tsc --noEmit src/ts/shop/quick-view.ts src/ts/shop/quick-view-variations.ts
```

手动：插件 inactive → 归档可变品「选择规格」→ QV → 文字按钮 → Store API 加购 → toast + 购物车抽屉。

## 性能优化（0.1.1）

- 服务端：分层缓存键、商品版本 meta、库存/更新钩子失效、变体 JSON 瘦身、渲染锁。
- 客户端：`sessionHtmlCache`、悬停预取、避免重复 `get_available_variations()`。

## 未纳入 v1

- 主图点击开 QV
- QV 内完整变体选择器（颜色图、grid/list）
- wooco 复合商品、礼物包装动态挂载
