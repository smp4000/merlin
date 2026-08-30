<?php

namespace App\Modules\Registration\Application\Data;

/**
 * Transportiert den nur auf der Bestätigungsseite eingegebenen Passwortnachweis.
 */
final readonly class ConfirmPartnerRegistrationData
{
    public function __construct(
        public string $publicId,
        public string $token,
        public string $password,
        public string $correlationId,
        public bool $termsAccepted,
        public string $termsVersion,
        public string $termsDigest,
        public bool $privacyAcknowledged,
        public string $privacyVersion,
        public string $privacyDigest,
    ) {}
}
