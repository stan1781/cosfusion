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

## 常用推荐组合（一点应用）

Customizer **设计 → 排版** 在「正文字体」下方按品类展示按钮；点击后同时写入 `typography_heading_font` / `typography_body_font`。组合定义见 `tailwindscore_typography_font_presets()`；标题与正文两侧字体均在磁盘可用时才出现该行。

| 品类 | 场景 | 标题 | 正文 |
|------|------|------|------|
| 运动 | 运动站默认 | Barlow Condensed | Barlow |
| 运动 | 促销 / 硬核 | Teko | IBM Plex Sans |
| 运动 | 科技运动 | Exo 2 | Manrope |
| 运动 | 经典运动 | Oswald | Source Sans 3 |
| 户外 | 户外装备默认 | Oswald | Source Sans 3 |
| 户外 | 功能 / 技术装备 | Barlow Condensed | IBM Plex Sans |
| 户外 | 户外生活方式 | Raleway | Open Sans |
| 时尚 | 优雅时装 | Playfair Display | Lato |
| 时尚 | 生活方式 | Raleway | Open Sans |
| 时尚 | 现代成衣 | Montserrat | DM Sans |
| 时尚 | 高级成衣 | Cormorant Garamond | Inter |
| 手表 / 奢侈 | 奢华表款 | Cormorant Garamond | Source Sans 3 |
| 手表 / 奢侈 | 经典正装 | Libre Baskerville | Open Sans |
| 手表 / 奢侈 | 现代精品 | Playfair Display | Inter |
| 通用电商 | 中性产品站 | Poppins | Inter |
| 通用电商 | 亲和营销 | Poppins | Nunito Sans |
| 通用电商 | 干净品牌感 | Montserrat | Plus Jakarta Sans |
| 通用电商 | 耐看电商 | Montserrat | Work Sans |
| 内容 / 博客 | 品牌故事 | Lora | Source Sans 3 |
| 内容 / 博客 | 长文阅读 | Merriweather | Open Sans |
| 科技 | 科技产品 | Exo 2 | IBM Plex Sans |
| 科技 | 竞速 / 电竞 | Rajdhani | Roboto |

Anton / Bebas Neue / Saira Condensed 等偏短标题字体未列入一点应用（易在长标题上挤）。等宽字体不参与站点级标题/正文组合。

## 相关

- [modules README](../modules/README.md)
- [前台性能](../architecture/performance.md)
