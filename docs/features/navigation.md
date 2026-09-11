# 主导航

TailwindScore 主导航支持标准下拉、Mega 菜单与移动端钻取抽屉，由主题 `inc/navigation/` 实现，不依赖 wp-enhanced-tools。

## 后台 CSS 类说明

外观 → 菜单，展开菜单项。核心「CSS Classes」字段下方会显示主题类名与简短用法；点击类名即可写入该字段（再点一次移除）。

- **顶级项**：布局类（`mega-menu`、`cols-2` / `cols-3`、`cols-mobile`）+ 角标（`hot` / `new` / `sale`）
- **子级项**：仅角标；布局分组不显示

须在右上角「显示选项」中勾选 **CSS Classes**。实现：`inc/navigation/menu-item-admin.php`。

## 后台大菜单编辑（折叠 + 批量移动）

主题在 **外观 → 菜单** 增强核心编辑器，减轻大型 mega（如数十个子分类）的操作压力。资源：`assets/admin/nav-menus-tree.js` / `.css`，由 `menu-item-admin.php` 仅在 `nav-menus.php` 入队。

### 子树折叠

- 有子项的菜单行在标题同一行左侧显示折叠按钮，其后为 Bulk 多选框与标题；标题旁显示子项数量（如 `(12)`）。
- 菜单项手柄加宽至约 560px，便于 PC 端浏览长标题。
- 折叠只隐藏后代行（不删 DOM），不影响 Save Menu / 拖拽数据结构。
- Bulk Select 旁提供 **Collapse all** / **Expand all**。

### 批量移动

1. 勾选 **Bulk Select**。
2. 勾选要移动的菜单项（若同时勾选父与子，只移动父项及其整棵子树，避免双重移动）。
3. 在 **Parent** 下拉中选目标父级（或 No Parent）；**Position** 默认 **End of parent**（接到该父级子项末尾），也可选 **Before: 某标题** 插到该同级项之前。
4. 点击 **Move selected**，再点核心 **Save Menu** 持久化。

不能把项移到自身或其后代下。仍可使用核心的 Bulk 删除。

## 商品分类树同步到菜单

主题可将 **非空** `product_cat` 树同步进现有导航菜单（服务端写库，避开大菜单 `max_input_vars`）。

实现：`inc/navigation/product-cat-menu-sync.php`。

### 行为

- **只处理** `type=taxonomy` + `object=product_cat` 的菜单项；Home、自定义链接、页面等非分类项保留。
- **匹配**：按分类 `term_id`（`object_id`）。已存在项更新父子与排序时**保留**标题、CSS Classes、缩略图 meta（`_ts_nav_item_thumbnail_id`）。
- **空分类**：叶子空分类（`count = 0` 且无需同步后代）**不同步**；父级自身为空但有非空后代时**仍保留**，以维持层级。
- **缺失**：分类已删或不在同步范围 → 删除对应菜单项（含重复的同 term 项）。
- **选择性同步**：可只勾选一个或多个**一级**分类，仅同步其子树；未勾选的一级分类树在菜单中**不动**。
- **全量同步**：仍可用 Sync all；会按规则处理全部非空分类树。
- **已有顶级分类**：保持其在菜单中的相对位置；新建顶级项接到末尾。
- **不自动**写入 `mega-menu` / `cols-*`（沿用你已设置的类）。

### 手动同步

外观 → 菜单，菜单结构下方有 **Sync product categories** 面板：

1. 勾选要同步的一级分类（可 Select all / none）
2. **Sync selected** — 只同步勾选的子树
3. **Sync all** — 全量同步

完成后显示 Added / Updated / Removed 数量。

### 自动同步（可选）

Customizer → 页头 → **商品分类菜单同步**：

| 设置 | 说明 |
|------|------|
| 分类变更时自动同步 | 默认关；开启后在创建 / 编辑 / 删除 `product_cat` 或分类计数变化时**全量**同步 |
| 自动同步目标菜单 | 指定菜单；选「Primary 菜单位置」则用已分配到 `primary` 的菜单 |

## 移动端抽屉（钻取面板）

- 有子项的顶级菜单显示 **钻取按钮**（chevron）。点击后子面板从右侧滑入。
- 抽屉顶栏（`.site-menu-drawer__header`）：`#ts-site-menu-title` 固定为 **Menu** + 关闭按钮。
- 钻取时 `.site-menu-drawer__nav-head` 显示 **← Back** 与当前面板名（有 URL 时可点进该分类）；返回上一级。
- 关闭抽屉时重置到根面板。
- Mega 项（`mega-menu`）在面板内渲染列布局；带子项的 mega 列也可使用相同钻取模式。

实现：`template-parts/header/menu-drawer.php`、`src/ts/site/menu-drawer-panel.ts`、`mobile-nav.ts`。

## 标准下拉菜单

1. 在 **外观 → 菜单** 中，在顶级菜单项下添加子项。
2. 无需额外 CSS 类。
3. 桌面端（`≥ lg`）：悬停或键盘聚焦时打开 flyout。
4. 移动端抽屉：点击钻取行进入子 panel。

一级菜单标签（`.nav__list > .nav__item > .nav__link`）为大写 + 略增字距；项间距桌面端 `gap: var(--ts-spacer-4)`。子菜单 / mega 列内链接不受影响。

## 扁平子菜单多列

适用于**同级扁平列表**（两级：父项 → 子链接）。若需要三级「列标题 → 链接」且非全宽，改用下方 **紧凑 Mega**。

在 **顶级** 菜单项的「CSS 类」中添加：

| 类名 | 作用 |
|------|------|
| `cols-2` | 2 列 |
| `cols-3` | 3 列 |
| `cols-mobile` | 多列同时作用于移动端 drawer（默认仅桌面 `≥ lg`） |

