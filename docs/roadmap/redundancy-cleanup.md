# 冗余精简与逻辑优化路线图

本文档是 TailwindScore **去重 / 精简 / 冗余逻辑复核** 的正式路线图（与 [Starcore 升级评估](starcore-upgrades.md) 分开：后者是能力移植，本表是存量清理）。

**范围收窄（2026-08-06，用户决定）：** 后续阶段**不再做文件拆分 / 组织重构**，仅检查并处理冗余代码与冗余逻辑；原 Phase 5「组织拆分」已相应改为「大文件冗余复核（不拆分）」。

**审计日期：** 2026-08-06  
**Phase 0 状态：** Done（本文件）  
**Phase 1 状态：** Done（2026-08-06）  
**Phase 2 状态：** Done（2026-08-06）  
**Phase 3 状态：** Done（2026-08-06；范围调整为不拆文件，见 Phase 3 章节）  
**Phase 4 状态：** Done（2026-08-06）  
**Phase 5 状态：** Done（2026-08-06）  
**下一闸门：** 手测 Phase 5 清单后，再单独开 Phase 6（默认不开，需产品/技术评审）；**禁止**自动连跑多阶段。

## 原则

1. **行为不变优先**：默认不改前台交互、Store API 契约、产品 meta key。
2. **对外 API 稳定**：至少保持 `refreshCart`、`addCartItem`、`notify`、`initProductCarousels` 等现有导出名与语义。
3. **只删有证据的死代码**：无调用方、文档已废弃且代码无引用，才删除。
4. **一阶一验**：每阶段 `npm run build` + `npm run typecheck`（见 [开发入门](../development/getting-started.md)）+ 该阶手测清单通过后，才开下一阶。
5. **可独立回滚**：每阶段变更集中、提交边界清晰。
6. **文档同步**：结构或行为有用户可见变化时，更新对应 `docs/features/` / `docs/modules/`（简体中文）。
7. **不做文件拆分**（2026-08-06 起）：后续阶段仅检查并处理冗余代码 / 冗余逻辑，不进行文件拆分或目录重组；文件体积过大本身不再作为独立整改项。

## 规模快照（审计时）

| 区域 | 约文件数 | 说明 |
|------|----------|------|
| `modules/` | 69 | 10 个模块均登记为可用，非整块死模块 |
| `src/ts/` | 68 | 最大：`cart/index.ts` ~1182 行 |
| `inc/` | 62 | `inc/woocommerce/` 占比高 |

主题问题主要是 **工具重复**、**单文件职责过重**、**遗留桥接**，不是「删掉半个模块」。

## 现状热点表

| # | 项 | 类型 | 价值 | 风险 | Phase | 路径 / 备注 |
|---|-----|------|------|------|-------|-------------|
| 1 | `escapeHtml` 六处副本 | 重复 | 中 | 低 | 1 | `cart/index.ts`、`cart/notifications.ts`、`gallery-photoswipe.ts`、`review-lightbox.ts`、`size-guide.ts`、`search/ajax-live.ts`（后者用 `&#39;`，其余 `&#039;`，抽取时统一） |
| 2 | Swiper `parseSlideCount` / `parseGap` 双份 | 重复 | 中 | 低 | 1 | `shop/product-carousel.ts`、`home/hero-carousel.ts`；已有 `src/ts/swiper/wrap-navigation.ts` |
| 3 | 可删 `@deprecated` 无调用方 | 遗留 | 中 | 低 | 2 | **已删除**（2026-08-06）：`tailwindscore_cart_discounted_subtotal()`（`shipping-rules.php`）、`tailwindscore_archive_category_url_with_filters()`（`archive/attribute-filters.php`）；主题内确认零调用方，`php -l` 通过 |
| 4 | Header bar legacy fallback / migrator | 遗留 | 低–中 | 低–中 | 2 | **已删除**（2026-08-06，用户确认生产无依赖）：`tailwindscore_header_bar_legacy_items()`、`tailwindscore_header_bar_maybe_migrate_legacy()` 及其 `add_action`；`header_bar_items` 为唯一数据源。同步更新 [site-shell.md](../features/site-shell.md) |
| 4b | Mega menu 一次性清理 | 遗留 | 低 | 低 | 2 | `inc/navigation/nav.php` `tailwindscore_maybe_delete_legacy_mega_menu_option()`；已用 `tailwindscore_legacy_mega_option_removed` 自我加锁，成本极低，**保留**（无需在本阶段删除该清理函数本身） |
| 5 | 购物车「全局分类 ID」已废弃 | 遗留 | 低 | 低 | 2 | **已核实**：`cart-product-suggestions.php` 与 Kirki 注册均无该字段；代码侧已完全干净，文档「已废弃」为历史记录，无需进一步动作 |
| 6 | `cart/index.ts` 上帝文件 | 过大 | 高 | 中 | 3 | Store API + HTML 渲染 + 优惠券 + 免运进度 + 绑定；导出：`refreshCart`、`addCartItem`、re-export `notify` |
| 7 | Modal / dialog 开闭散落 | 重复模式 | 中 | 中 | 4 | **原生 `<dialog>`**：`size-guide.ts`、`review-lightbox.ts`；**`hidden` + body class**：`login-modal.ts`、`item-edit-modal.ts`、`quick-view.ts`、`review-form-modal.ts` 等。抽薄辅助，不改 markup 语义 |
| 8 | `inc/not-found.php` ~568 行 | 过大（观察） | 低 | 低 | 5 | Kirki + 插图 + 分类 repeater + 人气商品；**不拆分**；已去重 5 处重复 `active_callback` 闭包，**Done** |
| 9 | `review-images` PHP 体积 | 过大（观察） | 中 | 低 | 5 | admin 669 / frontend 623 / upload 470 / showcase 330；**不拆分**；已抽取国家列表唯一数据源 + 查询去重 + 删 3 个死方法，**Done** |
| 10 | 建议引擎边界 | 假冗余 | — | 高若硬合并 | 6 | `product-suggestions.php`（cart/PDP 共享） vs `modules/related-products`（单品页底）；PDP cross-sells 紧凑列表 vs 购物车轮播壳——**有意双轨** |
| 11 | PhotoSwipe vs 评论灯箱 | 假冗余 | — | 高若统一 | 6 | `gallery-photoswipe.ts` vs `review-lightbox.ts`；无产品决策则保持分离 |
| 12 | `variations.ts` 仍偏厚 | 过大 | 中 | 中 | 6 或另开 | sticky / URL / display；核心已在 `variation-engine.ts`；QV 侧已薄。非早阶 |
| 13 | SCSS 疑似重复 | 观察 | 低 | 中（视觉） | 不入早阶 | 大范围选择器去重易视觉回归；有具体重复证据再单开任务 |

