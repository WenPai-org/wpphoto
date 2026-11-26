=== WPPhoto ===
Contributors: wenpai
Tags: favicon, site icon, webp, svg, motucloud, image optimization
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0-alpha
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

墨图图库（WPPhoto）：浏览器端站点图标工坊、上传前轻量图像预处理、商用矢量图标库与 SVG 双闸安全净化。

== Description ==

WPPhoto（墨图图库）是文派墨图生态的统一 WordPress 媒体与图标插件，坚持「浏览器端本地处理内核 + 轻量无状态云端图标库」双轨设计：

* **站点图标工坊**：复用 `motu-tools-core` 纯前端渲染内核，一键生成 512×512 PNG 与矢量 `favicon.svg` 并写入 WordPress `site_icon`。
* **SVG 上传安全双闸**：后端 PHP `DOMDocument` 严格白名单清洗，阻断 `<script>`、`<iframe>`、`<object>`、`<embed>`、`<foreignObject>`、`on*` 事件与 `javascript:` 协议。
* **WPCY 4.0 双向保活与白标中枢**：完整接入 `wenpai_plugin_display` 与 `wpcy_service_status`，与免费对象存储插件 WPSSS（文派智储）协同工作。
* **文派自有更新渠道**：通过 `https://updates.wenpai.net` 分发更新。

== Changelog ==

= 0.1.0-alpha =
* 初始化插件非 UI 核心骨架与公共互操作 API（`includes/api.php`）。
* 实现后端 PHP `DOMDocument` SVG 严格白名单清洗器（`WPPhoto_SVG_Sanitizer`）。
* 实现站点图标 REST 接口（`POST /wp-json/wpphoto/v1/site-icon`）及 `wp_head` 矢量图标补充输出。
* 接入 WPCY 4.0 `wpcy_service_status`、`wenpai_plugin_display` 白标、常规设置站点图标快捷入口与 WPSSS 协同探测。
* 同步 vendor `motu-tools-core` 与 `wenpai-admin-ui` v0.13.8；正式后台 UI 模板按 Tier A 门禁待原型 `APPROVAL.md` 签批后落地。
