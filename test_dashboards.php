<?php
echo "<h2>Verificación de Dashboards</h2>";

$files = [
    'admin_dashboard.html',
    'secretary_dashboard.html',
    'coordinator_dashboard.html'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $content = file_get_contents($file, false, null, 0, 200);
        echo "<h3>✅ $file</h3>";
        echo "Tamaño: " . number_format($size) . " bytes<br>";
        echo "Título: " . (preg_match('/<title>(.*?)<\/title>/', $content, $matches) ? $matches[1] : 'No encontrado') . "<br>";
        echo "Color header: " . (preg_match('/\.header.*?background:\s*([^;]+)/', $content, $matches) ? $matches[1] : 'No encontrado') . "<br><br>";
    } else {
        echo "<h3>❌ $file - NO EXISTE</h3><br>";
    }
}
?>