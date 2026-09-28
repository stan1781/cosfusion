# Product Groups

## 0.1.0

- 初版：CPT `ts_product_group`、PDP 变体式缩略图互跳、运行时联动 variations、Iconic 迁移工具。
- 后台 Products 选择/列表以 SKU 为主展示（无 SKU 回退名称）；搜索 AJAX `ts_pg_json_search_products`。
- 前台组标题用首个勾选属性标签（如 Style）；`disable_image` 在配置属性不存在于商品时回退到颜色类属性。
- 主题 Tooltip（`data-ts-tooltip`）用于组缩略图 Style 值；悬停同步预览行内当前值。
