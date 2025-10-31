(function () {
    'use strict';

    if (typeof window === 'undefined') {
        return;
    }

    const DEFAULT_LOADER_URL = '/bundles/filestorage/tinymce/tinymce.min.js';
    const SELECTOR = 'textarea.ea-tiny-editor-content[data-tinymce-field="true"]';
    const IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
    const VIDEO_EXTENSIONS = new Set(['mp4', 'mov', 'm4v', 'webm', 'avi', 'flv', 'wmv', 'mkv', 'ogv']);

    class TinyMceMediaPicker {
        constructor() {
            this.modalId = 'tinyMceMediaPickerModal';
            this.iframeId = `${this.modalId}Iframe`;
            this.activeEditor = null;
            this.loaderPromise = null;
            this.observer = null;
            this.init();
        }

        init() {
            this.createModal();
            this.bindEvents();
            this.prepareEditors();
            this.observeDom();
        }

        createModal() {
            if (document.getElementById(this.modalId)) {
                return;
            }

            const html = `
                <div class="modal fade" id="${this.modalId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xxl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-images me-2"></i>选择媒体
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                            </div>
                            <div class="modal-body p-0">
                                <iframe id="${this.iframeId}" src="" title="媒体选择器"></iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', html);
        }

        bindEvents() {
            window.addEventListener('message', (event) => this.handleMessage(event));

            const modal = document.getElementById(this.modalId);
            if (!modal) {
                return;
            }

            modal.addEventListener('hidden.bs.modal', () => {
                this.activeEditor = null;
                modal.classList.remove('iframe-loaded');
                const iframe = document.getElementById(this.iframeId);
                if (iframe) {
                    iframe.src = '';
                    iframe.onload = null;
                    iframe.onerror = null;
                }
            });
        }

        observeDom() {
            if (this.observer) {
                return;
            }

            this.observer = new MutationObserver(() => this.prepareEditors());
            this.observer.observe(document.body, { childList: true, subtree: true });
        }

        prepareEditors() {
            const targets = Array.from(document.querySelectorAll(SELECTOR))
                .filter((textarea) => !textarea.dataset.tinymceInitialized);

            if (targets.length === 0) {
                return;
            }

            const loaderUrl = this.resolveLoaderUrl(targets);
            this.ensureTinyMce(loaderUrl)
                .then(() => targets.forEach((textarea) => this.createEditor(textarea)))
                .catch((error) => {
                    console.error('[TinyMceMediaPicker] TinyMCE 加载失败', error);
                });
        }

        resolveLoaderUrl(textareas) {
            for (const textarea of textareas) {
                const candidate = textarea.dataset.tinymceLoader;
                if (candidate) {
                    return candidate;
                }
            }

            const globalConfig = window.TinyMceMediaPickerConfig;
            if (globalConfig && typeof globalConfig.loaderUrl === 'string' && globalConfig.loaderUrl.length > 0) {
                return globalConfig.loaderUrl;
            }

            return DEFAULT_LOADER_URL;
        }

        ensureTinyMce(loaderUrl) {
            if (window.tinymce) {
                return Promise.resolve(window.tinymce);
            }

            if (this.loaderPromise) {
                return this.loaderPromise;
            }

            this.loaderPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = loaderUrl;
                script.referrerPolicy = 'origin';
                script.async = true;
                script.onload = () => {
                    if (window.tinymce) {
                        resolve(window.tinymce);
                    } else {
                        reject(new Error('TinyMCE 未在加载完成后提供全局对象'));
                    }
                };
                script.onerror = () => reject(new Error(`无法从 ${loaderUrl} 加载 TinyMCE`));
                document.head.appendChild(script);
            });

            return this.loaderPromise;
        }

        createEditor(textarea) {
            if (textarea.dataset.tinymceInitialized) {
                return;
            }

            const config = this.buildConfig(textarea);
            window.tinymce.init(config);
        }

        buildConfig(textarea) {
            const rows = parseInt(textarea.dataset.numberOfRows || '0', 10);
            const height = rows > 0 ? Math.max(rows * 24, 160) : 320;
            const parsedConfig = this.parseJson(textarea.dataset.tinymceConfig);

            const baseConfig = {
                target: textarea,
                height,
                menubar: false,
                branding: false,
                convert_urls: false,
                plugins: 'link lists image media table code fullscreen autoresize',
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link image media | removeformat | code fullscreen | mediaGallery',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 14px; } img,video { max-width: 100%; height: auto; }',
                automatic_uploads: false,
                file_picker_types: 'image media',
                setup: (editor) => this.setupEditor(editor, textarea, parsedConfig),
            };

            const merged = Object.assign({}, baseConfig, parsedConfig);

            if (!merged.toolbar) {
                merged.toolbar = baseConfig.toolbar;
            }

            if (!merged.plugins) {
                merged.plugins = baseConfig.plugins;
            }

            return merged;
        }

        setupEditor(editor, textarea, parsedConfig) {
            textarea.dataset.tinymceInitialized = '1';

            editor.on('init', () => {
                textarea.classList.add('ea-tiny-editor-hidden');
            });

            editor.on('change input undo redo', () => {
                editor.save();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.dispatchEvent(new Event('change', { bubbles: true }));
            });

            editor.ui.registry.addButton('mediaGallery', {
                icon: 'image',
                tooltip: '从媒体库选择图片或视频',
                text: '媒体库',
                onAction: () => this.openMediaPicker(editor),
            });

            editor.ui.registry.addMenuItem('mediaGallery', {
                text: '媒体库',
                icon: 'image',
                onAction: () => this.openMediaPicker(editor),
            });

            if (parsedConfig && typeof parsedConfig === 'object' && parsedConfig.toolbar && !parsedConfig.toolbar.includes('mediaGallery')) {
                editor.on('init', () => {
                    console.warn('[TinyMceMediaPicker] 当前 toolbar 不包含 mediaGallery 按钮，媒体库入口可能不可见。');
                });
            }
        }

        openMediaPicker(editor) {
            this.activeEditor = editor;

            const modal = document.getElementById(this.modalId);
            if (!modal) {
                return;
            }

            modal.classList.remove('iframe-loaded');
            const iframe = document.getElementById(this.iframeId);
            if (iframe) {
                iframe.onload = () => modal.classList.add('iframe-loaded');
                iframe.onerror = () => {
                    modal.classList.add('iframe-loaded');
                    this.notifyError('媒体库加载失败，请检查网络连接');
                };
                iframe.src = '/gallery?mode=select&multiple=1';
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                const instance = new window.bootstrap.Modal(modal);
                instance.show();
            } else if (typeof window.$ === 'function') {
                window.$(modal).modal('show');
            } else {
                modal.style.display = 'block';
            }
        }

        closeMediaPicker() {
            const modal = document.getElementById(this.modalId);
            if (!modal) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                const instance = window.bootstrap.Modal.getInstance(modal);
                if (instance) {
                    instance.hide();
                }
            } else if (typeof window.$ === 'function') {
                window.$(modal).modal('hide');
            } else {
                modal.style.display = 'none';
            }
        }

        handleMessage(event) {
            if (!event.data || !event.data.type) {
                return;
            }

            if (window.location.origin && event.origin && event.origin !== window.location.origin) {
                return;
            }

            let handled = false;

            switch (event.data.type) {
                case 'mediaSelected':
                    handled = this.insertMediaItem(event.data);
                    break;
                case 'mediaSelectedMultiple':
                    handled = this.insertMultipleMedia(event.data.items || []);
                    break;
                case 'imageSelected':
                    handled = this.insertMediaItem({ url: event.data.url, mediaType: 'image', metadata: {} });
                    break;
                case 'imagesSelected':
                    handled = this.insertMultipleMedia((event.data.urls || []).map((url) => ({ url, mediaType: 'image', metadata: {} })));
                    break;
                default:
                    break;
            }

            if (handled) {
                this.closeMediaPicker();
            }
        }

        insertMultipleMedia(items) {
            let success = false;
            items.forEach((item) => {
                if (this.insertMediaItem(item)) {
                    success = true;
                }
            });
            return success;
        }

        insertMediaItem(payload) {
            const editor = this.activeEditor;
            if (!editor) {
                return false;
            }

            if (!payload || !payload.url) {
                return false;
            }

            const mediaType = this.resolveMediaType(payload);

            editor.focus();
            editor.undoManager.transact(() => {
                if (mediaType === 'video') {
                    editor.insertContent(this.buildVideoHtml(payload));
                } else {
                    editor.insertContent(this.buildImageHtml(payload));
                }
                editor.insertContent('<p></p>');
            });
            editor.save();
            this.ensureAutoResize(editor);
            return true;
        }

        resolveMediaType(payload) {
            const metadata = payload.metadata || {};
            const explicit = (payload.mediaType || '').toLowerCase();
            if (explicit === 'video' || explicit === 'image') {
                return explicit;
            }

            const mime = String(metadata.mimeType || '').toLowerCase();
            if (mime.startsWith('video/')) {
                return 'video';
            }
            if (mime.startsWith('image/')) {
                return 'image';
            }

            const filename = String(metadata.fileName || payload.url || '').toLowerCase();
            const extension = filename.includes('.') ? filename.split('.').pop() : '';
            if (extension) {
                if (VIDEO_EXTENSIONS.has(extension)) {
                    return 'video';
                }
                if (IMAGE_EXTENSIONS.has(extension)) {
                    return 'image';
                }
            }

            return 'image';
        }

        buildImageHtml(payload) {
            const url = this.escapeAttribute(payload.url);
            const metadata = payload.metadata || {};
            const alt = this.escapeAttribute(metadata.fileName || '插入的图片');
            return `<figure class="tinymce-media tinymce-media-image"><img src="${url}" alt="${alt}" style="max-width:100%;height:auto;" /></figure>`;
        }

        buildVideoHtml(payload) {
            const url = this.escapeAttribute(payload.url);
            const metadata = payload.metadata || {};
            const mimeType = this.escapeAttribute(metadata.mimeType || '');
            const source = mimeType ? `<source src="${url}" type="${mimeType}">` : '';
            const srcAttribute = source ? '' : ` src="${url}"`;
            return `<figure class="tinymce-media tinymce-media-video"><video controls preload="metadata" style="max-width:100%;height:auto;"${srcAttribute}>${source}</video></figure>`;
        }

        parseJson(value) {
            if (!value || value === 'null') {
                return {};
            }

            try {
                const parsed = JSON.parse(value);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                console.warn('[TinyMceMediaPicker] 无法解析 TinyMCE 配置，将使用默认配置。', error);
                return {};
            }
        }

        ensureAutoResize(editor) {
            if (!editor || typeof editor.hasPlugin !== 'function' || typeof editor.execCommand !== 'function') {
                return;
            }

            if (!editor.hasPlugin('autoresize')) {
                return;
            }

            const trigger = () => {
                try {
                    editor.execCommand('mceAutoResize');
                } catch (error) {
                    console.warn('[TinyMceMediaPicker] 执行 mceAutoResize 失败。', error);
                }
            };

            if (typeof window !== 'undefined' && typeof window.requestAnimationFrame === 'function') {
                window.requestAnimationFrame(trigger);
            } else {
                setTimeout(trigger, 0);
            }
        }

        escapeAttribute(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        notifyError(message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
            alert.style.cssText = 'top:20px;right:20px;z-index:9999;';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    }

    function boot() {
        if (window.TinyMceMediaPickerInstance) {
            return;
        }
        window.TinyMceMediaPickerInstance = new TinyMceMediaPicker();
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        boot();
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }
})();
