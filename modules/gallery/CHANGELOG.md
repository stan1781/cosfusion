# 相册模块更新日志

## 0.3.0

- 变体相册改用 WooCommerce 11.1+ 官方 Variation Gallery（`_product_image_gallery` / `gallery_image_ids`）。
- 移除主题后台「Variation images」字段、保存逻辑与 `_variation_images` REST meta 注册。
- 前台仍通过 `available_variation.variation_images` 驱动 Swiper（主图 + 官方附加图）。
- 一次性迁移：管理员进入后台后将旧 `_variation_images` 写入 `set_gallery_image_ids`（不覆盖已有官方相册；旧 meta 保留不删）。
- 移除 Kirki「每个变体最大图片数」；张数由官方后台管理。
- 正式环境需 WooCommerce ≥ 11.1；低于此版本时前台可回退读旧 `_variation_images`。

## 0.2.1

- 单品页 `wp_head` 预加载父商品首张主图（`woocommerce_single` + srcset），缩短 LCP 发现延迟；占位图与变体图不 preload。

## 0.2.0

- 主图点击打开 PhotoSwipe 4 全屏灯箱（`full` 尺寸），库与样式首次点击再加载。
- 灯箱图片列表随 `currentImages` 同步（含变体 patch / 整组替换）。
- 单品页 footer 输出 Woo `.pswp` 标记；不恢复 `wc-single-product` / Woo PhotoSwipe 脚本。
- 修复 Vite 下 default-skin 精灵图 404，恢复左右切换按钮（单品页由 Woo CSS 提供 skin，Vite 仅保留 caption 样式）。
- 灯箱底部显示产品名称与已选变体属性（与变体选择器 UI 同步）；标题写入 `item.title` 避免 `caption--empty` 隐藏。

## 0.1.0

- 单品页 Swiper 图库：桌面端左侧缩略图，移动端分页圆点（dots）。
- 主图区域上一张 / 下一张导航按钮。
- 变体相册后台：`_variation_images` 元数据；前台通过 `available_variation` 注入 `variation_images`。
- 变体换图行为对齐 WooCommerce：父相册内激活对应张、仅替换首张、或有自定义变体相册时整组切换。
- 可变商品父级无图时，回退使用变体特色图；仍无图则使用 WooCommerce 占位图。
- PHP 服务端预渲染图片，避免 JS 未加载时空白。
- Kirki 自定义项：缩略图条数量上限、幻灯计数器、每变体最大图片数。
- 需安装 Kirki Framework 插件以使用 Customizer 字段；未安装时使用代码内默认值。
