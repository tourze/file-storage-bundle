# Trix 图片选择器功能使用说明

## 🎯 功能概述

为 EasyAdmin 中的富文本编辑器提供统一的媒体库能力，支持：

- 📝 **Trix**：`TextEditorWithImagePickerField`（原有组件）
- 🧩 **TinyMCE**：`TinyMceEditorWithMediaPickerField`（新增组件）

两者均可在编辑器内一键插入图片与视频。

## 🚀 功能特性

- ✨ **一键集成**：只需在 CRUD Controller 中引入 JS/CSS 文件即可使用
- 🎨 **优雅界面**：与 EasyAdmin 和 Trix 编辑器完美融合的 UI 设计
- 📱 **响应式设计**：支持桌面和移动设备
- 🔧 **零侵入**：不修改 EasyAdmin 或 Trix 核心代码
- 🎯 **智能选择**：选择模式下提供清晰的视觉反馈

## 📁 文件结构

```
packages/file-storage-bundle/src/Resources/public/
├── js/
│   ├── tinymce-image-picker.js   # TinyMCE 媒体选择器主逻辑
│   ├── trix-image-picker.js      # Trix 图片选择器主逻辑
│   └── image-gallery.js          # 图片管理器（已优化选择模式）
└── css/
    ├── tinymce-image-picker.css  # TinyMCE 媒体选择器样式
    ├── trix-image-picker.css     # 图片选择器样式
    └── image-gallery.css         # 图片管理器样式（已增强）

packages/file-storage-bundle/src/Resources/views/form/
└── tiny_editor_theme.html.twig   # TinyMCE 专用 form theme
```

## 🔧 集成步骤

优先推荐使用封装好的通用字段，零配置集成；若需保持与原生 TextEditorField 完全一致的使用方式，也可继续采用手工注入资源的方式。

### 0a. 推荐：使用 TinyMceEditorWithMediaPickerField（TinyMCE 富文本）

```php
<?php

use Tourze\FileStorageBundle\Field\TinyMceEditorWithMediaPickerField;

class ArticleCrudController extends AbstractCrudController
{
    public function configureFields(string $pageName): iterable
    {
        yield TinyMceEditorWithMediaPickerField::new('content', '内容')
            ->setTinyMceConfig([
                'toolbar' => 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media | mediaGallery | code fullscreen',
                'plugins' => 'link lists image media table code fullscreen',
            ])
            // ->setTinyMceLoaderUrl('/build/tinymce/tinymce.min.js') // 可选：自定义 TinyMCE 资源地址
            ->hideOnIndex()
        ;
    }
}
```

> 默认从 Tiny Cloud CDN (`https://cdn.tiny.cloud/...`) 加载 TinyMCE。如需离线部署，可通过 `setTinyMceLoaderUrl()` 指定本地构建的 `tinymce.min.js`。

### 0b. 推荐：使用通用字段 TextEditorWithImagePickerField（Trix 富文本）

```php
<?php

use Tourze\FileStorageBundle\Field\TextEditorWithImagePickerField;

class ArticleCrudController extends AbstractCrudController
{
    public function configureFields(string $pageName): iterable
    {
        yield TextEditorWithImagePickerField::new('content', '内容')
            ->setTrixEditorConfig([
                'blockAttributes' => [
                    'default' => ['tagName' => 'p'],
                    'heading1' => ['tagName' => 'h3'],
                    'heading2' => ['tagName' => 'h4'],
                    'quote' => ['tagName' => 'blockquote'],
                ],
            ])
            ->hideOnIndex()
        ;
    }
}
```

该字段会自动注入所需 JS/CSS 资源，无需在 `configureAssets()` 中手工添加。

### 1. 手工方式：在 CRUD Controller 中添加资源

```php
<?php

use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

class ArticleCrudController extends AbstractCrudController
{
    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            // Trix 图片选择器集成
            ->addJsFile('/bundles/filestorage/js/trix-image-picker.js')
            ->addCssFile('/bundles/filestorage/css/trix-image-picker.css')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // ... 其他字段
        
        yield TextEditorField::new('content', '内容')
            ->hideOnIndex()
        ;
        
        // ... 其他字段
    }
}
```

### 2. 确保图片管理器路由可访问

确保以下路由在你的应用中可访问：
- `/gallery` - 图片管理器主页面
- `/gallery?mode=select` - 选择模式的图片管理器

## 🎮 使用方法

1. **进入编辑页面**：在使用了 TextEditorField 的表单页面中
2. **找到图片按钮**：在 Trix 编辑器工具栏中找到图片图标按钮 📷
3. **选择图片**：点击按钮打开图片选择器 Modal
4. **浏览和选择**：在图片管理器中浏览和选择所需图片
5. **确认插入**：点击图片后自动插入到编辑器光标位置

## 🎨 视觉特性

### 选择模式增强
- **悬停效果**：鼠标悬停时图片有蓝色边框和阴影效果
- **脉冲动画**：选择模式下的轻微脉冲动画提示
- **选择图标**：右上角显示选择图标
- **工具提示**：悬停时显示"点击选择此图片"提示
- **全局提示**：页面顶部显示选择模式提示条

### Modal 界面
- **大尺寸窗口**：1200px 宽度，适合浏览大量图片
- **响应式设计**：移动设备上自动调整大小
- **加载动画**：iframe 加载时的旋转加载指示器
- **优雅关闭**：支持 ESC 键和点击遮罩关闭

## 🔧 技术实现

### 核心机制
- **事件监听**：监听 `trix-initialize` 事件自动为编辑器添加按钮
- **iframe 通信**：使用 `postMessage` 实现跨域通信
- **DOM 操作**：动态创建和管理 Modal 及按钮
- **图片插入**：使用 Trix API 的 `insertHTML` 方法插入图片

### 关键类和方法
- `TrixImagePicker` - 主控制类
- `addImagePickerButton()` - 添加工具栏按钮
- `openImagePicker()` - 打开图片选择器
- `insertImageIntoEditor()` - 插入图片到编辑器

## 🎯 浏览器兼容性

- ✅ Chrome 80+
- ✅ Firefox 75+
- ✅ Safari 13+
- ✅ Edge 80+

## 🐛 故障排除

### 常见问题

1. **按钮不显示**
   - 检查 JS/CSS 文件是否正确引入
   - 确认使用的是 TextEditorField 而不是其他编辑器字段

2. **图片选择器打不开**
   - 检查 `/gallery` 路由是否可访问
   - 确认 Bootstrap Modal 库已加载

3. **图片插入失败**
   - 检查浏览器控制台是否有 JavaScript 错误
   - 确认图片 URL 有效且可访问

4. **样式异常**
   - 检查 CSS 文件是否正确加载
   - 确认没有样式冲突

### 调试模式

在浏览器开发者工具的 Console 中，插入图片时会输出调试信息：
```javascript
// 成功插入
Image inserted successfully: https://example.com/image.jpg

// 失败信息
Failed to insert image: Error details
```

## 🔄 更新日志

### v1.0.0 (2025-01-XX)
- ✨ 初始版本发布
- 🎨 添加完整的图片选择功能
- 📱 支持响应式设计
- 🎯 优化选择模式视觉反馈

## 🤝 贡献指南

如需改进或添加新功能，请：

1. 修改相关的 JS/CSS 文件
2. 测试所有支持的浏览器
3. 更新此文档
4. 提交 Pull Request

## 📞 技术支持

如有问题或建议，请联系开发团队或在项目仓库中提交 Issue。
