# Related products 模块变更记录

## 0.1.0 — 2026-05-22

- 从 `wc-enhanced-tools` 的 `Related_Products_Manager` 迁入主题 `modules/related-products`。
- Kirki `theme_mod`：`product_related_*`（不读取插件 `wc_enhanced_tools_related_products`）。
- 保留：hybrid / 标签 / 分类 / 简单算法、最大数量、缺货排除、随机排序、子分类不足时父分类补齐、排除 upsell/cross-sell、transient 缓存。
- 新增：标题关键词匹配阶段（分类/标签仍不足时，按标题 token 命中数排序补足，再随机 fallback）。
- 缓存键前缀 `ts_rp_`，含引擎版本指纹。
