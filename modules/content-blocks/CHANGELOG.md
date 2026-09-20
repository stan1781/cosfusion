# Content Blocks

## 0.3.0

- **Singular CSS**：`_ts_singular_css` 覆盖 Page 与特稿 Post；读兼容旧 `_ts_page_css`；写只写新 key
- Post 侧栏「Feature layout」开关；开启或已有 CSS 时显示「文章 CSS」metabox
- 正文壳增加 `ts-content-root`（`page.php` / `single.php` / `index.php`）；Full Width 包一层 `ts-content-root`
- 实现文件：`class-singular-style.php`（`Tailwindscore_Page_Style` 类名别名保留）

## 0.2.0

- **Page 页面 CSS**：编辑 Page 时新增「页面 CSS」metabox；正文内 `<style>` 保存时自动抽离至 `_ts_page_css`，前台挂到 `tailwindscore-app` inline style
- 修复 Page 正文连续 `[block]` 经 `wpautop` 后残留空 `<br>` / `<p></p>` 造成多余间距
- `extract_styles_from_html`：抽取前遮蔽 HTML 注释，避免注释内的 `<style>` 字面量误匹配并吞掉正文
- 区块编辑页 HTML/CSS 使用 WordPress CodeMirror（不可用时回退 textarea）

## 0.1.0

- 从 Starcore 移植内容区块 CPT（`ts_block`）与 `[block]` 短代码
- 后台挂在「外观 → 内容区块」
- 编辑页代码 / 预览 tab；预览注入 `--ts-*` tokens
