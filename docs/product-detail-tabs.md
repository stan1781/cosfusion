# 产品详情页 Tab 布局

## 概述

产品详情页现在采用响应式布局：
- **PC端（≥768px）**：两列网格布局，描述和Size Guide并排显示，评论在下方单独一行
- **移动端（<768px）**：手风琴布局，可展开/折叠描述、Size Guide和评论

## 功能特点

### PC端布局
- 第一行：左列显示产品描述，右列显示Size Guide表格
- 第二行：完整宽度显示评论组件
- 所有区块都有统一的背景色和圆角样式

### 移动端布局
- 手风琴形式的可折叠区块
- 每个区块（描述、Size Guide、评论）都可独立展开/折叠
- 点击标题切换展开状态，带有旋转动画的图标指示器

### Size Guide 集成
- 自动读取产品的Size Guide配置
- **完全复用弹窗的HTML结构和样式**（`.ts-size-guide-dialog__inner`）
- 包含单位切换器（cm/inch），与弹窗行为一致
- 支持表格数据和传统HTML内容
- 无需单独维护样式，所有样式继承自`_product-variations.scss`和`_tables.scss`

## 实现文件

### PHP
- `inc/woocommerce/product-detail-tabs.php` - 主要模板和逻辑
  - 移除默认WooCommerce tabs
  - 渲染自定义两列布局和手风琴布局
  - 与Size Guide模块集成

### TypeScript
- `src/ts/product/product-detail-tabs.ts` - 前端交互逻辑
  - 手风琴展开/折叠功能
  - **复用`size-guide.ts`的渲染函数**
  - 生成与弹窗相同的HTML结构（header + body + unit toggle）
  - 从`window.tailwindscoreProduct.sizeGuide`获取配置

### SCSS
- `src/scss/woocommerce/_product-detail-tabs.scss` - 样式定义
  - 响应式网格布局
  - 手风琴样式和动画
  - **Size Guide样式完全继承自现有类**（无需重复定义）
  - 完全使用设计Token，无硬编码值

## 使用方法

### 激活功能
功能已自动集成到主题中，无需额外配置：

1. 在`functions.php`中已加载：
```php
require_once get_template_directory() . '/inc/woocommerce/product-detail-tabs.php';
```

2. 在`src/ts/product/index.ts`中已初始化：
```typescript
import { initProductDetailTabs } from './product-detail-tabs';
```

3. 在`src/scss/woo.scss`中已导入样式：
```scss
@use "woocommerce/product-detail-tabs";
```

### 内容显示条件
- **描述**：如果产品有长描述内容，则显示
- **Size Guide**：如果产品配置了Size Guide且有内容，则显示
- **评论**：如果产品开启了评论功能，则显示

### 自定义样式
所有样式都使用设计Token，可以在`src/scss/abstracts/_variables.scss`中统一调整：

```scss
// 间距
$ts-space-sm: 0.375rem;
$ts-space-md: 0.5rem;
$ts-space-lg: 0.75rem;
$ts-space-xl: 1rem;
$ts-space-2xl: 1.5rem;

// 颜色
$ts-colors-surface: #fff;
$ts-colors-surface-muted: #faf8f6;
$ts-colors-text: #181818;
$ts-colors-text-muted: #4a4a4a;
$ts-colors-border: #ece6df;

// 圆角
$ts-radius-md: 0.125rem;
```

## 与Size Guide模块的集成

此功能完全复用了现有的Size Guide模块：

1. **数据获取**：使用`tailwindscore_size_guide_config()`函数获取配置
2. **渲染函数**：直接导入`size-guide.ts`的`renderTableHTML()`函数
3. **HTML结构**：生成与`.ts-size-guide-dialog__inner`完全相同的结构
4. **样式复用**：使用现有的CSS类：
   - `.ts-size-guide-dialog__header` - 顶部容器
   - `.ts-size-guide-dialog__controls` - 控制区域
   - `.ts-size-guide-unit-toggle` - 单位切换器容器
   - `.ts-size-guide-unit-btn` - 单位切换按钮
   - `.ts-size-guide-dialog__body` - 内容区域
   - `.ts-size-guide-table` - 表格（来自`_tables.scss`）
   - `.ts-size-guide-legacy` - 传统HTML内容
5. **单位切换**：支持cm和inch切换，功能与弹窗完全一致

## 兼容性

- 不影响现有的Size Guide弹窗功能
- 与产品variations模块完全兼容
- 移动端和PC端平滑切换
- 支持所有现代浏览器（使用CSS Grid和Flexbox）

## 技术细节

### 移除默认Tabs
```php
function tailwindscore_remove_default_product_tabs(): void {
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
}
add_action( 'wp', 'tailwindscore_remove_default_product_tabs', 99 );
```

### 响应式断点
- 移动端：`< 768px` 显示手风琴
- PC端：`≥ 768px` 显示两列网格

### 无障碍支持
- 手风琴按钮使用`aria-expanded`属性
- 折叠内容使用`hidden`属性
- 图标使用`aria-hidden="true"`

## 维护注意事项

1. **设计Token规范**：所有样式修改必须使用`_variables.scss`中的token，不要在组件中硬编码数值
2. **响应式测试**：修改布局时需要测试768px断点前后的显示效果
3. **Size Guide兼容**：
   - 内嵌Size Guide完全复用弹窗样式，无需单独维护
   - 修改Size Guide样式时，在`_product-variations.scss`和`_tables.scss`中统一修改
   - 保持与Size Guide模块的配置格式一致
4. **无障碍测试**：手风琴功能需要确保键盘导航和屏幕阅读器兼容性

## 调试

### 检查Size Guide配置
在浏览器控制台中：
```javascript
console.log(window.tailwindscoreProduct?.sizeGuide);
```

### 检查元素是否渲染
```javascript
document.querySelector('.ts-product-detail-sections');
document.querySelector('[data-ts-product-size-guide-embed]');
```

### 重新编译资源
```bash
npm run build
```
