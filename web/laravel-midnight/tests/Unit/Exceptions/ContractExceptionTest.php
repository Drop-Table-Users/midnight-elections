<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use VersionTwo\Midnight\Exceptions\ContractException;
use VersionTwo\Midnight\Exceptions\MidnightException;

#[CoversClass(ContractException::class)]
final class ContractExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_midnight_exception(): void
    {
        $exception = new ContractException('Test');

        $this->assertInstanceOf(MidnightException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_creates_deployment_failed_exception(): void
    {
        $reason = 'Insufficient gas';

        $exception = ContractException::deploymentFailed($reason);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame("Contract deployment failed: {$reason}", $exception->getMessage());
        $this->assertSame([
            'reason' => $reason,
            'contract_path' => null,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_deployment_failed_with_contract_path(): void
    {
        $reason = 'Invalid bytecode';
        $contractPath = '/contracts/TokenContract.compact';

        $exception = ContractException::deploymentFailed($reason, $contractPath);

        $this->assertSame(
            "Contract deployment failed: {$reason} (contract: {$contractPath})",
            $exception->getMessage()
        );
        $this->assertSame([
            'reason' => $reason,
            'contract_path' => $contractPath,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_deployment_failed_with_additional_context(): void
    {
        $reason = 'Network error';
        $contractPath = '/contracts/MyContract.compact';
        $context = [
            'gas_used' => 5000000,
            'deployer' => '0xabc123',
        ];

        $exception = ContractException::deploymentFailed($reason, $contractPath, $context);

        $this->assertSame([
            'reason' => $reason,
            'contract_path' => $contractPath,
            'gas_used' => 5000000,
            'deployer' => '0xabc123',
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('deploymentReasonProvider')]
    public function it_handles_various_deployment_failure_reasons(
        string $reason,
        ?string $contractPath
    ): void {
        $exception = ContractException::deploymentFailed($reason, $contractPath);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
        $this->assertSame($contractPath, $exception->getContext()['contract_path']);
    }

    #[Test]
    public function it_creates_call_failed_exception(): void
    {
        $contractAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';
        $entrypoint = 'transfer';
        $reason = 'Insufficient balance';

        $exception = ContractException::callFailed($contractAddress, $entrypoint, $reason);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame(
            "Contract call to {$contractAddress}::{$entrypoint} failed: {$reason}",
            $exception->getMessage()
        );
        $this->assertSame([
            'contract_address' => $contractAddress,
            'entrypoint' => $entrypoint,
            'reason' => $reason,
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_call_failed_with_additional_context(): void
    {
        $contractAddress = '0x123';
        $entrypoint = 'mint';
        $reason = 'Not authorized';
        $context = [
            'caller' => '0xdef456',
            'amount' => 1000,
        ];

        $exception = ContractException::callFailed($contractAddress, $entrypoint, $reason, $context);

        $this->assertSame([
            'contract_address' => $contractAddress,
            'entrypoint' => $entrypoint,
            'reason' => $reason,
            'caller' => '0xdef456',
            'amount' => 1000,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('contractCallProvider')]
    public function it_handles_various_contract_calls(
        string $address,
        string $entrypoint,
        string $reason
    ): void {
        $exception = ContractException::callFailed($address, $entrypoint, $reason);

        $this->assertStringContainsString($address, $exception->getMessage());
        $this->assertStringContainsString($entrypoint, $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
    }

    #[Test]
    public function it_creates_execution_reverted_exception(): void
    {
        $contractAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';
        $entrypoint = 'withdraw';
        $revertReason = 'Withdrawal amount exceeds balance';

        $exception = ContractException::executionReverted($contractAddress, $entrypoint, $revertReason);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame(
            "Contract execution reverted at {$contractAddress}::{$entrypoint}: {$revertReason}",
            $exception->getMessage()
        );
        $this->assertSame([
            'contract_address' => $contractAddress,
            'entrypoint' => $entrypoint,
            'revert_reason' => $revertReason,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('revertReasonProvider')]
    public function it_handles_various_revert_reasons(string $revertReason): void
    {
        $exception = ContractException::executionReverted('0x123', 'method', $revertReason);

        $this->assertStringContainsString($revertReason, $exception->getMessage());
        $this->assertSame($revertReason, $exception->getContext()['revert_reason']);
    }

    #[Test]
    public function it_creates_invalid_address_exception(): void
    {
        $address = '0xinvalid';

        $exception = ContractException::invalidAddress($address);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame("Invalid contract address '{$address}': Invalid format", $exception->getMessage());
        $this->assertSame([
            'address' => $address,
            'reason' => 'Invalid format',
        ], $exception->getContext());
    }

    #[Test]
    public function it_creates_invalid_address_with_custom_reason(): void
    {
        $address = '0x123';
        $reason = 'Address too short';

        $exception = ContractException::invalidAddress($address, $reason);

        $this->assertSame("Invalid contract address '{$address}': {$reason}", $exception->getMessage());
        $this->assertSame([
            'address' => $address,
            'reason' => $reason,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('invalidAddressProvider')]
    public function it_handles_various_invalid_addresses(string $address, string $reason): void
    {
        $exception = ContractException::invalidAddress($address, $reason);

        $this->assertStringContainsString($address, $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
    }

    #[Test]
    public function it_creates_not_found_exception(): void
    {
        $contractAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';

        $exception = ContractException::notFound($contractAddress);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame("Contract not found at address: {$contractAddress}", $exception->getMessage());
        $this->assertSame(['contract_address' => $contractAddress], $exception->getContext());
    }

    #[Test]
    #[DataProvider('contractAddressProvider')]
    public function it_handles_various_contract_addresses_for_not_found(string $address): void
    {
        $exception = ContractException::notFound($address);

        $this->assertStringContainsString($address, $exception->getMessage());
        $this->assertSame($address, $exception->getContext()['contract_address']);
    }

    #[Test]
    public function it_creates_method_not_found_exception(): void
    {
        $contractAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';
        $method = 'nonExistentMethod';

        $exception = ContractException::methodNotFound($contractAddress, $method);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame(
            "Method '{$method}' not found on contract at {$contractAddress}",
            $exception->getMessage()
        );
        $this->assertSame([
            'contract_address' => $contractAddress,
            'method' => $method,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('methodNameProvider')]
    public function it_handles_various_method_names(string $method): void
    {
        $address = '0x123';
        $exception = ContractException::methodNotFound($address, $method);

        $this->assertStringContainsString($method, $exception->getMessage());
        $this->assertSame($method, $exception->getContext()['method']);
    }

    #[Test]
    public function it_creates_invalid_arguments_exception(): void
    {
        $entrypoint = 'transfer';
        $details = 'Expected 2 arguments, got 1';

        $exception = ContractException::invalidArguments($entrypoint, $details);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame("Invalid arguments for {$entrypoint}: {$details}", $exception->getMessage());
        $this->assertSame([
            'entrypoint' => $entrypoint,
            'details' => $details,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('invalidArgumentsProvider')]
    public function it_handles_various_invalid_argument_scenarios(string $entrypoint, string $details): void
    {
        $exception = ContractException::invalidArguments($entrypoint, $details);

        $this->assertStringContainsString($entrypoint, $exception->getMessage());
        $this->assertStringContainsString($details, $exception->getMessage());
    }

    #[Test]
    public function it_creates_join_failed_exception(): void
    {
        $contractAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';
        $reason = 'Contract not deployed';

        $exception = ContractException::joinFailed($contractAddress, $reason);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame("Failed to join contract at {$contractAddress}: {$reason}", $exception->getMessage());
        $this->assertSame([
            'contract_address' => $contractAddress,
            'reason' => $reason,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('joinFailureProvider')]
    public function it_handles_various_join_failure_reasons(string $reason): void
    {
        $exception = ContractException::joinFailed('0x123', $reason);

        $this->assertStringContainsString($reason, $exception->getMessage());
        $this->assertSame($reason, $exception->getContext()['reason']);
    }

    #[Test]
    public function it_creates_state_read_failed_exception(): void
    {
        $contractAddress = '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb';
        $selector = 'balances[0x456]';
        $reason = 'State not available';

        $exception = ContractException::stateReadFailed($contractAddress, $selector, $reason);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame(
            "Failed to read state '{$selector}' from contract at {$contractAddress}: {$reason}",
            $exception->getMessage()
        );
        $this->assertSame([
            'contract_address' => $contractAddress,
            'selector' => $selector,
            'reason' => $reason,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('stateReadProvider')]
    public function it_handles_various_state_read_failures(
        string $selector,
        string $reason
    ): void {
        $exception = ContractException::stateReadFailed('0x123', $selector, $reason);

        $this->assertStringContainsString($selector, $exception->getMessage());
        $this->assertStringContainsString($reason, $exception->getMessage());
    }

    #[Test]
    public function it_creates_compilation_failed_exception(): void
    {
        $contractPath = '/contracts/TokenContract.compact';
        $errors = 'Syntax error on line 42: Unexpected token';

        $exception = ContractException::compilationFailed($contractPath, $errors);

        $this->assertInstanceOf(ContractException::class, $exception);
        $this->assertSame("Contract compilation failed for {$contractPath}", $exception->getMessage());
        $this->assertSame([
            'contract_path' => $contractPath,
            'errors' => $errors,
        ], $exception->getContext());
    }

    #[Test]
    #[DataProvider('compilationErrorProvider')]
    public function it_handles_various_compilation_errors(string $contractPath, string $errors): void
    {
        $exception = ContractException::compilationFailed($contractPath, $errors);

        $this->assertStringContainsString($contractPath, $exception->getMessage());
        $this->assertSame($errors, $exception->getContext()['errors']);
    }

    #[Test]
    public function all_factory_methods_return_contract_exception_instance(): void
    {
        $methods = [
            ContractException::deploymentFailed('reason'),
            ContractException::callFailed('0x123', 'method', 'reason'),
            ContractException::executionReverted('0x123', 'method', 'reason'),
            ContractException::invalidAddress('0xinvalid'),
            ContractException::notFound('0x123'),
            ContractException::methodNotFound('0x123', 'method'),
            ContractException::invalidArguments('method', 'details'),
            ContractException::joinFailed('0x123', 'reason'),
            ContractException::stateReadFailed('0x123', 'selector', 'reason'),
            ContractException::compilationFailed('/path', 'errors'),
        ];

        foreach ($methods as $exception) {
            $this->assertInstanceOf(ContractException::class, $exception);
            $this->assertInstanceOf(MidnightException::class, $exception);
        }
    }

    public static function deploymentReasonProvider(): array
    {
        return [
            'insufficient gas' => ['Insufficient gas', null],
            'invalid bytecode' => ['Invalid bytecode', '/contracts/Token.compact'],
            'network error' => ['Network error during deployment', '/contracts/NFT.compact'],
            'timeout' => ['Deployment timeout', null],
        ];
    }

    public static function contractCallProvider(): array
    {
        return [
            'insufficient balance' => ['0x123', 'transfer', 'Insufficient balance'],
            'not authorized' => ['0x456', 'mint', 'Caller not authorized'],
            'paused' => ['0x789', 'swap', 'Contract is paused'],
        ];
    }

    public static function revertReasonProvider(): array
    {
        return [
            'insufficient balance' => ['Insufficient balance'],
            'unauthorized' => ['Unauthorized: caller is not owner'],
            'overflow' => ['Arithmetic overflow detected'],
            'custom error' => ['Custom validation failed: amount must be positive'],
        ];
    }

    public static function invalidAddressProvider(): array
    {
        return [
            'too short' => ['0x123', 'Address too short'],
            'invalid characters' => ['0xGGG', 'Contains invalid hexadecimal characters'],
            'no prefix' => ['123abc', 'Missing 0x prefix'],
            'wrong checksum' => ['0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb', 'Checksum mismatch'],
        ];
    }

    public static function contractAddressProvider(): array
    {
        return [
            'standard address' => ['0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'],
            'short address' => ['0x123'],
            'all zeros' => ['0x0000000000000000000000000000000000000000'],
        ];
    }

    public static function methodNameProvider(): array
    {
        return [
            'camelCase' => ['transferFrom'],
            'snake_case' => ['get_balance'],
            'mixed' => ['_internalMethod'],
            'numbers' => ['method123'],
        ];
    }

    public static function invalidArgumentsProvider(): array
    {
        return [
            'wrong count' => ['transfer', 'Expected 2 arguments, got 1'],
            'wrong type' => ['mint', 'Expected address, got string'],
            'out of range' => ['setValue', 'Value must be between 0 and 100'],
            'missing required' => ['approve', 'Missing required argument: spender'],
        ];
    }

    public static function joinFailureProvider(): array
    {
        return [
            'not deployed' => ['Contract not deployed at this address'],
            'network error' => ['Network error while fetching contract state'],
            'incompatible version' => ['Contract version incompatible with client'],
        ];
    }

    public static function stateReadProvider(): array
    {
        return [
            'simple selector' => ['totalSupply', 'State not synchronized'],
            'mapping' => ['balances[0x123]', 'Key not found in mapping'],
            'nested' => ['data.users[0].name', 'Invalid state path'],
        ];
    }

    public static function compilationErrorProvider(): array
    {
        return [
            'syntax error' => [
                '/contracts/Token.compact',
                'Syntax error on line 42: Unexpected token',
            ],
            'type error' => [
                '/contracts/NFT.compact',
                'Type mismatch: expected uint256, got string',
            ],
            'multiple errors' => [
                '/contracts/Complex.compact',
                "Line 10: Undefined variable 'x'\nLine 25: Invalid function signature",
            ],
        ];
    }
}
