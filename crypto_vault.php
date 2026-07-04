<?php
require_once 'db_config.php';

function encrypt_aes_256_gcm($plaintext, $key) {
    if (strlen($key) !== 32) {
        throw new InvalidArgumentException("Encryption key must be exactly 32 bytes (256 bits).");
    }
    
    $iv = openssl_random_pseudo_bytes(12, $crypto_strong);
    if (!$crypto_strong || $iv === false) {
        throw new RuntimeException("Secure IV generation failed.");
    }
    
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false) {
        throw new RuntimeException("Encryption failed.");
    }
    
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt_aes_256_gcm($serialized, $key) {
    if (strlen($key) !== 32) {
        throw new InvalidArgumentException("Decryption key must be exactly 32 bytes (256 bits).");
    }
    
    $data = base64_decode($serialized);
    if (strlen($data) < 28) {
        throw new RuntimeException("Malformed ciphertext payload.");
    }
    
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        throw new RuntimeException("Decryption or integrity verification failed (AEAD Tag mismatch).");
    }
    
    return $plaintext;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $medical_payload = $_POST['payload'] ?? '';
    
    $key_hex = $_ENV['CRYPTO_KEY'] ?? getenv('CRYPTO_KEY');
    
    if (!$key_hex) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Cryptographic key configuration missing."]);
        exit;
    }
    
    $secret_key = (ctype_xdigit($key_hex) && strlen($key_hex) === 64) ? hex2bin($key_hex) : $key_hex;
    
    try {
        $encrypted = encrypt_aes_256_gcm($medical_payload, $secret_key);
        echo json_encode(["status" => "vaulted", "data" => $encrypted]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Cryptographic operation failed."]);
    }
}
?>