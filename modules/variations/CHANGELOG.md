# Variations 模块变更记录

## 0.1.7

- 单品 / Quick View / 购物车编辑：表单统一 `data-ts-require-variation-selection`；已选 swatch 再点不取消，只能切换其他值；无选中时自动选第一项可用值。

## 0.1.6

- 移除主加购按钮价格前缀；选齐变体后主 / sticky ATC 均为默认「Add to cart」。
- 删除仅为 ATC 价格服务的 `ensure_variation_price_html` 与 `getVariationPriceText`。

## 0.1.5

- 未选齐变体时主加购 / sticky ATC 灰显并显示 `Select {Attribute}`；点击滚到未选属性，无 toast / alert。
- 选齐后主加购文案前缀变体价格；sticky ATC 不加价格。
- i18n：`selectAttribute`（`Select %s`）；`.variation-row` 增加 `data-ts-attribute-label`。
- 修复：同价变体时 Woo 清空 `price_html` 导致主 ATC 无价格；`woocommerce_available_variation` 强制补全，并回退读取摘要区价格。

## 0.1.4

- 文本/尺码 `text-selector` 同步 `shape-square` / `shape-round`；选中态改为加粗边框（浅色底），对齐尺码圆钮参考样式。

## 0.1.3

- Grid「显示文案」Customizer 默认关闭（`variation_grid_show_text`）。
- 圆形仅作用于 grid：`li` 与 `.variation-media` 同步 `border-radius: 50%`。
- List 模式固定方角缩略图 + 属性名称（忽略圆形设置，隐藏描述行）。

## 0.1.2

- 修复：表单 `data-tailwindscore-variations-config` SSR 配置，不依赖 `window.tailwindscoreProduct` 也能初始化。
- 修复：`variations.ts` 静态打入 `app` bundle；点击用 capture 阶段；`findAttributeSelect()` 兼容 `attribute_pa_*` 命名。
- 修复：`wc_get_template` 优先级 20 强制使用主题 `variable.php`（避免插件仍激活时抢走模板且无 JS）。

## 0.1.1

- 修复：显式 enqueue `jquery` + `wc-add-to-cart-variation`；`variations.ts` 等待 jQuery 后再绑定 WC 事件，点击/grid 切换不再因 jQuery 未就绪而静默失败。
- 修复：从隐藏 `select` 同步 `li.disabled` 状态；`variation_selector_enabled` 兼容 Kirki 布尔值。

## 0.1.0

- 从 `wc-enhanced-tools` 迁入自定义变体选择器（不含 Size Guide）。
- Kirki：`variation_selector_enabled`、颜色/尺寸属性名、grid/list、形状、grid 文案开关。
- 主题模板 `woocommerce/single-product/add-to-cart/variable.php`；关闭时回退 Woo 核心模板。
- 产品 meta：`_wc_variation_selector_disabled_image_attributes`、`_wc_variation_selector_color_default_mode`。
- 前台 `src/ts/product/variations.ts`；配置经 `window.tailwindscoreProduct.variations`。
- 预留 hook：`tailwindscore_after_size_attribute_label`（供未来 Size Guide 模块）。
