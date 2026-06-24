# 产品详情 Tab 布局

产品详情页采用响应式 **描述 / Size Guide / 评论** 布局，替代 WooCommerce 默认 Tab 组件。

## 布局行为

| 视口 | 布局 |
|------|------|
| `< md`（< 48rem） | 手风琴：描述、Size Guide、评论可独立展开/折叠 |
| `≥ md` | 两列网格：左列描述、右列 Size Guide；评论单独占满下一行 |

断点使用 `ts-up(md)` / `ts-down(md)`，与 [设计令牌](../architecture/design-tokens.md) 一致。

## 显示条件

- **描述**：产品有长描述时显示
- **Size Guide**：产品配置了 Size Guide 且有内容时显示
- **评论**：产品开启评论时显示

## 实现文件

| 层 | 路径 |
|----|------|
| PHP | `inc/woocommerce/product-detail-tabs.php` |
| TypeScript | `src/ts/product/product-detail-tabs.ts` |
| SCSS | `src/scss/woocommerce/_product-detail-tabs.scss` |

加载关系：

- `functions.php` → `require` product-detail-tabs.php
- `src/ts/product/index.ts` → `initProductDetailTabs()`
- `src/scss/woo.scss` → `@use "woocommerce/product-detail-tabs"`

## 移除默认 WooCommerce Tabs

```php
function tailwindscore_remove_default_product_tabs(): void {
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
}
add_action( 'wp', 'tailwindscore_remove_default_product_tabs', 99 );
```

## Size Guide 集成（DRY）

内嵌 Size Guide **完全复用**弹窗模块的 HTML 结构与样式，避免重复维护。

### 数据与渲染

1. **数据**：`tailwindscore_size_guide_config()` / `window.tailwindscoreProduct.sizeGuide`
2. **渲染**：`product-detail-tabs.ts` 导入 `size-guide.ts` 的 `renderTableHTML()`
3. **HTML 结构**：与 `.ts-size-guide-dialog__inner` 内部一致（header + unit toggle + body）

### 复用的 CSS 类

| 类名 | 来源 |
|------|------|
| `.ts-size-guide-dialog__header`、`.ts-size-guide-dialog__controls` | `_product-variations.scss` |
| `.ts-size-guide-unit-toggle`、`.ts-size-guide-unit-btn` | 同上 |
| `.ts-size-guide-dialog__body` | 同上 |
| `.ts-size-guide-table` | `_tables.scss` |
| `.ts-size-guide-legacy` | 传统 HTML 内容 |

内嵌容器：`[data-ts-product-size-guide-embed]`。

### 单位切换

支持 cm / inch，逻辑与弹窗一致；**弹窗与内嵌各自维护单位状态**，不共享。

## 无障碍

- 手风琴按钮：`aria-expanded`
- 折叠内容：`hidden` 属性
- 装饰图标：`aria-hidden="true"`

## 调试

浏览器控制台：

```javascript
console.log(window.tailwindscoreProduct?.sizeGuide);
document.querySelector('.ts-product-detail-sections');
document.querySelector('[data-ts-product-size-guide-embed]');
```

修改样式或逻辑后：

```bash
npm run build
```

## 维护注意事项

1. 样式修改使用 `_variables.scss` token，禁止组件内硬编码数值
2. 修改 Size Guide 视觉时，在 `_product-variations.scss` 与 `_tables.scss` 统一修改，弹窗与内嵌同步
3. 测试 `md` 断点前后布局切换
4. 不影响现有 Size Guide `<dialog>` 弹窗功能

## 相关模块

- [Size Guide 模块](../modules/README.md#size-guide)
