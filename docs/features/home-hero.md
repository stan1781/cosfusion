# 首页 Hero 轮播

静态首页 Page 通过短代码 `[ts_home_hero]` 展示可配置 Swiper Hero；幻灯片与布局由 Kirki Customizer 管理。

## 实现

| 能力 | 位置 |
|------|------|
| Kirki 字段 | `inc/config/kirki/home.php`（panel `tailwindscore_home` → section Hero Banner） |
| 解析 / 渲染 / 短代码 | `inc/home/hero-carousel.php` |
| 壳层模板 | `template-parts/home/hero-carousel.php` |
| Swiper 初始化 | `src/ts/home/hero-carousel.ts`（`app.ts` 全局 `initHeroCarousels()`） |
| 样式 | `src/scss/components/_hero-carousel.scss` |

## 运营用法

1. **设置 → 阅读**：静态首页选对应 Page  
2. 页面模板：**Full Width**  
3. 正文放入：`[ts_home_hero]`  
4. **外观 → 自定义 → 首页 → Hero Banner**：上传幻灯片并调整布局  

临时 peek（不改 Customizer）：

```text
[ts_home_hero columns_desktop="2.5" gap="16"]
```

## Kirki 字段

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `home_hero_enable` | toggle | true | 总开关 |
| `home_hero_autoplay` | toggle | true | 自动播放 |
| `home_hero_autoplay_delay` | slider 2000–10000 | 5000 | 间隔 ms |
| `home_hero_columns_mobile` | select | `1` | 移动端 `slidesPerView`（1–4，0.5 步进） |
| `home_hero_columns_desktop` | select | `1` | `md+`（48rem）`slidesPerView` |
| `home_hero_gap` | slider 0–48 | 0 | slide 间距 px |
| `home_hero_slides` | repeater | `[]` | 幻灯片 |

### Repeater 子字段

| 字段 | 说明 |
|------|------|
| `image` | 桌面图（必填；无图行前台略过） |
| `image_mobile` | 手机图（可选；`< 48rem` 用 `<picture>`） |
| `title` | 可选标题（`screen-reader-text`） |
| `link_url` | 点击跳转 |
| `link_target` | `_self` / `_blank` |
| `alt` | 图片替代文字；空则回退 `title` |

## 短代码参数（可选覆盖）

| 参数 | 说明 |
|------|------|
| `columns_mobile` | 覆盖移动端可见张数 |
| `columns_desktop` | 覆盖桌面可见张数 |
| `gap` | 覆盖间距 |
| `autoplay` | `1` / `true` / `yes` / `on` 视为开启 |
| `autoplay_delay` | 覆盖间隔 ms |

## DOM

```html
<section class="hero-carousel" data-ts-hero-carousel
  data-ts-columns-mobile="1" data-ts-columns-desktop="1"
  data-ts-gap="0" data-ts-autoplay="1" data-ts-autoplay-delay="5000">
  …
</section>
```

左右箭头叠在图上；单张时隐藏。使用 Swiper `rewind` + `bindSwiperWrapNavigation`（不用 `loop`）。

## 布局模式

- **一屏**（默认 `columns_*=1`，`gap=0`）：全宽单图  
- **peek**（如 `2.5`）：同时露出邻张；建议 `gap` 8–16  

比例由 CSS 变量控制：`--ts-hero-carousel-aspect-mobile`（默认 `4 / 3`）、`--ts-hero-carousel-aspect-desktop`（默认 `21 / 9`）。

## PHP API

```php
tailwindscore_render_hero_carousel( $slides, array(
	'columns_mobile'  => 1.5,
	'columns_desktop' => 2.5,
	'gap'             => 16,
	'autoplay'        => true,
	'autoplay_delay'  => 5000,
) );
```

## 相关文档

- [首页](homepage.md)
- [内容区块](../modules/content-blocks.md)
- [产品轮播](product-carousel.md)
