<?php
$ext_dir = ini_get('extension_dir');
echo "<h2>Extension dir: $ext_dir</h2>";
$files = glob($ext_dir . '/*.so');
echo "<h3>Available .so files:</h3><ul>";
foreach ($files as $f) echo "<li>" . basename($f) . "</li>";
echo "</ul>";
echo "<h3>Loaded extensions:</h3><ul>";
foreach (get_loaded_extensions() as $e) echo "<li>$e</li>";
echo "</ul>";
?>
