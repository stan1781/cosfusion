# 产品组（Product Groups）

**状态**：已实现（`modules/product-groups` 0.1.0）  
**替代**：Iconic Woo Linked Variations（相近款 / 同主题角色互跳，非真变体、非配件 Cross-sells）

## 目标

用主题模块提供「精选商品组」：后台操作贴近 Linked Variations；前台在选项上方以 **变体色块同款缩略图** 切换组内商品；组级运行时控制 `modules/variations` 的图像显示 / 属性行可见性，**不改写**商品 meta。

非目标（首版）：系列归档页、写入 Upsells/Cross-sells、Speculation Rules prerender、AJAX 换品（整页跳转）。

## 数据模型

| 项 | 约定 |
|----|------|
| 模块路径 | `modules/product-groups/` |
| CPT | `ts_product_group`；`supports` 仅 `title`；挂在 Products 子菜单；无前台归档 |
| 组名 | CPT `post_title`（后台识别用）；**前台行标题**用 **Group display attribute**（如 Style），未设时回退 CPT 标题 / `Styles` |

### 组 meta

| 键 | 类型 | 说明 |
|----|------|------|
| `_ts_pg_product_ids` | `int[]` | 组内商品，有序 |
| `_ts_pg_display_attribute` | `string` | 组缩略图标题 / tip（如 `pa_style`）；通常为**非变体**商品属性；空则回退 CPT 标题 |
| `_ts_pg_attributes` | `string[]` | 摘要用（显示属性 + 有动作的属性）；由保存时派生 |
| `_ts_pg_attr_actions` | `array<string, string>` | 变体属性 → `disable_image` \| `hide_row`（如 Color）；**不含** display 属性 |
| `_ts_pg_migrated_from_iconic` | `int` | 幂等迁移来源 Iconic `post_id`（可选） |
| `_ts_pg_group_id`（写在商品上） | `int` | 反向索引，一组一品 |

### 约束

- **一组一品**：保存时若商品已在其他组，移出旧组并 admin notice。
- **不写** `_upsell_ids` / `_crosssell_ids`。
- **不写** `_wc_variation_selector_disabled_image_attributes`（运行时生效）。
- Cross-sells 继续只服务配件「Complete the look」。

## 后台 UI

菜单位置：Products → Product Groups。

列表列：Title、Products（数量 + 摘要）、Attributes、Date。

列表搜索：组标题，以及组内商品的 **SKU / 商品名**（经 Woo `search_products`，变体命中会归到父商品所属组）。

CSV：**Products → Product Groups** 列表「Import / Export CSV」。列 `group_id,title,skus,display_attribute,attr_actions`（`skus` 用 `|`；`attr_actions` 如 `pa_style:hide_row|pa_color:disable_image`）。有 `group_id` 则更新，否则新建。未知全局属性跳过并提示。旧列 `attributes` 仍可读（首项作 display）。

编辑 meta box：Products；**Group display attribute**（多为非变体 Style）；**Variation attribute actions**（变体属性如 Color；Display 属性不出现在动作表）。

## 前台

| 类型 | Hook |
|------|------|
| Variable | `woocommerce_before_variations_form` priority 5 |
| Simple 等 | `woocommerce_before_add_to_cart_button` |

可见组员 &lt; 2 → 不输出。DOM 复用 `custom-variation-selector color-selector mode-grid`；hover/focus 对目标 permalink 一次性 `rel=prefetch`。缩略图提示用主题 Tooltip（`data-ts-tooltip` = 组首属性 term，如 Style 值）。

### 与 variations 联动

| 动作 | 行为 |
|------|------|
| `disable_image` | 配在变体属性（如 Color）上；filter `is_color_attribute` → false。旧数据若误写在 Style 且商品无该变体维，仍可回退颜色类 |
| `hide_row` | 配在变体属性上；Display（Style）保存时会被忽略 |

## Iconic 迁移与退役

- 后台：**Products → Product Groups** 列表 views 行「Migrate from Iconic」（不进侧栏；CLI 仍可用）
- CLI（若可用）：`wp ts-pg-migrate-iconic [--dry-run] [--force]`
- 映射：见历史设计；`show_image` 时首属性默认 `disable_image`
- **已退役**主题侧 `inc/integrations/iconic-linked-variations.php`（不再从 bootstrap 加载）
- 请在 WP 后台**停用** Iconic 插件；**暂留** `wp_iconic_woo_linked_variations` 表，确认无误后再手工 DROP

## 实现文件

| 层 | 路径 |
|----|------|
| 模块 | `modules/product-groups/` |
| variations 钩子 | `modules/variations/.../class-variation-settings.php`、`woocommerce/single-product/add-to-cart/variable.php` |
| 样式 | `src/scss/woocommerce/_product-variations.scss` |
| TS | `src/ts/product/product-groups.ts` |
| CSV | `modules/product-groups/includes/class-product-group-csv.php` |

## 已否决

- 批量迁到 Cross-sells / Upsells 作为主数据源。
- 组设置写入 variations 商品 meta。
- 一品多组。
- 隐藏行时从 DOM 删除 select。
