# Recently viewed

访客浏览单品后记录父商品 ID，在空购物车与单品 Related 上方展示轮播。

## 存储

| 层 | 键 | 说明 |
|----|-----|------|
| localStorage | `ts_recently_viewed` | JSON 数组，最近在前，最多 12 |
| Cookie | `ts_recently_viewed` | 逗号分隔 ID，供 PHP SSR；与 localStorage 同步写入 |

不写 user meta（代发站轻量）。

## 短代码

```text
[ts_recently_viewed limit="8" title="Recently viewed" exclude="0"]
```

挂载点：`[data-ts-recently-viewed-host]`；客户端用 REST `GET /wp-json/tailwindscore/v1/recently-viewed?ids=&exclude=&limit=` 水合。

## 默认挂载

| 位置 | 开关（Kirki） |
|------|----------------|
| 购物车空态下方 | `recently_viewed_show_on_cart`（默认开） |
| 单品 Related 上方（`woocommerce_after_single_product_summary` 优先级 18） | `recently_viewed_show_on_product`（默认开） |

Customizer：商品详情页 → Recently viewed。

## 实现

- PHP：`inc/woocommerce/recently-viewed.php`
- TS：`src/ts/product/recently-viewed.ts`（`app.ts` 启动；购物车空态重绘调用 `mountRecentlyViewedHosts`）
- 列表复用 [产品轮播](product-carousel.md) 壳层
- 可变品 QV：REST 水合也会输出眼睛触发器（见 Quick View `should_emit_loop_trigger`）；简单品仍为列表加购

## 闪烁防护

- 先初始化 SSR 轮播，再 REST 水合；**商品 ID 序列相同则不 `innerHTML` 替换**（避免首图拆掉重载）
- localStorage 为空时保留 cookie SSR，不清空挂载点
- 当前商品写入浏览记录放在水合之后，避免本页列表被前置后整段重绘
