# 构建系统

TailwindScore 使用 [Vite](https://vitejs.dev/) 编译 TypeScript 与 SCSS，产物输出到 `assets/dist/`，WordPress 通过 `manifest.json` 解析带 hash 的文件名。

## npm 脚本

```bash
npm install
npm run dev        # 启动 Vite 开发服务器（localhost:5173）
npm run build      # 生产构建 + prune-dist 清理孤儿文件
npm run typecheck  # tsc --noEmit
npm run clean:dist # 删除 assets/dist
```

提交前建议执行 `npm run typecheck && npm run build`。

## Vite 入口

配置见 `vite.config.ts`：

| 入口 | 源文件 | 用途 |
|------|--------|------|
| `app` | `src/ts/app.ts` | 全站 TypeScript（按页动态 import 子模块） |
| `styles` | `src/scss/app.scss` | 全站样式 |
| `woo` | `src/scss/woo.scss` | WooCommerce 页面样式 |
| `editor` | `src/scss/editor.scss` | 区块编辑器样式 |

`base: './'` 确保 WordPress 从主题路径加载资源，而非站点根 `/assets/`。

Swiper 通过 `manualChunks` 单独分包为 `swiper` chunk。

## 资源加载（PHP）

`inc/enqueue.php` 负责：

1. 读取 `assets/dist/manifest.json` 解析构建产物路径
2. 注册 `tailwindscore-app`（`type="module"`）与样式句柄
3. 通过 `wp_add_inline_script` 注入 `window.tailwindscore*` 运行时配置
4. WooCommerce 页面额外加载 `woo` 样式

### Vite 开发模式

在 `wp-config.php` 中定义：

```php
define( 'TAILWINDSCORE_VITE_DEV', true );
```

并运行 `npm run dev`。主题会探测 `http://localhost:5173/@vite/client` 是否可达；仅当 flag 开启且 dev server 在线时才走 HMR 路径。

## TypeScript 模块组织

`src/ts/app.ts` 为入口，按页面条件加载子模块：

| 目录 | 职责 |
|------|------|
| `site/` | 头部吸顶、移动导航、菜单抽屉、移动底栏 |
| `cart/` | 购物车 Drawer、通知、购物车页、行项编辑 |
| `checkout/` | 结账加载态 |
| `product/` | 单品：图库、变体、Size Guide、详情 Tab、评论图 |
| `shop/` | 归档：Quick View、列表加购、loop swatches |
| `search/` | AJAX 实时搜索 |
| `account-auth/` | 登录/注册 |
| `integrations/` | 独立插件前台（mailnest、order-process） |
| `home/` | 首页交互 |
| `ui/` | 通用 UI：数量输入、优惠码、登录 modal、loading |
| `breakpoints.ts` | 与 SCSS 对齐的媒体查询常量 |

## SCSS 组织

```text
src/scss/
  abstracts/     # _variables, _root, _mixins
  base/          # 重置、排版基础
  components/    # 站点壳层、drawer、表单等
  woocommerce/   # 商城组件
  app.scss       # 全站入口
  woo.scss       # 商城入口
  editor.scss    # 编辑器
```

组件样式使用 `.ts-*` BEM 风格类名，消费 `var(--ts-*)`。

## 构建产物清理

`scripts/prune-dist.mjs` 在 `vite build` 后删除 manifest 中不再引用的旧 hash 文件。`emptyOutDir: false` 避免 Windows 下 `dist` 被锁定时构建失败。

## 相关文档

- [开发入门](../development/getting-started.md)
- [架构总览](overview.md)
- [设计令牌](design-tokens.md)
