// 全局状态
let currentFolder = 'all';
let currentPage = 1;
let files = [];
let folders = [];
let selectedFolderId = null;
let selectedFolderData = null;
let isSelectMode = false;
let isMultiSelect = false;
let postMessageToken = null;
const selectedItems = new Map();

function escapeHtmlText(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function escapeHtmlAttr(value) {
    return escapeHtmlText(value)
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeJsString(value) {
    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');
}

// 分页状态
let paginationInfo = {
    current_page: 1,
    total: 0,
    per_page: 24,
    total_pages: 0
};

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    // 检测是否为选择模式
    isSelectMode = document.body.getAttribute('data-select-mode') === 'true';
    // 选择模式下读取 token（由父窗口生成并通过 query 传入）
    if (isSelectMode) {
        const params = new URLSearchParams(window.location.search);
        postMessageToken = params.get('token');
        const multipleParam = params.get('multiple') || params.get('multi');
        isMultiSelect = multipleParam === '1' || multipleParam === 'true';
        if (isMultiSelect) {
            ensureSelectionBar();
        }
    }

    loadFolders();
    loadFiles();
    bindEvents();
    updateBreadcrumb(currentFolder);
    // 初始化时显示分页组件
    renderPagination();
});

function bindEvents() {
    // 搜索
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));
    
    // 筛选
    document.getElementById('yearFilter').addEventListener('change', applyFilters);
    document.getElementById('monthFilter').addEventListener('change', applyFilters);
    
    // 拖拽上传
    const uploadArea = document.getElementById('uploadArea');
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('dragleave', handleDragLeave);
    uploadArea.addEventListener('drop', handleDrop);
    
    // 文件选择
    document.getElementById('fileInput').addEventListener('change', handleFileSelection);
    
    // 新建文件夹表单
    document.getElementById('createFolderForm').addEventListener('submit', handleCreateFolder);
}

// 加载文件夹
async function loadFolders() {
    try {
        console.log('Loading folders...');
        const response = await fetch('/gallery/api/folders');
        const result = await response.json();
        
        if (result.success) {
            console.log('Folders loaded successfully:', result.data);
            folders = result.data;
            renderFolderTree();
        } else {
            console.error('Failed to load folders:', result.error);
        }
    } catch (error) {
        console.error('加载文件夹失败:', error);
    }
}

// 渲染文件夹树
function renderFolderTree() {
    const tree = document.getElementById('folderTree');
    
    // 保留默认选项
    let html = `
        <div class="folder-item ${currentFolder === 'all' ? 'active' : ''}" data-folder-id="all">
            <i class="folder-icon bi bi-collection"></i>
            <span>所有文件</span>
        </div>
        <div class="folder-item ${currentFolder === 'images' ? 'active' : ''}" data-folder-id="images">
            <i class="folder-icon bi bi-image"></i>
            <span>图片文件</span>
        </div>
        <div class="folder-item ${currentFolder === 'documents' ? 'active' : ''}" data-folder-id="documents">
            <i class="folder-icon bi bi-file-text"></i>
            <span>文档文件</span>
        </div>
        <div class="folder-item ${currentFolder === 'videos' ? 'active' : ''}" data-folder-id="videos">
            <i class="folder-icon bi bi-camera-video"></i>
            <span>视频文件</span>
        </div>
        <div class="folder-item ${currentFolder === 'recent' ? 'active' : ''}" data-folder-id="recent">
            <i class="folder-icon bi bi-clock-history"></i>
            <span>最近上传</span>
        </div>
    `;
    
    // 添加真实文件夹
    if (folders.length > 0) {
        html += '<hr class="my-3">';
        folders.forEach(folder => {
            html += renderFolderItem(folder);
        });
    }
    
    tree.innerHTML = html;
    
    // 绑定点击事件
    tree.querySelectorAll('.folder-item').forEach(item => {
        // 左键点击选择文件夹
        item.addEventListener('click', () => {
            const folderId = item.dataset.folderId;
            selectFolder(folderId);
        });

        // 右键点击显示菜单（仅针对数字ID的文件夹，不包括all, images等预设类型）
        item.addEventListener('contextmenu', (e) => {
            const folderId = item.dataset.folderId;
            if (folderId && /^\d+$/.test(folderId)) { // 只有数字ID的文件夹可以编辑删除
                e.preventDefault();
                selectedFolderId = folderId;
                selectedFolderData = folders.find(f => f.id == folderId);
                showContextMenu(e.clientX, e.clientY);
            }
        });
    });
}

