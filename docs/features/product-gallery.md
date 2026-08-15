# 单品商品画廊

单品页主图 + 桌面竖向缩略图条，基于 Swiper；点击主图打开 PhotoSwipe。模板见 `template-parts/woocommerce/product-gallery.php`，逻辑在 `src/ts/product/gallery.ts`，样式在 `src/scss/woocommerce/_product.scss`。模块配置经 `modules/gallery/`（Kirki + 数据组装）。

## 布局

- 桌面（`lg+`）：左侧竖向缩略图列 + 右侧主图；主图由 `aspect-ratio` 定高，缩略图列用 `height: 0; min-height: 100%` **不撑高** grid 行，高度跟随主图列。
- 小于 `lg`：仅主图 + 底部 pagination；无竖向缩略图。
- 多图时显示主图左右导航与（可选）角标计数器。

## 桌面缩略图尺寸（均分）

缩略图为正方形，边长写入 gallery 根上的 `--ts-gallery-thumb-size`（CSS 默认回退 `5rem`）。均分基准高度 `H` 取自主图 Swiper（`[data-ts-gallery-main]`），非缩略图固有高度。张数 `n`、间距 `gap = 8`、Customizer `thumbLimit`（默认 6）、列宽 `colW`。

1. `fitSize = (H - (n - 1) * gap) / n`
2. `minSize = (H - (thumbLimit - 1) * gap) / thumbLimit`
3. `size = clamp(fitSize, minSize, colW)`

含义：

- 张数少：尺寸增大至列宽上限；正方形受限时底部可留白，条带顶对齐。
- 张数适中：均分铺满列高。
- 张数多：使用 `minSize`；激活项尽量落在列垂直中线。translate 夹紧后：开头顶对齐完整显示前 `thumbLimit` 张（如 7 张时完整显示前 6 张），末尾底对齐；中间则真正居中，不会首尾各裁半张。
- 动态重建缩略图 slide 时使用 thumbnail URL（非主图大图）。

主图切换（箭头 / 键盘 / 点击缩略图）时 thumbs 跟随居中（带边界）。窗口 resize 与断点切换会重算尺寸。

Customizer → 商品画廊 →「缩略图均分下限张数」即 `product_gallery_thumb_limit`。

## 变体

`variations_form` 变更时按匹配结果：`restoreParent` / `activateSlide` / `patchFirstSlide` / `replaceAllSlides`（见 `gallery-variations.ts`）。重建后重新初始化 Swiper 并重算缩略图尺寸。

## 图片与 LCP

- SSR 首张主图：`loading="eager"` + `fetchpriority="high"` + `data-no-lazy="1"`（`woocommerce_single` + srcset）。
- 单品页 `wp_head`：`tailwindscore_gallery_preload_lcp_in_head()` 对父商品第一张输出 `<link rel="preload" as="image">`（`imagesrcset` / `imagesizes`）；占位图与变体图不 preload。
- `imagesizes`：`(max-width: 63.999rem) 100vw, min(50vw, 36rem)`（对齐 `lg`：手机全宽、桌面半栏+缩略图）。

详见 [前台性能](../architecture/performance.md)。

## 不做

- 末张 `+N` 遮罩、缩略图专用上下箭头、非正方形缩略图。
- 变体图 `<head>` preload（发生在 JS 之后，预载浪费带宽）。
