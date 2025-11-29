<?php
require_once 'config.php';

echo "<h1>Test Academic System APIs</h1>\n";

try {
    $pdo = getDBConnection();
    
    echo "<h2>Testing Database Connection</h2>\n";
    echo "<p>✅ Database connected successfully</p>\n";
    
    echo "<h2>Testing Tables</h2>\n";
    $tables = ['subjects', 'academic_grades', 'achievements', 'student_grades', 'academic_periods'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p>✅ Table '$table': $count records</p>\n";
        } catch (PDOException $e) {
            echo "<p>❌ Table '$table': " . $e->getMessage() . "</p>\n";
        }
    }
    
    echo "<h2>Testing Subjects API</h2>\n";
    
    // Simular llamada a API de subjects
    $_GET['user_id'] = 1;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/subjects';
    
    ob_start();
    include 'api/routes/subjects.php';
    $output = ob_get_clean();
    
    echo "<pre>$output</pre>\n";
    
    echo "<h2>Testing Grades API</h2>\n";
    
    // Simular llamada a API de grades
    $_SERVER['REQUEST_URI'] = '/grades';
    
    ob_start();
    include 'api/routes/grades.php';
    $output = ob_get_clean();
    
    echo "<pre>$output</pre>\n";
    
    echo "<h2>Testing Achievements API</h2>\n";
    
    // Simular llamada a API de achievements
    $_SERVER['REQUEST_URI'] = '/achievements';
    
    ob_start();
    include 'api/routes/achievements.php';
    $output = ob_get_clean();
    
    echo "<pre>$output</pre>\n";
    
    echo "<h2>Testing Student Grades API</h2>\n";
    
    // Simular llamada a API de student-grades
    $_SERVER['REQUEST_URI'] = '/student-grades';
    
    ob_start();
    include 'api/routes/student-grades.php';
    $output = ob_get_clean();
    
    echo "<pre>$output</pre>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>