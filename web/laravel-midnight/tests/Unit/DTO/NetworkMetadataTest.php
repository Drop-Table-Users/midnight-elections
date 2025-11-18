<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\NetworkMetadata;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the NetworkMetadata DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\NetworkMetadata
 */
final class NetworkMetadataTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_required_fields(): void
    {
        $metadata = new NetworkMetadata(
            chainId: 'midnight-1',
            name: 'mainnet'
        );

        $this->assertInstanceOf(NetworkMetadata::class, $metadata);
        $this->assertSame('midnight-1', $metadata->chainId);
        $this->assertSame('mainnet', $metadata->name);
        $this->assertNull($metadata->explorerUri);
        $this->assertSame([], $metadata->protocolParams);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_fields(): void
    {
        $protocolParams = ['param1' => 'value1', 'param2' => 'value2'];
        $metadata = new NetworkMetadata(
            chainId: 'midnight-testnet-1',
            name: 'testnet',
            explorerUri: 'https://explorer.midnight.network',
            protocolParams: $protocolParams
        );

        $this->assertSame('midnight-testnet-1', $metadata->chainId);
        $this->assertSame('testnet', $metadata->name);
        $this->assertSame('https://explorer.midnight.network', $metadata->explorerUri);
        $this->assertSame($protocolParams, $metadata->protocolParams);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $metadata = new NetworkMetadata('chain-1', 'testnet');

        $this->assertClassIsFinal($metadata);
        $this->assertClassIsReadonly($metadata);
    }

    #[Test]
    public function it_can_be_created_from_array_with_snake_case_keys(): void
    {
        $data = [
            'chain_id' => 'midnight-dev-1',
            'name' => 'devnet',
            'explorer_uri' => 'https://explorer.dev.midnight.network',
            'protocol_params' => ['fee' => '100', 'version' => '1.0'],
        ];

        $metadata = NetworkMetadata::fromArray($data);

        $this->assertSame('midnight-dev-1', $metadata->chainId);
        $this->assertSame('devnet', $metadata->name);
        $this->assertSame('https://explorer.dev.midnight.network', $metadata->explorerUri);
        $this->assertSame(['fee' => '100', 'version' => '1.0'], $metadata->protocolParams);
    }

    #[Test]
    public function it_can_be_created_from_array_with_camel_case_keys(): void
    {
        $data = [
            'chainId' => 'midnight-main',
            'name' => 'mainnet',
            'explorerUri' => 'https://explorer.midnight.network',
            'protocolParams' => ['setting' => 'value'],
        ];

        $metadata = NetworkMetadata::fromArray($data);

        $this->assertSame('midnight-main', $metadata->chainId);
        $this->assertSame('mainnet', $metadata->name);
        $this->assertSame('https://explorer.midnight.network', $metadata->explorerUri);
        $this->assertSame(['setting' => 'value'], $metadata->protocolParams);
    }

    #[Test]
    public function it_handles_missing_optional_fields_in_fromArray(): void
    {
        $data = [
            'chain_id' => 'midnight-1',
            'name' => 'testnet',
        ];

        $metadata = NetworkMetadata::fromArray($data);

        $this->assertNull($metadata->explorerUri);
        $this->assertSame([], $metadata->protocolParams);
    }

    #[Test]
    public function it_prefers_snake_case_over_camel_case_in_fromArray(): void
    {
        $data = [
            'chain_id' => 'snake-case-id',
            'chainId' => 'camel-case-id',
            'name' => 'testnet',
        ];

        $metadata = NetworkMetadata::fromArray($data);

        $this->assertSame('snake-case-id', $metadata->chainId);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $metadata = new NetworkMetadata(
            chainId: 'midnight-1',
            name: 'mainnet',
            explorerUri: 'https://explorer.midnight.network',
            protocolParams: ['param' => 'value']
        );

        $array = $metadata->toArray();

        $this->assertSame([
            'chain_id' => 'midnight-1',
            'name' => 'mainnet',
            'explorer_uri' => 'https://explorer.midnight.network',
            'protocol_params' => ['param' => 'value'],
        ], $array);
    }

    #[Test]
    public function it_converts_to_array_with_null_explorer_uri(): void
    {
        $metadata = new NetworkMetadata(
            chainId: 'midnight-1',
            name: 'testnet'
        );

        $array = $metadata->toArray();

        $this->assertArrayHasKey('explorer_uri', $array);
        $this->assertNull($array['explorer_uri']);
    }

    #[Test]
    #[DataProvider('explorerUrlProvider')]
    public function it_generates_explorer_transaction_url(
        ?string $explorerUri,
        string $txHash,
        ?string $expectedUrl
    ): void {
        $metadata = new NetworkMetadata(
            chainId: 'midnight-1',
            name: 'testnet',
            explorerUri: $explorerUri
        );

        $url = $metadata->getExplorerTxUrl($txHash);

        $this->assertSame($expectedUrl, $url);
    }

    #[Test]
    public function it_returns_null_for_explorer_url_when_explorer_uri_not_set(): void
    {
        $metadata = new NetworkMetadata('midnight-1', 'testnet');

        $url = $metadata->getExplorerTxUrl('0xabc123');

        $this->assertNull($url);
    }

    #[Test]
    public function it_strips_trailing_slash_from_explorer_uri_in_url(): void
    {
        $metadata = new NetworkMetadata(
            chainId: 'midnight-1',
            name: 'testnet',
            explorerUri: 'https://explorer.midnight.network/'
        );

        $url = $metadata->getExplorerTxUrl('0xabc123');

        $this->assertSame('https://explorer.midnight.network/tx/0xabc123', $url);
    }

    #[Test]
    #[DataProvider('mainnetDetectionProvider')]
    public function it_detects_mainnet_network(string $name, bool $expectedIsMainnet): void
    {
        $metadata = new NetworkMetadata('chain-1', $name);

        $this->assertSame($expectedIsMainnet, $metadata->isMainnet());
    }

    #[Test]
    #[DataProvider('devnetDetectionProvider')]
    public function it_detects_devnet_network(string $name, bool $expectedIsDevnet): void
    {
        $metadata = new NetworkMetadata('chain-1', $name);

        $this->assertSame($expectedIsDevnet, $metadata->isDevnet());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $metadata = new NetworkMetadata('midnight-1', 'testnet');

        $this->assertPropertyIsReadonly($metadata, 'chainId');
        $this->assertPropertyIsReadonly($metadata, 'name');
        $this->assertPropertyIsReadonly($metadata, 'explorerUri');
        $this->assertPropertyIsReadonly($metadata, 'protocolParams');
    }

    #[Test]
    public function it_handles_complex_protocol_params(): void
    {
        $complexParams = [
            'fees' => ['base' => 100, 'multiplier' => 1.5],
            'limits' => ['max_tx_size' => 1024, 'max_block_size' => 1048576],
            'version' => '2.0.1',
        ];

        $metadata = new NetworkMetadata(
            chainId: 'midnight-1',
            name: 'testnet',
            protocolParams: $complexParams
        );

        $this->assertSame($complexParams, $metadata->protocolParams);
        $this->assertSame($complexParams, $metadata->toArray()['protocol_params']);
    }

    #[Test]
    public function fromArray_handles_empty_array(): void
    {
        $metadata = NetworkMetadata::fromArray([]);

        $this->assertSame('', $metadata->chainId);
        $this->assertSame('', $metadata->name);
        $this->assertNull($metadata->explorerUri);
        $this->assertSame([], $metadata->protocolParams);
    }

    #[Test]
    public function it_roundtrips_through_array_conversion(): void
    {
        $original = new NetworkMetadata(
            chainId: 'midnight-test',
            name: 'testnet',
            explorerUri: 'https://explorer.test',
            protocolParams: ['key' => 'value']
        );

        $array = $original->toArray();
        $restored = NetworkMetadata::fromArray($array);

        $this->assertSame($original->chainId, $restored->chainId);
        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->explorerUri, $restored->explorerUri);
        $this->assertSame($original->protocolParams, $restored->protocolParams);
    }

    /**
     * Data provider for explorer URL generation tests.
     *
     * @return array<string, array{string|null, string, string|null}>
     */
    public static function explorerUrlProvider(): array
    {
        return [
            'with explorer uri' => [
                'https://explorer.midnight.network',
                '0xabc123',
                'https://explorer.midnight.network/tx/0xabc123',
            ],
            'with trailing slash' => [
                'https://explorer.midnight.network/',
                '0xdef456',
                'https://explorer.midnight.network/tx/0xdef456',
            ],
            'without explorer uri' => [
                null,
                '0xabc123',
                null,
            ],
        ];
    }

    /**
     * Data provider for mainnet detection tests.
     *
     * @return array<string, array{string, bool}>
     */
    public static function mainnetDetectionProvider(): array
    {
        return [
            'lowercase mainnet' => ['mainnet', true],
            'uppercase MAINNET' => ['MAINNET', true],
            'mixed case Mainnet' => ['Mainnet', true],
            'testnet' => ['testnet', false],
            'devnet' => ['devnet', false],
            'custom name' => ['production', false],
        ];
    }

    /**
     * Data provider for devnet detection tests.
     *
     * @return array<string, array{string, bool}>
     */
    public static function devnetDetectionProvider(): array
    {
        return [
            'lowercase devnet' => ['devnet', true],
            'uppercase DEVNET' => ['DEVNET', true],
            'mixed case Devnet' => ['Devnet', true],
            'lowercase testnet' => ['testnet', true],
            'uppercase TESTNET' => ['TESTNET', true],
            'mainnet' => ['mainnet', false],
            'custom name' => ['development', false],
        ];
    }
}
