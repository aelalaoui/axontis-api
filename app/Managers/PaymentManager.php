<?php

namespace App\Managers;

use App\Providers\Payment\PaymentProviderInterface;
use App\Providers\Payment\StripeProvider;
use App\Providers\Payment\CmiProvider;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Default payment provider
     */
    protected string $defaultProvider = 'stripe';

    /**
     * Available payment providers
     */
    protected array $providers = [
        'stripe' => StripeProvider::class,
        'cmi' => CmiProvider::class,
    ];

    /**
     * Provider instances cache
     */
    protected array $instances = [];

    /**
     * Get payment provider instance
     */
    public function getProvider(string $provider = null): PaymentProviderInterface
    {
        $providerName = $provider ?? $this->defaultProvider;

        // Check if provider is cached
        if (isset($this->instances[$providerName])) {
            return $this->instances[$providerName];
        }

        // Check if provider exists
        if (!isset($this->providers[$providerName])) {
            throw new InvalidArgumentException(
                "Payment provider '{$providerName}' is not supported. Available: " .
                implode(', ', array_keys($this->providers))
            );
        }

        // Create provider instance
        $providerClass = $this->providers[$providerName];
        $instance = new $providerClass();

        // Cache the instance
        $this->instances[$providerName] = $instance;

        return $instance;
    }

    /**
     * Set default provider
     */
    public function setDefaultProvider(string $provider): self
    {
        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException(
                "Payment provider '{$provider}' is not supported. Available: " .
                implode(', ', array_keys($this->providers))
            );
        }

        $this->defaultProvider = $provider;

        return $this;
    }

    /**
     * Get default provider name
     */
    public function getDefaultProvider(): string
    {
        return $this->defaultProvider;
    }

    /**
     * Register a new payment provider
     */
    public function registerProvider(string $name, string $class): self
    {
        if (!is_subclass_of($class, PaymentProviderInterface::class)) {
            throw new InvalidArgumentException(
                "Provider class must implement " . PaymentProviderInterface::class
            );
        }

        $this->providers[$name] = $class;

        // Clear instances cache
        $this->instances = [];

        return $this;
    }

    /**
     * Get all available providers
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Check if provider exists
     */
    public function hasProvider(string $provider): bool
    {
        return isset($this->providers[$provider]);
    }
}

