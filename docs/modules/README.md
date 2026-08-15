# 主题模块

`modules/` 目录存放从 `wc-enhanced-tools` 迁入或可独立开关的主题业务能力。每个模块遵循统一结构，配置使用 Kirki `theme_mod`（`tailwindscore_mod()`）。

## 模块结构

```text
modules/{slug}/
  module.php              # 版本常量、bootstrap
  kirki.php               # add_action( 'tailwindscore/kirki/register', ... )
  CHANGELOG.md            # 变更记录
  includes/
    class-*-settings.php  # theme_mod、业务规则
    class-*-admin.php     # 产品 meta、后台 UI
    class-*-assets.php    # 前台 hooks、config（可选）
```

在 `functions.php` 中 `require_once` 各 `module.php`。

## 配置约定

| 规则 | 说明 |
|------|------|
| 存储 | 仅 `theme_mod` |
| 命名 | `product_{module}_{setting}` 或模块内一致前缀 |
| 读取 | `tailwindscore_mod( $key, $default )` |
| 禁止 | 读取 `wc_variation_selector_options` 等插件 option |

开发环境请 **禁用 `wc-enhanced-tools`**，避免与主题模块双开。

## 模块登记表

| 模块 | 路径 | 版本 | 状态 | 说明 |
|------|------|------|------|------|
| typography | `modules/typography` | 0.1.0 | ✅ | 字体加载与 Kirki 排版设置 |
| gallery | `modules/gallery` | 0.1.0 | ✅ | Swiper 单品图库、变体相册 `_variation_images` |
| variations | `modules/variations` | 0.1.0 | ✅ | 图片/色块 swatch、grid/list 布局 |
| size-guide | `modules/size-guide` | 0.1.1 | ✅ | JSON 尺码表 modal + 选码单行 |
| related-products | `modules/related-products` | 0.1.0 | ✅ | 单品页相关产品引擎 |
| quick-view | `modules/quick-view` | 0.1.8 | ✅ | 归档 / 首页 / 购物车 / 404 轮播 Quick View |
| review-images | `modules/review-images` | 0.1.1 | ✅ | 评论附图、买家秀 Showcase |
| account-auth | `modules/account-auth` | 0.1.1 | ✅ | 分屏登录、独立注册页、Google 登录预设 |
| wishlist | `modules/wishlist` | 0.1.3 | ✅ | 心愿单：父商品收藏、登录合并、Account + QV 加购 |
| content-blocks | `modules/content-blocks` | 0.2.0 | ✅ | CPT `ts_block` + `[block]` 可复用 HTML/CSS；Page 页面 CSS 抽离 |

各模块详细变更见对应 `CHANGELOG.md`。

## 模块摘要

### content-blocks

- CPT `ts_block`，后台：**外观 → 内容区块**
- 短代码 `[block id="slug|id"]`（Flatsome 兼容）
- Page「页面 CSS」：正文 `<style>` 抽离至 `_ts_page_css`，前台 inline 到 `tailwindscore-app`
- 详见 [content-blocks.md](content-blocks.md)

### gallery

- 桌面左侧缩略图 + 主图，移动端 dots
- 变体图行为对齐 WooCommerce（父相册激活 / 首张替换 / 自定义多图整组切换）
- Customizer → Gallery（需 [Kirki](https://wordpress.org/plugins/kirki/)）
- TS：`src/ts/product/gallery.ts`、`gallery-variations.ts`、`gallery-photoswipe.ts`

### variations

- 可变商品：swatch、grid/list、产品级覆盖
- Customizer → Variation selector
- Hooks：`tailwindscore_after_size_attribute_label`、`tailwindscore_after_size_attribute_value`（供 Size Guide）
- TS：`src/ts/product/variations.ts`、`variation-engine.ts`

### size-guide

- 数据：`_size_guide`（JSON v2 或遗留 HTML）、模板表 `wp_wc_size_guide_templates`
- 前台：`<dialog id="ts-size-guide-dialog">`，配置 `tailwindscoreProduct.sizeGuide`
- 内嵌展示见 [产品详情 Tab](../features/product-detail-tabs.md)

### quick-view

- 商店归档、首页、购物车推荐、404 人气商品等循环中的可变品 AJAX 快速查看
- 变体 UI 为文字按钮方案，不 enqueue 归档页完整 `variations.ts`
- 卡片悬停预读（180ms 延迟）：移开不取消；打开复用同一 in-flight Promise；`saveData` / 2g 跳过
- TS：`src/ts/shop/quick-view.ts`、`quick-view-variations.ts`

### related-products

- Customizer 控制数量与分类策略
- 子分类不足时回退父分类商品

### review-images

- 评论上传图片、后台管理
- Showcase Gallery 买家秀
- TS：`review-images.ts`、`showcase-gallery.ts`、`review-lightbox.ts`
- Lightbox：弹窗较窄（`max-width: 36rem`）；md+ 图文各半；不展示底部缩略图条（仍可用左右箭头切换）

### account-auth

- 访客 My Account：分屏登录（无内联注册）
- 独立 `/register/` + `[ts_registration_form]`
- Site Kit Google 登录 Kirki 开关（默认关）
- 独立认证页布局（无站点 header/footer）
- Kirki：Customizer 根级「我的账户」区块（`tailwindscore_account_auth`，与页脚同级）

### wishlist

- 仅父商品；访客 `localStorage`、登录 user meta；登录后并集合并
- 归档 / 单品心形；My Account `wishlist`（可变品走 Quick View）
- REST：`tailwindscore/v1/wishlist*`
- 详见 [wishlist.md](wishlist.md)

### typography

- 自定义字体 enqueue 与 Kirki 排版控件
- 输出 CSS 变量覆盖全局字号/字体族

## 前台配置注入

单品页合并进 `window.tailwindscoreProduct`（图库、变体、Size Guide 等）。避免多处 inline script；统一由 `inc/enqueue.php` 输出。

过滤器：`tailwindscore_runtime_config_parts`。

## WooCommerce 契约

1. 可变商品表单保留隐藏 `select[name^="attribute_"]` 与 `wc-add-to-cart-variation`
2. 变体图 **仅** gallery 模块处理
3. 自定义变体 UI：点击 → 写 select → `trigger('change')` → WC 校验
4. 产品 meta 键名保持与插件时代一致（如 `_wc_variation_selector_*`、`_size_guide*`）

## 验证清单

```bash
npm run typecheck
npm run build
```

可变商品单品页手动验证：

1. 颜色 grid/list、形状、grid 文案开关
2. 后台：禁用属性图片、覆盖颜色模式
3. 选变体 → 价格、`variation_id`、图库切换
4. 未选全 → 加购拦截
5. `wc-enhanced-tools` inactive → 无重复脚本
6. URL `?attribute_pa_color=slug` 预填
7. Size Guide 弹窗与内嵌 Tab 均正常

## 新模块 / 迁移指引

1. 阅读 [development/migration-from-wc-enhanced-tools.md](../development/migration-from-wc-enhanced-tools.md)
2. 对照插件源做只读参考，在主题实现，不复制 Bootstrap/option 依赖
3. 完成后更新本登记表与 `CHANGELOG.md`

## 归档

完整迁移规范：[archive/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md](../archive/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md)
