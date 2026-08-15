# 商店 / 分类归档

Shop（`is_shop()`）与产品分类法归档（`is_product_taxonomy()`）共用一套布局：桌面侧栏筛选 + 主区商品列表；客户端以 **整页 `fetch` + 主区替换** 实现 AJAX 导航与无限滚动（不对列表新建 admin-ajax / REST）。

## 模板结构

```
woocommerce/archive-product.php
  └── template-parts/woocommerce/archive/layout.php
        ├── filters-mobile.php         # 移动端：左侧筛选 Drawer（主区外）
        ├── sidebar-filters.php        # 桌面内联侧栏
        ├── filter-groups.php          # 分组 chips 共用片段
        └── loop.php                   # toolbar（Filters + 排序下拉）+ wc-product-grid + 分页
              └── filters-mobile-toggle.php
woocommerce/loop/orderby.php           # 覆盖 WC：页内 Sort 下拉（非 form/select）
```

标记：`[data-ts-archive]` 根节点；主区 `.wc-archive-main`；筛选容器 `[data-ts-archive-filters]`；导航链接 `[data-ts-archive-nav]`（含 `data-ts-archive-nav-type="orderby"` 排序项）；排序根 `[data-ts-archive-ordering]`。

## 筛选维度

| 组 | 来源 | URL |
|----|------|-----|
| Categories | 顶层 `movie` / `anime` / `game` / `tv` / `halloween-costumes` | `/product-category/{slug}/` |
| Series | 当前频道下的 IP 子分类 | 同上 |
| Color | 当前归档结果中出现的 `pa_color` 值 | `?filter_color={slug}&query_type_color=or` |

属性筛选走 WooCommerce layered nav 查询变量（`WC_Query::get_layered_nav_chosen_attributes()`），主题不另写 `pre_get_posts`。默认仅 **Color**；**Gender / Style 不参与**归档筛选。属性 chip 按**当前主查询结果集**计数（Woo `Filterer::get_filtered_term_product_counts`），当前列表中不存在的颜色不展示；已选中的仍保留以便取消。单选 MVP：点已选项清除该维；「All」清除该维。**点击分类 / Series 会清除全部属性筛选**（干净 permalink），避免落到无货结果。

### SEO（属性筛选 URL）

属性组合 URL 易被蜘蛛当成垃圾入口，采取：

| 手段 | 行为 |
|------|------|
| `rel="nofollow"` | 仅属性 chip 链接（分类链接可跟） |
| `noindex, follow` | 当前请求带任意 `filter_*` 时（`wp_robots` + Yoast / Rank Math） |
| `canonical` | 若主题/插件仍输出 canonical，则指向干净归档 URL；Rank Math 在 noindex 时会省略 canonical（符合其默认策略） |

扩展钩子：

- `tailwindscore/archive/filter_groups`
- `tailwindscore/archive/sidebar_groups`
- `tailwindscore/archive/attribute_slugs`
- `tailwindscore/archive/top_level_category_slugs`
- `tailwindscore/archive/skip_sidebar_term`

## 排序

覆盖 `woocommerce/loop/orderby.php`：页内 disclosure（`Sort` + 短标签 + chevron），菜单项为带 `?orderby=` 的链接（`data-ts-archive-nav` / `data-ts-archive-nav-type="orderby"`）。避免手机原生 `<select>` 系统弹层。

| 项 | 行为 |
|----|------|
| 触发器短标签 | `Default` / `Popularity` / `Rating` / `Latest` / `Price: Low` / `Price: High`（`inc/woocommerce/archive/ordering.php`） |
| 菜单文案 | Woo 完整文案去掉前导 `Sort by`（如 `price: low to high`）；菜单 `width: max-content`，可宽于触发按钮、尽量不换行 |
| 无 JS | 整页跳转 |
| 有 JS | `archive-nav` AJAX；`archive-ordering.ts` 仅负责开闭菜单 |
| 同步 | `syncFilterNavHrefs` / `syncFilterActiveFromHrefs` **跳过** orderby 链接（当前项与 URL 由服务端 HTML 决定） |

## AJAX 行为

| 模块 | 路径 | 职责 |
|------|------|------|
| Prefetch | `src/ts/shop/archive-prefetch.ts` | URL 缓存、`fetch` HTML、抽出主区 |
| Nav | `src/ts/shop/archive-nav.ts` | chip / 排序链接 / 编号分页、`pushState`、同步侧栏 active |
| Ordering UI | `src/ts/shop/archive-ordering.ts` | Sort 下拉开闭、Escape / 外点关闭 |
| Infinite | `src/ts/shop/archive-infinite-scroll.ts` | sentinel 追加 `.wc-product-grid > li` |
| Mobile | `src/ts/shop/archive-filters-mobile.ts` | 工具栏 Filters 开闭左侧 Drawer |

- 无限滚动为默认；`saveData` / `2g` 时降级为编号分页（仍 AJAX 替换主区）。
- 只替换 `.wc-archive-main`；筛选 chip 组（`.wc-archive-sidebar__groups`）随目标页 HTML 同步替换，使属性选项随当前列表裁剪；再同步 `is-active`。Drawer 壳在主区外，替换后不丢；工具栏 Filters / Sort 随主区重建。
- 点 chip / 排序成功后关闭 Drawer 与排序菜单；徽标用 `syncArchiveFiltersMobileCount`。
- 移动 Drawer **打开**时导航：对 `.wc-archive-filters-mobile__body` 同步 `loading-host`（主区 loading 仍保留，但会被 z-panel 挡住，故面板内需可见反馈）。
- `loop-add-to-cart` / swatches / Quick View 为 document 委托，主区替换后无需重绑。

## 布局

- **桌面 `md+`**：`.wc-archive-layout` 约 `3fr / 9fr`；侧栏 sticky（`top` 用 `--ts-site-header-height`，缺省回退 `5rem`）。移动筛选触发与 Drawer 隐藏；工具栏左侧 result count、右侧同一套 Sort 下拉。
- **移动端 `< md`**：内联侧栏隐藏；工具栏用 **两列 grid（`1fr 1fr`）** 让 Filters 与 Sort 等宽（隐藏 result count）；空结果仅有 Filters 时退回单列内容宽。筛选为左侧 fixed Drawer（`var(--ts-drawer-width)`，非底部 Sheet）。
- **商品卡操作区**（`.wc-product-card__actions`）：叠在媒体图上（绝对定位），不占卡片文档流；`< md` 常显，按钮为 `0.8 × --ts-control-min-height`（约 2rem），避免两列网格里挡住主图；`md+` 恢复标准控件高度，悬停/焦点显。右上圆形图标统一样式：简单商品加购（cart）/ 可变品 QV（eye）/ Wishlist（heart）；Woo 可变品 `screen-reader-text` 与未图标化的文字链在此隐藏。

## PHP

| 文件 | 说明 |
|------|------|
| `inc/woocommerce/archive/context.php` | 请求上下文 |
| `inc/woocommerce/archive/filter-registry.php` | 筛选组注册 |
| `inc/woocommerce/archive/category-repository.php` | 分类树 |
| `inc/woocommerce/archive/attribute-filters.php` | 属性 URL / 已选读取 |
| `inc/woocommerce/archive/ordering.php` | 排序短标签与 orderby URL |
| `woocommerce/loop/orderby.php` | 页内 Sort 下拉模板覆盖 |

## 不做（本版）

价格区间、Size/鞋码归档筛、网格/紧凑视图、FacetWP / WC 小工具侧栏、移动端底部 Sheet、与 menu/cart Alpine 互斥编排。
