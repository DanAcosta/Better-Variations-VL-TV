<?php

function xcopy($source, $dest, $permissions = 0755)
{
    $sourceHash = hashDirectory($source);
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        if($sourceHash != hashDirectory($source."/".$entry)){
             xcopy("$source/$entry", "$dest/$entry", $permissions);
        }
    }

    // Clean up
    $dir->close();
    return true;
}

// In case of coping a directory inside itself, there is a need to hash check the directory otherwise and infinite loop of coping is generated

function hashDirectory($directory){
    if (! is_dir($directory)){ return false; }

    $files = array();
    $dir = dir($directory);

    while (false !== ($file = $dir->read())){
        if ($file != '.' and $file != '..') {
            if (is_dir($directory . '/' . $file)) { $files[] = hashDirectory($directory . '/' . $file); }
            else { $files[] = md5_file($directory . '/' . $file); }
        }
    }

    $dir->close();

    return md5(implode('', $files));
}

$rootPath = __DIR__."/wc-vapelab-sheets-connector";

$nombreArchivoZip = __DIR__ . "/wc-vapelab-sheets-connector.zip";

require_once __DIR__. '/vendor/autoload.php';

$app = new \Kunnu\Dropbox\DropboxApp("og1wq3ehrng0i9m", "ndzliif3hyrujnc", "sl.BYEV9vct5DMPcb0yIYwKEifaTyBTLsDNaT2u6_px5798bqqrOcUa3YhH_9v6NQOJHeIl6RXiWtbZsiFlcbXRLF5twyQW9qJLhMvJciSkt_953QKB6MshDQo-5bdqL3uv1hGHM7TK");

$dropbox = new \Kunnu\Dropbox\Dropbox($app);

$mode =  \Kunnu\Dropbox\DropboxFile::MODE_READ;
$dropboxFile = \Kunnu\Dropbox\DropboxFile::createByPath($nombreArchivoZip, $mode );

$dropbox->delete("/Plugins Woo Daniel/wc-vapelab-sheets-connector.zip");
$file = $dropbox->simpleUpload($dropboxFile, "/Plugins Woo Daniel/wc-vapelab-sheets-connector.zip", ['autorename' => true]);

//Uploaded File
echo '<pre>';var_dump($file->getName());echo '</pre>';exit();



xcopy(__DIR__,$rootPath);

$zip = new ZipArchive();



if (!$zip->open($nombreArchivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    exit("Error abriendo ZIP en $nombreArchivoZip");
}



$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__."/wc-vapelab-sheets-connector"),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file)
{
    // Skip directories (they would be added automatically)
    if (!$file->isDir())
    {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
}


// No olvides cerrar el archivo
$resultado = $zip->close();
if (!$resultado) {
    exit("Error creando archivo");
}


?>