// 渲染单个文件夹项
function renderFolderItem(folder, level = 0) {
    const isActive = currentFolder == folder.id;
    let html = `
        <div class="folder-item ${isActive ? 'active' : ''}" 
             data-folder-id="${folder.id}"
             style="margin-left: ${level * 16}px">
            <i class="folder-icon bi bi-folder${folder.children ? '-fill' : ''}"></i>
            <span>${folder.title}</span>
        </div>
    `;
    
    // 递归渲染子文件夹
    if (folder.children) {
        folder.children.forEach(child => {
            html += renderFolderItem(child, level + 1);
        });
    }
    
    return html;
}

// 选择文件夹
function selectFolder(folderId) {
    currentFolder = folderId;
    document.querySelectorAll('.folder-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-folder-id="${folderId}"]`).classList.add('active');
    
    // 更新面包屑导航
    updateBreadcrumb(folderId);
    
    loadFiles();
}

// 更新面包屑导航
function updateBreadcrumb(folderId) {
    const breadcrumb = document.getElementById('breadcrumb');
    let breadcrumbText = '';
    
    if (folderId === 'all') {
        breadcrumbText = '<i class="bi bi-house me-2"></i><span>所有文件</span>';
    } else if (folderId === 'images') {
        breadcrumbText = '<i class="bi bi-image me-2"></i><span>图片文件</span>';
    } else if (folderId === 'documents') {
        breadcrumbText = '<i class="bi bi-file-text me-2"></i><span>文档文件</span>';
    } else if (folderId === 'recent') {
        breadcrumbText = '<i class="bi bi-clock-history me-2"></i><span>最近上传</span>';
    } else if (/^\d+$/.test(folderId)) {
        // 查找文件夹名称
        const folder = folders.find(f => f.id == folderId);
        const folderName = folder ? folder.title : `文件夹 ${folderId}`;
        breadcrumbText = `<i class="bi bi-folder me-2"></i><span>${folderName}</span> <span class="text-success ms-2">(可上传)</span>`;
    } else {
        breadcrumbText = '<i class="bi bi-house me-2"></i><span>未知位置</span>';
    }
    
    breadcrumb.innerHTML = breadcrumbText;
}

// 加载文件
async function loadFiles() {
    const params = new URLSearchParams({
        folder: currentFolder,
        page: currentPage,
        limit: 24
    });
    
    // 添加筛选参数
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    const search = document.getElementById('searchInput').value;
    
    if (year) params.append('year', year);
    if (month) params.append('month', month);
    if (search) params.append('filename', search);
    
    try {
        const response = await fetch(`/gallery/api/files?${params.toString()}`);
        const result = await response.json();
        
        if (result.success) {
            files = result.data;
            paginationInfo = result.pagination;
            console.log('Files loaded successfully:', files, 'Pagination:', paginationInfo);
            renderFiles();
            updateStats(result.pagination);
            renderPagination();
        } else {
            console.error('API返回错误:', result);
            showError('加载文件失败: ' + (result.error || '未知错误'));
            // 设置空的分页信息
            paginationInfo = {
                current_page: 1,
                total: 0,
                per_page: 24,
                total_pages: 0
            };
            renderPagination();
        }
    } catch (error) {
        console.error('加载文件失败:', error);
        showError('加载文件失败: ' + error.message);
        // 设置空的分页信息
        paginationInfo = {
            current_page: 1,
            total: 0,
            per_page: 24,
            total_pages: 0
        };
        renderPagination();
    }
}

// 渲染文件
function renderFiles() {
    const grid = document.getElementById('fileGrid');
    
    if (Object.keys(files).length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="empty-icon bi bi-folder2-open"></i>
                <h5>暂无文件</h5>
                <p>点击上传文件按钮开始添加文件</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    for (const [, fileList] of Object.entries(files)) {
        fileList.forEach(file => {
            const mediaType = file.isVideo ? 'video' : (file.isImage ? 'image' : 'file');
            const fileUrl = file.publicUrl || '';
            const fileName = file.originalName || '';
            const mimeType = file.mimeType || '';
            const createTime = file.createTime || '';
            const formattedSize = file.formattedSize || '';

            const safeUrlAttr = escapeHtmlAttr(fileUrl);
            const safeNameAttr = escapeHtmlAttr(fileName);
            const safeMimeAttr = escapeHtmlAttr(mimeType);
            const safeNameText = escapeHtmlText(fileName);
            const safeCreateText = escapeHtmlText(createTime);
            const safeSizeText = escapeHtmlText(formattedSize);

            const previewUrlJs = escapeJsString(fileUrl);
            const previewNameJs = escapeJsString(fileName);
            const previewMimeJs = escapeJsString(mimeType);
            const copyUrlJs = escapeJsString(fileUrl);
            
            html += `
                <div class="file-item ${isSelectMode ? 'selectable' : ''}"
                     data-file-id="${file.id}"
                     data-file-url="${safeUrlAttr}"
                     data-media-type="${mediaType}"
                     data-mime-type="${safeMimeAttr}"
                     data-file-name="${safeNameAttr}"
                     style="${isSelectMode ? 'cursor: pointer;' : ''}">
                    ${!isSelectMode ? `
                        <div class="file-popover" data-file-id="${file.id}">
                            ${(file.isImage || file.isVideo) ? `
                                <div class="popover-item" onclick="previewFile(${file.id}, '${previewUrlJs}', '${previewNameJs}', '${previewMimeJs}')" title="预览">
                                    <i class="bi bi-eye"></i>
                                    <span>预览</span>
                                </div>
                            ` : ''}
                            <div class="popover-item" onclick="copyFileUrl('${copyUrlJs}')" title="复制地址">
                                <i class="bi bi-link-45deg"></i>
                                <span>复制地址</span>
                            </div>
                            <div class="popover-item danger" onclick="deleteFile('${file.id}')" title="删除">
                                <i class="bi bi-trash"></i>
                                <span>删除</span>
                            </div>
                        </div>
                    ` : ''}
                    <div class="file-preview">
                        ${file.isImage ? 
                            `<img src="${safeUrlAttr}" alt="${safeNameAttr}" loading="lazy">` : 
                            file.isVideo ?
                                `<div class="video-container" style="position: relative; width: 100%; height: 160px;">
                                    <video src="${safeUrlAttr}" 
                                           muted 
                                           preload="metadata" 
                                           style="width: 100%; height: 100%; object-fit: cover; background: #000;"
                                           data-video-url="${safeUrlAttr}"
                                           data-video-name="${safeNameAttr}"
                                           controls
                                           >
                                    </video>
                                    ${!isSelectMode ? `
                                    <div class="video-play-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); cursor: pointer;" onclick="playVideoPreview('${previewUrlJs}', '${previewNameJs}')">
                                        <div style="width: 50px; height: 50px; background: rgba(0,0,0,0.7); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-play-fill" style="color: white; font-size: 20px; margin-left: 3px;"></i>
                                        </div>
                                    </div>
                                    ` : ''}
                                    <div class="video-badge" style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; display: flex; align-items: center; gap: 4px;">
                                        <i class="bi bi-camera-video"></i>
                                        <span>视频</span>
                                    </div>
                                </div>` :
                                `<i class="file-icon ${getFileIcon(file.mimeType)}"></i>`
                        }
                        ${isSelectMode ? '<div class="select-overlay"><i class="bi bi-check-circle-fill"></i></div>' : ''}
                    </div>
                    <div class="file-info">
                        <div class="file-name" title="${safeNameAttr}">${safeNameText}</div>
                        <div class="file-meta">
                            <span>${safeCreateText}</span>
                            <span>${safeSizeText}</span>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    grid.innerHTML = html;

    if (isSelectMode) {
        grid.querySelectorAll('.file-item.selectable').forEach((el) => {
            const url = el.getAttribute('data-file-url');
            const mediaType = el.getAttribute('data-media-type') || 'file';
            const mimeType = el.getAttribute('data-mime-type') || '';
            const fileName = el.getAttribute('data-file-name') || '';

            el.addEventListener('click', () => {
                if (!url) {
                    return;
                }

                if (isMultiSelect) {
                    toggleSelectMedia(el, {
                        url,
                        mediaType,
                        metadata: {
                            mimeType,
                            fileName,
                        },
                    });

                    return;
                }

                selectMedia(url, mediaType, {
                    mimeType,
                    fileName,
                });
            });

            if (url && selectedItems.has(url)) {
                el.classList.add('selected');
            }
        });
    }
}

// 获取文件图标
function getFileIcon(mimeType) {
    if (mimeType === 'image') return 'bi-image text-success';
    if (mimeType === 'document') return 'bi-file-text text-primary';
    if (mimeType === 'audio') return 'bi-music-note text-warning';
    if (mimeType === 'video') return 'bi-play-circle text-danger';
    return 'bi-file-earmark text-muted';
}

// 更新统计信息
function updateStats(pagination) {
    const stats = document.getElementById('fileStats');
    stats.textContent = `共 ${pagination.total} 个文件`;
}

// 显示/隐藏上传区域
function showUploadArea() {
    // 检查是否选择了具体的文件夹
    if (!currentFolder || !/^\d+$/.test(currentFolder)) {
        showError('请先在左侧选择一个具体的文件夹，然后再上传文件');
        return;
    }
    
    document.getElementById('uploadBackdrop').style.display = 'block';
    document.getElementById('uploadArea').style.display = 'flex';
}

function hideUploadArea() {
    document.getElementById('uploadBackdrop').style.display = 'none';
    document.getElementById('uploadArea').style.display = 'none';
}

// 显示/隐藏新建文件夹模态框
function showCreateFolderModal() {
    document.getElementById('createFolderModal').style.display = 'flex';
    document.getElementById('folderName').focus();
}

function hideCreateFolderModal() {
    document.getElementById('createFolderModal').style.display = 'none';
    document.getElementById('createFolderForm').reset();
}

// 创建文件夹
async function handleCreateFolder(e) {
    e.preventDefault();
    
    const name = document.getElementById('folderName').value.trim();
    const description = document.getElementById('folderDescription').value.trim();
    
    if (!name) {
        showError('请输入文件夹名称');
        return;
    }
    
    try {
        const response = await fetch('/gallery/api/folders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: name,
                description: description || null
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('文件夹创建成功');
            hideCreateFolderModal();
            loadFolders();
        } else {
            showError('创建失败: ' + result.error);
        }
    } catch (error) {
        console.error('创建文件夹失败:', error);
        showError('创建失败: ' + error.message);
    }
}

// 文件操作
async function deleteFile(fileId) {
    if (!confirm('确定要删除这个文件吗？删除后无法恢复。')) {
        return;
    }
    
    console.log('开始删除文件:', fileId);
    
    try {
        const response = await fetch(`/gallery/api/files/${fileId}`, {
            method: 'DELETE'
        });
        
        console.log('删除请求响应状态:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('删除结果:', result);
        
        if (result.success) {
            showSuccess('文件删除成功');
            loadFiles();
        } else {
            showError('删除失败: ' + result.error);
        }
    } catch (error) {
        console.error('删除文件异常:', error);
        showError('删除失败: ' + error.message);
    }
}

function detectMediaPreviewType(fileUrl, fileName, mimeType) {
    const normalizedMime = (mimeType || '').toLowerCase();
    if (normalizedMime.startsWith('image/')) {
        return 'image';
    }
    if (normalizedMime.startsWith('video/')) {
        return 'video';
    }

    const name = (fileName || fileUrl || '').toLowerCase();
    const extension = name.includes('.') ? name.split('.').pop() : '';

    if (!extension) {
        return 'unknown';
    }

    const imageExtensions = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
    if (imageExtensions.has(extension)) {
        return 'image';
    }

    const videoExtensions = new Set(['mp4', 'mov', 'm4v', 'webm', 'avi', 'flv', 'wmv', 'mkv']);
    if (videoExtensions.has(extension)) {
        return 'video';
    }

    return 'unknown';
}

function previewFile(fileId, fileUrl, fileName, mimeType) {
    const previewType = detectMediaPreviewType(fileUrl, fileName, mimeType);

    if (previewType === 'image') {
        const modal = document.createElement('div');
        modal.className = 'image-preview-modal';
        modal.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99999; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <div style="position: relative;" onclick="event.stopPropagation()">
                    <img src="${fileUrl}" alt="${fileName}" style="max-width: 90%; max-height: 90vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                    <button onclick="this.closest('.image-preview-modal').remove()" style="position: absolute; top: -15px; right: -15px; background: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.3); font-size: 16px;">×</button>
                </div>
            </div>
        `;
        modal.onclick = function() { modal.remove(); };
        document.body.appendChild(modal);
        return;
    }

    if (previewType === 'video') {
        playVideoPreview(fileUrl, fileName);
        return;
    }

    window.open(fileUrl, '_blank');
}

function copyFileUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        showSuccess('文件地址已复制到剪贴板');
    }).catch(err => {
        console.error('复制失败:', err);
        showError('复制失败');
    });
}

// 拖拽上传处理
function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('active');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('active');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('active');
    const files = Array.from(e.dataTransfer.files);
    if (files.length > 0) {
        uploadFiles(files);
    }
}

