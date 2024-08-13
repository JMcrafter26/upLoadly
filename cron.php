<?php 

require_once('config.php');
header('Content-Type: text/plain');

try {
    $db = new PDO($config['db_connection'], $config['db_username'], $config['db_password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// Create tables if not exist
$db->exec("CREATE TABLE IF NOT EXISTS " . $config['db_prefix'] . "files (
    id INTEGER PRIMARY KEY, 
    filename TEXT, 
    name TEXT, 
    extension TEXT, 
    path TEXT, 
    directory TEXT,
	size INTEGER,
    downloads INTEGER NOT NULL DEFAULT 0, 
    lastdownload DATETIME, 
    ip TEXT, 
    created DATETIME
)");

// Check for expired files
$files = $db->query("SELECT id, path, directory, downloads, lastdownload, created FROM " . $config['db_prefix'] . "files")->fetchAll(PDO::FETCH_ASSOC);

if (count($files) == 0) {
    echo 'No files in database';
} else {
    foreach ($files as $file) {
    
        $currentTime = time();

        if (($file['created'] + $config['expire']) < $currentTime && ($file['lastdownload'] + ($config['expire'] * $config['download_extend'])) < $currentTime) {
            if (file_exists($file['path'])) {
                unlink($file['path']);
                echo 'Deleted file ' . $file['path'] . " as it has expired\n";
            }

            // Check if directory is empty and delete if so
            $dirPath = __DIR__ . '/' . $file['directory'];
            if (is_dir($dirPath) && count(scandir($dirPath)) <= 2) {
                rmdir($dirPath);
                echo 'Deleted directory: ' . $dirPath . "\n";
            }

            // Delete record from database
            $stmt = $db->prepare("DELETE FROM " . $config['db_prefix'] . "files WHERE id = :id");
            $stmt->execute([':id' => $file['id']]);
        } else {
            echo 'File ' . $file['directory'] . " is NOT expired. Skipping...\n";
        }
    }
}

// Remove empty folders in upload directory
$dirs = scandir($config['upload_path']);
foreach ($dirs as $dir) {
    $dirPath = __DIR__ . '/' . $config['upload_path'] . '/' . $dir;
    if (is_dir($dirPath) && $dir != '.' && $dir != '..') {
        $filesInDir = scandir($dirPath);
        if (count($filesInDir) <= 2) { // Only '.' and '..' present
            rmdir($dirPath);
            echo 'Deleted empty folder: ' . $dir . "\n";
        }
    }
}



// Synchronize database and filesystem
// Fetch all directories from the filesystem
$fsDirs = array_filter(scandir(__DIR__ . '/' . $config['upload_path']), function ($dir) use ($config) {
    return is_dir(__DIR__ . '/' . $config['upload_path'] . '/' . $dir) && $dir != '.' && $dir != '..';
});

$fsDirsTemp = array();
foreach($fsDirs as $fsDir) {
    $fsDirsTemp[] = $config['upload_path'] . '/' . $fsDir;
}
unset($fsDirs, $fsDir);
$fsDirs = $fsDirsTemp;

// die(json_encode($fsDirs));

// Fetch all directories from the database
$dbDirsStmt = $db->prepare("SELECT directory FROM " . $config['db_prefix'] . "files");
$dbDirsStmt->execute();
$dbDirs = $dbDirsStmt->fetchAll(PDO::FETCH_COLUMN);

// Find and delete missing directories in the database
foreach ($dbDirs as $dbDir) {
    if (!in_array($dbDir, $fsDirs)) {
        $stmt = $db->prepare("DELETE FROM " . $config['db_prefix'] . "files WHERE directory = :directory");
        $stmt->execute([':directory' => $dbDir]);
        echo 'Deleted database entries for directory: ' . $dbDir . "\n";
    }
}

// Find and delete missing directories in the filesystem
foreach ($fsDirs as $fsDir) {
    if (!in_array($fsDir, $dbDirs)) {
        $fsDirPath = __DIR__ . '/' . $fsDir;
        if (is_dir($fsDirPath)) {
            removeDir($fsDirPath);
            echo 'Deleted filesystem directory: ' . $fsDir . " as it was missing in database\n";
        }
    }
}


function removeDir(string $dir): void {
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($dir);
}