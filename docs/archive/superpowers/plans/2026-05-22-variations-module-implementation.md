# Variations 模块实施计划

**规范**：[2026-05-22-wc-enhanced-tools-migration-design.md](../specs/2026-05-22-wc-enhanced-tools-migration-design.md)  
**版本**：v0.1.0  
**范围**：仅 `Variation_Selector`（不含 Size Guide、Quick View）

---

## 目标

在 `modules/variations` 提供自定义变体选择器（颜色图/grid/list、文本尺码等），替代插件 `wc_get_template` + `variation-selector.js`。

---

## 文件清单

| 文件 | 说明 |
|------|------|
| `modules/variations/module.php` | Bootstrap |
| `modules/variations/kirki.php` | Customizer 字段 |
| `modules/variations/includes/class-variation-settings.php` | 设置、config、模板 helpers |
| `modules/variations/includes/class-variation-admin.php` | 产品 attributes 页 meta |
| `modules/variations/CHANGELOG.md` | 中文变更记录 |
| `woocommerce/single-product/add-to-cart/variable.php` | 主题模板（无 Size Guide） |
| `src/ts/product/variations.ts` | 前台交互 |
| `src/scss/woocommerce/_product-variations.scss` | 样式 |
| `functions.php` | require module |
| `inc/enqueue.php` | `tailwindscore_product_page_config()` |

---

## 插件对照

| 插件 | 主题 |
|------|------|
| `get_option( 'wc_variation_selector_options' )` | `tailwindscore_mod( 'variation_*' )` |
| `Variation_Selector::is_color_attribute()` | `tailwindscore_is_color_variation_attribute()` |
| `variation-selector.js` | `variations.ts` |
| `wcVariationSelector` | `window.tailwindscoreProduct.variations` |
| 插件 `templates/variable.php` | 主题 `woocommerce/.../variable.php` |

---

## 不包含

- `Product_Size_Guide`、尺码指南按钮与 modal  
- Quick View 归档页 enqueue  
- `wc_enhanced_tools_*` option 读取  

---

## 测试用例

1. Customizer 关闭「启用变体选择器」→ 显示 Woo 默认下拉  
2. 颜色属性：grid ↔ list 切换、方/圆、grid 下是否显示文案  
3. 后台禁用某 color 属性的图像 → 该属性变为 text-selector  
4. 产品级颜色默认模式覆盖全局  
5. 选择变体后 summary 价格更新；图库随变体变化  
6. `wc-enhanced-tools` inactive，控制台无 `wcVariationSelector`  

---

## 完成标准

- [x] Spec 登记表 variations → ✅  
- [x] `npm run typecheck` / `npm run build` 通过  
- [x] README 链接迁移规范  