function handleFileSelection(e) {
    const files = Array.from(e.target.files);
    if (files.length > 0) {
        uploadFiles(files);
    }
}

// 上传文件
async function uploadFiles(files) {
    // 验证是否选择了具体的文件夹
    if (!currentFolder || !/^\d+$/.test(currentFolder)) {
        showError('请先在左侧选择一个具体的文件夹，然后再上传文件');
        return;
    }
    
    hideUploadArea();
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        console.log('上传文件:', file.name);
        
        try {
            await uploadSingleFile(file);
        } catch (error) {
            console.error('上传失败:', error);
            showError(`文件 "${file.name}" 上传失败: ${error.message}`);
        }
    }
    
    loadFiles();
}

function uploadSingleFile(file) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', file);
        
        // 添加文件夹ID - 只有选择了具体的数字ID文件夹才上传
        if (currentFolder && /^\d+$/.test(currentFolder)) {
            formData.append('folderId', currentFolder);
        } else {
            reject(new Error('请先选择一个具体的文件夹再上传文件'));
            return;
        }

        const xhr = new XMLHttpRequest();
        
        xhr.onload = function() {
            if (xhr.status === 201) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.error || '上传失败'));
                    }
                } catch (e) {
                    reject(new Error('服务器返回数据格式错误'));
                }
            } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    reject(new Error(response.error || `HTTP ${xhr.status}: ${xhr.statusText}`));
                } catch (e) {
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('网络错误'));
        };
        
        xhr.open('POST', '/gallery/api/upload', true);
        xhr.send(formData);
    });
}

