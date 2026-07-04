<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputKey = $_POST['auth_key'] ?? '';
    
    if (mb_strlen($inputKey, 'UTF-8') > 256) {
        die("Fatal Error: Bound overflow detected."); 
    }

    $stored_hash = '$argon2id$v=19$m=65536,t=4,p=1$bW11YldnZGF1L3B5bTlkNg$bmAnNsJiknAUfSpCYgn4GulVESTdjvAqK0XQRWX9gZk'; 
    
    if (password_verify($inputKey, $stored_hash)) {
        echo "Access Granted.";
    } else {
        echo "Access Denied.";
    }
}
?>