# Review Images 模块变更记录

## 0.1.0 — 2026-05-23

- 从 `wc-enhanced-tools` 迁入评论图片上传、展示、买家秀 Showcase、后台 metabox/批量操作。
- 配置改用 Kirki `product_review_images_*` theme_mod。
- 评论图片上传默认 WebP（`wp_enhanced_convert_image_to_webp`，context=review）。
- 前台 TS：上传 + `<dialog>` + Swiper lightbox。
- 保留 meta 键与 AJAX action 契约。