// 应用筛选
function applyFilters() {
    currentPage = 1;
    loadFiles();
}

// 工具函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000;';
    alert.textContent = message;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000;';
    alert.textContent = message;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 2000);
}

// 右键菜单功能
function showContextMenu(x, y) {
    const menu = document.getElementById('contextMenu');
    menu.style.display = 'block';
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
}

function hideContextMenu() {
    document.getElementById('contextMenu').style.display = 'none';
}

// 编辑文件夹
function editFolder() {
    if (selectedFolderData) {
        document.getElementById('editFolderName').value = selectedFolderData.title;
        document.getElementById('editFolderDescription').value = selectedFolderData.description || '';
        showEditFolderModal();
    }
    hideContextMenu();
}

// 删除文件夹
async function deleteFolder() {
    if (selectedFolderId && confirm('确定要删除这个文件夹吗？')) {
        try {
            const response = await fetch(`/gallery/api/folders/${selectedFolderId}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (result.success) {
                showSuccess('文件夹删除成功');
                loadFolders();
                if (currentFolder == selectedFolderId) {
                    selectFolder('all');
                }
            } else {
                showError(result.error || '删除失败');
            }
        } catch (error) {
            showError('删除失败: ' + error.message);
        }
    }
    hideContextMenu();
}

// 显示编辑文件夹模态框
function showEditFolderModal() {
    document.getElementById('editFolderModal').style.display = 'flex';
}

// 隐藏编辑文件夹模态框
function hideEditFolderModal() {
    console.log('Hiding edit folder modal');
    document.getElementById('editFolderModal').style.display = 'none';
    document.getElementById('editFolderForm').reset();
}

// 处理编辑文件夹表单提交
document.addEventListener('DOMContentLoaded', function() {
    // 确保DOM加载完成后再绑定事件
    const editForm = document.getElementById('editFolderForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('editFolderName').value.trim();
            const description = document.getElementById('editFolderDescription').value.trim();
            
            if (!name) {
                showError('请输入文件夹名称');
                return;
            }
            
            try {
                const response = await fetch(`/gallery/api/folders/${selectedFolderId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: name,
                        description: description || null
                    })
                });
                
                const result = await response.json();
                console.log('Update result:', result);
                if (result.success) {
                    console.log('Update successful, hiding modal and reloading folders');
                    showSuccess('文件夹更新成功');
                    hideEditFolderModal();
                    // 清理选中状态
                    selectedFolderId = null;
                    selectedFolderData = null;
                    await loadFolders(); // 等待文件夹加载完成
                } else {
                    showError(result.error || '更新失败');
                }
            } catch (error) {
                showError('更新失败: ' + error.message);
            }
        });
    }
});

