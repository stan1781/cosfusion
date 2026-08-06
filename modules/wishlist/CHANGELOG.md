# Wishlist Module

## 0.1.4

- Account 列表：删除按钮请求期间显示 loading（与加购按钮同一套 `setControlLoading`）；补齐 `.btn-icon.is-loading` spinner 样式。
- Account 列表价格：套用全局 `ts-price-html`（原价删除线 + 特价强调）。

## 0.1.3

- 精简：去掉未用 `context` / `accountUrl` / `networkError` 与列表无用 data 属性；账户菜单数量文案走 i18n；徽章同步仅登录路径。

## 0.1.2

- 账户菜单：Wishlist 使用心形图标；头部下拉与 My Account 导航显示数量徽章（增减时 JS 同步）。

## 0.1.1

- 精简：仅收藏父商品（忽略变体）；旧 variation 数据归一到父 ID。
- Account 列表：可变品用 Quick View 选规格加购；简单商品直接加购。
- 修复：Wishlist 页正确注册 WC query var，并加载 Quick View（此前按钮无响应）。

## 0.1.0

- 独立心愿单模块：访客 localStorage、登录用户 user meta、登录后并集合并。
- REST `tailwindscore/v1/wishlist*`（GET / toggle / DELETE / merge）。
- 归档卡片与单品页心形；My Account `wishlist` endpoint 列表。
