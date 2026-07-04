<?php
// tests/SecurityTest.php - Secure Hashing & Cryptographic Subsystem Security Verification Suite
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../../crypto_vault.php';

class SecurityTest extends TestCase {
    private $secretKey;

    protected function setUp(): void {
        parent::setUp();
        // Load the key from the environment
        $keyHex = $_ENV['CRYPTO_KEY'] ?? getenv('CRYPTO_KEY');
        if (!$keyHex) {
            $this->fail("CRYPTO_KEY environment variable is not defined.");
        }
        // Decode hex if appropriate
        $this->secretKey = (ctype_xdigit($keyHex) && strlen($keyHex) === 64) ? hex2bin($keyHex) : $keyHex;
    }

    /**
     * TEST CASE 1: Untampered Cryptographic Lifecycle
     * Asserts the successful roundtrip encryption and decryption of patient clinical datasets.
     */
    public function testUntamperedCryptographicLifecycle() {
        $originalPayload = "DIAGNOSIS: Stage-2 Carcinoma. TREATMENT: Chemotherapy cycle 1. STATUS: Critical.";
        
        // Encrypt the medical record using AES-256-GCM
        $encryptedPayload = encrypt_aes_256_gcm($originalPayload, $this->secretKey);
        $this->assertNotEmpty($encryptedPayload, "Ciphertext payload should not be empty.");
        
        // Decrypt the medical record
        $decryptedPayload = decrypt_aes_256_gcm($encryptedPayload, $this->secretKey);
        
        // Assert mathematical roundtrip integrity matches the original data
        $this->assertEquals($originalPayload, $decryptedPayload, "Decrypted data must match original plaintext.");
    }

    /**
     * TEST CASE 2: Tampered Ciphertext Throws AEAD Exception
     * Simulates packet/payload manipulation and asserts that the AEAD decryption pipeline
     * rejects the tampered ciphertext securely by throwing a RuntimeException instead of running insecure blocks.
     */
    public function testTamperedCiphertextThrowsAEADException() {
        $originalPayload = "DIAGNOSIS: Stage-2 Carcinoma. TREATMENT: Chemotherapy cycle 1. STATUS: Critical.";
        $encryptedPayload = encrypt_aes_256_gcm($originalPayload, $this->secretKey);
        
        // Decode the base64 payload to access raw bytes
        $rawData = base64_decode($encryptedPayload);
        
        // Tamper with the raw ciphertext by altering the very last byte
        $lastByteIndex = strlen($rawData) - 1;
        $rawData[$lastByteIndex] = chr(ord($rawData[$lastByteIndex]) ^ 0xFF);
        
        // Re-encode to simulate an active payload manipulation attack
        $tamperedPayload = base64_encode($rawData);
        
        // Assert that decryption throws a RuntimeException due to the authentication tag mismatch
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Decryption or integrity verification failed (AEAD Tag mismatch).");
        
        decrypt_aes_256_gcm($tamperedPayload, $this->secretKey);
    }

    /**
     * TEST CASE 3: Credential Hash Integrity Matches (Argon2id Hashing Check)
     * Validates that modern, memory-hard Argon2id hashes verified against password_verify()
     * successfully authenticate valid keys while blocking invalid credentials.
     */
    public function testCredentialHashIntegrityMatches() {
        $plainPassword = "test";
        // The secure Argon2id hash of 'test' generated in auth.php
        $storedArgon2idHash = '$argon2id$v=19$m=65536,t=4,p=1$bW11YldnZGF1L3B5bTlkNg$bmAnNsJiknAUfSpCYgn4GulVESTdjvAqK0XQRWX9gZk';
        
        // Assert that password_verify returns true for the correct password
        $this->assertTrue(
            password_verify($plainPassword, $storedArgon2idHash),
            "Argon2id verification must succeed for valid credentials."
        );
        
        // Assert that password_verify returns false for an incorrect password
        $this->assertFalse(
            password_verify("wrongpassword", $storedArgon2idHash),
            "Argon2id verification must fail for invalid credentials."
        );
    }
}
?>
