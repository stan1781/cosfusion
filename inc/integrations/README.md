# 独立插件前台资源整合

本目录存放「插件逻辑在插件、前台 JS/CSS 在主题」的整合代码。

**规范文档**：[docs/integrations/README.md](../../docs/integrations/README.md)

在 `functions.php` 中按插件逐个 `require_once` 对应文件。改造某插件前请先更新规范内的 **插件整合登记表**。
