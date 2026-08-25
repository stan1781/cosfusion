# 排版（Typography）

Customizer **设计 → 排版** 选择正文 / 标题字体；前台输出 `@font-face` 与 `--ts-font-body` / `--ts-font-heading`。

## 字体存放

| 项 | 说明 |
|----|------|
| 根目录 | `wp-content/uploads/tailwindscore-fonts/{slug}/` |
| 目录定义 | 主题 [`font-catalog.php`](../../modules/typography/includes/font-catalog.php)：slug、分组、风格、备注、faces |
| 文件名 | 保持 [GWFH](https://gwfh.mranftl.com/fonts) 下载原名；版本号可变，会按字重后缀（`-latin-regular` / `-600` / `-700`）自动匹配 |
| 子目录 | **必须**与目录 slug 一致（如 `playfair-display`、`source-sans-3`，勿写成 `playfair` / `source-sans`） |
| 可选条件 | 对应 slug 目录下能匹配到至少一个登记字重的 woff2 |

示例：

```
uploads/tailwindscore-fonts/source-code-pro/
  source-code-pro-v31-latin-regular.woff2
  source-code-pro-v31-latin-600.woff2
```

下载约定：Charsets **latin**；Formats 只勾 **woff2**；字重 **regular + 600**（衬线标题可加 **700**）。版本号变更一般无需改目录；子目录名必须等于 slug。

## 分组（Customizer optgroup）

系统 / 正文 Sans / 标题 Sans / 标题衬线 / 等宽。选项文案：`名称 — 风格（备注）`。

## 相关

- [modules README](../modules/README.md)
- [前台性能](../architecture/performance.md)
