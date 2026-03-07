<?php
/**
 * Surprise! Store - TOTP (RFC 6238) Implementation
 * Two-Factor Authentication via Google Authenticator
 * 
 * Pure PHP — No external libraries required
 * @version 6.0.0
 */

/**
 * Generate a random TOTP secret (Base32 encoded)
 * @param int $length Number of random bytes (default 20 = 160 bits, standard)
 * @return string Base32 encoded secret
 */
function generateTOTPSecret($length = 20) {
    $randomBytes = random_bytes($length);
    return base32Encode($randomBytes);
}

/**
 * Verify a TOTP code against a secret
 * @param string $secret Base32 encoded secret
 * @param string $code 6-digit code from authenticator app
 * @param int $window Number of 30-second windows to check (before and after)
 * @return bool True if code is valid
 */
function verifyTOTPCode($secret, $code, $window = 2) {
    if (strlen($code) !== 6 || !ctype_digit($code)) {
        return false;
    }
    
    $secretBytes = base32Decode($secret);
    if ($secretBytes === false) {
        return false;
    }
    
    $timeSlice = floor(time() / 30);
    
    // Check current time slice and adjacent windows
    for ($i = -$window; $i <= $window; $i++) {
        $calculatedCode = calculateTOTP($secretBytes, $timeSlice + $i);
        if (hash_equals($calculatedCode, $code)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Calculate TOTP code for a given time slice
 * @param string $secretBytes Raw secret bytes
 * @param int $timeSlice Time counter (floor(time/30))
 * @return string 6-digit TOTP code
 */
function calculateTOTP($secretBytes, $timeSlice) {
    // Pack time as 8-byte big-endian
    $time = pack('N*', 0, $timeSlice);
    
    // HMAC-SHA1
    $hash = hash_hmac('sha1', $time, $secretBytes, true);
    
    // Dynamic truncation (RFC 4226)
    $offset = ord($hash[19]) & 0x0F;
    $code = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    ) % 1000000;
    
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

/**
 * Generate otpauth:// URI for QR code
 * @param string $secret Base32 encoded secret
 * @param string $username User's display name / username
 * @param string $issuer App name (default: Surprise!)
 * @return string otpauth URI
 */
function getTOTPUri($secret, $username, $issuer = 'Surprise! Store') {
    $label = rawurlencode($issuer) . ':' . rawurlencode($username);
    $params = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => 'SHA1',
        'digits' => 6,
        'period' => 30
    ]);
    return 'otpauth://totp/' . $label . '?' . $params;
}

/**
 * Format secret for manual entry (groups of 4)
 * @param string $secret Base32 encoded secret
 * @return string Formatted secret (e.g., "ABCD EFGH IJKL MNOP")
 */
function formatSecretForDisplay($secret) {
    return trim(chunk_split($secret, 4, ' '));
}

/**
 * Base32 Encode (RFC 4648)
 * @param string $data Raw binary data
 * @return string Base32 encoded string
 */
function base32Encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    
    for ($i = 0; $i < strlen($data); $i++) {
        $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }
    
    $encoded = '';
    $chunks = str_split($binary, 5);
    
    foreach ($chunks as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $encoded .= $alphabet[bindec($chunk)];
    }
    
    // Pad to multiple of 8
    while (strlen($encoded) % 8 !== 0) {
        $encoded .= '=';
    }
    
    return $encoded;
}

/**
 * Base32 Decode (RFC 4648)
 * @param string $data Base32 encoded string
 * @return string|false Raw binary data or false on error
 */
function base32Decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(rtrim($data, '='));
    
    if (empty($data)) {
        return false;
    }
    
    $binary = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $pos = strpos($alphabet, $data[$i]);
        if ($pos === false) {
            return false;
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    
    $decoded = '';
    $chunks = str_split($binary, 8);
    
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 8) break;
        $decoded .= chr(bindec($chunk));
    }
    
    return $decoded;
}
