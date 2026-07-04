<?php
require_once 'db_config.php';

$keyword = $_GET['keyword'] ?? '';

$sql = "SELECT id, name, illness_history FROM patient_records WHERE name LIKE :keyword";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyword' => '%' . $keyword . '%']);
    $results = $stmt->fetchAll();
    
    $safeKeyword = htmlspecialchars($keyword, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    if (count($results) > 0) {
        foreach ($results as $row) {
            $safeName = htmlspecialchars($row['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $safeHistory = htmlspecialchars($row['illness_history'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            echo "<div>Result found for keyword: " . $safeKeyword . "<br>";
            echo "Patient: " . $safeName . " | History: " . $safeHistory . "</div><hr>";
        }
    } else {
        echo "No records found for: " . $safeKeyword;
    }
} catch (\PDOException $e) {
    error_log("Database error during search: " . $e->getMessage());
    echo "An error occurred while processing your request. Please try again later.";
}
?>