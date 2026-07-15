# Bootscore 映射

TailwindScore 在结构上借鉴 Bootscore 主题的组织方式，但在技术栈与样式哲学上做了明确替换。

## 保留的结构思路

- 根模板（`index.php`、`header.php`、`footer.php` 等）
- `inc/` 目录集中主题逻辑
- `template-parts/` 可复用片段
- `woocommerce/` WooCommerce 模板覆盖目录

## 有意替换的部分

| Bootscore / 传统 | TailwindScore |
|------------------|---------------|
| Bootstrap 框架 | SCSS 设计令牌 + 无 `ts-` 前缀的语义类（命名贴近 Bootstrap，如 `btn btn-outline-primary`；见 [conventions.md](../conventions.md)） |
| jQuery | TypeScript（Vite 构建） |
| WooCommerce 默认前台样式 | 主题 `woo.scss` |
| Bootstrap 组件 data 属性 | Alpine.js（轻量壳层）+ 原生 `<dialog>` |

## 不继承的方向

- Bootstrap **运行时**、`data-bs-*`、网格（`row`/`col`）；类名可对标 Bootstrap 写法，但不加载框架
- jQuery 插件式页面补丁
- 主题内部重型内容治理框架
- 模板层大量堆叠原子 utility 作为长期书写方式

## 令牌模式

与 bootscore-child 类似，采用 `$ts-*` SCSS 变量 → `var(--ts-*)` 的模式，但令牌集经过精简，避免配置膨胀。详见 [设计令牌](design-tokens.md)。
