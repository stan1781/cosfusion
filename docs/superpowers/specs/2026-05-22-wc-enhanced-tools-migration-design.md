# wc-enhanced-tools → TailwindScore 迁移设计规范

**状态**：长期有效  
**适用主题**：[tailwindscore](D:/www/base/wp-content/themes/tailwindscore)  
**源插件**：[wc-enhanced-tools](D:/www/base/wp-content/plugins/wc-enhanced-tools)

---

## 目的

将 `wc-enhanced-tools` 中面向前台/单品的能力迁入主题的 `modules/` 目录，与 Vite + TypeScript + Tailwind 技术栈一致。目标站点为**新站**：配置只使用 Kirki `theme_mod`，**不**从插件 `get_option( 'wc_*' )` 做 legacy 回退。

任何新会话在迁移 **wc-enhanced-tools** 功能前，应先阅读本文、对应 `docs/superpowers/plans/*-implementation.md`，以及相关模块的 `CHANGELOG.md`。

**其它独立插件**（用户、邮件、订单流程等）不按本文迁入 `modules/`，而按 [独立插件 × 主题资源整合规范](./2026-05-23-plugin-theme-asset-integration-design.md) 处理。

---

## 开发环境

- 本地/开发环境默认将 **整个** `wc-enhanced-tools` 插件设为 **inactive**。
- 同一前台能力不得插件与主题**双开**（例如同时加载插件 `variation-selector.js` 与主题 `variations.ts`）。
- 图库模块已接管单品相册时，插件内的 flexslider / `wc-single-product` 图库逻辑必须保持停用。

---

## 模块结构（Module anatomy）

每个迁入能力对应 `modules/{slug}/`：

```
modules/{slug}/
  module.php              # 版本常量、bootstrap、require includes
  kirki.php               # add_action( 'tailwindscore/kirki/register', ... )
  CHANGELOG.md            # 中文变更记录
  includes/
    class-*-settings.php  # theme_mod 读取、业务规则
    class-*-admin.php     # 产品 meta、后台 UI（可用 jQuery）
    class-*-assets.php      # 前台 hooks、config 载荷（可选）
```

- 在 [functions.php](D:/www/base/wp-content/themes/tailwindscore/functions.php) 中 `require_once` 各 `module.php`。
- Kirki 字段通过 [inc/config/kirki-config.php](D:/www/base/wp-content/themes/tailwindscore/inc/config/kirki-config.php) 的 `tailwindscore/kirki/register` 注册。
- 读取配置统一使用 [tailwindscore_mod()](D:/www/base/wp-content/themes/tailwindscore/inc/config/kirki/helpers.php)。

---

## 配置约定

| 规则 | 说明 |
|------|------|
| 存储 | 仅 `theme_mod`（Kirki `option_type => theme_mod`） |
| 命名 | `product_{module}_{setting}` 或 `variation_{setting}`（模块内一致即可） |
| 默认值 | 写在 Kirki `default` 与 `tailwindscore_mod( $key, $default )` 中 |
| 禁止 | 读取 `wc_variation_selector_options`、`wc_enhanced_tools_*` 等插件 option |

---

## 分层边界

| 层 | 职责 | 路径示例 |
|----|------|----------|
| PHP 模块 | 数据解析、后台、Woo hooks、config 函数 | `modules/*/includes/` |
| 模板 | SSR 标记结构，无 Bootstrap | `woocommerce/`、`template-parts/` |
| TypeScript | 用户交互、DOM 同步 | `src/ts/product/` |
| 样式 | Tailwind / `--ts-*` token | `src/scss/` |

**禁止**：Bootstrap 类、`data-bs-toggle`、插件路径下的 `wc_get_template` filter 覆盖（改用主题 `woocommerce/` 模板文件）。

---

## WooCommerce 契约

1. **可变商品表单**必须保留隐藏的 `select[name^="attribute_"]` 与 `wc-add-to-cart-variation` 脚本（可由 gallery 模块 enqueue）。
2. **变体图**仅由 `modules/gallery` + [gallery-variations.ts](D:/www/base/wp-content/themes/tailwindscore/src/ts/product/gallery-variations.ts) 处理；**禁止**在 variations TS 中复刻 `wc_variations_image_update`。
3. **加购**继续依赖 [src/ts/product/index.ts](D:/www/base/wp-content/themes/tailwindscore/src/ts/product/index.ts) 对 `variation_id` / `attribute_*` 的读取。
4. 自定义变体 UI 是「皮肤」：点击 → 写 select → `trigger('change')` → WC 核心校验。

---

## 模板策略

- 优先在主题 [woocommerce/](D:/www/base/wp-content/themes/tailwindscore/woocommerce/) 放置覆盖模板。
- 功能可通过 Kirki 关闭时，在主题模板**顶部** `wc_get_template( ..., '', WC()->plugin_path() . 'templates/' )` 回退 Woo 核心模板（见 variations `variable.php`）。
- **不用**插件目录 + `wc_get_template` filter 指向插件文件。

