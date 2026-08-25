# Typography Module

## 0.2.0

- 字体迁至 `uploads/tailwindscore-fonts/{slug}/`；主题包不再携带 woff2。
- 目录 `font-catalog.php`：分组 + 短风格/备注；仅磁盘有文件的条目可选。
- Customizer 原生 optgroup 控件（替代 Kirki ReactSelect）。
- GWFH 下载文件名保持原样，放入对应 slug 子目录即可。

## 0.1.1

- 内置 Roboto（后迁出主题目录）。

## 0.1.0

- 内置 System UI / Inter / Playfair Display；Kirki 设计 → 排版选择正文与标题字体。
- 前台输出 `@font-face` 与 `--ts-font-body` / `--ts-font-heading`。
