# 首页

首页采用与 Starcore 一致的模型：**静态 Page + 短代码组合**，不再使用 `front-page.php` 或「首页展示」Kirki 全套区块。

## 运营用法

1. **设置 → 阅读**：静态首页选对应 Page  
2. 页面模板：**Full Width**（`page-templates/full-width.php`；新建 Page 默认全宽）  
3. 正文示例：

```text
[ts_home_hero]
[block id="homepage-featured"]
[ts_products_carousel limit="8" orderby="date"]
```

4. **外观 → 自定义 → 首页 → Hero Banner**：上传幻灯片并调整布局  
5. **外观 → 内容区块**：维护可复用 HTML/CSS 片段（`[block]`）  
6. 单页一次性 HTML（含 `<style>`）：可直接贴在 Page 正文，主题会把样式抽到「页面 CSS」meta（`_ts_page_css`）

## Customizer 范围

仅 **Hero Banner**（`home_hero_*` theme_mod）。Occasion / Trending / Shop by Color / Trust 等旧 Kirki 字段已移除，现网 `homepage_*` 会在主题加载时一次性清除。

## 相关能力

| 能力 | 文档 |
|------|------|
| Hero 轮播 | [home-hero.md](home-hero.md) |
| 内容区块 | [content-blocks.md](../modules/content-blocks.md) |
| 产品轮播 | [product-carousel.md](product-carousel.md) |

## 相关文件

- `page-templates/full-width.php`
- `inc/page-templates.php`
- `inc/home/hero-carousel.php`
- `inc/config/kirki/home.php`
- `modules/content-blocks/`
- `inc/woocommerce/product-carousel.php`
