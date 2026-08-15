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
2. 页面模板：**Full Width**（默认亦可；Hero 会以 CSS 突破 `.container` 全视口宽）  
3. 正文放入：`[ts_home_hero]`  
4. **外观 → 自定义 → 首页 → Hero Banner**：上传幻灯片并调整布局  

临时试布局（不改 Customizer）：

```text
[ts_home_hero layout="peek" columns_desktop="2.5" gap="16"]
```

## 全宽

`.hero-carousel` 使用 `width: 100vw` + `margin-inline: calc(50% - 50vw)` 突破父级 `.container`，不依赖页面模板。含 Hero 的 `.site-main` 设 `overflow-x: clip`，避免 `100vw` 引发横向滚动。父级若另设 `overflow: hidden` 会裁切 peek，应避免。

`full` / `peek` 均用 Swiper `rewind` + `bindSwiperWrapNavigation`（不用 `loop`）。`peek` 另开 `centeredSlides`，激活卡居中、两侧露出邻张。

## Kirki 字段

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `home_hero_enable` | toggle | true | 总开关 |
| `home_hero_autoplay` | toggle | true | 自动播放 |
| `home_hero_autoplay_delay` | slider 2000–10000 | 5000 | 间隔 ms |
| `home_hero_layout` | select | `full` | 布局：`full` / `peek`（居中露出邻张；`centered` 会回退为 `full`） |
| `home_hero_columns_mobile` | select | `1` | 移动端 `slidesPerView`（1–4，0.5 步进；仅 peek） |
| `home_hero_columns_desktop` | select | `1` | `md+`（48rem）`slidesPerView`（仅 peek） |
| `home_hero_gap` | slider 0–48 | 0 | slide 间距 px |
| `home_hero_slides` | repeater | `[]` | 幻灯片 |

未存 `home_hero_layout` 且已存 `columns_mobile > 1` 或 `columns_desktop > 1` 时，读取时视为 `peek`（兼容旧配置）。

### Repeater 子字段

| 字段 | 说明 |
|------|------|
| `image` | 桌面图（必填；无图行前台略过） |
| `image_mobile` | 手机图（可选；`< 48rem` 用 `<picture>`） |
| `title` | 可选标题；有链接时作 `aria-label`（空则附件 alt /「Promotional banner」）；链接内图片 `alt=""` |
| `link_url` | 点击跳转（同一窗口打开） |

已移除 `link_target` / `alt`；旧 theme_mod 中的键忽略。

## 短代码参数（可选覆盖）

| 参数 | 说明 |
|------|------|
| `layout` | `full` / `peek`（其它值含 `centered` → `full`） |
| `columns_mobile` | 覆盖移动端可见张数 |
| `columns_desktop` | 覆盖桌面可见张数 |
| `gap` | 覆盖间距 |
| `autoplay` | `1` / `true` / `yes` / `on` 视为开启 |
| `autoplay_delay` | 覆盖间隔 ms |

## DOM

```html
<section class="hero-carousel hero-carousel--full" data-ts-hero-carousel
  data-ts-layout="full"
  data-ts-columns-mobile="1" data-ts-columns-desktop="1"
  data-ts-gap="0" data-ts-autoplay="1" data-ts-autoplay-delay="5000">
  …
</section>
```

左右箭头叠在图上；单张时隐藏。

## 布局模式

| 模式 | 行为 | 图片 |
|------|------|------|
| `full` | 一屏：`slidesPerView: 1` | 固定 `--ts-hero-carousel-aspect-mobile`（`4 / 3`）/ `--ts-hero-carousel-aspect-desktop`（`21 / 9`）+ `object-fit: cover` |
| `peek` | 居中多张：`slidesPerView` = columns + `centeredSlides`；两侧露出邻张（首尾仍贴边，无无限循环） | 附件元数据写 `--ts-hero-ar` / `--ts-hero-ar-mobile`（兜底 `1200/504`、`768/600`）+ `object-fit: contain` |

## 图片与性能

- 幻灯片解析保留附件 ID（`image_id` / `image_mobile_id`）；有 ID 时用 `wp_get_attachment_image( …, 'large' )` 输出 **srcset / sizes**，避免直出 `full` PNG URL。
- 首张：`loading="eager"` + `fetchpriority="high"`；其余 `loading="lazy"`。
- 首页 / 含 `[ts_home_hero]` 的 singular：`wp_head` 对首张输出桌面/手机分流 **preload**（`media="(width < 48rem)"` / `>= 48rem`）。
- 禁止 WP 对 `.hero-carousel__img` 注入 `sizes="auto"`（`wp_img_tag_add_auto_sizes`）。
- 有 `link_url` 时链接带 `aria-label`（title → 附件 alt →「Promotional banner」），图 `alt=""`。

## PHP API

```php
tailwindscore_render_hero_carousel( $slides, array(
	'layout'          => 'peek',
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
