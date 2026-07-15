# Quick View 模块变更记录

## 0.1.5 — 2026-07-15

- 性能：悬停预读改为 fire-and-forget（移开不 abort）；打开与预读共享同一 Promise 缓存。
- 性能：并发预读上限 3；`saveData` / `2g` 网络跳过预读。

## 0.1.4 — 2026-05-25

- 文案：循环可变品按钮由「Select options」改为「Quick view」，与弹窗标题及实际行为一致。

## 0.1.3 — 2026-05-25

- 修复：同一张卡片内从图片/标题移到「选择规格」按钮时，不再因子元素 `mouseenter` 重复调用 `cancelPrefetch()` 而中断预取。

## 0.1.2 — 2026-05-25

- 修复：QV 变体 JSON 瘦身时清除 `dimensions`/`weight`，避免 Woo `add-to-cart-variation.js` 报 `reading 'data'`。
- 修复：Ajax 响应解析更稳健（非 JSON / 空 body）。
- 改进：悬停预取绑定整张 `data-product-card`（仅含 `[data-ts-quick-view]` 的可变品卡片）。

## 0.1.1 — 2026-05-25

- 性能：缓存键增加 locale/货币/模块版本 + 商品级 `_ts_qv_cache_ver` 失效。
- 性能：保存/更新商品、库存变化时 bump 缓存版本；并发 miss 时短锁避免重复渲染。
- 性能：QV 内 `get_available_variations()` 只算一次；变体 JSON 去掉 image/description 等字段。
- 性能：主图改用 `woocommerce_thumbnail`；前端会话缓存 + 悬停 180ms 预取。

## 0.1.0 — 2026-05-25

- 新增主题原生 Quick View（`modules/quick-view`），配置使用 Kirki `theme_mod`。
- WC-Ajax 端点：`tailwindscore_get_product_quick_view`，HTML 缓存键 `ts_qv_v1_{product_id}`。
- 生效页面：商店/分类/标签归档、首页、单品页（含相关产品循环）。
- 可变品弹窗内使用方案 B：隐藏 `select` + 文字按钮（无变体图）；加购走 Store API。
- 循环可变品按钮改为 `data-ts-quick-view` 触发；简单品仍走 `loop-add-to-cart.ts`。
