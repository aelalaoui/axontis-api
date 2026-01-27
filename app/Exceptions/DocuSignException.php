<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception spécifique aux erreurs DocuSign
 *
 * Permet de distinguer les erreurs DocuSign des autres exceptions
 * et de gérer les retries de manière appropriée.
 */
class DocuSignException extends Exception
{
    protected bool $retryable;
    protected ?string $errorCode;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        bool $retryable = true,
        ?string $errorCode = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->retryable = $retryable;
        $this->errorCode = $errorCode;
    }

    /**
     * Indique si l'erreur est récupérable (peut être réessayée)
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Retourne le code d'erreur DocuSign
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Crée une exception pour une erreur d'authentification
     */
    public static function authenticationFailed(string $message, ?Exception $previous = null): self
    {
        return new self(
            "DocuSign authentication failed: {$message}",
            401,
            $previous,
            false, // Non-retryable
            'AUTH_FAILED'
        );
    }

    /**
     * Crée une exception pour un consentement requis
     */
    public static function consentRequired(string $consentUrl): self
    {
        return new self(
            "DocuSign consent required. Please visit: {$consentUrl}",
            403,
            null,
            false, // Non-retryable
            'CONSENT_REQUIRED'
        );
    }

    /**
     * Crée une exception pour une enveloppe non trouvée
     */
    public static function envelopeNotFound(string $envelopeId): self
    {
        return new self(
            "DocuSign envelope not found: {$envelopeId}",
            404,
            null,
            false, // Non-retryable
            'ENVELOPE_NOT_FOUND'
        );
    }

    /**
     * Crée une exception pour une signature webhook invalide
     */
    public static function invalidWebhookSignature(): self
    {
        return new self(
            "Invalid DocuSign webhook signature",
            400,
            null,
            false, // Non-retryable
            'INVALID_SIGNATURE'
        );
    }

    /**
     * Crée une exception pour une erreur réseau temporaire
     */
    public static function networkError(string $message, ?Exception $previous = null): self
    {
        return new self(
            "DocuSign network error: {$message}",
            503,
            $previous,
            true, // Retryable
            'NETWORK_ERROR'
        );
    }

    /**
     * Crée une exception pour une erreur de rate limit
     */
    public static function rateLimitExceeded(): self
    {
        return new self(
            "DocuSign rate limit exceeded",
            429,
            null,
            true, // Retryable after delay
            'RATE_LIMIT'
        );
    }
}

