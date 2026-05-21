# WooCommerce 购买流程设计

**目标**

重构主题中的 WooCommerce 购买流程，使主要用户路径变为 `product -> cart drawer -> checkout`，同时将 `cart` 页面降级为轻量级兜底页面。`drawer` 将成为购物车的主要交互界面，而 `checkout` 继续承担正式下单流程的职责。

**产品方向**

- `cart drawer` 是主要购物容器。
- `cart` 页面不再是首选编辑界面。
- 用户应当可以在不离开当前页面的前提下，完成大多数结账前购物车操作。
- `checkout` 仍然是账单、配送、支付和订单复核的权威页面。

**范围**

这一轮包含：

- 将头部 `cart drawer` 重构为可复用的 WooCommerce 视图层
- 让单品页 `add-to-cart` 流程与 `drawer-first` 行为保持一致
- 轻量化重做 `cart` 页面
- 让 `checkout` 模板在文案和反馈模型上与 `drawer/cart` 保持一致
- 在 `drawer` 流程中支持优惠码入口
- 建立统一的通知接口，支持 `toast` 与 `inline/persistent` 多种承载方式

这一轮不包含：

- 交叉销售、加购推荐或商品推荐
- `drawer` 内运费预估
- 多步骤 `checkout`
- 一次性替换全站所有 WooCommerce notice
- 后台通知行为配置项

## 架构

购买流程将拆分为四层：

1. **WooCommerce 页面模板**
   `woocommerce/*.php` 文件保留为页面级骨架和 Woo hook 编排层。

2. **可复用的 WooCommerce 视图片段**
   新增 `template-parts/woocommerce/*` 文件，承载 `drawer`、`cart`、`checkout` 以及单行商品项等可跨界面复用的标记结构。

3. **WooCommerce 领域 helper**
   `inc/woocommerce/*` 负责 URL、文案、配置载荷、购物车渲染 helper、通知映射以及购买流程相关功能开关。

4. **前端交互模块**
   `src/ts/cart`、`src/ts/product` 和 `src/ts/checkout` 负责 Store API 调用、UI 增强、DOM 刷新和通知分发。它们不应承担页面结构决策。

边界规则很简单：

- 页面模板决定页面包含哪些区块
- `template part` 决定这些区块如何渲染
- `helper` 决定暴露什么数据和配置
- TypeScript 决定用户操作后界面如何响应

## 购买流程行为

### 主要路径

1. 用户在单品页添加商品。
2. 主题通过当前 `drawer` 流程已使用的 Store API 路径更新购物车状态。
3. 加购成功后自动打开 `drawer`。
4. 用户在 `drawer` 内修改数量、删除商品或应用优惠码。
5. 主 CTA 直接将用户带到 `checkout`。

### `cart` 页面角色

`cart` 页面将变成轻量级兜底界面：

- 仍然保留，以兼容直接访问链接和 WooCommerce 默认行为
- 不再被视为主要编辑目的地
- 在可行时复用与 `drawer` 相同的商品行与汇总文案
- 它的主要职责是概览当前订单状态，并引导用户进入 `checkout`

### `checkout` 页面角色

`checkout` 继续作为以下内容的权威界面：

- 客户信息
- 配送信息
- 支付方式选择
- 最终订单复核与提交

`checkout` 不应成为第二个购物车编辑器。数量和优惠码反馈仍然应当可被理解，但 `drawer` 仍是首选的购物车操作位置。

## 视图模型与模板结构

### 新增或扩展的视图片段

- `template-parts/woocommerce/cart-drawer-content.php`
  `drawer` 主内容区域，包含商品行、小计、优惠码区、反馈区域和 `checkout` CTA。

- `template-parts/woocommerce/cart-line-item.php`
  单个商品行的共享片段，供 `drawer` 和轻量 `cart` 页面复用。

- `template-parts/woocommerce/cart-summary.php`
  小计和操作层级相关的共享汇总区块，在适合复用时使用。

- `template-parts/woocommerce/cart-feedback.php`
  用于库存问题、优惠码问题和购物车状态警告的 `inline` 或持久反馈区。

现有的 `template-parts/header/cart-drawer.php` 应降为一个壳层，用来挂载 WooCommerce 专用的 `drawer` 内容，而不再内联完整购物车展示结构。

### 页面模板职责

- `woocommerce/cart/cart.php`
  只保留轻量 `cart` 页面骨架，组合共享商品行与汇总片段，在必要时保留 Woo 兼容 hook，并保持清晰的 `checkout` 路径。

- `woocommerce/checkout/form-checkout.php`
  保留 WooCommerce 的结账机制，同时让标题、反馈层级和汇总文案与 `drawer/cart` 流程保持一致。

- `woocommerce/single-product.php` 及相关单品交互流程
  保留 WooCommerce 的商品渲染方式，同时确保 `drawer-first` 的加购行为仍然是默认主路径。

## 领域 Helper 职责

