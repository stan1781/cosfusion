# 产品详情 Tab 布局

产品详情页采用响应式 **描述 / Size Guide / 评论** 布局，替代 WooCommerce 默认 Tab 组件。

## 布局行为

| 视口 | 布局 |
|------|------|
| `< md`（< 48rem） | 手风琴：描述、Size Guide（及 filter 扩展 Tab）、评论可独立展开/折叠 |
| `≥ md` | 下划线滑动 Tab（描述 / Size Guide 等）；评论单独占满下一行（不进 Tab） |

断点使用 `ts-up(md)` / `ts-down(md)`，与 [设计令牌](../architecture/design-tokens.md) 一致。

## 显示条件

- **描述**：产品有长描述时显示
- **Size Guide**：产品含尺码类属性，且配置了 Size Guide 并有可渲染内容（表行或 legacy HTML）时显示；无 size 属性不显示该 Tab
- **评论**：产品开启评论时显示（桌面独立行；移动端手风琴末项）
- **自定义 Tab**：经 `tailwindscore_product_detail_tabs` filter 注入（见下）

## 自定义 Tab（filter）

桌面 Tab 与移动端手风琴共享同一列表。评论**不**进入该 filter。

```php
add_filter( 'tailwindscore_product_detail_tabs', function ( array $tabs, WC_Product $product ): array {
	$tabs['shipping'] = array(
		'title'    => __( 'Shipping & Returns', 'tailwindscore' ),
		'priority' => 30,
		'callback' => static function (): void {
			echo '<p>…</p>';
		},
	);
	return $tabs;
}, 10, 2 );
```

每项字段：`title`（string）、`priority`（int，默认 10）、`callback`（callable，输出 HTML）。内置 `description` priority `10`，`size_guide` priority `20`。

## 实现文件

| 层 | 路径 |
|----|------|
| PHP | `inc/woocommerce/product-tabs.php`（含 detail sections 布局） |
| TypeScript | `src/ts/product/product-detail-tabs.ts` |
| SCSS | `src/scss/woocommerce/_product-detail-tabs.scss` |

加载经 `inc/bootstrap.php`。

前台联动：

- `src/ts/product/index.ts` → `initProductDetailTabs()`
- `src/scss/woo.scss` → `@use "woocommerce/product-detail-tabs"`

## 桌面下划线动画

- 指示器：`.product-detail-tabs__indicator`，相对 tablist 用 `getBoundingClientRect` 测距，以 `transform: translate3d` + `width` 滑动
- 缓动：`cubic-bezier(0.65, 0, 0.35, 1)`，约 `0.4s`；动画期间忽略 ResizeObserver 同步，避免打断
- 激活标签色：`--ts-primary`；未激活：`--ts-text-muted`；`color` 同步过渡
- `prefers-reduced-motion: reduce`：指示器瞬时对齐，无过渡
- `ResizeObserver`：窗口/布局变化时无动画重新对齐
- 键盘：左右方向键 / Home / End 在 tablist 内切换

## 描述限高与展开

- 默认约 8 行高（`font-size-sm × 1.5 × 8`）；超出时显示 **Show more** / **Show less**
- 折叠且可展开时底部 `--ts-surface` 白色渐变遮罩
- 桌面 Tab panel 与移动端手风琴共用同一 markup（`data-ts-product-description`）

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
3. **HTML 结构**：与 `.size-guide-dialog__inner` 内部一致（header + unit toggle + body）

### 复用的 CSS 类

| 类名 | 来源 |
|------|------|
| `.size-guide-dialog__header`、`.size-guide-dialog__controls` | `_product-variations.scss` |
| `.size-guide-unit-toggle`、`.size-guide-unit-btn` | 同上 |
| `.size-guide-dialog__body` | 同上 |
| `.size-guide-table` / `.size-guide-legacy table` | `_shell.scss`：compact（对齐 BS `.table-sm` padding + `--ts-font-size-sm`） |
| `.size-guide-legacy` | 传统 HTML 内容 |

内嵌容器：`[data-ts-product-size-guide-embed]`。

### 单位切换

支持 cm / inch，逻辑与弹窗一致；**弹窗与内嵌各自维护单位状态**，不共享。

## 摘要区评分链接

主题覆盖 `woocommerce/single-product/rating.php`：有评分且评论开放时，`.woocommerce-review-link` 文案为 `(N review)` / `(N reviews)`（无 customer），锚点仍为 `#reviews`。

## 评价表单弹窗

桌面与手风琴各自调用 `comments_template()`，但 **Write a review** 弹窗只渲染一次，挂在 `.product-detail-sections` 末尾（两套布局之外）。打开时 JS 再挂到 `document.body`，避免小屏桌面行 `display: none` 或手风琴 `overflow: hidden` 裁切固定层。

摘要卡按钮与无买家秀时的 fallback 链接均经 `tailwindscore_get_review_form_open_label()`，固定为 **Write a review**（不嵌入产品名，避免长标题撑破 CTA）。

| 层 | 路径 |
|----|------|
| 模板 | `template-parts/woocommerce/review-form-modal.php`（由 `inc/woocommerce/product-tabs.php` 引入） |
| TS | `src/ts/product/review-form-modal.ts` |
| SCSS | `src/scss/components/_review-form-modal.scss` |

## 无障碍

- 桌面：`role="tablist"` / `tab` / `tabpanel`；`aria-selected`、`aria-controls`、`hidden`
- 手风琴按钮：`aria-expanded`
- 折叠内容：`hidden` 属性
- 装饰图标：`aria-hidden="true"`

## 调试

浏览器控制台：

```javascript
console.log(window.tailwindscoreProduct?.sizeGuide);
document.querySelector('.product-detail-sections');
document.querySelector('.product-detail-tabs');
document.querySelector('[data-ts-product-size-guide-embed]');
```

修改样式或逻辑后：

```bash
npm run build
```

## 维护注意事项

1. 样式修改使用 `_variables.scss` token，禁止组件内硬编码数值
2. 修改 Size Guide 视觉时，在 `_product-variations.scss` 与 `_shell.scss`（表格段）统一修改，弹窗与内嵌同步
3. 测试 `md` 断点前后布局切换
4. 不影响现有 Size Guide `<dialog>` 弹窗功能
5. 扩展 Tab 只用 `tailwindscore_product_detail_tabs`，勿把评论塞进该 filter

## 相关模块

- [Size Guide 模块](../modules/README.md#size-guide)
