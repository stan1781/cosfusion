# 主题短代码速查

在 **文章 / 页面 / 内容区块** 编辑页侧栏提供可复制的运营常用短代码示例，避免运营翻文档。

## 挂载

| 项 | 值 |
|----|-----|
| Meta Box | `ts-shortcode-help`，标题「主题短代码」 |
| 屏幕 | `post`、`page`、`ts_block` |
| 位置 | 侧栏 `side` |
| 实现 | `inc/admin/shortcode-help.php`（`bootstrap.php` 仅 `is_admin()` 加载） |

与 `ts_block` 现有侧栏「短代码」（本条 `[block id="…"]`）并存，标题区分。

## 收录

| 短代码 | 侧栏内容 |
|--------|----------|
| `[ts_products_carousel]` | 示例 + 常用属性简表 |
| `[ts_recently_viewed]` | 示例 + 常用属性简表 |
| `[ts_home_split_hero]` / `[ts_category_tiles]` | 分栏 Hero / 分类磁贴 |
| `[ts_home_hero]` | 仅示例 + 用途（幻灯片 / 布局在 Customizer；可选覆盖属性不进运营速查，见 [首页 Hero](home-hero.md)） |

不含 `[block]`（内容区块编辑页已有本条短代码复制）。

## 维护

改短代码属性或默认示例时，同步更新 `tailwindscore_shortcode_help_catalog()` 与对应功能文档。

`[ts_products_carousel]` / `[ts_recently_viewed]` 的 `card` / `carousel` 说明以 [产品轮播](product-carousel.md) 为准；侧栏速查表需与 `shortcode_atts` 同步。

## 相关文档

- [产品轮播](product-carousel.md)
- [Recently viewed](recently-viewed.md)
- [首页 Hero](home-hero.md)
- [内容区块](../modules/content-blocks.md)
