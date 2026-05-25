# TailwindScore

TailwindScore 是一个基于 PHP、Tailwind CSS、TypeScript 和 WooCommerce 的轻量级 WordPress 主题基线。

## 当前目标

- 保持模板结构清晰直观
- 用 Tailwind CSS 替代 Bootstrap
- 用 TypeScript 替代 jQuery
- 由主题统一接管 WooCommerce 前台样式
- 以渐进增强方式处理商城交互

## Phase 1 范围

- 基础主题结构
- Vite 构建与资源清单
- 站点通用模板
- WooCommerce 核心模板覆盖

## 从 wc-enhanced-tools 迁移

迁移约束与模块登记表见 [docs/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md](docs/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md)。  
新会话在迁插件功能前应先阅读该文档及对应 `docs/superpowers/plans/*-implementation.md`。

**开发环境请默认禁用** `wc-enhanced-tools`，避免与主题模块冲突。

| 模块 | 状态 |
|------|------|
| `modules/gallery` | 已迁入 |
| `modules/variations` | 已迁入（变体选择器；不含 Size Guide） |
| `modules/size-guide` | 已迁入（JSON 尺码表 modal + 选码单行） |

## WooCommerce 相册模块 (`modules/gallery`)

- 单品页使用 Swiper 图库（桌面左侧缩略图，移动端 dots）。
- 变体相册后台字段 `_variation_images`；前台变体图行为对齐 WooCommerce（父相册内激活 / 首张替换 / 自定义多图整组切换）。
- Customizer 字段需安装 [Kirki Framework](https://wordpress.org/plugins/kirki/) 插件；未安装时使用代码默认值。
- 与变体选择器模块并存时，同样需禁用 `wc-enhanced-tools` 插件。

## WooCommerce 变体选择器 (`modules/variations`)

- 单品可变商品：图片/色块 swatch、grid/list 布局、产品级覆盖。
- Customizer → **Variation selector**（需 Kirki）。
- Size Guide 未迁入；尺寸行预留 `tailwindscore_after_size_attribute_label` hook。

## 本地开发

```bash
npm install
npm run build
npm run typecheck
```
