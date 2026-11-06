<?php
namespace App\Utils;

/**
 * Verifies a Google Sign-In ID token by asking Google's own tokeninfo
 * endpoint to validate its signature/expiry, instead of implementing
 * RS256/JWKS verification ourselves (no Composer deps in this project).
 */
class GoogleAuth
{
    /**
     * @return array|null Decoded payload (sub, email, email_verified, name, picture, aud) or null if invalid.
     */
    public static function verifyIdToken(string $idToken): ?array
    {
        if ($idToken === '') {
            return null;
        }

        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $payload = json_decode($response, true);
        if (!is_array($payload) || empty($payload['sub']) || empty($payload['email'])) {
            return null;
        }

        $expectedClientId = Env::get('GOOGLE_CLIENT_ID');
        if ($expectedClientId && ($payload['aud'] ?? null) !== $expectedClientId) {
            return null;
        }

        if (($payload['email_verified'] ?? 'false') !== 'true') {
            return null;
        }

        return $payload;
    }
}
