# 主导航（tailwindscore）

## 移动端抽屉（滑动面板）

- 有子项的顶级菜单显示**钻取按钮**（chevron）。点击后子面板从右侧滑入。
- 抽屉标题会从 **Menu** 切换为父级标签（例如 **Help**）。使用 **← Back** 滑出返回。
- 关闭抽屉会重置到根面板。
- Mega 项（`mega-menu` / `ts-mega-menu`）在其面板内渲染列布局；未来带子项的 mega 列也可使用相同的钻取模式。

## 标准下拉菜单

1. 在 **外观 → 菜单** 中，在顶级菜单项下添加子项。
2. 无需额外 CSS 类。
3. 桌面端（≥ lg）：悬停或键盘聚焦时打开 flyout。
4. 移动端抽屉：点击行展开（`<details>`）。

## Mega 菜单（扩展）

仅在**顶级**菜单项上添加 CSS 类 **`mega-menu`** 或 **`ts-mega-menu`**。

结构（三级）：

```
Products (mega-menu)
├── Men (column title)
│   ├── Shirts
│   └── Pants
└── Women (column title)
    ├── Dresses
    └── Tops
```

- 第 1 级：mega 父级标签（链接可选）
- 第 2 级：列标题（链接）
- 第 3 级：各列下的链接

## 角标（仅 HTML；样式后续阶段）

任意菜单项可选类：`hot`、`new`、`sale`。

## 过滤器（开发者）

- `tailwindscore_nav_menu_args` — 调整 `wp_nav_menu()` 参数
- `tailwindscore_nav_menu_item_classes` — 额外 `<li>` 类名
- `tailwindscore_nav_menu_is_mega_item` — 覆盖 mega 检测逻辑

## 旧版插件

**wp-enhanced-tools** 已移除 Mega Menu；导航由本主题负责。保留 `mega-menu` 类名以兼容现有菜单数据。