// 点击空白处隐藏右键菜单
document.addEventListener('click', hideContextMenu);

// 防止页面默认拖拽行为
document.addEventListener('dragover', function(e) {
    e.preventDefault();
});

document.addEventListener('drop', function(e) {
    if (!document.getElementById('uploadArea').contains(e.target)) {
        e.preventDefault();
    }
});

// 媒体选择函数（用于选择模式）
function selectMedia(url, mediaType, metadata = {}) {
    if (!isSelectMode || !parent || parent === window || !url) {
        return;
    }

    const payload = {
        type: 'mediaSelected',
        mediaType,
        url,
        metadata,
        token: postMessageToken,
    };

    try {
        parent.postMessage(payload, window.location.origin);
    } catch (e) {
        parent.postMessage(payload, window.location.origin);
    }

    if (mediaType === 'image') {
        const legacy = {
            type: 'imageSelected',
            url,
            token: postMessageToken,
        };

        try {
            parent.postMessage(legacy, window.location.origin);
        } catch (e) {
            parent.postMessage(legacy, window.location.origin);
        }
    }
}

function selectImage(imageUrl) {
    selectMedia(imageUrl, 'image');
}

function selectVideo(videoUrl, mimeType = '', fileName = '') {
    selectMedia(videoUrl, 'video', {
        mimeType,
        fileName,
    });
}

