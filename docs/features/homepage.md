# 首页

首页采用与 Starcore 一致的模型：**静态 Page + 短代码组合**，不再使用 `front-page.php` 或「首页展示」Kirki 全套区块。

Spoutini 户外站改造指南见 [spoutini-outdoor-storefront.md](../roadmap/spoutini-outdoor-storefront.md)。

## 运营用法

1. **设置 → 阅读**：静态首页选对应 Page
2. 页面模板选 **Full Width**（`page-templates/full-width.php`；新建 Page 默认全宽）
3. Spoutini 首页正文示例：

```text
[ts_home_split_hero]
[ts_category_tiles columns="4" limit="4"]
[ts_products_carousel carousel="0" columns_desktop="4" columns_wide="4" limit="4" orderby="featured" eyebrow="Featured products" title="Ready for your next adventure" view_all="View All Products"]
[block id="homepage-trust"]
```

分栏 Hero、分类磁贴与产品轮播短代码均自带主题 `.container` 限宽（Full Width 正文本身无容器）。产品轮播可用 `container="0"` 取消限宽；`eyebrow` / `view_all` 对齐 Featured 区块 mockup，见 [产品轮播](product-carousel.md)。

其它落地页 / 促销仍可用图轮播 Hero：

```text
[ts_home_hero]
[ts_products_carousel limit="8" orderby="date"]
```

4. **外观 → 自定义 → 首页 → 分栏 Hero**：配置标题、副文、CTA、右侧图
5. **外观 → 自定义 → 首页 → Hero Banner**：全图 Swiper 幻灯片（`[ts_home_hero]`）
6. **外观 → 内容区块**：维护 Trust 等可复用 HTML/CSS（如 slug `homepage-trust`）
7. 单页一次性 HTML（含 `<style>`）：可直接贴到 Page 正文，主题会把样式抽到 Singular CSS meta（`_ts_singular_css`，兼容旧 `_ts_page_css`）

## Customizer 范围

- **分栏 Hero**（`home_split_hero_*`）
- **Hero Banner**（`home_hero_*` theme_mod）

Occasion / Trending / Shop by Color / Trust 等旧 Kirki 字段已移除，现网 `homepage_*` 会在主题加载时一次性清除。Trust 请用 Content Block，不要恢复旧字段。

## 相关能力

| 能力 | 文档 |
|------|------|
| 分栏 Hero | [home-split-hero.md](home-split-hero.md) |
| Hero 轮播 | [home-hero.md](home-hero.md) |
| 内容区块 | [content-blocks.md](../modules/content-blocks.md) |
| 产品轮播 | [product-carousel.md](product-carousel.md) |
| Spoutini 改造指南 | [spoutini-outdoor-storefront.md](../roadmap/spoutini-outdoor-storefront.md) |

## 相关文件

- `page-templates/full-width.php`
- `inc/page-templates.php`
- `inc/home/split-hero.php`
- `inc/home/category-tiles.php`
- `inc/home/hero-carousel.php`
- `inc/config/kirki/home.php`
- `modules/content-blocks/`
- `inc/woocommerce/product-carousel.php`
