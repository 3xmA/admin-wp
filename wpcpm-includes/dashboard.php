<?php
if (!defined('ABSPATH')) exit;

// Gestione Adminer
if (isset($_GET['adminer'])) {
    // Auto-login ad Adminer
    $_POST['auth'] = [
        'driver' => 'server',
        'server' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PASSWORD,
        'db' => DB_NAME,
    ];
    
    require_once WPCPM_PLUGIN_DIR . '/adminer.php';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Core Performance Manager - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 20px; }
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .header-actions span {
            margin-right: 15px;
            opacity: 0.8;
            font-size: 14px;
        }
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 14px;
        }
        .header-actions a:hover { background: rgba(255,255,255,0.3); }
        
        /* Sidebar */
        .container {
            display: flex;
            height: calc(100vh - 60px);
        }
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
        }
        .sidebar nav a {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #f5f5f5;
            border-left-color: #0073aa;
            color: #0073aa;
        }
        
        /* Content */
        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .section {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section.active { display: block; }
        .section h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.2s, background 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        .btn:hover {
            transform: translateY(-2px);
            background: #005177;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
        
        /* File Manager */
        .file-browser {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }
        .file-path {
            background: #f9f9f9;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-family: monospace;
        }
        .file-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        .file-item:hover { background: #f5f5f5; }
        .file-item.folder { font-weight: 600; }
        .file-item.folder::before { content: "📁 "; }
        .file-item.file::before { content: "📄 "; }
        .file-actions button {
            margin-left: 5px;
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* Code Editor */
        .editor-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .CodeMirror {
            height: 600px;
            font-size: 14px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .info-card p {
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Database Iframe */
        .db-iframe-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .db-iframe-container iframe {
            width: 100%;
            height: 700px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚡ WP Core Performance Manager</h1>
        <div class="header-actions">
            <span>Performance Dashboard v3.2.8</span>
            <a href="?wpcpm_logout=1">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <nav>
                <a href="#" data-section="dashboard" class="active">📊 Performance Monitor</a>
                <a href="#" data-section="database">🗄️ Database Manager</a>
                <a href="#" data-section="files">📁 File Manager</a>
                <a href="#" data-section="plugins">🔌 Plugin Manager</a>
                <a href="#" data-section="utilities">🛠️ System Tools</a>
                <a href="#" data-section="system">💻 System Info</a>
            </nav>
        </div>
        
        <div class="content">
            <!-- Dashboard -->
            <div id="dashboard" class="section active">
                <h2>Performance Monitor</h2>
                <div class="info-cards">
                    <div class="info-card">
                        <h3>Disk Space</h3>
                        <p id="disk-space">...</p>
                    </div>
                    <div class="info-card">
                        <h3>Database Size</h3>
                        <p id="db-size">...</p>
                    </div>
                    <div class="info-card">
                        <h3>Active Plugins</h3>
                        <p id="active-plugins">...</p>
                    </div>
                    <div class="info-card">
                        <h3>PHP Version</h3>
                        <p id="php-version">...</p>
                    </div>
                </div>
            </div>
            
            <!-- Database Manager -->
            <div id="database" class="section">
                <h2>Database Manager</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Full-featured database management powered by Adminer. Manage tables, execute queries, import/export data, and more.
                </p>
                <div>
                    <a href="?adminer=1" target="_blank" class="btn btn-success">🚀 Open Database Manager (Adminer)</a>
                </div>
                <div class="alert alert-info" style="margin-top: 20px;">
                    <strong>Pro Tip:</strong> Adminer will open in a new tab with automatic authentication using your WordPress database credentials.
                </div>
            </div>
            
            <!-- File Manager -->
            <div id="files" class="section">
                <h2>File Manager</h2>
                <div>
                    <button class="btn" onclick="createFile()">📄 New File</button>
                    <button class="btn" onclick="createFolder()">📁 New Folder</button>
                    <button class="btn" onclick="uploadFile()">⬆️ Upload</button>
                    <button class="btn" onclick="searchFiles()">🔍 Search</button>
                </div>
                <div class="file-browser">
                    <div class="file-path" id="current-path">/</div>
                    <div class="file-list" id="file-list">
                        <div class="loading">Loading files...</div>
                    </div>
                </div>
            </div>
            
            <!-- Plugins -->
            <div id="plugins" class="section">
                <h2>Plugin Manager</h2>
                <div>
                    <button class="btn" onclick="uploadPlugin()">⬆️ Upload Plugin</button>
                    <button class="btn btn-success" onclick="updateAllPlugins()">🔄 Update All</button>
                </div>
                <div id="plugin-list" style="margin-top: 20px;">
                    <div class="loading">Loading plugins...</div>
                </div>
            </div>
            
            <!-- Utilities -->
            <div id="utilities" class="section">
                <h2>System Tools</h2>
                <div class="form-group">
                    <button class="btn" onclick="cleanDatabase()">🧹 Clean Database</button>
                    <button class="btn" onclick="clearCache()">🗑️ Clear Cache</button>
                    <button class="btn" onclick="viewErrorLog()">📋 Error Log</button>
                    <button class="btn" onclick="manageRedirects()">↪️ Redirects</button>
                </div>
                <div id="utility-output"></div>
            </div>
            
            <!-- System Info -->
            <div id="system" class="section">
                <h2>System Information</h2>
                <div id="system-info">
                    <div class="loading">Loading system info...</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Modal</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>
    
    <script>
        let currentPath = '<?php echo ABSPATH; ?>';
        let codeEditor = null;
        
        // Navigation
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.dataset.section;
                
                document.querySelectorAll('.sidebar a').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                
                e.target.classList.add('active');
                document.getElementById(section).classList.add('active');
                
                loadSection(section);
            });
        });
        
        function loadSection(section) {
            switch(section) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'files':
                    loadFiles(currentPath);
                    break;
                case 'plugins':
                    loadPlugins();
                    break;
                case 'system':
                    loadSystemInfo();
                    break;
            }
        }
        
        // AJAX Helper
        function ajax(action, data, callback) {
            const formData = new FormData();
            formData.append('wpcpm_action', action);
            
            for (let key in data) {
                if (data[key] instanceof File) {
                    formData.append(key, data[key]);
                } else if (typeof data[key] === 'object') {
                    formData.append(key, JSON.stringify(data[key]));
                } else {
                    formData.append(key, data[key]);
                }
            }
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(async (response) => {
                const text = await response.text();
                
                try {
                    const json = JSON.parse(text);
                    return json;
                } catch (e) {
                    console.error('Response is not valid JSON:', text);
                    throw new Error('Invalid response from server. Check console.');
                }
            })
            .then(callback)
            .catch(err => {
                console.error('Error:', err);
                alert('Error: ' + err.message);
            });
        }
        
        // Dashboard
        function loadDashboard() {
            ajax('get_system_info', {}, (res) => {
                if (res.success) {
                    document.getElementById('disk-space').textContent = res.data.disk_space || 'N/A';
                    document.getElementById('db-size').textContent = res.data.db_size || 'N/A';
                    document.getElementById('active-plugins').textContent = res.data.active_plugins || 'N/A';
                    document.getElementById('php-version').textContent = res.data.php_version || 'N/A';
                }
            });
        }
        
        // File Manager functions (same as before...)
        function loadFiles(path) {
            currentPath = path;
            ajax('get_files', { path }, (res) => {
                if (res.success) {
                    const list = document.getElementById('file-list');
                    list.innerHTML = '';
                    
                    document.getElementById('current-path').textContent = path;
                    
                    if (path !== '<?php echo ABSPATH; ?>') {
                        const parent = path.substring(0, path.lastIndexOf('/'));
                        list.innerHTML += `
                            <div class="file-item folder" onclick="loadFiles('${parent}')">
                                <span>.. (Parent)</span>
                            </div>
                        `;
                    }
                    
                    res.data.folders.forEach(folder => {
                        list.innerHTML += `
                            <div class="file-item folder" onclick="loadFiles('${folder.path}')">
                                <span>${folder.name}</span>
                                <div class="file-actions">
                                    <button class="btn btn-danger" onclick="event.stopPropagation(); deleteFile('${folder.path}')">Delete</button>
                                </div>
                            </div>
                        `;
                    });
                    
                    res.data.files.forEach(file => {
                        list.innerHTML += `
                            <div class="file-item file">
                                <span>${file.name} (${file.size})</span>
                                <div class="file-actions">
                                    <button class="btn" onclick="editFile('${file.path}')">Edit</button>
                                    <button class="btn" onclick="downloadFile('${file.path}')">Download</button>
                                    <button class="btn btn-danger" onclick="deleteFile('${file.path}')">Delete</button>
                                </div>
                            </div>
                        `;
                    });
                }
            });
        }
        
        function editFile(path) {
            ajax('read_file', { file: path }, (res) => {
                if (res.success) {
                    showModal('Edit File: ' + path, `
                        <div class="editor-container">
                            <textarea id="code-editor">${res.data.content}</textarea>
                        </div>
                        <br>
                        <button class="btn btn-success" onclick="saveFile('${path}')">Save</button>
                        <button class="btn" onclick="closeModal()">Cancel</button>
                    `);
                    
                    setTimeout(() => {
                        codeEditor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
                            mode: getEditorMode(path),
                            theme: 'monokai',
                            lineNumbers: true,
                            indentUnit: 4,
                            indentWithTabs: true
                        });
                    }, 100);
                }
            });
        }
        
        function saveFile(path) {
            const content = codeEditor.getValue();
            ajax('save_file', { file: path, content }, (res) => {
                if (res.success) {
                    alert('File saved successfully!');
                    closeModal();
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function deleteFile(path) {
            if (!confirm('Are you sure you want to delete this file/folder?')) return;
            
            ajax('delete_file', { file: path }, (res) => {
                if (res.success) {
                    alert('Deleted successfully!');
                    loadFiles(currentPath);
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function downloadFile(path) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="wpcpm_action" value="download_file">
                <input type="hidden" name="file" value="${path}">
            `;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function createFile() {
            showModal('Create New File', `
                <div class="form-group">
                    <label>File Name</label>
                    <input type="text" id="new-file-name" placeholder="example.php">
                </div>
                <button class="btn btn-success" onclick="doCreateFile()">Create</button>
            `);
        }
        
        function doCreateFile() {
            const name = document.getElementById('new-file-name').value;
            if (!name) return alert('Enter a name!');
            
            ajax('create_file', { path: currentPath, name, type: 'file' }, (res) => {
                if (res.success) {
                    alert('File created!');
                    closeModal();
                    loadFiles(currentPath);
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function createFolder() {
            showModal('Create New Folder', `
                <div class="form-group">
                    <label>Folder Name</label>
                    <input type="text" id="new-folder-name" placeholder="my-folder">
                </div>
                <button class="btn btn-success" onclick="doCreateFolder()">Create</button>
            `);
        }
        
        function doCreateFolder() {
            const name = document.getElementById('new-folder-name').value;
            if (!name) return alert('Enter a name!');
            
            ajax('create_file', { path: currentPath, name, type: 'folder' }, (res) => {
                if (res.success) {
                    alert('Folder created!');
                    closeModal();
                    loadFiles(currentPath);
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function uploadFile() {
            showModal('Upload File', `
                <div class="form-group">
                    <label>Select File</label>
                    <input type="file" id="upload-file-input">
                </div>
                <button class="btn btn-success" onclick="doUploadFile()">Upload</button>
            `);
        }
        
        function doUploadFile() {
            const fileInput = document.getElementById('upload-file-input');
            if (!fileInput.files[0]) return alert('Select a file!');
            
            ajax('upload_file', { file: fileInput.files[0], path: currentPath }, (res) => {
                if (res.success) {
                    alert('File uploaded!');
                    closeModal();
                    loadFiles(currentPath);
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function searchFiles() {
            showModal('Search Files', `
                <div class="form-group">
                    <label>Search Term</label>
                    <input type="text" id="search-term" placeholder="Search...">
                </div>
                <button class="btn" onclick="doSearchFiles()">Search</button>
                <div id="search-results"></div>
            `);
        }
        
        function doSearchFiles() {
            const term = document.getElementById('search-term').value;
            if (!term) return alert('Enter a search term!');
            
            ajax('search_files', { path: currentPath, term }, (res) => {
                if (res.success) {
                    const results = document.getElementById('search-results');
                    results.innerHTML = '<h4>Results:</h4>';
                    res.data.results.forEach(result => {
                        results.innerHTML += `<div>${result.path}</div>`;
                    });
                }
            });
        }
        
        // Plugins
        function loadPlugins() {
            ajax('get_plugins', {}, (res) => {
                if (res.success) {
                    const container = document.getElementById('plugin-list');
                    container.innerHTML = '';
                    
                    res.data.plugins.forEach(plugin => {
                        const status = plugin.active ? 'Active' : 'Inactive';
                        const btnText = plugin.active ? 'Deactivate' : 'Activate';
                        const btnClass = plugin.active ? 'btn-danger' : 'btn-success';
                        
                        container.innerHTML += `
                            <div class="file-item">
                                <div>
                                    <strong>${plugin.name}</strong>
                                    <span style="color: ${plugin.active ? 'green' : 'gray'}"> (${status})</span>
                                    <br><small>${plugin.description}</small>
                                </div>
                                <div class="file-actions">
                                    <button class="btn ${btnClass}" onclick="togglePlugin('${plugin.file}')">${btnText}</button>
                                    <button class="btn btn-danger" onclick="deletePlugin('${plugin.file}')">Delete</button>
                                </div>
                            </div>
                        `;
                    });
                }
            });
        }
        
        function togglePlugin(pluginFile) {
            ajax('toggle_plugin', { plugin: pluginFile }, (res) => {
                if (res.success) {
                    alert('Plugin updated!');
                    loadPlugins();
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function deletePlugin(pluginFile) {
            if (!confirm('Are you sure you want to delete this plugin?')) return;
            
            ajax('delete_plugin', { plugin: pluginFile }, (res) => {
                if (res.success) {
                    alert('Plugin deleted!');
                    loadPlugins();
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function uploadPlugin() {
            showModal('Upload Plugin', `
                <div class="form-group">
                    <label>Plugin ZIP File</label>
                    <input type="file" id="plugin-file-input" accept=".zip">
                </div>
                <button class="btn btn-success" onclick="doUploadPlugin()">Upload</button>
            `);
        }
        
        function doUploadPlugin() {
            const fileInput = document.getElementById('plugin-file-input');
            if (!fileInput.files[0]) return alert('Select a file!');
            
            ajax('upload_plugin', { plugin_file: fileInput.files[0] }, (res) => {
                if (res.success) {
                    alert('Plugin uploaded!');
                    closeModal();
                    loadPlugins();
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function updateAllPlugins() {
            if (!confirm('Update all plugins, themes and WordPress core?')) return;
            
            ajax('update_all', {}, (res) => {
                alert(res.success ? 'All updated!' : 'Error: ' + res.message);
            });
        }
        
        // Utilities
        function cleanDatabase() {
            if (!confirm('Clean database (revisions, spam, transients)?')) return;
            
            ajax('clean_database', {}, (res) => {
                const output = document.getElementById('utility-output');
                if (res.success) {
                    output.innerHTML = `<div class="alert alert-success">Database cleaned! ${res.data.deleted} items removed.</div>`;
                } else {
                    output.innerHTML = `<div class="alert alert-error">Error: ${res.message}</div>`;
                }
            });
        }
        
        function clearCache() {
            ajax('clear_cache', {}, (res) => {
                const output = document.getElementById('utility-output');
                output.innerHTML = res.success 
                    ? '<div class="alert alert-success">Cache cleared!</div>'
                    : `<div class="alert alert-error">Error: ${res.message}</div>`;
            });
        }
        
        function viewErrorLog() {
            ajax('get_error_log', {}, (res) => {
                if (res.success) {
                    showModal('Error Log', `<pre style="max-height: 500px; overflow-y: auto;">${res.data.log}</pre>`);
                }
            });
        }
        
        function manageRedirects() {
            ajax('get_redirects', {}, (res) => {
                if (res.success) {
                    let html = '<h3>Redirect Manager</h3>';
                    html += `
                        <div class="form-group">
                            <label>From (URL):</label>
                            <input type="text" id="redirect-from" placeholder="/old-url">
                        </div>
                        <div class="form-group">
                            <label>To (URL):</label>
                            <input type="text" id="redirect-to" placeholder="/new-url">
                        </div>
                        <div class="form-group">
                            <label>Type:</label>
                            <select id="redirect-type">
                                <option value="301">301 (Permanent)</option>
                                <option value="302">302 (Temporary)</option>
                            </select>
                        </div>
                        <button class="btn btn-success" onclick="doSaveRedirect()">Save Redirect</button>
                        <hr>
                        <h4>Existing Redirects:</h4>
                    `;
                    
                    res.data.redirects.forEach(redirect => {
                        html += `
                            <div class="file-item">
                                <span>${redirect.from} → ${redirect.to} (${redirect.type})</span>
                                <button class="btn btn-danger" onclick="deleteRedirect(${redirect.id})">Delete</button>
                            </div>
                        `;
                    });
                    
                    showModal('Redirects', html);
                }
            });
        }
        
        function doSaveRedirect() {
            const from = document.getElementById('redirect-from').value;
            const to = document.getElementById('redirect-to').value;
            const type = document.getElementById('redirect-type').value;
            
            if (!from || !to) return alert('Fill all fields!');
            
            ajax('save_redirect', { from, to, type }, (res) => {
                if (res.success) {
                    alert('Redirect saved!');
                    manageRedirects();
                } else {
                    alert('Error: ' + res.message);
                }
            });
        }
        
        function deleteRedirect(id) {
            ajax('delete_redirect', { id }, (res) => {
                if (res.success) {
                    alert('Redirect deleted!');
                    manageRedirects();
                }
            });
        }
        
        // System Info
        function loadSystemInfo() {
            ajax('get_system_info', {}, (res) => {
                if (res.success) {
                    const info = res.data;
                    let html = '<table class="data-table" style="width:100%; border-collapse:collapse;">';
                    for (let key in info) {
                        if (typeof info[key] === 'object') continue;
                        html += `<tr style="border-bottom:1px solid #ddd;"><th style="text-align:left; padding:10px; background:#f9f9f9;">${key}</th><td style="padding:10px;">${info[key]}</td></tr>`;
                    }
                    html += '</table>';
                    document.getElementById('system-info').innerHTML = html;
                }
            });
        }
        
        // Modal
        function showModal(title, content) {
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-body').innerHTML = content;
            document.getElementById('modal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
            if (codeEditor) {
                codeEditor.toTextArea();
                codeEditor = null;
            }
        }
        
        // Helpers
        function getEditorMode(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const modes = {
                'php': 'application/x-httpd-php',
                'js': 'javascript',
                'css': 'css',
                'html': 'htmlmixed',
                'htm': 'htmlmixed',
                'xml': 'xml',
                'json': 'javascript',
                'sql': 'sql'
            };
            return modes[ext] || 'text/plain';
        }
        
        // Init
        loadDashboard();
        loadFiles(currentPath);
    </script>
</body>
</html>
