# Section title

共享区块标题：衬线（`--ts-font-heading`）+ 居中细线；**无** Starcore 餐饮纹样 SVG。

## API

```php
tailwindscore_render_section_title(
	$title,
	array(
		'id'    => 'optional-id',
		'tag'   => 'h2', // h1–h6
		'class' => 'section-title--inline',
	)
);
```

| 类名 | 用途 |
|------|------|
| `.section-title` | 居中标题 + 下方短细线 |
| `.section-title--inline` | 轮播头与导航同行；左对齐、无细线 |

## 接入

- `[ts_products_carousel]` / 轮播壳默认 `title_class` → `section-title--inline`
- Related / 购物车推荐 / 404 人气轮播同用
- Woo 原生 `.related.products > h2` 等亦套用居中标题样式

不支持产品轮播 banner / view_all（见 [b2c-conversion.md](../roadmap/b2c-conversion.md)）。
