# 主导航

TailwindScore 主导航支持标准下拉、Mega 菜单与移动端钻取抽屉，由主题 `inc/navigation/` 实现，不依赖 wp-enhanced-tools。

## 移动端抽屉（钻取面板）

- 有子项的顶级菜单显示 **钻取按钮**（chevron）。点击后子面板从右侧滑入。
- 抽屉标题从 **Menu** 切换为父级标签（如 **Help**）。**← Back** 返回上一级。
- 关闭抽屉时重置到根面板。
- Mega 项（`mega-menu` / `ts-mega-menu`）在面板内渲染列布局；带子项的 mega 列也可使用相同钻取模式。

实现：`src/ts/site/menu-drawer-panel.ts`、`mobile-nav.ts`。

## 标准下拉菜单

1. 在 **外观 → 菜单** 中，在顶级菜单项下添加子项。
2. 无需额外 CSS 类。
3. 桌面端（`≥ lg`）：悬停或键盘聚焦时打开 flyout。
4. 移动端抽屉：点击行展开（`<details>`）。

## Mega 菜单

仅在 **顶级** 菜单项上添加 CSS 类 **`mega-menu`** 或 **`ts-mega-menu`**。

结构（三级）：

```text
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

Walker：`inc/navigation/class-nav-menu-walker.php`。

## 角标

任意菜单项可选类：`hot`、`new`、`sale`（HTML 类名；样式由导航 SCSS 承接）。

## 开发者过滤器

| 过滤器 | 用途 |
|--------|------|
| `tailwindscore_nav_menu_args` | 调整 `wp_nav_menu()` 参数 |
| `tailwindscore_nav_menu_item_classes` | 额外 `<li>` 类名 |
| `tailwindscore_nav_menu_is_mega_item` | 覆盖 mega 检测逻辑 |

## 兼容性说明

**wp-enhanced-tools** 的 Mega Menu 已移除；导航由本主题负责。保留 `mega-menu` 类名以兼容现有菜单数据。

## 相关文档

- [站点壳层](site-shell.md)
