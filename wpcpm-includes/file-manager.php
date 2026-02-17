<?php
if (!defined('ABSPATH')) exit;

class WPCPM_FileManager {
    
    public static function get_files($path) {
        if (!is_dir($path)) {
            return ['success' => false, 'message' => 'Invalid directory'];
        }
        
        $files = [];
        $folders = [];
        
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $full_path = rtrim($path, '/') . '/' . $item;
            
            if (is_dir($full_path)) {
                $folders[] = [
                    'name' => $item,
                    'path' => $full_path,
                    'permissions' => substr(sprintf('%o', fileperms($full_path)), -4)
                ];
            } else {
                $files[] = [
                    'name' => $item,
                    'path' => $full_path,
                    'size' => self::format_size(filesize($full_path)),
                    'modified' => date('Y-m-d H:i:s', filemtime($full_path)),
                    'permissions' => substr(sprintf('%o', fileperms($full_path)), -4)
                ];
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'folders' => $folders,
                'files' => $files,
                'current_path' => $path
            ]
        ];
    }
    
    public static function read_file($file) {
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        if (!is_readable($file)) {
            return ['success' => false, 'message' => 'File not readable'];
        }
        
        $content = file_get_contents($file);
        
        return [
            'success' => true,
            'data' => [
                'content' => $content,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ]
        ];
    }
    
    public static function save_file($file, $content) {
        // Backup before saving
        if (file_exists($file)) {
            $backup = $file . '.backup-' . time();
            copy($file, $backup);
        }
        
        $result = file_put_contents($file, $content);
        
        if ($result === false) {
            return ['success' => false, 'message' => 'Cannot save file'];
        }
        
        return [
            'success' => true,
            'message' => 'File saved successfully',
            'data' => ['bytes_written' => $result]
        ];
    }
    
    public static function delete_file($file) {
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        if (is_dir($file)) {
            $result = self::delete_directory($file);
        } else {
            $result = unlink($file);
        }
        
        if (!$result) {
            return ['success' => false, 'message' => 'Cannot delete'];
        }
        
        return ['success' => true, 'message' => 'Deleted successfully'];
    }
    
    private static function delete_directory($dir) {
        if (!is_dir($dir)) return false;
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    public static function create_file($path, $name, $type) {
        $full_path = rtrim($path, '/') . '/' . $name;
        
        if (file_exists($full_path)) {
            return ['success' => false, 'message' => 'File/Folder already exists'];
        }
        
        if ($type === 'folder') {
            $result = mkdir($full_path, 0755, true);
        } else {
            $result = file_put_contents($full_path, '');
        }
        
        if (!$result) {
            return ['success' => false, 'message' => 'Cannot create'];
        }
        
        return ['success' => true, 'message' => 'Created successfully'];
    }
    
    public static function rename_file($old_path, $new_name) {
        if (!file_exists($old_path)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        $dir = dirname($old_path);
        $new_path = $dir . '/' . $new_name;
        
        if (file_exists($new_path)) {
            return ['success' => false, 'message' => 'Name already exists'];
        }
        
        $result = rename($old_path, $new_path);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Cannot rename'];
        }
        
        return ['success' => true, 'message' => 'Renamed successfully'];
    }
    
    public static function copy_file($source, $destination) {
        if (!file_exists($source)) {
            return ['success' => false, 'message' => 'Source file not found'];
        }
        
        if (is_dir($source)) {
            $result = self::copy_directory($source, $destination);
        } else {
            $result = copy($source, $destination);
        }
        
        if (!$result) {
            return ['success' => false, 'message' => 'Cannot copy'];
        }
        
        return ['success' => true, 'message' => 'Copied successfully'];
    }
    
    private static function copy_directory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $src = $source . '/' . $item;
            $dst = $destination . '/' . $item;
            
            if (is_dir($src)) {
                self::copy_directory($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
        
        return true;
    }
    
    public static function upload_file($file, $path) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Upload error'];
        }
        
        $destination = rtrim($path, '/') . '/' . $file['name'];
        
        $result = move_uploaded_file($file['tmp_name'], $destination);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Cannot upload file'];
        }
        
        return ['success' => true, 'message' => 'File uploaded successfully'];
    }
    
    public static function download_file($file) {
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('Pragma: public');
        
        readfile($file);
        exit;
    }
    
    public static function zip_files($files, $zip_name) {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive not available'];
        }
        
        $zip = new ZipArchive();
        $zip_path = sys_get_temp_dir() . '/' . $zip_name;
        
        if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
            return ['success' => false, 'message' => 'Cannot create ZIP'];
        }
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::add_directory_to_zip($zip, $file, basename($file));
            } else {
                $zip->addFile($file, basename($file));
            }
        }
        
        $zip->close();
        
        return [
            'success' => true,
            'message' => 'ZIP created successfully',
            'data' => ['path' => $zip_path]
        ];
    }
    
    private static function add_directory_to_zip($zip, $dir, $base) {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            $zip_path = $base . '/' . $item;
            
            if (is_dir($path)) {
                $zip->addEmptyDir($zip_path);
                self::add_directory_to_zip($zip, $path, $zip_path);
            } else {
                $zip->addFile($path, $zip_path);
            }
        }
    }
    
    public static function unzip_file($file, $destination) {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'ZipArchive not available'];
        }
        
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'ZIP file not found'];
        }
        
        $zip = new ZipArchive();
        
        if ($zip->open($file) !== true) {
            return ['success' => false, 'message' => 'Cannot open ZIP'];
        }
        
        $zip->extractTo($destination);
        $zip->close();
        
        return ['success' => true, 'message' => 'Files extracted successfully'];
    }
    
    public static function chmod_file($file, $permissions) {
        if (!file_exists($file)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        $octal = octdec($permissions);
        $result = chmod($file, $octal);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Cannot change permissions'];
        }
        
        return ['success' => true, 'message' => 'Permissions modified'];
    }
    
    public static function search_files($path, $term) {
        $results = [];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                
                // Search in filename
                if (stripos($filename, $term) !== false) {
                    $results[] = [
                        'path' => $file->getPathname(),
                        'type' => 'filename'
                    ];
                }
                
                // Search in content (only small text files)
                if ($file->getSize() < 1048576) { // 1MB
                    $ext = strtolower($file->getExtension());
                    $text_extensions = ['php', 'js', 'css', 'html', 'txt', 'md', 'json', 'xml'];
                    
                    if (in_array($ext, $text_extensions)) {
                        $content = file_get_contents($file->getPathname());
                        if (stripos($content, $term) !== false) {
                            $results[] = [
                                'path' => $file->getPathname(),
                                'type' => 'content'
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'results' => array_slice($results, 0, 100),
                'total' => count($results)
            ]
        ];
    }
    
    private static function format_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