// 多选：切换选择状态
function toggleSelectMedia(el, item) {
    const key = item?.url;
    if (!key) {
        return;
    }

    if (selectedItems.has(key)) {
        selectedItems.delete(key);
        el.classList.remove('selected');
    } else {
        selectedItems.set(key, item);
        el.classList.add('selected');
    }

    updateSelectionBar();
}

function ensureSelectionBar() {
    if (document.getElementById('multiSelectBar')) return;
    const bar = document.createElement('div');
    bar.id = 'multiSelectBar';
    bar.style.cssText = 'position:fixed; left:50%; transform:translateX(-50%); bottom:20px; background:#111c; color:#fff; padding:8px 12px; border-radius:20px; z-index:2000; display:none; backdrop-filter: blur(6px);';
    bar.innerHTML = '<span id="multiSelectCount">已选择 0 项</span>\n        <button class="btn-toolbar btn-primary" style="margin-left:10px; padding:4px 10px; border-radius:14px;" onclick="confirmMultiSelection()">确定</button>';
    document.body.appendChild(bar);
}

function updateSelectionBar() {
    const bar = document.getElementById('multiSelectBar');
    if (!bar) return;
    const count = selectedItems.size;
    bar.querySelector('#multiSelectCount').textContent = '已选择 ' + count + ' 项';
    bar.style.display = count > 0 ? 'block' : 'none';
}

function confirmMultiSelection() {
    if (!parent || parent === window) return;
    if (selectedItems.size === 0) return;

    const items = Array.from(selectedItems.values());

    const payload = {
        type: 'mediaSelectedMultiple',
        items,
        token: postMessageToken,
    };

    try {
        parent.postMessage(payload, window.location.origin);
    } catch (e) {
        parent.postMessage(payload, window.location.origin);
    }

    const imageUrls = items
        .filter((item) => item.mediaType === 'image')
        .map((item) => item.url);

    if (imageUrls.length > 0) {
        const legacy = {
            type: 'imagesSelected',
            urls: imageUrls,
            token: postMessageToken,
        };

        try {
            parent.postMessage(legacy, window.location.origin);
        } catch (e) {
            parent.postMessage(legacy, window.location.origin);
        }
    }

    selectedItems.clear();
    document.querySelectorAll('.file-item.selected').forEach((el) => el.classList.remove('selected'));
    updateSelectionBar();
}

