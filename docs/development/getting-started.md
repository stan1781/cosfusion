# 开发入门

## 环境要求

| 依赖 | 版本建议 |
|------|----------|
| WordPress | 6.x+ |
| PHP | 8.0+ |
| WooCommerce | 8.x+（商城功能） |
| Node.js | 18+ |
| Kirki Framework | 推荐安装（Customizer 字段；未安装时使用代码默认值） |

## 本地设置

1. 将主题放入 `wp-content/themes/tailwindscore` 并激活
2. 安装并配置 WooCommerce
3. **禁用 `wc-enhanced-tools`**（若已安装），避免与主题模块冲突
4. 安装依赖并构建：

```bash
cd wp-content/themes/tailwindscore
npm install
npm run build
npm run typecheck
```

## Vite 开发模式

```bash
npm run dev
```

在 `wp-config.php` 添加：

```php
define( 'TAILWINDSCORE_VITE_DEV', true );
```

主题会检测 `http://localhost:5173` 是否在线，自动加载 HMR 客户端与源模块。

生产环境 **不要** 定义 `TAILWINDSCORE_VITE_DEV`，确保使用 `assets/dist/` 构建产物。

## 常用命令

| 命令 | 说明 |
|------|------|
| `npm run dev` | Vite 开发服务器 |
| `npm run build` | 生产构建 + 清理孤儿 dist 文件 |
| `npm run typecheck` | TypeScript 类型检查 |
| `npm run clean:dist` | 删除 `assets/dist/` |
| `npm run package` | 打可上传 zip（不 bump 版本，须已 build） |
| `npm run release` | patch +1 → build → `release/tailwindscore-{ver}.zip` |
| `npm run release:minor` | minor +1 → build → zip |
| `npm run release:major` | major +1 → build → zip |

## 代码规范速查

- **PHP**：`declare(strict_types=1);`，WordPress 编码规范，转义输出
- **SCSS**：遵循 [设计令牌](../architecture/design-tokens.md)，组件用 `ts-up`/`ts-down` mixin
- **TypeScript**：断点用 `src/ts/breakpoints.ts`，商城逻辑放 `src/ts/` 对应域目录
- **模板**：语义类 `.ts-*`，避免 Bootstrap / 大量 Tailwind 原子类

## 目录速查

| 任务 | 先看 |
|------|------|
| 改头部/导航 | `inc/navigation/`、`docs/features/site-shell.md` |
| 改购物车/结账 | `inc/woocommerce/`、`src/ts/cart/`、`docs/features/purchase-flow.md` |
| 改单品页 | `woocommerce/single-product/`、`modules/`、`src/ts/product/` |
| 改样式令牌 | `src/scss/abstracts/_variables.scss` |
| 加主题模块 | `docs/modules/README.md` |
| 整合独立插件 | `docs/integrations/README.md` |

## 提交前检查

```bash
npm run typecheck
npm run build
```

涉及 PHP 模板时，在环境中做一次相关页面冒烟测试。

## 文档索引

完整文档目录：[docs/README.md](../README.md)
