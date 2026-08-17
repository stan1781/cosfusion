# Recently viewed

访客浏览单品后记录父商品 ID，在空购物车与单品 Related 下方展示列表（默认轮播壳）。

## 存储

| 层 | 键 | 说明 |
|----|-----|------|
| localStorage | `ts_recently_viewed` | JSON 数组，最近在前，最多 12 |
| Cookie | `ts_recently_viewed` | 逗号分隔 ID，供 PHP SSR；与 localStorage 同步写入 |

不写 user meta（代发站轻量）。

## 短代码

```text
[ts_recently_viewed limit="8" title="Recently viewed" exclude="0" card="grid" carousel="1"]
```

| 属性 | 默认 | 说明 |
|------|------|------|
| `limit` | `8` | 1–24 |
| `title` | 主题默认 / Kirki | 区块标题 |
| `exclude` | `0` | 排除的商品 ID |
| `card` | `grid` | 与 [产品轮播](product-carousel.md) 相同；`list` 强制关轮播 |
| `carousel` | `1` | `0` 静态布局 |
| `columns_mobile` / `columns_desktop` / `columns_wide` | list：`1` / `2`；grid：`2.5` / `4` | 未写时按 `card` 默认；list 下为 CSS grid 列数 |
| `gap` | `16` | 间距 px |

编辑页侧栏速查见 [主题短代码](shortcode-help.md)。

挂载点：`[data-ts-recently-viewed-host]`（含 card / carousel / columns / gap 的 `data-ts-recently-viewed-*`）；客户端 REST 水合会带回这些参数以免模式丢失。

## 默认挂载

| 位置 | 开关（Kirki） | 展示 |
|------|----------------|------|
| 购物车空态下方 | `recently_viewed_show_on_cart`（默认开） | 竖卡轮播（默认，不受单品页布局字段影响） |
| 单品 Related 下方（`woocommerce_after_single_product_summary` 优先级 25） | `recently_viewed_show_on_product`（默认开） | 由下方「单品页布局」控制 |

Customizer：**外观 → 自定义 → 商品详情页 → Recently viewed**。

### 单品页布局（Kirki）

| `theme_mod` | 类型 | 默认 | 说明 |
|-------------|------|------|------|
| `recently_viewed_product_card` | select | `list` | `grid` 竖卡 / `list` 横卡 |
| `recently_viewed_product_carousel` | toggle | 关 | 仅 `card=grid` 时显示；list 强制关轮播 |
| `recently_viewed_product_columns_mobile` | select 1–4 | `1` | 手机列数 |
| `recently_viewed_product_columns_desktop` | select 1–4 | `2` | 桌面 `md+` 列数 |

读取入口：`tailwindscore_recently_viewed_product_display_args()`。

## 实现

- PHP：`inc/woocommerce/recently-viewed.php`
- TS：`src/ts/product/recently-viewed.ts`（`app.ts` 启动；购物车空态重绘调用 `mountRecentlyViewedHosts`）
- 列表复用 [产品轮播](product-carousel.md) 壳层
- 可变品 QV：REST 水合也会输出眼睛触发器（见 Quick View `should_emit_loop_trigger`）；简单品仍为列表加购

## 闪烁防护

- 先初始化 SSR 轮播，再 REST 水合；**商品 ID 序列相同则不 `innerHTML` 替换**（避免首图拆掉重载）
- localStorage 为空时保留 cookie SSR，不清空挂载点
- 当前商品写入浏览记录放在水合之后，避免本页列表被前置后整段重绘