### 已较干净（勿「为清理而合并」）

- 产品轮播壳：`product-carousel.ts` + PHP 短代码，首页 / 购物车 / 404 共享
- QV 复用 `variation-engine`（`quick-view-variations.ts` 很薄）
- Size guide 表格 HTML 可导出给详情 Tab
- Cart / PDP suggestions 已集中在 `inc/woocommerce/product-suggestions.php` 低层 helper

## 阶段定义

```text
Phase 0 (Audit_Doc) → 1 SharedUtils → 2 LegacyPrune → 3 CartDedup
  → 4 DialogHelper → 5 LargeFileDedup(不拆分) → 6 OptionalBoundaries
```

### Phase 0 — 审计与路线图

| 项 | 内容 |
|----|------|
| 交付 | 本文档 + [docs/README.md](../README.md) 入口 |
| 行为变更 | 无 |
| 验收 | 文档评审通过 |
| 进度 | **Done** |

### Phase 1 — 共享 util（低风险）

| 项 | 内容 |
|----|------|
| 动作 | 新增 `src/ts/utils/escape-html.ts`、`src/ts/swiper/parse.ts`；替换全部本地副本；统一实体转义风格 |
| 不改 | 任何业务分支、DOM 结构、i18n 文案 |
| 验证命令 | `npm run build` && `npm run typecheck` |
| 手测 | 首页 Hero 轮播；任意产品轮播；加购 toast/Drawer 文案；PhotoSwipe 标题；尺码表 modal；AJAX 搜索结果；评论灯箱文案（含特殊字符） |
| 进度 | **Done**（2026-08-06；`typecheck` + `build` 已通过，待前台手测） |

### Phase 2 — 遗留修剪（低–中风险）

| 项 | 内容 |
|----|------|
| 已删除 | `tailwindscore_cart_discounted_subtotal`、`tailwindscore_archive_category_url_with_filters`（主题内无引用，`php -l` 通过）；header_bar `header_bar_text` legacy fallback + 一次性 migrator（用户确认生产无依赖后删除） |
| 已核实无需动作 | mega menu one-shot 清理函数自带加锁，成本低，保留；购物车全局分类 ID 代码侧已干净 |
| 保留（禁区内） | size-guide `legacyHtml` 路径（内容未迁完前）；mailnest 表单 legacy nonce 字段 |
| 手测 | 商店分类链接（`tailwindscore_archive_category_url` 未受影响）；免运进度条金额计算仍正确；Header bar：设置多条 repeater 消息 / 仅一条 / 未配置时的默认文案，均正常渲染与轮播 |
| 进度 | **Done** |

### Phase 3 — `cart/index.ts` 去重与逻辑优化（不拆分文件）（低–中风险）

