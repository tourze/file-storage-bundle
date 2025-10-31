/**
 * Trix Image Picker Extension
 * 为 Trix 富文本编辑器添加图片选择功能
 *
 * @author Claude Code
 */

const IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
const VIDEO_EXTENSIONS = new Set(['mp4', 'mov', 'm4v', 'webm', 'avi', 'flv', 'wmv', 'mkv', 'ogv']);

class TrixImagePicker {
    constructor() {
        this.modal = null;
        this.currentEditor = null;
        this.modalId = 'trixImagePickerModal';
        this.videoObservers = new WeakMap();
        this.debounceTimers = new Map(); // 管理防抖计时器

        this.init();

        // 页面卸载时清理资源
        window.addEventListener('beforeunload', () => this.cleanup());
    }

    init() {
        this.createModal();
        this.bindEvents();
        this.initializeTrixEditors();
    }

    /**
     * 创建 Bootstrap Modal
     */
    createModal() {
        if (document.getElementById(this.modalId)) {
            return; // Modal 已存在
        }

        const modalHTML = `
            <div class="modal fade" id="${this.modalId}" tabindex="-1" aria-labelledby="${this.modalId}Label" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${this.modalId}Label">
                                <i class="bi bi-images me-2"></i>选择图片
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                        </div>
                        <div class="modal-body p-0">
                            <iframe id="${this.modalId}Iframe" 
                                    src="" 
                                    style="width:100%;height:600px;border:none;"
                                    title="图片选择器">
                            </iframe>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById(this.modalId);
    }

    /**
     * 绑定事件监听器
     */
    bindEvents() {
        const self = this;
        // 监听来自 iframe 的消息
        window.addEventListener('message', (event) => {
            if (!event.data || !event.data.type) {
                return;
            }

            const data = event.data;
            let hasInserted = false;

            switch (data.type) {
                case 'mediaSelected':
                    hasInserted = this.handleMediaSelection({
                        url: data.url,
                        mediaType: data.mediaType,
                        metadata: data.metadata,
                    });
                    break;
                case 'mediaSelectedMultiple':
                    if (Array.isArray(data.items)) {
                        data.items.forEach((item) => {
                            const inserted = this.handleMediaSelection({
                                url: item?.url,
                                mediaType: item?.mediaType,
                                metadata: item?.metadata,
                            });
                            hasInserted = hasInserted || inserted;
                        });
                    }
                    break;
                case 'imageSelected':
                    hasInserted = this.handleMediaSelection({
                        url: data.url,
                        mediaType: 'image',
                        metadata: {},
                    });
                    break;
                case 'imagesSelected':
                    if (Array.isArray(data.urls)) {
                        data.urls.forEach((url) => {
                            const inserted = this.handleMediaSelection({
                                url,
                                mediaType: 'image',
                                metadata: {},
                            });
                            hasInserted = hasInserted || inserted;
                        });
                    }
                    break;
                default:
                    break;
            }

            if (hasInserted) {
                this.closeModal();
            }
        });

        // 监听 Modal 关闭事件，清理状态
        if (this.modal) {
            this.modal.addEventListener('hidden.bs.modal', () => {
                this.currentEditor = null;
                // 移除加载完成标记
                this.modal.classList.remove('iframe-loaded');
                // 清空 iframe src 以节省资源
                const iframe = document.getElementById(this.modalId + 'Iframe');
                if (iframe) {
                    iframe.src = '';
                    iframe.onload = null;
                    iframe.onerror = null;
                }
            });
        }

        // 监听自定义按钮点击事件 - 使用更简单直接的方法
        document.addEventListener('click', (event) => {
            if (event.target.matches('[data-trix-action="x-image-gallery"]') ||
                event.target.closest('[data-trix-action="x-image-gallery"]')) {

                event.preventDefault();
                event.stopPropagation();

                console.log('图片选择按钮被点击');

                // 简化逻辑：直接查找页面上的第一个 trix-editor
                // 因为通常一个页面只有一个活跃的编辑器
                const editors = document.querySelectorAll('trix-editor');
                console.log('找到的编辑器数量:', editors.length);

                if (editors.length === 0) {
                    console.error('页面中没有找到 trix-editor 元素');
                    this.showError('没有找到文本编辑器');
                    return;
                }

                // 如果只有一个编辑器，直接使用它
                let targetEditor = null;
                if (editors.length === 1) {
                    targetEditor = editors[0];
                } else {
                    // 多个编辑器的情况，尝试找到最相关的那个
                    const button = event.target.closest('[data-trix-action="x-image-gallery"]');

                    // 方法1：查找同一个表单中的编辑器
                    const form = button.closest('form');
                    if (form) {
                        const formEditor = form.querySelector('trix-editor');
                        if (formEditor) {
                            targetEditor = formEditor;
                        }
                    }

                    // 方法2：查找最近的编辑器
                    if (!targetEditor) {
                        const container = button.closest('.form-group, .field-group, .ea-form-column-group-content, .form-widget');
                        if (container) {
                            targetEditor = container.querySelector('trix-editor');
                        }
                    }

                    // 方法3：使用第一个编辑器作为后备
                    if (!targetEditor) {
                        targetEditor = editors[0];
                        console.log('使用第一个编辑器作为后备方案');
                    }
                }

                if (targetEditor && targetEditor.editor) {
                    console.log('找到目标编辑器，打开图片选择器');
                    this.openImagePicker(targetEditor);
                } else {
                    console.error('编辑器未正确初始化', targetEditor);
                    this.showError('编辑器未准备好，请稍后再试');
                }
            }
        });

        document.addEventListener('trix-attachment-add', (event) => {
            const editorEl = event.target.closest('trix-editor');
            if (editorEl) {
                setTimeout(() => self.ensureVideoControlsForEditor(editorEl), 50);
            }
        });
    }

    /**
     * 初始化现有的 Trix 编辑器
     */
    initializeTrixEditors() {
        console.log('Initializing Trix image picker...');

        // 为页面上已存在的 Trix 编辑器添加按钮
        const existingEditors = document.querySelectorAll('trix-editor');
        console.log(`Found ${existingEditors.length} existing trix-editor(s)`);

        existingEditors.forEach((editor) => {
            this.addImagePickerButton(editor);

            // 监听附件变化，但使用防抖
            editor.addEventListener('trix-attachment-add', () => {
                setTimeout(() => this.ensureVideoControlsForEditor(editor), 50);
            });
            editor.addEventListener('trix-attachment-remove', () => {
                setTimeout(() => this.ensureVideoControlsForEditor(editor), 50);
            });

            // 延迟处理，确保编辑器完全初始化
            setTimeout(() => {
                this.ensureVideoControlsForEditor(editor);
                this.setupVideoObserver(editor);
            }, 100);
        });

        // 监听新的 Trix 编辑器初始化
        document.addEventListener('trix-initialize', (event) => {
            console.log('Trix editor initialized:', event.target);
            const editor = event.target;

            this.addImagePickerButton(editor);

            // 监听附件变化，但使用防抖
            editor.addEventListener('trix-attachment-add', () => {
                setTimeout(() => this.ensureVideoControlsForEditor(editor), 50);
            });
            editor.addEventListener('trix-attachment-remove', () => {
                setTimeout(() => this.ensureVideoControlsForEditor(editor), 50);
            });

            // 延迟处理，确保编辑器完全初始化
            setTimeout(() => {
                this.ensureVideoControlsForEditor(editor);
                this.setupVideoObserver(editor);
            }, 100);
        });
    }

    /**
     * 为 Trix 编辑器添加图片选择按钮
     */
    addImagePickerButton(editor) {
        console.log('Adding image picker button for editor:', editor);

        const toolbar = editor.toolbarElement;
        if (!toolbar) {
            console.warn('No toolbar found for editor:', editor);
            return;
        }

        console.log('Found toolbar:', toolbar);

        // 检查是否已添加按钮
        if (toolbar.querySelector('[data-trix-action="x-image-gallery"]')) {
            console.log('Image picker button already exists for this toolbar');
            return;
        }

        // 创建图片选择按钮 - 使用文字显示更明显
        const buttonHTML = `
            <button type="button" 
                    class="trix-button trix-button--text trix-button--image-gallery" 
                    data-trix-action="x-image-gallery" 
                    title="从图库选择图片"
                    tabindex="-1"
                    style="font-size: 12px; padding: 0 6px;">
                图库
            </button>
        `;

        // 多种策略查找合适的插入位置
        let insertPoint = null;
        let insertPosition = 'afterend';

        // 策略1: 在链接按钮后面
        insertPoint = toolbar.querySelector('[data-trix-attribute="href"]');

        // 策略2: 在附件按钮后面
        if (!insertPoint) {
            insertPoint = toolbar.querySelector('[data-trix-action="x-attach"]');
        }

        // 策略3: 在任何图像相关按钮后面
        if (!insertPoint) {
            insertPoint = toolbar.querySelector('[data-trix-action*="image"], [data-trix-attribute*="image"]');
        }

        // 策略4: 在最后一个按钮组的开头
        if (!insertPoint) {
            const lastButtonGroup = toolbar.querySelector('.trix-button-group:last-child');
            if (lastButtonGroup) {
                insertPoint = lastButtonGroup;
                insertPosition = 'afterbegin';
            }
        }

        // 策略5: 直接添加到工具栏末尾
        if (!insertPoint) {
            // 创建一个新的按钮组
            const newButtonGroup = document.createElement('div');
            newButtonGroup.className = 'trix-button-group';
            newButtonGroup.innerHTML = buttonHTML;
            toolbar.appendChild(newButtonGroup);

            console.log('Added image picker button in new button group');
            return;
        }

        // 在找到的位置插入按钮
        insertPoint.insertAdjacentHTML(insertPosition, buttonHTML);
        console.log('Added image picker button after', insertPoint);
    }

    /**
     * 打开图片选择器
     */
    openImagePicker(editor) {
        this.currentEditor = editor;

        // 移除之前的加载完成标记
        this.modal.classList.remove('iframe-loaded');

        // 设置 iframe src
        const iframe = document.getElementById(this.modalId + 'Iframe');
        if (iframe) {
            // 监听 iframe 加载完成
            iframe.onload = () => {
                console.log('iframe 加载完成');
                // 添加加载完成标记，隐藏 loading
                this.modal.classList.add('iframe-loaded');
            };

            // 监听 iframe 加载错误
            iframe.onerror = () => {
                console.error('iframe 加载失败');
                this.modal.classList.add('iframe-loaded');
                this.showError('图片管理器加载失败，请检查网络连接');
            };

            iframe.src = '/gallery?mode=select';
        }

        // 显示 Modal
        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = new window.bootstrap.Modal(this.modal);
            modal.show();
        } else {
            // 兼容旧版本 Bootstrap
            $(this.modal).modal('show');
        }
    }

    /**
     * 关闭 Modal
     */
    closeModal() {
        if (window.bootstrap && window.bootstrap.Modal) {
            const modalInstance = window.bootstrap.Modal.getInstance(this.modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        } else {
            // 兼容旧版本 Bootstrap
            $(this.modal).modal('hide');
        }
    }

    /**
     * 处理媒体选择结果
     */
    handleMediaSelection(payload) {
        if (this.currentEditor || this.currentEditor.editor) {
            if (!payload || !payload.url) {
                return false;
            }

            const metadata = payload.metadata || {};
            const mediaType = this.resolveMediaType(payload.mediaType, metadata, payload.url);

            if (mediaType === 'video') {
                this.insertVideoIntoEditor(payload.url, metadata);
            } else {
                this.insertImageIntoEditor(payload.url, metadata);
            }

            return true;
        }
    }

    resolveMediaType(initialType, metadata, url) {
        const normalized = (initialType || '').toLowerCase();
        if (normalized === 'video' || normalized === 'image') {
            return normalized;
        }

        const mime = String(metadata?.mimeType ?? '').toLowerCase();
        if (mime.startsWith('video/')) {
            return 'video';
        }
        if (mime.startsWith('image/')) {
            return 'image';
        }

        const fileName = String(metadata?.fileName ?? '').toLowerCase();
        const candidate = fileName || String(url ?? '').toLowerCase();
        const extension = candidate.includes('.') ? candidate.split('.').pop() : '';

        if (extension) {
            if (VIDEO_EXTENSIONS.has(extension)) {
                return 'video';
            }
            if (IMAGE_EXTENSIONS.has(extension)) {
                return 'image';
            }
        }

        return normalized || 'image';
    }

    /**
     * 将图片插入到编辑器
     */
    insertImageIntoEditor(imageUrl, metadata = {}) {
        if (!this.currentEditor || !this.currentEditor.editor) {
            console.error('No active editor found');
            return;
        }

        try {
            const safeUrl = this.escapeHtml(imageUrl);
            const alt = this.escapeHtml(metadata.fileName || '插入的图片');
            const imageHTML = `<img src="${safeUrl}" alt="${alt}" style="max-width: 100%; height: auto;" />`;

            this.currentEditor.editor.insertHTML(imageHTML);
            this.triggerEditorChange();

            console.log('Image inserted successfully:', imageUrl);
        } catch (error) {
            console.error('Failed to insert image:', error);
            this.showError('插入图片失败，请重试');
        }
    }

    /**
     * 将视频插入到编辑器
     */
    insertVideoIntoEditor(videoUrl, metadata = {}) {
        if (!this.currentEditor || !this.currentEditor.editor) {
            console.error('No active editor found');
            return;
        }

        try {
            const safeUrl = this.escapeHtml(videoUrl);
            let mimeType = this.escapeHtml(metadata.mimeType || '');
            if (!mimeType) {
                mimeType = this.detectMimeTypeFromUrl(safeUrl) || '';
            }

            // 创建视频HTML（用于预览）
            const videoHTML = mimeType
                ? `<video controls preload="metadata" style="max-width: 100%; height: auto;" playsinline webkit-playsinline controlslist="nodownload"><source src="${safeUrl}" type="${mimeType}"></video>`
                : `<video controls preload="metadata" style="max-width: 100%; height: auto;" src="${safeUrl}" playsinline webkit-playsinline controlslist="nodownload"></video>`;

            // 创建占位符HTML（编辑时显示）
            const placeholderHTML = `
                <div class="trix-video-placeholder" 
                     data-video-url="${safeUrl}" 
                     data-video-mime="${mimeType}"
                     style="min-height: 200px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; color: #6c757d;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎥</div>
                        <div>视频文件</div>
                        <div style="font-size: 0.875rem; opacity: 0.7;">${metadata.fileName || '视频'}</div>
                    </div>
                </div>
            `;

            // 使用Trix附件系统插入
            if (typeof Trix !== 'undefined' && Trix.Attachment) {
                const attachment = new Trix.Attachment({
                    content: placeholderHTML,
                    previewHTML: videoHTML,
                    contentType: 'text/html',
                    videoUrl: safeUrl,
                    videoMime: mimeType,
                });
                this.currentEditor.editor.insertAttachment(attachment);
            } else {
                // 降级处理：直接插入视频HTML
                this.currentEditor.editor.insertHTML(videoHTML);
            }

            this.currentEditor.editor.insertString('\n');
            this.triggerEditorChange();

            console.log('Video inserted successfully:', videoUrl);
        } catch (error) {
            console.error('Failed to insert video:', error);
            this.showError('插入视频失败，请重试');
        }
    }

    ensureVideoControlsForEditor(root) {
        if (!root) {
            return;
        }

        const enforce = (target) => {
            if (!target) {
                return;
            }

            // 1. 处理视频占位符（编辑模式）
            target.querySelectorAll('.trix-video-placeholder:not([data-video-rendered="1"])').forEach((placeholder) => {
                let url = placeholder.dataset.videoUrl || '';
                let mime = placeholder.dataset.videoMime || '';

                if (!url) {
                    // 尝试从附件中提取URL
                    const figure = placeholder.closest('figure[data-trix-attachment]');
                    if (figure) {
                        try {
                            const attachment = JSON.parse(figure.getAttribute('data-trix-attachment'));
                            if (attachment) {
                                if (attachment.videoUrl) {
                                    url = attachment.videoUrl;
                                    mime = attachment.videoMime || mime;
                                } else {
                                    const candidates = [attachment.content, attachment.previewHTML];
                                    for (const html of candidates) {
                                        if (typeof html !== 'string') {
                                            continue;
                                        }
                                        const urlMatch = html.match(/data-video-url="([^"]+)"/);
                                        const mimeMatch = html.match(/data-video-mime="([^"]+)"/);
                                        if (urlMatch) {
                                            url = urlMatch[1];
                                            mime = mimeMatch ? mimeMatch[1] : mime;
                                            break;
                                        }
                                    }
                                }
                            }
                        } catch (error) {
                            console.warn('Failed to parse attachment JSON', error);
                        }
                    }
                }

                if (!url) {
                    return;
                }

                if (!mime) {
                    mime = this.detectMimeTypeFromUrl(url) || '';
                }

                placeholder.dataset.videoUrl = url;
                placeholder.dataset.videoMime = mime;

                // 渲染实际的视频元素
                placeholder.innerHTML = '';

                const video = document.createElement('video');
                video.controls = true;
                video.preload = 'metadata';
                video.style.maxWidth = '100%';
                video.style.height = 'auto';
                video.setAttribute('playsinline', '');
                video.setAttribute('webkit-playsinline', '');
                video.setAttribute('controlslist', 'nodownload');
                video.dataset.trixProcessed = '1';

                const source = document.createElement('source');
                source.src = url;
                if (mime) {
                    source.type = mime;
                }
                video.appendChild(source);

                placeholder.appendChild(video);
                placeholder.dataset.videoRendered = '1';
            });

            // 2. 处理直接的视频元素（查看模式）
            target.querySelectorAll('video:not([data-trix-processed="1"])').forEach((video) => {
                video.dataset.trixProcessed = '1';

                // 确保基本属性
                if (!video.hasAttribute('controls')) {
                    video.setAttribute('controls', '');
                }
                if (!video.hasAttribute('preload')) {
                    video.setAttribute('preload', 'metadata');
                }
                if (!video.hasAttribute('playsinline')) {
                    video.setAttribute('playsinline', '');
                }
                if (!video.hasAttribute('webkit-playsinline')) {
                    video.setAttribute('webkit-playsinline', '');
                }
                if (!video.hasAttribute('controlslist')) {
                    video.setAttribute('controlslist', 'nodownload');
                }

                // 确保样式
                if (!video.style.maxWidth) {
                    video.style.maxWidth = '100%';
                }
                if (!video.style.height) {
                    video.style.height = 'auto';
                }

                // 处理 source 元素
                const source = video.querySelector('source');
                const src = video.getAttribute('src') || (source ? source.getAttribute('src') : '');

                if (!source && src) {
                    const newSource = document.createElement('source');
                    newSource.src = src;
                    const mime = this.detectMimeTypeFromUrl(src);
                    if (mime) {
                        newSource.type = mime;
                    }
                    video.appendChild(newSource);
                } else if (source && src && !source.getAttribute('type')) {
                    const mime = this.detectMimeTypeFromUrl(src);
                    if (mime) {
                        source.setAttribute('type', mime);
                    }
                }
            });

            // 3. 处理包含视频的 figure 元素
            target.querySelectorAll('figure[data-trix-attachment]:not([data-trix-processed="1"])').forEach((figure) => {
                figure.dataset.trixProcessed = '1';

                try {
                    const attachment = JSON.parse(figure.getAttribute('data-trix-attachment'));
                    if (!attachment) return;

                    let videoUrl = null;
                    let videoMime = '';

                    // 从 attachment 中提取视频信息
                    if (attachment.videoUrl) {
                        videoUrl = attachment.videoUrl;
                        videoMime = attachment.videoMime || '';
                    } else {
                        const candidates = [attachment.content, attachment.previewHTML];
                        for (const html of candidates) {
                            if (typeof html !== 'string') {
                                continue;
                            }
                            const urlMatch = html.match(/src="([^"]+)"/);
                            const mimeMatch = html.match(/type="([^"]+)"/);
                            if (urlMatch) {
                                videoUrl = urlMatch[1];
                                videoMime = mimeMatch ? mimeMatch[1] : '';
                                break;
                            }
                        }
                    }

                    if (videoUrl && !figure.querySelector('video')) {
                        figure.innerHTML = '';

                        const video = document.createElement('video');
                        video.controls = true;
                        video.preload = 'metadata';
                        video.style.maxWidth = '100%';
                        video.style.height = 'auto';
                        video.setAttribute('playsinline', '');
                        video.setAttribute('webkit-playsinline', '');
                        video.dataset.trixProcessed = '1';

                        const source = document.createElement('source');
                        source.src = videoUrl;
                        if (videoMime) {
                            source.type = videoMime;
                        } else {
                            const detectedMime = this.detectMimeTypeFromUrl(videoUrl);
                            if (detectedMime) {
                                source.type = detectedMime;
                            }
                        }
                        video.appendChild(source);
                        figure.appendChild(video);
                    }
                } catch (error) {
                    console.warn('Failed to process video attachment:', error);
                }
            });
        };

        enforce(root);
        if (root.shadowRoot) {
            enforce(root.shadowRoot);
        }

        const controller = root.editor || root.trixEditor;
        if (controller) {
            if (controller.element) {
                enforce(controller.element);
            }
            if (controller.composition && controller.composition.element) {
                enforce(controller.composition.element);
            }
        }
    }

    detectMimeTypeFromUrl(url) {
        const extension = (url.split('.').pop() || '').toLowerCase();
        const mapping = {
            mp4: 'video/mp4',
            webm: 'video/webm',
            mov: 'video/quicktime',
            m4v: 'video/mp4',
            ogv: 'video/ogg',
        };

        return mapping[extension] || '';
    }

    setupVideoObserver(editor) {
        if (!editor || this.videoObservers.has(editor)) {
            return;
        }

        const observer = new MutationObserver((mutations) => {
            let shouldProcess = false;

            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    // 检查是否有相关的视频元素被添加
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // 检查是否是需要处理的元素
                            if (node.tagName === 'VIDEO' && node.dataset.trixProcessed !== '1') {
                                shouldProcess = true;
                            } else if (node.classList?.contains('trix-video-placeholder') &&
                                node.dataset.videoRendered !== '1') {
                                shouldProcess = true;
                            } else if (node.tagName === 'FIGURE' &&
                                node.hasAttribute('data-trix-attachment') &&
                                node.dataset.trixProcessed !== '1') {
                                shouldProcess = true;
                            } else if (node.querySelector?.('video:not([data-trix-processed="1"]), .trix-video-placeholder:not([data-video-rendered="1"]), figure[data-trix-attachment]:not([data-trix-processed="1"])')) {
                                shouldProcess = true;
                            }
                        }
                    });
                }
            });

            if (shouldProcess) {
                // 使用编辑器ID作为键来管理防抖计时器
                const editorId = editor.id || 'editor-' + Date.now();
                const existingTimer = this.debounceTimers.get(editorId);
                if (existingTimer) {
                    clearTimeout(existingTimer);
                }

                const timer = setTimeout(() => {
                    this.ensureVideoControlsForEditor(editor);
                    this.debounceTimers.delete(editorId);
                }, 200); // 200ms防抖，平衡性能和响应性

                this.debounceTimers.set(editorId, timer);
            }
        });

        // 观察编辑器和其子元素
        observer.observe(editor, {
            childList: true,
            subtree: true
        });

        this.videoObservers.set(editor, observer);

        // 立即处理一次现有的视频元素
        setTimeout(() => this.ensureVideoControlsForEditor(editor), 100);
    }

    triggerEditorChange() {
        if (!this.currentEditor) {
            return;
        }

        this.currentEditor.dispatchEvent(new CustomEvent('trix-change', {
            bubbles: true,
            cancelable: true,
        }));
    }

    /**
     * 清理资源，防止内存泄漏
     */
    cleanup() {
        // 清理防抖计时器
        this.debounceTimers.forEach((timer) => {
            clearTimeout(timer);
        });
        this.debounceTimers.clear();

        // 清理观察器
        this.videoObservers = new WeakMap();

        // 暂停所有视频
        document.querySelectorAll('video').forEach((video) => {
            video.pause();
            video.src = '';
            video.load();
        });
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * 显示错误信息
     */
    showError(message) {
        // 创建临时错误提示
        const alertHTML = `
            <div class="alert alert-danger alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', alertHTML);

        // 3秒后自动移除
        setTimeout(() => {
            const alert = document.querySelector('.alert-danger:last-of-type');
            if (alert) {
                alert.remove();
            }
        }, 3000);
    }
}

if (typeof window !== 'undefined') {
    window.TrixImagePicker = TrixImagePicker;
}

// 初始化图片选择器
document.addEventListener('DOMContentLoaded', function() {
    // 确保在页面完全加载后初始化
    new TrixImagePicker();
});

// 如果页面已经加载完成（动态加载的情况）
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    new TrixImagePicker();
}

