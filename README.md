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

## WooCommerce 相册模块 (`modules/gallery`)

- 单品页使用 Swiper 图库（桌面左侧缩略图，移动端 dots）。
- 变体相册后台字段 `_variation_images`；前台变体图行为对齐 WooCommerce（父相册内激活 / 首张替换 / 自定义多图整组切换）。
- Customizer 字段需安装 [Kirki Framework](https://wordpress.org/plugins/kirki/) 插件；未安装时使用代码默认值。
- **开发环境请默认禁用** `wc-enhanced-tools` 插件，避免与主题图库脚本冲突。

## 本地开发

```bash
npm install
npm run build
npm run typecheck
```
