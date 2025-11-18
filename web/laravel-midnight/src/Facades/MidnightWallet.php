<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Facades;

use Illuminate\Support\Facades\Facade;
use VersionTwo\Midnight\Contracts\WalletGateway;
use VersionTwo\Midnight\DTO\TxHash;

/**
 * MidnightWallet Facade
 *
 * Provides static access to the WalletGateway for wallet-level operations
 * such as retrieving addresses, checking balances, and transferring assets.
 *
 * @method static string getAddress() Get the current wallet's Midnight address
 * @method static string getBalance() Get the current wallet's balance
 * @method static TxHash transfer(string $toAddress, string $amount, ?string $asset = null) Transfer assets from the current wallet to another address
 *
 * @see \VersionTwo\Midnight\Contracts\WalletGateway
 * @see \VersionTwo\Midnight\Services\WalletGatewayService
 */
class MidnightWallet extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return WalletGateway::class;
    }
}