---

## 产品 meta 与数据兼容

迁移涉及 stored data 时：

- **保留**既有 meta 键名（如 `_wc_variation_selector_*`、`_size_guide*`），以兼容 `feed-export` 等工具。
- 后台仅数据录入可用 jQuery / `wp_editor`；前台新品必须用 TS。
- 自定义数据库表（如 `wc_size_guide_templates`）在功能未迁完前**不要删除**。

---

## 前台配置注入

单品页合并进 `window.tailwindscoreProduct`（见 [tailwindscore_product_page_config()](D:/www/base/wp-content/themes/tailwindscore/modules/variations/includes/class-variation-settings.php)）：

- `gallery`：图库 SSR / Swiper 配置
- `variations`：选择器 enabled、属性列表、颜色模式等

避免多个 inline script 互相覆盖；只在一处（[inc/enqueue.php](D:/www/base/wp-content/themes/tailwindscore/inc/enqueue.php)）输出。

---

## 延后 / 不在范围的功能

| 功能 | 状态 | 预留 |
|------|------|------|
| Product gallery | ✅ 已迁 `modules/gallery` | — |
| Variation selector | ✅ 已迁 `modules/variations` | — |
| Size guide | ⏸ 未迁 | `do_action( 'tailwindscore_after_size_attribute_label', $attribute_name, $product )`；meta/表保留 |
| Quick View 变体 UI | ⏸ | 不 enqueue 归档页选择器脚本 |
| Related products | ✅ 已迁 `modules/related-products` | `Related_Products_Manager` |
| Gift wrap 等其它插件类 | ⏸ | 按本规范独立 `modules/{slug}` |

### Size Guide 特别说明（未迁）

- 不实现 `#size-guide` modal、`Product_Size_Guide` 前台。
- 数据 `_size_guide`、`_size_guide_template_id`、`wp_wc_size_guide_templates` 保留在库中。
- 未来新建 `modules/size-guide`，独立 dialog + 解析函数 `tailwindscore_resolve_size_guide()`，仅挂上述 action，不写入 variations 模块。

---

## 模块登记表（Module registry）

| 模块 | 路径 | 状态 | 插件源类 |
|------|------|------|----------|
| gallery | `modules/gallery` | ✅ v0.1.0 | `Variation_Images_Manager` 等 |
| variations | `modules/variations` | ✅ v0.1.0 | `Variation_Selector` |
| size-guide | — | ⏸ | `Product_Size_Guide` |
| quick-view | — | ⏸ | 插件 Quick View |
| related-products | `modules/related-products` | ✅ v0.1.0 | `Related_Products_Manager` |
| review-images | `modules/review-images` | ✅ v0.1.0 | `Review_Images`、`Showcase_Gallery` |

### 独立插件（不迁入 modules）

以下保持 `wp-content/plugins/` 安装；前台 JS/CSS 由主题按 [资源整合规范](./2026-05-23-plugin-theme-asset-integration-design.md) 接管：

| 插件 | 整合状态 |
|------|----------|
| wc-enhanced-user | ⏸ 见资源整合登记表 |
| wc-mailnest | ⏸ 见资源整合登记表 |
| wc-order-process-manager | ⏸ 见资源整合登记表 |
| feed-export | — 仅后台，跳过 |

---

## 验证清单

```bash
npm run typecheck
npm run build
```

手动（可变商品单品页）：

1. 颜色 grid/list、形状、grid 文案开关  
2. 产品后台：禁用属性图片、覆盖颜色默认模式  
3. 选变体 → 价格、`variation_id`、图库切换  
4. 未选全 → 加购拦截  
5. 插件 inactive → 无重复脚本/样式  
6. URL `?attribute_pa_color=slug` 预填  
7. 单品页相关产品数量 ≈ Customizer 设置；子分类不足时含父分类商品；插件 inactive 无重复区块  

---

## 新会话 / AI 工作指引

1. 阅读 **本文** 确认边界与登记表。  
2. 若任务来自 **独立插件**（非 wc-enhanced-tools），改读 [资源整合规范](./2026-05-23-plugin-theme-asset-integration-design.md)。  
3. 阅读目标模块的 `plans/*-implementation.md` 与 `CHANGELOG.md`。  
4. 对照插件源文件做**只读**参考，在主题中实现，不复制 Bootstrap/option 依赖。  
5. 完成后更新对应 **模块登记表** 或 **插件整合登记表** 与 `CHANGELOG.md`。  
6. 不要编辑 `.cursor/plans/*.plan.md`（计划附件仅供人类追溯）。

---

## 参考实施计划

- [2026-05-22-variations-module-implementation.md](../plans/2026-05-22-variations-module-implementation.md)
- [2026-05-23-plugin-theme-asset-integration-design.md](./2026-05-23-plugin-theme-asset-integration-design.md)（独立插件）