**范围调整（2026-08-06，用户决定）：** 原计划拆分为 `cart/api.ts` / `cart/render.ts` / `cart/coupons.ts` 等多文件；改为**只做内部去重与优化，保持单文件**。此后本路线图**不再对文件体积过大本身做结构性拆分**（见「原则」第 7 条），仅作为观察项记录，不在任何后续阶段强制执行拆分。

| 项 | 内容 |
|----|------|
| 必须保持 | `export async function refreshCart`、`addCartItem`；`export { notify }`；Drawer 打开 2s 内跳过冗余 GET；nonce TTL / 403 重试 |
| 已实施 | ① 抽取 `renderEmptyCartMarkup(config, wrapperClass)`，消除 `renderCartDrawer`/`renderCartPage` 完全重复的空购物车 markup；② `syncCartUi` 改为仅在 Drawer / 购物车页对应 DOM 存在时才渲染各自的完整 HTML 字符串，避免非购物车页仍计算购物车页 markup（反之亦然）的多余开销 |
| 复核但未改动 | `getCartConfig()` 的内联 fallback i18n/图标字面量（仅在 `data-ts-cart-config` 无 `window.tailwindscoreCart` 时触发，非重复逻辑，属兜底数据）；`bindCouponControls` 与 `applyCheckoutCouponFromButton` 表面相似但服务不同 DOM/流程（购物车表单 vs 结账页），强行合并会引入 `refreshCheckoutOrderReview` 等分支耦合，判定为**假冗余**不合并 |
| 验证命令 | `npm run typecheck` && `npm run build`（均通过） |
| 手测 | 加购→Drawer；改数量 / 删除；优惠券应用与移除；免运进度条；购物车页重绘 + 推荐轮播再 init；item-edit 保存后 `refreshCart`；结账页优惠券；**验证空购物车状态在 Drawer 与购物车页均正常展示**（重点回归项，因改了共享路径） |
| 进度 | **Done**（2026-08-06；范围已按用户决定调整为不拆分） |

### Phase 4 — Dialog 薄辅助（中风险）

| 项 | 内容 |
|----|------|
| 新增 | `src/ts/ui/dialog.ts`：`isHiddenModalOpen`/`showHiddenModal`/`hideHiddenModal`/`restoreFocus`/`bindEscapeToClose`（`hidden`+aria 型公共部分）、`bindBackdropClickToClose`（原生 `<dialog>` 型 backdrop 关闭） |
| 约束 | **不改**各 modal 的 HTML 结构与 CSS 类名；焦点目标解析、returnFocus 存储时机、Esc 是否 `preventDefault` 等差异化行为**原样保留**在各调用方，未被强行统一 |
| 已接入 | `login-modal.ts`、`cart/item-edit-modal.ts`、`shop/quick-view.ts`、`product/review-form-modal.ts`（四者的 `hidden`/`aria-hidden`/body class 切换、Esc 绑定、returnFocus 收尾改为调用共享辅助）；`product/size-guide.ts`、`product/review-lightbox.ts`（backdrop 点击关闭改为调用 `bindBackdropClickToClose`） |
| 顺手统一（用户确认） | quick-view 的 Esc 处理原本未 `preventDefault`（其余三个 `hidden` 型均有），确认无特殊语义依赖后统一为 `preventDefault`；`bindEscapeToClose` 相应简化为无 options 参数 |
| 验证命令 | `npm run typecheck` && `npm run build`（均通过） |
| 手测 | 各 modal 开/关、Esc、焦点返回、与壳层抽屉互斥（登录 modal）；size-guide / review-lightbox 点击 backdrop 关闭 |
| 进度 | **Done**（2026-08-06） |

### Phase 5 — 大文件冗余复核（不拆分）（低风险）

**范围调整（2026-08-06，用户决定）：** 原计划拆分 `inc/not-found.php`、`modules/review-images`；改为**只在原文件内检查并处理冗余代码/逻辑，不拆分文件**，与 Phase 3 的调整原则一致。

