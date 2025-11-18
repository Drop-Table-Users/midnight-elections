<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Facades;

use Illuminate\Support\Facades\Facade;
use VersionTwo\Midnight\Contracts\ContractGateway;
use VersionTwo\Midnight\DTO\ContractCallResult;
use VersionTwo\Midnight\DTO\TxHash;

/**
 * MidnightContracts Facade
 *
 * Provides static access to the ContractGateway for high-level contract operations
 * including deployment, joining, calling, and reading contract state.
 *
 * @method static string deploy(string $compiledContractPath, array $initArgs = []) Deploy a new contract to the Midnight network
 * @method static ContractCallResult join(string $contractAddress, array $args = []) Join an existing contract on the Midnight network
 * @method static TxHash call(string $contractAddress, string $entrypoint, array $publicArgs = [], array $privateArgs = []) Call a contract method that modifies state
 * @method static ContractCallResult read(string $contractAddress, string $selector, array $args = []) Read contract state without modifying it
 *
 * @see \VersionTwo\Midnight\Contracts\ContractGateway
 * @see \VersionTwo\Midnight\Services\ContractGatewayService
 */
class MidnightContracts extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ContractGateway::class;
    }
}
