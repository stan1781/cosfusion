# 独立插件前台资源整合

本目录存放「插件逻辑在插件、前台 JS/CSS 在主题」的整合代码（`wc-mailnest.php` 等）。

**规范文档**：[docs/superpowers/specs/2026-05-23-plugin-theme-asset-integration-design.md](../../docs/superpowers/specs/2026-05-23-plugin-theme-asset-integration-design.md)

在 `functions.php` 中按插件逐个 `require_once` 对应文件。改造某插件前请先更新规范内的 **插件整合登记表**。
