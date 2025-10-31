# 视频上传支持实现总结

## 概述
成功为 file-storage-bundle 添加了视频上传支持，包括视频文件的预览功能。

## 实施的更改

### 1. 后端更改

#### FileTypeFixtures.php
- 将视频文件类型（MP4、AVI、WebM、MOV）的 uploadType 从 'member' 改为 'both'
- 添加了 MOV 视频格式支持

#### ImageGalleryGetFilesController.php
- 添加了 `isVideoFile()` 方法来识别视频文件
- 在 `formatFileData()` 中添加了 `isVideo` 字段

#### ImageGalleryUploadFileController.php
- 同样添加了 `isVideoFile()` 方法以保持一致性

#### FileFilterService.php
- 更新了 `matchesTypeFilter()` 方法：
  - 'documents' 筛选现在排除视频文件
  - 添加了 'videos' 筛选选项
- 添加了 `isVideoFile()` 方法

### 2. 前端更改

#### image-gallery.html.twig
- 在侧边栏添加了"视频文件"筛选选项

#### image-gallery.js
- 更新了 `renderFiles()` 函数：
  - 视频文件显示 `<video>` 缩略图而不是图标
  - 预览功能现在支持视频文件
- 更新了 `previewFile()` 函数：
  - 根据 MIME 类型判断是图片还是视频
  - 视频预览使用带控制器的 `<video>` 标签
- 更新了 `renderFolderTree()` 函数，添加视频文件夹选项

#### image-gallery.css
- 添加了视频缩略图的样式
- 添加了视频预览模态框的样式

## 功能特性

### 视频上传
- 支持格式：MP4、AVI、WebM、MOV
- 文件大小限制：100MB
- 支持匿名和会员上传

### 视频预览
- 点击视频可打开预览模态框
- 预览时自动播放
- 包含完整的视频控制栏

### 视频筛选
- 侧边栏新增"视频文件"选项
- 可单独筛选查看所有视频文件
- 文档文件筛选不再包含视频

## 测试建议

1. 上传不同格式的视频文件
2. 测试视频预览功能
3. 验证视频文件筛选功能
4. 确认文档文件筛选不再包含视频
5. 测试已选择视频的预览功能（如果使用 ImageGalleryField）

## 注意事项

- 视频缩略图使用浏览器原生 `<video>` 标签，可能会加载元数据
- 如需生成视频封面图，可考虑使用 FFmpeg 等工具
- 视频文件较大时，上传可能需要较长时间