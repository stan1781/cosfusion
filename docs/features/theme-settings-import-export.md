# 主题设置导入 / 导出

外观后台对 **已注册 Kirki `theme_mod`** 做白名单 JSON 迁移（非全量 `get_theme_mods()`）。对照 Starcore 同名能力；不含菜单、Widget、内容区块 CPT、媒体库附件侧载。

## 入口

| 项 | 说明 |
|----|------|
| 菜单 | **外观 → 导入/导出** |
| 能力 | `edit_theme_options` |
| 实现 | [`inc/config/kirki/import-export.php`](../../inc/config/kirki/import-export.php) |
| Schema | `tailwindscore.kirki.v1`（不接受 Starcore JSON） |
| 本机快照 | option `tailwindscore_kirki_local_backup` |

## 流程

1. **导出**：可选是否包含站点专属 ID → 下载 JSON。
2. **导入**：上传 → 预览（新增 / 覆盖 / 跳过计数）→ 确认写入。
3. 确认前自动写入本机快照；亦可手动保存 / 还原快照。
4. 预览 transient 绑定当前用户，15 分钟过期。

## 范围

| 项 | 说明 |
|----|------|
| 白名单 | Kirki 已注册且 `option_type = theme_mod` 的键；排除 `custom` 占位 |
| 站点专属键 | 跨站默认导入跳过；测试站→正式站且同库同 ID 时两边勾选 |
| 媒体键 | 导入时可将来源站 `home_url` 替换为本站（无附件侧载） |

### 站点专属键

| `theme_mod` | 说明 |
|-------------|------|
| `shipping_hide_rates_ids` | 免运达标后隐藏的付费 rate ID |
| `not_found_products_category_id` | 404 人气商品分类 ID |
| `shop_archive_header_bg_default` | 归档 Hero 背景（附件 ID） |
| `shop_page_header_bg` | 商店页 Hero 背景（附件 ID） |
| `footer_payment_image` | 页脚支付图（附件 ID） |

### 媒体键（可 URL 替换）

`tailwindscore_logo_pc`、`tailwindscore_logo_mobile`、`account_auth_hero_image`、`not_found_illustration`、`home_hero_slides`、`not_found_categories`、`header_bar_items`、`product_assurances_items`。

### 排除键

`footer_nav_menu_notice`、`tailwindscore_checkout_settings_notice`（及所有 `type === custom`）。

## 过滤器

| 钩子 | 用途 |
|------|------|
| `tailwindscore/kirki/export/setting_keys` | 可导出键列表 |
| `tailwindscore/kirki/export/site_bound_keys` | 站点专属键 |
| `tailwindscore/kirki/export/site_bound_key_labels` | 站点专属键说明 |
| `tailwindscore/kirki/export/media_keys` | 媒体 / URL 键 |
| `tailwindscore/kirki/export/excluded_keys` | 强制排除 |

## 安全

仅 `json_decode`；nonce + `edit_theme_options`。目标站媒体需已存在或接受断链后在 Customizer 重选。

## 相关

- [路线图：Starcore 升级评估](../roadmap/starcore-upgrades.md)
- Starcore：`docs/architecture/customizer.md` 导入/导出一节
