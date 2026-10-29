# 分类磁贴

短代码 `[ts_category_tiles]` 从 WooCommerce `product_cat` 动态输出磁贴网格（Spoutini 首页四宫格等）。

## 实现

| 能力 | 位置 |
|------|------|
| 短代码 / 取数 | `inc/home/category-tiles.php` |
| 模板 | `template-parts/home/category-tiles.php` |
| 样式 | `src/scss/components/_category-tiles.scss` |

## 用法

```text
[ts_category_tiles columns="4" limit="4"]
[ts_category_tiles ids="12,34,56,78" columns="4"]
[ts_category_tiles slugs="camping,hiking,travel,water-sports"]
```

| 属性 | 默认 | 说明 |
|------|------|------|
| `ids` / `slugs` | 空 | 逗号分隔；都空则按 `menu_order` 取前 `limit` 个 |
| `columns` | `4` | 桌面列数（1–6）；手机固定 2 列 |
| `limit` | `4` | 最大数量（1–12） |
| `title` | 空 | 可选区块标题 |

自动略过 `uncategorized`。无缩略图时使用渐变占位，不输出无效图片地址。

## 相关文档

- [首页](homepage.md)
- [Spoutini 改造指南](../roadmap/spoutini-outdoor-storefront.md)
