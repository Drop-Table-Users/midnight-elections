<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\DTO;

/**
 * Represents Midnight network metadata.
 *
 * This immutable value object contains information about the Midnight network
 * including chain ID, network name, and optional explorer URI.
 */
final readonly class NetworkMetadata
{
    /**
     * Create a new NetworkMetadata instance.
     *
     * @param string $chainId The chain identifier
     * @param string $name The network name (e.g., 'devnet', 'testnet', 'mainnet')
     * @param string|null $explorerUri Optional blockchain explorer base URI
     * @param array<string, mixed> $protocolParams Optional protocol parameters
     */
    public function __construct(
        public string $chainId,
        public string $name,
        public ?string $explorerUri = null,
        public array $protocolParams = [],
    ) {
    }

    /**
     * Create a NetworkMetadata instance from an array.
     *
     * @param array<string, mixed> $data The network metadata array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            chainId: $data['chain_id'] ?? $data['chainId'] ?? '',
            name: $data['name'] ?? '',
            explorerUri: $data['explorer_uri'] ?? $data['explorerUri'] ?? null,
            protocolParams: $data['protocol_params'] ?? $data['protocolParams'] ?? [],
        );
    }

    /**
     * Convert the NetworkMetadata to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'chain_id' => $this->chainId,
            'name' => $this->name,
            'explorer_uri' => $this->explorerUri,
            'protocol_params' => $this->protocolParams,
        ];
    }

    /**
     * Get the explorer URL for a specific transaction hash.
     *
     * @param string $txHash The transaction hash
     * @return string|null The full explorer URL or null if no explorer URI is set
     */
    public function getExplorerTxUrl(string $txHash): ?string
    {
        if ($this->explorerUri === null) {
            return null;
        }

        return rtrim($this->explorerUri, '/') . '/tx/' . $txHash;
    }

    /**
     * Check if this is the mainnet network.
     *
     * @return bool
     */
    public function isMainnet(): bool
    {
        return strtolower($this->name) === 'mainnet';
    }

    /**
     * Check if this is a development/test network.
     *
     * @return bool
     */
    public function isDevnet(): bool
    {
        return in_array(strtolower($this->name), ['devnet', 'testnet'], true);
    }
}
