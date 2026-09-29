# Product Groups

## 0.1.0

- 初版：CPT `ts_product_group`、PDP 变体式缩略图互跳、运行时联动 variations、Iconic 迁移工具。
- 后台 Products 选择/列表以 SKU 为主展示（无 SKU 回退名称）；搜索 AJAX `ts_pg_json_search_products`。
- 前台组标题用首个勾选属性标签（如 Style）；`disable_image` 在配置属性不存在于商品时回退到颜色类属性。
- 主题 Tooltip（`data-ts-tooltip`）用于组缩略图 Style 值；悬停同步预览行内当前值。
- 后台列表搜索支持组标题、组内商品 SKU 与商品名。
- Iconic 迁移入口改到 Product Groups 列表（不再挂 WooCommerce 侧栏）。
- CSV 导出/导入（SKU + 属性 action）；未知全局属性跳过并提示（不创建）；term/商品属性不在此处理。
- 拆分 **Group display attribute** 与 **Variation attribute actions**：Display（多为非变体 Style）不进动作表；动作针对变体属性（如 Color）。
- **Display mode**（组 meta `_ts_pg_display_mode`）：`thumbnail`（默认）或 `button`；按钮模式复用变体 `text-selector`，仅显示 display attribute term，无 term 组员跳过；CSV 列 `display_mode`。
- **Display position**（组 meta `_ts_pg_display_position`）：`above`（默认）或 `below`；Variable 上 `below` 挂到 `woocommerce_after_variations_table`；Simple 忽略；CSV 列 `display_position`。