购买流程相关的 helper 逻辑应收拢到 `inc/woocommerce/` 下，而不是继续扩展 `inc/template-tags.php`。

预期 helper 职责包括：

- `cart` 与 `checkout` URL helper
- 购物车数量与购物车摘要文案 helper
- 提供给前端使用的 `cart drawer` 配置序列化
- 优惠码能力开关与相关文案
- 通知载荷标准化
- 可复用购物车片段的服务端渲染入口

`inc/template-tags.php` 应只保留站点级通用关注点。购买流程渲染 helper 应从中迁出。

## 通知策略

购买流程需要统一的通知接口，因为 `drawer-first` 路径由局部更新驱动，而不是传统页面跳转。

### 决策

**不要**把所有通知都做成 `toast`。

应该统一成一套支持多种承载面的通知契约：

- `toast`
- `inline`
- `drawer`
- `page`

建议的前端 API 形态：

```ts
notify({
	type: 'success' | 'info' | 'warning' | 'error',
	message: string,
	surface: 'toast' | 'inline' | 'drawer' | 'page',
	context?: string,
});
```

具体签名可以在实现阶段微调，但系统必须支持通知落点选择，而不是把所有行为都硬编码成 `toast`。

### 通知规则

以下短时操作结果反馈使用 **toast**：

- 加购成功
- 优惠码应用成功
- 商品删除成功

以下与当前购物车内容强相关的问题使用 **drawer 内联或持久反馈**：

- 库存不足
- 数量被上限限制或自动修正
- `drawer` 流程中的无效优惠码
- 商品不可用等购物车状态问题

以下需要用户修正表单或处理支付问题的错误使用 **checkout inline/page 反馈**：

- 账单或配送信息校验错误
- 支付失败
- 缺少必填结账选项

### 为什么有必要

WooCommerce 默认的页面级 notice 假设的是一个强依赖页面跳转的流程。新的购买路径则是一个高交互、以 `drawer` 为中心的流程。没有统一通知层，反馈就会在 `drawer` 内容、`checkout` 标记和传统 Woo notice 之间变得不一致、不可见或重复出现。

### 这一轮的承诺

这一轮应该：

- 定义通知契约
- 增加支持 `toast` 的 UI 承载层
- 让购买流程动作统一走通知分发入口
- 在暂不适合完全替换的地方保留 Woo notice 兼容性

这一轮不尝试做全站 notice 重写。

## 交互要求

### Drawer

`drawer` 必须支持：

- 数量修改
- 商品移除
- 小计展示
- 直达 `checkout` 的 CTA
- 优惠码入口
- 清晰的库存和优惠码反馈

`drawer` 应该表现为主要购物车，而不是一个迷你预览层。

### Cart 页面

`cart` 页面应当：

- 保持可访问和可用
- 在编辑强调上比 `drawer` 更轻
- 不再鼓励把长时间整理购物车作为主路径
- 保留 WooCommerce 对直接访问 `cart` 页的兼容预期

### Checkout

`checkout` 应当：

- 继承与购买流程其余部分一致的内容层级
- 让订单汇总和反馈文案与 `drawer/cart` 流程保持一致
- 避免把重要支付和校验错误吞进短暂通知里

## 错误处理

实现中必须明确以下行为：

- Store API 失败时不能静默失败；用户需要看到有上下文的反馈。
- 因库存上限导致的数量修正必须在 `drawer` 或相关内联区域可见。
- 优惠码失败必须绑定到优惠码输入区域，而不是只显示为通用页面 notice。
- 如果增强后的 JS 路径失败，`add-to-cart` 的兜底提交行为仍然必须可用。
- 在 WooCommerce 未启用的情况下，页面应继续安全降级，不能破坏头部渲染。

## 验证预期

实现应在三个层面完成验证：

1. **PHP 语法**
   对所有改动过的 PHP 模板和 helper 文件执行 lint。

2. **TypeScript 正确性**
   运行 `npm run typecheck`。

3. **构建与交互基本验证**
   在环境允许时运行 `npm run build`，并手动验证：
   - 单品页加购后会打开 `drawer`
   - `drawer` 内数量更新可用
   - `drawer` 内删除商品可用
   - 优惠码反馈出现在正确位置
   - `checkout` 仍然能在上下文中展示阻塞型校验错误

## 实施边界

这项工作应严格聚焦于购买流程本身，不应扩展成一次通用主题清理。重构只在以下方面直接产生收益时才成立：

- 购买流程模板边界
- 购买流程状态归属
- 通知一致性
- `drawer-first` 用户路径

## 成功标准

当满足以下条件时，这份设计可视为完成：

- `drawer` 明确成为主要购物车交互界面
- `cart` 页面明显更轻，不再是首选编辑路径
- `checkout` 稳定，并与购物车编辑职责保持清晰分离
- 购买流程模板与 helper 拥有更清晰的边界
- 通知行为在接口层实现统一，且 `toast` 是被选择性使用，而不是无差别滥用
