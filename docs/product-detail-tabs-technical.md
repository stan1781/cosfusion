# 产品详情页 Tab - 技术实现说明

## 核心设计原则

**DRY（Don't Repeat Yourself）**：完全复用现有Size Guide弹窗的代码和样式

## 实现细节

### 1. 样式复用策略

```scss
// ❌ 错误做法：重复定义Size Guide样式
.ts-product-detail-size-guide-embed {
  .ts-size-guide-table { /* 重复样式 */ }
  .ts-size-guide-unit-btn { /* 重复样式 */ }
}

// ✅ 正确做法：直接使用现有类
.ts-product-detail-size-guide-embed {
  // 内部使用 .ts-size-guide-dialog__header
  // 内部使用 .ts-size-guide-dialog__body
  // 内部使用 .ts-size-guide-unit-toggle
  // 所有样式自动继承，无需重复定义
}
```

### 2. TypeScript代码复用

```typescript
// ✅ 导出可复用函数
// size-guide.ts
export function renderTableHTML(table: SizeGuideTable, unit: 'cm' | 'inch', showUnitLabel = false): string {
  return renderTable(table, unit, showUnitLabel);
}

// ✅ 导入并使用
// product-detail-tabs.ts
import { renderTableHTML } from './size-guide';
bodyElement.innerHTML = renderTableHTML(cfg.table, unit, false);
```

### 3. HTML结构复制

内嵌Size Guide生成的HTML结构与弹窗完全一致：

```html
<!-- 弹窗结构 -->
<dialog class="ts-size-guide-dialog">
  <div class="ts-size-guide-dialog__inner">
    <div class="ts-size-guide-dialog__header">
      <div class="ts-size-guide-dialog__controls">
        <div class="ts-size-guide-unit-toggle">
          <button class="ts-size-guide-unit-btn" data-unit="cm">cm</button>
          <button class="ts-size-guide-unit-btn" data-unit="inch">inch</button>
        </div>
      </div>
    </div>
    <div class="ts-size-guide-dialog__body">
      <table class="ts-size-guide-table">...</table>
    </div>
  </div>
</dialog>

<!-- 内嵌结构（完全相同） -->
<div data-ts-product-size-guide-embed>
  <div class="ts-size-guide-dialog__header">
    <div class="ts-size-guide-dialog__controls">
      <div class="ts-size-guide-unit-toggle">
        <button class="ts-size-guide-unit-btn" data-unit="cm">cm</button>
        <button class="ts-size-guide-unit-btn" data-unit="inch">inch</button>
      </div>
    </div>
  </div>
  <div class="ts-size-guide-dialog__body">
    <table class="ts-size-guide-table">...</table>
  </div>
</div>
```

### 4. 单位切换实现

```typescript
// 复用相同的逻辑
unitToggle.querySelectorAll('.ts-size-guide-unit-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const newUnit = btn.getAttribute('data-unit') as 'cm' | 'inch';
    
    // 更新按钮状态
    unitToggle.querySelectorAll('.ts-size-guide-unit-btn').forEach((b) => {
      b.classList.toggle('is-active', b.getAttribute('data-unit') === newUnit);
    });
    
    // 重新渲染表格
    updateSizeGuideBody(cfg, newUnit, bodyElement);
  });
});
```

## 优势分析

### 1. 维护成本低
- 只需在一处修改样式（`_product-variations.scss`和`_tables.scss`）
- 弹窗和内嵌自动同步更新
- 减少重复代码，降低bug风险

### 2. 文件体积小
- SCSS减少约2KB（避免重复样式定义）
- TypeScript代码更简洁（直接导入函数）
- 最终构建产物更小

### 3. 行为一致性
- 表格渲染逻辑完全相同
- 单位切换功能完全相同
- 用户体验保持一致

### 4. 扩展性好
- 新增功能（如新单位）自动应用到两处
- 易于添加新的显示模式
- 符合开闭原则

## 关键代码位置

### 样式来源
```
src/scss/woocommerce/_product-variations.scss
├─ .ts-size-guide-dialog__header
├─ .ts-size-guide-dialog__controls
├─ .ts-size-guide-dialog__body
├─ .ts-size-guide-unit-toggle
└─ .ts-size-guide-unit-btn

src/scss/woocommerce/_tables.scss
└─ .ts-size-guide-table（共享表格样式）
```

### 渲染逻辑
```
src/ts/product/size-guide.ts
├─ renderTable() - 内部渲染函数
└─ renderTableHTML() - 导出给外部使用

src/ts/product/product-detail-tabs.ts
└─ import { renderTableHTML } from './size-guide'
```

## 测试要点

### 样式一致性测试
1. 对比弹窗和内嵌的表格样式（应完全一致）
2. 对比单位切换按钮样式（应完全一致）
3. 对比hover、active状态（应完全一致）

### 功能测试
1. 单位切换按钮点击后表格数值正确更新
2. 默认单位与配置一致
3. 传统HTML内容正确显示
4. 空内容时正确隐藏

### 响应式测试
1. PC端：Size Guide显示在右列
2. 移动端：Size Guide在手风琴中显示
3. 768px断点前后平滑切换

## 常见问题

**Q: 为什么不直接用弹窗的dialog元素？**
A: 因为需要在页面内嵌入显示，而不是弹出层。但我们复用了它的内部结构和样式。

**Q: 如果需要修改Size Guide样式怎么办？**
A: 在`_product-variations.scss`或`_tables.scss`中修改，会自动应用到弹窗和内嵌两处。

**Q: 内嵌版本可以有不同的样式吗？**
A: 可以，但建议保持一致。如需差异化，可以在`.ts-product-detail-size-guide-embed`下覆盖特定样式。

**Q: 单位切换状态是否共享？**
A: 不共享。弹窗和内嵌各自维护自己的单位状态，互不影响。