| 项 | 内容 |
|----|------|
| 范围 | `inc/not-found.php`（~568 行）、`modules/review-images`（admin/frontend/upload/showcase/kirki/settings） |
| 已实施 — `inc/not-found.php` | 新增 `tailwindscore_not_found_categories_active_callback()`、`tailwindscore_not_found_products_active_callback()` 两个具名函数，替换 5 处内容完全相同的 Kirki `active_callback` 匿名闭包（`not_found_categories_enable` 判断重复 2 处、`not_found_products_enable` 判断重复 3 处），改为传函数名字符串复用 |
| 已实施 — `review-images` 跨文件重复 | `Tailwindscore_Review_Images_Admin::get_country_list()`（16 国家 code=>name）与 `Tailwindscore_Review_Images_Frontend::get_country_info()` 内联的同一份国家数组几乎完全重复（仅顺序不同），存在两处独立维护易漂移的风险；抽取为 `Frontend::get_supported_countries()` 唯一数据源，`Admin::get_country_list()` 改为委托调用。**已知微小影响**：admin 评论 metabox 国家 `<select>` 的选项顺序变化（CA/GB 顺序），纯后台展示顺序，无数据/权限影响 |
| 已实施 — `review-images` 文件内重复 | `class-showcase-gallery.php` 的 `mark_showcase_images_as_used()` / `filter_unused_ids()`、`class-review-images-frontend.php` 的 `mark_review_images_as_used()` / `exclude_review_images_from_unused()` 各自两两重复同一段 `$wpdb->get_col(...)` 查询 + `maybe_unserialize` 循环；分别抽取 `get_all_showcase_selected_image_ids()` / `get_all_review_image_ids()` 私有方法复用 |
| 已实施 — 死代码 | 删除 `Tailwindscore_Review_Images_Admin::register_settings()`、`sanitize_settings()`、`woocommerce_missing_notice()`：均无 `add_action`/`add_filter` 挂载、`wc_review_images_settings` option 全项目零读取，确认为遗留 Settings API 页面的死代码 |
| 复核但未改动 | `admin_enqueue_scripts()` / `modify_comment_author_column()` / `handle_product_reviews_bulk_actions()` 中三处「是否为 WooCommerce 产品评论页」判断逻辑相似但依据变量不同（`$hook` vs `$screen` vs 纯 `$_GET`），强行合并需统一判断依据、有引入行为差异的风险，判定为**假冗余**不合并 |
| 不做 | 按职责拆分为多文件；调整 hook 优先级或函数签名 |
| 验证命令 | `php -l`（4 个改动文件均通过）；`ReadLints` 无错误 |
| 手测 | 404 Hero / 快捷导航 / 人气轮播（含仅启用其一/关闭快捷导航或人气商品时 Customizer 面板字段显隐是否正常）；评论附图上传与 Showcase；后台评论 metabox 国家下拉选择与保存；后台评论列表批量标记认证买家/取消/设置国家 |
| 进度 | **Done**（2026-08-06） |

### Phase 6 — 可选边界（高风险，单独评审）

| 项 | 内容 |
|----|------|
| 可做 | 文档化 suggestions / related / cross-sells 边界；极小共享 query helper（若仍有重复） |
| 不做除非产品要求 | 合并 PhotoSwipe 与评论灯箱；合并 related-products 与 cart 瀑布算法 |
| 进度 | **Pending（默认不开）** |

## 禁区（早阶不可动）

- Gallery ↔ variation 图库契约（`modules/gallery`、`gallery.ts` / `gallery-variations.ts`）
- 产品 meta key 名（遗留 `_variation_images` 已迁官方 gallery、`_size_guide*`、`_wc_variation_selector_*` 等）
- Store API 购买路径核心语义（nonce TTL、Drawer auto-open、加购后同步）
- `variation-engine.ts` 核心（UI 层清理另议）
- Wishlist 主题模块 vs WPC Smart Wishlist 插件边界
- 插件业务逻辑并入主题（见 [integrations](../integrations/README.md)）
- 以归档 `docs/archive/superpowers/` 为「活债务工单」重写实现

## 进度总表

| Phase | 状态 | 备注 |
|-------|------|------|
| 0 审计文档 | Done | 2026-08-06 |
| 1 共享 util | Done | 2026-08-06；`escape-html.ts` + `swiper/parse.ts` |
| 2 遗留修剪 | Done | 2026-08-06；已删 2 个死函数 + header_bar legacy fallback/migrator |
| 3 Cart 去重优化 | Done | 2026-08-06；范围改为不拆文件（用户决定）；已消除空购物车 markup 重复 + `syncCartUi` 惰性渲染 |
| 4 Dialog 辅助 | Done | 2026-08-06；新增 `src/ts/ui/dialog.ts`，接入 6 个 modal，保留各自差异化行为 |
| 5 大文件冗余复核 | Done | 2026-08-06；`not-found.php` 去重 5 处 Kirki active_callback 闭包；`review-images` 抽取国家列表唯一数据源 + 两处查询去重 + 删 3 个死方法 |
| 6 可选边界 | Pending | 需单独产品/技术评审 |

## 工作方式

1. 评审并认可本文档（尤其热点表与禁区）。
2. 明确说「做 Phase N」再改代码；做完更新本表进度与相关功能文档。
3. 验证以 `npm run build`、`npm run typecheck` 为底线；无 E2E 时用手测清单。
4. 能力移植需求走 [starcore-upgrades.md](starcore-upgrades.md)，不塞进本路线图。

## 维护

- 执行某阶时同步更新本文件「进度」列。
- 审计结论过时（大重构后）可再开一轮只读审计并改版本日期。
