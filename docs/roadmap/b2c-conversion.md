# B2C 转化优化路线图

面向 Cosfusion（欧美 B2C、1688 一件代发）的主题侧转化与工程路线。与 [starcore-upgrades.md](starcore-upgrades.md) 对照：本文件是**执行清单**（一阶一验，禁止自动连跑）。

**审计 / 启动日期：** 2026-08-15

## 明确不做

| 项 | 原因 |
|----|------|
| 缺货到货通知 | 一件代发，暂不维护库存召回 |
| 积分 / `wc-enhanced-user` 前台整合 | 暂无扩展计划 |
| 结账手机步进 | Shopify 风格单页；订单摘要折叠已 Done |
| 产品轮播 banner / view_all | 运营不需要 |
| Kirki → `--ts-*` / `tokens-output.php` | 颜色仍以 SCSS 为真源 |

## 阶段总表

| Phase | 内容 | 状态 |
|-------|------|------|
| 0 文档 | 本文件 + 更新 starcore-upgrades / docs/README | Done |
| 1 UI 反馈 | global loading、woo-notices→toast、bottom-sheet 原语 | Done |
| 2 Recently viewed | localStorage + 短代码 + Cart/PDP | Done |
| 3 Section title | 共享标题组件，接入轮播 title | Done |
| 4 PWA L1 | 可安装 + 静态缓存（无专属券） | Done |
| 5 E2E 冒烟 | Playwright：加购 → Drawer | Done |

```text
Phase1_UI → Phase2_RecentlyViewed → Phase3_SectionTitle → Phase4_PWA → Phase5_E2E
```

## 并行 Ops 清单（不改主题代码）

| 项 | 动作 |
|----|------|
| 弃单挽回 | 选用 Klaviyo / AutomateWoo 等；主题不实现弃单模块；确认 GTM `add_to_cart` / `begin_checkout` |
| Product schema | Rank Math / Yoast 预览简单品 + 可变品 JSON-LD；主题不另写 `ld+json` |
| 退换货一致 | 核对 [product-assurances](../features/product-assurances.md) 与 Returns 页、order-process-manager |
| 产线性能 | 按 [performance.md](../architecture/performance.md) 在 Rocket 预热后测首页 / Shop / PDP LCP |
| 结账单页打磨（可选） | Express Pay、摘要折叠手感、错误 banner——**不**引入步进 |

## 工作方式

1. 明确说「做 Phase N」或按本路线顺序改代码；做完更新本表状态与相关 `docs/features/`。
2. 每 Phase：`npm run typecheck && npm run build` + 该阶手测通过后再开下一阶。
3. 前台默认可视文案为英文（[conventions.md](../conventions.md)）。

## Phase 验收摘要

### Phase 1

- 结账优惠券成功 → toast，notice DOM 移除；字段错误仍页内
- QV 打开用弹层内 loading；写操作（如行项编辑保存）用 global loading
- bottom-sheet 原语可用；首个业务消费者在 Phase 4

### Phase 2

- 浏览多单品后空购物车出现 Recently viewed；刷新仍在；当前 PDP 不出现在自身列表

### Phase 3

- `[ts_products_carousel]` 带 `title` 时使用 section title；无 title 与现网一致

### Phase 4

- Kirki 开启后 manifest + SW；关则无；无 PWA 专属券

### Phase 5

- `npm run test:e2e`（需 `TS_E2E_BASE_URL`、`TS_E2E_PRODUCT_PATH`）加购后 Drawer 可见
