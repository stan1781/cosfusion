# 相册模块更新日志

## 0.1.0

- 单品页 Swiper 图库：桌面端左侧缩略图，移动端分页圆点（dots）。
- 主图区域上一张 / 下一张导航按钮。
- 变体相册后台：`_variation_images` 元数据；前台通过 `available_variation` 注入 `variation_images`。
- 变体换图行为对齐 WooCommerce：父相册内激活对应张、仅替换首张、或有自定义变体相册时整组切换。
- 可变商品父级无图时，回退使用变体特色图；仍无图则使用 WooCommerce 占位图。
- PHP 服务端预渲染图片，避免 JS 未加载时空白。
- Kirki 自定义项：缩略图条数量上限、幻灯计数器、每变体最大图片数。
- 需安装 Kirki Framework 插件以使用 Customizer 字段；未安装时使用代码内默认值。
