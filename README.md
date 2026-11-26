# WPPhoto（墨图图库）

文派（WenPai）墨图生态 WordPress 插件，定位为「浏览器端本地图像内核 + 轻量商用矢量图标源 + WordPress 媒体库闭环」统一载体。

- **产品主页**：https://wpcy.com/photo
- **更新通道**：`https://updates.wenpai.net`
- **当前版本**：`0.1.0-alpha`（非 UI 架构骨架 + 核心 API / REST / SVG 净化 / WPCY 桥接）
- **后台 UI 档位**：Tier A（`wenpai-admin-ui` v0.13.8；正式后台页面模板按治理门禁待 `prototypes/wpphoto/APPROVAL.md` 签批后落地）

## 目录结构

```text
wpphoto/
├── wpphoto.php                             # 插件入口与加载引导
├── includes/
│   ├── api.php                             # 公共 PHP 互操作 API（PLUGIN-INTEROP.md）
│   ├── class-wpphoto-svg-sanitizer.php     # 严格 DOMDocument SVG 白名单净化器
│   ├── class-wpphoto-rest-site-icon.php    # POST /wp-json/wpphoto/v1/site-icon + wp_head 矢量输出
│   ├── class-wpphoto-wpcy-bridge.php       # WPCY 4.0 / 白标 / WPSSS / 常规设置快捷入口桥接
│   └── class-wpphoto-admin-stub.php        # 轻量菜单占位（等待 APPROVAL.md 签批）
├── assets/vendor/motu-tools-core/          # 由 motucloud-tools sync-core.sh 同步的共享前端内核
├── lib/wenpai-admin-ui/                    # 由 vendor-into-plugin.sh 同步的官方 UI Kit（勿手改）
├── docs/README.md                          # 公开文档指引
└── tests/smoke.php                         # 独立 PHP CLI 冒烟测试（无需 WordPress 环境）
```

## 快速验证

```bash
find . -maxdepth 3 -name '*.php' -not -path './lib/*' -print0 | xargs -0 -n1 php -l
php tests/smoke.php
```