示例：

- 仅桌面 2 列：`cols-2`
- 桌面 + 手机 3 列：`cols-3 cols-mobile`

同时写 `cols-2` 与 `cols-3` 时取 3。单独使用时渲染标准 flyout（非 mega 结构）。

长分类名：桌面子链接（含缩略图）在列宽内**换行**，不横向溢出叠到邻列；多列面板 `min-width` 较单列更宽（2 列约 `24rem`、3 列约 `32rem`），并设视口上限。

实现：Walker 将列数规范化为 `nav__item--cols-{n}`；移动端仅在带 `cols-mobile` 时给 panel 加 `nav__panel--cols-{n}`。

## Mega 菜单

仅在 **顶级** 菜单项上添加 CSS 类 **`mega-menu`**。

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

### 桌面端全宽（仅 `mega-menu`，无 `cols-*`）

- 悬停或键盘聚焦时，在 **sticky header 下方**展开**全宽**面板（相对 `.site-header` 定位，而非菜单项）。
- 列网格对齐站点 `.container`（`max-width: var(--ts-container-max)` + 水平 gutter），`auto-fit` 多列横向排布。
- 面板最大高度 `min(70vh, 32rem)`，超出部分在面板内纵向滚动。
- 关闭态折叠高度并 `overflow: hidden`，避免隐藏面板撑高页面（footer 下空白）。
- 桌面端导航行拉伸至 header 内容区高度，避免标签与面板之间的 padding 死区打断 `:hover`。
- 全宽隐形悬停桥仅在该 mega 项 `:hover` / `:focus-within` 时出现（覆盖 header 底 padding + 面板衔接带）；常驻桥会与其他 mega 项叠在同一条带上并抢悬停。

### 桌面端紧凑（`mega-menu` + `cols-2` / `cols-3`）

三级结构与全宽 mega 相同，但面板**相对菜单项**展开（直角、无边框，阴影区分层次），列数为固定 2 或 3，不拉满顶栏。

| 顶级 CSS 类 | 效果 |
|-------------|------|
| `mega-menu` | 全宽 |
| `mega-menu cols-2` | 紧凑 2 列 |
| `mega-menu cols-3` | 紧凑 3 列 |

示例：

```text
Collections (mega-menu cols-2)
├── Men
│   ├── Shirts
│   └── Pants
└── Women
    ├── Dresses
    └── Tops
```

样式：`src/scss/components/_navigation.scss`。

## 角标

任意菜单项可选类：`hot`、`new`、`sale`（在「外观 → 菜单」中为该项添加 CSS 类）。

| 类名 | 输出 | 视觉 |
|------|------|------|
| `hot` | `.nav__badge--hot` | 危险色（红）pill |
| `new` | `.nav__badge--new` | 成功色（绿）pill |
| `sale` | `.nav__badge--sale` | 警告色（琥珀）pill |

- 位置：链接标题文字之后（仍在 `<a>` / drill `<button>` 内；有 chevron 时在图标前）
- 字号：`var(--ts-font-size-xs)`
- 顶级项：`.nav__badge--top`
- 子项 / mega 链接：`.nav__badge--inline`

样式：`src/scss/components/_navigation.scss`；HTML：`tailwindscore_nav_item_badge_html()`。

## 子菜单缩略图

子级菜单链接（`depth > 0`）按 **缩略图 → 名称** 排列，适用于：

- 移动端 drawer 子 panel
- 桌面端标准下拉 flyout
- Mega 菜单列标题与子链接

### 缩略图来源（优先级）

1. **菜单项手动指定** — 外观 → 菜单，展开菜单项，在「Nav thumbnail」字段用媒体库选图（meta：`_ts_nav_item_thumbnail_id`）。适用于自定义链接、页面等非分类项，也可覆盖分类默认图。
2. **产品分类 Thumbnail** — `product_cat` 菜单项，或自定义链接 URL 可反查到分类归档页时，读取 WooCommerce 分类 Thumbnail（`thumbnail_id` term meta）。
3. **不显示** — 未手动指定且无法解析到带缩略图的分类时保持纯文本（无占位图）。

实现：`inc/navigation/menu-item-helpers.php`（前台解析）、`inc/navigation/menu-item-admin.php`（后台字段）；样式 `.nav__thumb` / `.nav__link--with-thumb` / `.nav__link-label`（左图右文、垂直居中；桌面与移动端均在 label 内换行，多列时不溢出邻列）。

## 开发者过滤器

| 过滤器 | 用途 |
|--------|------|
| `tailwindscore_nav_menu_args` | 调整 `wp_nav_menu()` 参数 |
| `tailwindscore_nav_menu_item_classes` | 额外 `<li>` 类名 |
| `tailwindscore_nav_menu_is_mega_item` | 覆盖 mega 检测逻辑 |
| `tailwindscore_nav_menu_submenu_cols` | 覆盖扁平子菜单列数（0 / 2 / 3） |
| `tailwindscore_nav_menu_submenu_cols_include_mobile` | 覆盖多列是否含移动端 |
| `tailwindscore_nav_menu_item_product_cat` | 覆盖菜单项解析到的 `product_cat` |
| `tailwindscore_nav_menu_item_thumbnail_html` | 覆盖子菜单缩略图 HTML |
| `tailwindscore_nav_menu_css_class_guide_groups` | 覆盖后台「CSS Classes」旁的类名说明 |

## 兼容性说明

**wp-enhanced-tools** 的 Mega Menu 已移除；导航由本主题负责。保留 `mega-menu` 类名以兼容现有菜单数据。

## 相关文档

- [站点壳层](site-shell.md)
