# 冗余精简与逻辑优化路线图

本文档是 TailwindScore **去重 / 精简 / 文件职责拆分** 的正式路线图（与 [Starcore 升级评估](starcore-upgrades.md) 分开：后者是能力移植，本表是存量清理）。

**审计日期：** 2026-08-06  
**Phase 0 状态：** Done（本文件）  
**Phase 1 状态：** Done（2026-08-06）  
**Phase 2 状态：** Done（2026-08-06）  
**下一闸门：** 手测 Phase 2 清单后，再单独开 Phase 3；**禁止**自动连跑多阶段。

## 原则

1. **行为不变优先**：默认不改前台交互、Store API 契约、产品 meta key。
2. **对外 API 稳定**：至少保持 `refreshCart`、`addCartItem`、`notify`、`initProductCarousels` 等现有导出名与语义。
3. **只删有证据的死代码**：无调用方、文档已废弃且代码无引用，才删除。
4. **一阶一验**：每阶段 `npm run build` + `npm run typecheck`（见 [开发入门](../development/getting-started.md)）+ 该阶手测清单通过后，才开下一阶。
5. **可独立回滚**：每阶段变更集中、提交边界清晰。
6. **文档同步**：结构或行为有用户可见变化时，更新对应 `docs/features/` / `docs/modules/`（简体中文）。

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
| 8 | `inc/not-found.php` ~568 行 | 过大（组织） | 低–中 | 低 | 5 | Kirki + 插图 + 分类 repeater + 人气商品；仅拆文件 |
| 9 | `review-images` PHP 体积 | 过大（组织） | 中 | 低–中 | 5 | admin 669 / frontend 623 / upload 470 / showcase 330；可选切块，零行为变化 |
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
Phase 0 (Audit_Doc) → 1 SharedUtils → 2 LegacyPrune → 3 CartSplit
  → 4 DialogHelper → 5 OrgSplits → 6 OptionalBoundaries
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

### Phase 3 — 拆分 `cart/index.ts`（中风险）

| 项 | 内容 |
|----|------|
| 建议拆分 | 例如 `cart/api.ts`（nonce / `requestStoreApi` / `refreshCart` / mutations）、`cart/render.ts`（line item / drawer / page HTML）、`cart/coupons.ts`（drawer + checkout 优惠券绑定）、`cart/index.ts`（编排与 `initializeCartUi`） |
| 必须保持 | `export async function refreshCart`、`addCartItem`；`export { notify }`；Drawer 打开 2s 内跳过冗余 GET；nonce TTL / 403 重试 |
| 手测 | 加购→Drawer；改数量 / 删除；优惠券应用与移除；免运进度条；购物车页重绘 + 推荐轮播再 init；item-edit 保存后 `refreshCart`；结账页优惠券 |
| 进度 | **Pending** |

### Phase 4 — Dialog 薄辅助（中风险）

| 项 | 内容 |
|----|------|
| 动作 | 新增薄层 helper（如 `src/ts/ui/dialog.ts`）：覆盖 showModal/close 与 `hidden`+aria 两套模式的公共部分（Esc、return focus、body class 可选） |
| 约束 | **不改**各 modal 的 HTML 结构与 CSS 类名；逐个接入，可分提交 |
| 候选接入 | size-guide、review-lightbox、login-modal、item-edit-modal、quick-view、review-form-modal |
| 手测 | 各 modal 开/关、Esc、焦点返回、与壳层抽屉互斥（登录 modal） |
| 进度 | **Pending** |

### Phase 5 — 仅组织拆分（低风险）

| 项 | 内容 |
|----|------|
| 动作 | 拆 `inc/not-found.php`；可选拆 `modules/review-images` 过大 PHP（按职责文件，不改 hook 优先级与签名） |
| 手测 | 404 Hero / 快捷导航 / 人气轮播；评论附图上传与 Showcase |
| 进度 | **Pending** |

### Phase 6 — 可选边界（高风险，单独评审）

| 项 | 内容 |
|----|------|
| 可做 | 文档化 suggestions / related / cross-sells 边界；极小共享 query helper（若仍有重复） |
| 不做除非产品要求 | 合并 PhotoSwipe 与评论灯箱；合并 related-products 与 cart 瀑布算法 |
| 进度 | **Pending（默认不开）** |

## 禁区（早阶不可动）

- Gallery ↔ variation 图库契约（`modules/gallery`、`gallery.ts` / `gallery-variations.ts`）
- 产品 meta key 名（`_variation_images`、`_size_guide*`、`_wc_variation_selector_*` 等）
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
| 3 Cart 拆分 | Pending | |
| 4 Dialog 辅助 | Pending | |
| 5 组织拆分 | Pending | |
| 6 可选边界 | Pending | 需单独产品/技术评审 |

## 工作方式

1. 评审并认可本文档（尤其热点表与禁区）。
2. 明确说「做 Phase N」再改代码；做完更新本表进度与相关功能文档。
3. 验证以 `npm run build`、`npm run typecheck` 为底线；无 E2E 时用手测清单。
4. 能力移植需求走 [starcore-upgrades.md](starcore-upgrades.md)，不塞进本路线图。

## 维护

- 执行某阶时同步更新本文件「进度」列。
- 审计结论过时（大重构后）可再开一轮只读审计并改版本日期。
