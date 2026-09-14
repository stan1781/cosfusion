# Wishlist（心愿单）

独立主题模块 `modules/wishlist`：访客可先收藏，登录/注册后并集合并；不依赖 WPC，不扩展 `account-auth`。

## 行为摘要

| 能力 | 说明 |
|------|------|
| 粒度 | **仅父商品**（可变品不区分变体） |
| 访客 | `localStorage` 键 `ts_wishlist` |
| 登录用户 | user meta `_ts_wishlist` |
| 合并 | 登录后页载 `POST /wishlist/merge` 并集去重 |
| 入口 | 归档 / 单品心形、My Account → Wishlist、头部账户菜单（心形 + 数量） |
| 列表加购 | 简单商品 → Store API；可变品 → **Quick View** |
| 上限 | 100 条 |

首版不做：Header 独立角标、Drawer、分享、多列表、Kirki 开关。账户下拉菜单内数量徽章已做。

## REST（`tailwindscore/v1`，需登录）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/wishlist` | 列表 |
| POST | `/wishlist/items` | body `{ productId }` toggle |
| DELETE | `/wishlist/items?productId=` | 删除 |
| POST | `/wishlist/merge` | body `{ items }` |

配置：`window.tailwindscoreWishlist`。

## 与 Quick View

Account Wishlist 页通过 `tailwindscore_quick_view_is_target_page` 加载 QV；列表可变品按钮使用 `data-ts-quick-view`。

## 账户列表样式

`.wishlist-account__name` 默认 `--ts-font-size-xs`（`md+` 升为 `sm`）、`--ts-text-primary`、无下划线；hover 为 `--ts-link-color` + underline。账户页 `body.woocommerce-account` 的 `.entry__content` 仅壳层（与 cart/checkout 相同），避免正文 prose 的主色链接样式污染。

行操作：主按钮（Quick view / Add to cart / View product）与取消收藏同为 `btn btn-sm`；取消收藏用 `btn-outline` + 线框 `heart` + CSS 斜线（非圆形、非 `close`）。手机端：`__info` 用 `display: contents`，价格与操作区同一行；`md+` 恢复 info 块 + 独立第三列。
