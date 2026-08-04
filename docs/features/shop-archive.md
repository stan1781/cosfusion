# 商店 / 分类归档

Shop（`is_shop()`）与产品分类法归档（`is_product_taxonomy()`）共用一套布局：桌面侧栏筛选 + 主区商品列表；客户端以 **整页 `fetch` + 主区替换** 实现 AJAX 导航与无限滚动（不对列表新建 admin-ajax / REST）。

## 模板结构

```
woocommerce/archive-product.php
  └── template-parts/woocommerce/archive/layout.php
        ├── filters-mobile.php         # 移动端：左侧筛选 Drawer（主区外）
        ├── sidebar-filters.php        # 桌面内联侧栏
        ├── filter-groups.php          # 分组 chips 共用片段
        └── loop.php                   # toolbar（含移动筛选图标）+ wc-product-grid + 分页
              └── filters-mobile-toggle.php
```

标记：`[data-ts-archive]` 根节点；主区 `.wc-archive-main`；筛选容器 `[data-ts-archive-filters]`；导航链接 `[data-ts-archive-nav]`。

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

## AJAX 行为

| 模块 | 路径 | 职责 |
|------|------|------|
| Prefetch | `src/ts/shop/archive-prefetch.ts` | URL 缓存、`fetch` HTML、抽出主区 |
| Nav | `src/ts/shop/archive-nav.ts` | chip / 排序 / 编号分页、`pushState`、同步侧栏 active |
| Infinite | `src/ts/shop/archive-infinite-scroll.ts` | sentinel 追加 `.wc-product-grid > li` |
| Mobile | `src/ts/shop/archive-filters-mobile.ts` | 工具栏图标开闭左侧 Drawer |

- 无限滚动为默认；`saveData` / `2g` 时降级为编号分页（仍 AJAX 替换主区）。
- 只替换 `.wc-archive-main`；筛选 chip 组（`.wc-archive-sidebar__groups`）随目标页 HTML 同步替换，使属性选项随当前列表裁剪；再同步 `is-active`。Drawer 壳在主区外，替换后不丢；工具栏图标随主区重建。
- 点 chip / 排序成功后关闭 Drawer；徽标用 `syncArchiveFiltersMobileCount`。
- `loop-add-to-cart` / swatches / Quick View 为 document 委托，主区替换后无需重绑。

## 布局

- **桌面 `md+`**：`.wc-archive-layout` 约 `3fr / 9fr`；侧栏 sticky（`top` 用 `--ts-site-header-height`，缺省回退 `5rem`）。移动筛选触发与 Drawer 隐藏。
- **移动端 `< md`**：内联侧栏隐藏；工具栏单行 **筛选图标 + orderby**（隐藏 result count）；筛选为左侧 fixed Drawer（`var(--ts-drawer-width)`，非底部 Sheet）。

## PHP

| 文件 | 说明 |
|------|------|
| `inc/woocommerce/archive/context.php` | 请求上下文 |
| `inc/woocommerce/archive/filter-registry.php` | 筛选组注册 |
| `inc/woocommerce/archive/category-repository.php` | 分类树 |
| `inc/woocommerce/archive/attribute-filters.php` | 属性 URL / 已选读取 |

## 不做（本版）

价格区间、Size/鞋码归档筛、网格/紧凑视图、FacetWP / WC 小工具侧栏、移动端底部 Sheet、与 menu/cart Alpine 互斥编排。
