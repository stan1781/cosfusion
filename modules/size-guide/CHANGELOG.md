# Size Guide module

## 0.1.3

- 修复：`hasContent` 要求尺码表有 `rows`（或 legacy HTML）；仅有空表头不再视为有内容。

## 0.1.2

- 改进：`initSizeGuideInlineInRoot` — QV / cart-edit 从内嵌 JSON 初始化选码 inline（作用域限 root，不绑 PDP）。
- 新增：`tailwindscore_print_size_guide_config_script()` 输出 `data-ts-size-guide-config`。
- 重构：inline 更新 / 变体监听改为 scoped（`ParentNode`），PDP `initSizeGuide` 行为不变。

## 0.1.1

- 修复弹窗无法打开：TS 查找 `size-guide-dialog`，与 PHP 输出的 `ts-size-guide-dialog` 不一致。

## 0.1.0

- JSON size guide modal via `Product_Size_Guide::resolve()`.
- Size Guide button on size attribute label hook.
- Inline measurements when a size swatch is selected.
- Config merged into `window.tailwindscoreProduct.sizeGuide`.
