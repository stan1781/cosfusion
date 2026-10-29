# 分栏 Hero

静态首页 Page 通过短代码 `[ts_home_split_hero]` 展示左右分栏 Hero（桌面左文右图，移动端纵向堆叠）。文案与图片由 Kirki Customizer 管理。

Spoutini 首页默认使用本短代码；全图 Swiper 仍用 [`[ts_home_hero]`](home-hero.md)。

## 实现

| 能力 | 位置 |
|------|------|
| Kirki 字段 | `inc/config/kirki/home.php`（section「分栏 Hero」） |
| 解析 / 渲染 / 短代码 | `inc/home/split-hero.php` |
| 壳层模板 | `template-parts/home/split-hero.php` |
| 样式 | `src/scss/components/_home-split-hero.scss` |

## 运营用法

1. **设置 → 阅读**：静态首页选对应 Page
2. 页面模板选 **Full Width**
3. 正文放入：`[ts_home_split_hero]`
4. **外观 → 自定义 → 首页 → 分栏 Hero**：填写眉题 / 标题 / 副文 / CTA / 右侧图

编辑页侧栏速查见 [主题短代码](shortcode-help.md)。

可选短代码覆盖（空则用 Customizer）：

```text
[ts_home_split_hero title="Gear up. Get out." cta_label="Shop all gear" cta_url="/shop/"]
```

## Kirki 字段

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `home_split_hero_enable` | toggle | true | 总开关 |
| `home_split_hero_eyebrow` | text | Outdoor & Adventure Gear | 眉题（可空） |
| `home_split_hero_title` | text | Gear up. Get out. | 主标题（必填才渲染） |
| `home_split_hero_text` | textarea | Everyday essentials… | 副文 |
| `home_split_hero_cta_label` | text | Shop all gear | CTA 文案 |
| `home_split_hero_cta_url` | text | 空 | 空则回退商店页 / 首页 |
| `home_split_hero_image` | image | 空 | 右侧图；无图时柔和占位，不破图 |

## 降级

- 关闭 enable 或标题为空 → 不输出
- 无图 → `home-split-hero--no-media` + 占位块
- Woo 未启用时 CTA 回退 `home_url( '/' )`

## 相关文档

- [首页](homepage.md)
- [首页 Hero 轮播](home-hero.md)
- [Spoutini 改造指南](../roadmap/spoutini-outdoor-storefront.md)
