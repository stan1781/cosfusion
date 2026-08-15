# 404 页面

WordPress 标准 `404.php` 模板，三段式布局：错误 Hero、快捷导航卡片、人气商品轮播。文案与开关由 Kirki Customizer 配置。前台默认可视文案为英文。

## 模板层级

```
404.php
  └── template-parts/site/not-found.php
```

| 文件 | 职责 |
|------|------|
| `404.php` | 模板入口 |
| `template-parts/site/not-found.php` | 三段 markup |
| `inc/not-found.php` | 读取 `tailwindscore_mod()`、产品查询、Kirki 登记与轮播渲染 |
| `src/scss/components/_not-found.scss` | 页面样式（经 `app.scss` 引入） |

## Customizer

**外观 → 定制 → 404 页面**（根级 Section `tailwindscore_not_found`）

| 字段 | 类型 | 默认 | 说明 |
|------|------|------|------|
| `not_found_illustration` | image | （空） | Hero 插图；空则 `assets/img/not-found.png`（文件存在时） |
| `not_found_title` | text | `Sorry, we can't find that page` | Hero `<h1>` |
| `not_found_description` | textarea | 见 PHP | Hero 说明 |
| `not_found_home_button_label` | text | `Back to homepage` | 主 CTA |
| `not_found_categories_enable` | toggle | `true` | 是否显示快捷导航 |
| `not_found_categories_title` | text | `Looking for something?` | 导航区块标题 |
| `not_found_categories` | repeater | 见 PHP | 卡片：`title`、`subtitle`、`url`、`icon` |
| `not_found_products_enable` | toggle | `true` | 是否显示人气商品轮播 |
| `not_found_products_title` | text | `Popular right now` | 产品区块标题 |
| `not_found_products_limit` | slider 1–12 | `8` | 展示数量 |
| `not_found_products_orderby` | select | `popularity` | 排序 |
| `not_found_products_category_id` | number | `0` | `product_cat` term ID；`0` 不限分类 |

### 插图回退

`not_found_illustration` 留空且主题文件不存在时，不输出 `<img>`（Hero 仅保留文案与 CTA）。

### 快捷导航卡片

默认 4 行时装站英文文案（New Arrivals / Best Sellers / Dresses / Sale），URL 默认 Shop 页。空 URL 或空标题的行会被过滤。

过滤器：`tailwindscore_not_found_categories`。

### 人气商品

- 复用 `tailwindscore_render_products_carousel_shell()` 与全局 Swiper 初始化
- 可变品按钮走 Quick View（Customizer → 商店/归档 → 快速查看 → 生效页面含「404 页面」）
- 移动 2.5 列 / 桌面 4 列
- WooCommerce 未启用或无可视产品时不输出该段
- 过滤器：`tailwindscore_not_found_products_limit`、`tailwindscore_not_found_products_category_id`、`tailwindscore_not_found_product_ids` 等

## 资源

- 页面样式：`app.scss`（含 `_not-found.scss`）
- 产品卡 / loop：经 `tailwindscore_is_woo_page()` 在 `is_404()` 时加载 `woo.scss`（与首页轮播一致）

## 刻意不做

- WhatsApp / 客服协助条
- 全站搜索表单
- 独立 Vite 入口

## 相关文档

- [产品轮播](product-carousel.md)
- [命名与语言约定](../conventions.md)