// 分页相关函数
function renderPagination() {
    const wrapper = document.getElementById('paginationWrapper');
    const info = document.getElementById('paginationInfo');
    const total = document.getElementById('paginationTotal');
    const pages = document.getElementById('paginationPages');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');

    // 调试日志
    console.log('renderPagination called with paginationInfo:', paginationInfo);

    // 检查元素是否存在
    if (!wrapper) {
        console.error('分页容器元素未找到');
        return;
    }

    // CSS已设置display: flex !important，这里不需要控制显示/隐藏
    // 分页组件始终显示，但内容会根据数据动态更新

    // 更新分页信息
    const currentPage = paginationInfo.current_page || 1;
    const totalPages = paginationInfo.total_pages || 1;
    const totalFiles = paginationInfo.total || 0;
    
    info.textContent = `第 ${currentPage} 页，共 ${totalPages} 页`;
    total.textContent = `共 ${totalFiles} 个文件`;

    // 更新上一页/下一页按钮状态
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;

    // 生成页码按钮
    renderPageNumbers(pages);
}

function renderPageNumbers(container) {
    const currentPage = paginationInfo.current_page;
    const totalPages = paginationInfo.total_pages;
    
    container.innerHTML = '';

    // 计算显示的页码范围
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    // 调整范围以始终显示5个页码（如果可能）
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, startPage + 4);
        } else {
            startPage = Math.max(1, endPage - 4);
        }
    }

    // 如果不是从第1页开始，显示第1页和省略号
    if (startPage > 1) {
        addPageButton(container, 1, false);
        if (startPage > 2) {
            addEllipsis(container);
        }
    }

    // 显示页码
    for (let i = startPage; i <= endPage; i++) {
        addPageButton(container, i, i === currentPage);
    }

    // 如果不是到最后一页，显示省略号和最后一页
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            addEllipsis(container);
        }
        addPageButton(container, totalPages, false);
    }
}

function addPageButton(container, page, isActive) {
    const btn = document.createElement('button');
    btn.className = `pagination-page${isActive ? ' active' : ''}`;
    btn.textContent = page;
    btn.onclick = () => changePage(page);
    container.appendChild(btn);
}

function addEllipsis(container) {
    const ellipsis = document.createElement('span');
    ellipsis.className = 'pagination-ellipsis';
    ellipsis.textContent = '...';
    container.appendChild(ellipsis);
}

function changePage(page) {
    if (page < 1 || page > paginationInfo.total_pages || page === currentPage) {
        return;
    }
    
    currentPage = page;
    loadFiles();
}

// 获取视频MIME类型
function getVideoMimeType(url) {
    const extension = url.toLowerCase().split('.').pop();
    const mimeTypes = {
        'mp4': 'video/mp4',
        'avi': 'video/x-msvideo',
        'mov': 'video/quicktime',
        'wmv': 'video/x-ms-wmv',
        'flv': 'video/x-flv',
        'webm': 'video/webm',
        'mkv': 'video/x-matroska',
        'm4v': 'video/x-m4v'
    };
    return mimeTypes[extension] || 'video/mp4';
}

// 视频预览播放功能
function playVideoPreview(videoUrl, videoName) {
    console.log('播放视频预览:', videoUrl, videoName);
    
    // 创建一个简单的测试视频预览
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 99999; display: flex; align-items: center; justify-content: center;';
    modal.id = 'videoPreviewModal';
    
    const videoContent = `
        <div style="position: relative; width: 90%; max-width: 900px; background: #000; border-radius: 8px; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="color: white; margin: 0;">${videoName}</h3>
                <button onclick="this.closest('#videoPreviewModal').remove()" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 18px;">×</button>
            </div>
            <video controls autoplay style="width: 100%; max-height: 70vh; background: #000;">
                <source src="${videoUrl}" type="video/mp4">
                <p>您的浏览器不支持视频播放。</p>
            </video>
            <div style="margin-top: 15px; text-align: center;">
                <button onclick="window.open('${videoUrl}', '_blank')" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;">在新窗口打开</button>
                <button onclick="this.closest('#videoPreviewModal').remove()" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">关闭</button>
            </div>
        </div>
    `;
    
    modal.innerHTML = videoContent;
    document.body.appendChild(modal);
    
    // 点击背景关闭
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // ESC 键关闭
    document.addEventListener('keydown', function closeOnEscape(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('videoPreviewModal');
            if (modal) {
                modal.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        }
    });
    
    // 自动移除旧的模态框（如果存在）
    const oldModal = document.getElementById('videoPreviewModal');
    if (oldModal && oldModal !== modal) {
        oldModal.remove();
    }
}
