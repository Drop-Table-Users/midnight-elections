<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Contracts;

use VersionTwo\Midnight\DTO\TxHash;

/**
 * Wallet operations gateway interface.
 *
 * This interface provides wallet-level operations such as retrieving the current
 * wallet address, checking balances, and transferring assets. All operations are
 * proxied through the bridge service, which manages the actual wallet using the
 * Midnight SDK.
 *
 * The wallet used is determined by the bridge service configuration. This interface
 * does not handle key management directly.
 */
interface WalletGateway
{
    /**
     * Get the current wallet's Midnight address.
     *
     * This method retrieves the address of the wallet currently configured in
     * the bridge service. This address can be used to receive funds or identify
     * the wallet in contract operations.
     *
     * @return string The Midnight network address of the current wallet
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the wallet is not accessible
     * @throws \VersionTwo\Midnight\Exceptions\MidnightException If the bridge returns an error
     */
    public function getAddress(): string;

    /**
     * Get the current wallet's balance.
     *
     * This method retrieves the balance of the native token (or default asset)
     * for the current wallet. The balance is returned as a string to handle
     * arbitrary precision without floating-point errors.
     *
     * @return string The wallet balance as a string (may represent a large integer
     *                or decimal value depending on the network's token precision)
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the balance cannot be retrieved
     * @throws \VersionTwo\Midnight\Exceptions\MidnightException If the bridge returns an error
     */
    public function getBalance(): string;

    /**
     * Transfer assets from the current wallet to another address.
     *
     * This method initiates a transfer transaction from the configured wallet
     * to the specified recipient address. The transfer can be for the native
     * token or a specific asset if supported by the network.
     *
     * @param string $toAddress The recipient's Midnight network address
     * @param string $amount The amount to transfer as a string (to support arbitrary
     *                       precision)
     * @param string|null $asset Optional asset identifier. If null, transfers the
     *                           native token
     * @return TxHash The transaction hash for tracking the transfer
     * @throws \VersionTwo\Midnight\Exceptions\NetworkException If the transfer fails
     * @throws \VersionTwo\Midnight\Exceptions\MidnightException If insufficient balance or invalid address
     * @throws \InvalidArgumentException If the address or amount format is invalid
     */
    public function transfer(string $toAddress, string $amount, ?string $asset = null): TxHash;
}
