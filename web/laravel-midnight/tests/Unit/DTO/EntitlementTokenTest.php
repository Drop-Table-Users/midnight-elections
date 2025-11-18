<?php

declare(strict_types=1);

namespace VersionTwo\Midnight\Tests\Unit\DTO;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use VersionTwo\Midnight\DTO\EntitlementToken;
use VersionTwo\Midnight\Tests\Unit\TestCase;

/**
 * Test suite for the EntitlementToken DTO.
 *
 * @covers \VersionTwo\Midnight\DTO\EntitlementToken
 */
final class EntitlementTokenTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_required_fields(): void
    {
        $token = new EntitlementToken(
            token: '0xtoken123',
            identity: 'user@example.com'
        );

        $this->assertInstanceOf(EntitlementToken::class, $token);
        $this->assertSame('0xtoken123', $token->token);
        $this->assertSame('user@example.com', $token->identity);
        $this->assertNull($token->issuedAt);
        $this->assertNull($token->expiresAt);
        $this->assertSame([], $token->claims);
        $this->assertSame([], $token->metadata);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_fields(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $expiresAt = new DateTimeImmutable('2024-12-31 23:59:59');
        $claims = ['role' => 'voter', 'district' => '5'];
        $metadata = ['version' => '1.0'];

        $token = new EntitlementToken(
            token: '0xtoken456',
            identity: 'voter123',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            claims: $claims,
            metadata: $metadata
        );

        $this->assertSame('0xtoken456', $token->token);
        $this->assertSame('voter123', $token->identity);
        $this->assertSame($issuedAt, $token->issuedAt);
        $this->assertSame($expiresAt, $token->expiresAt);
        $this->assertSame($claims, $token->claims);
        $this->assertSame($metadata, $token->metadata);
    }

    #[Test]
    public function it_is_final_and_readonly(): void
    {
        $token = new EntitlementToken('0xtoken', 'identity');

        $this->assertClassIsFinal($token);
        $this->assertClassIsReadonly($token);
    }

    #[Test]
    public function it_throws_exception_when_token_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entitlement token cannot be empty');

        new EntitlementToken(
            token: '',
            identity: 'user'
        );
    }

    #[Test]
    public function it_throws_exception_when_identity_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identity cannot be empty');

        new EntitlementToken(
            token: '0xtoken',
            identity: ''
        );
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $token = EntitlementToken::fromString('0xabc123');

        $this->assertInstanceOf(EntitlementToken::class, $token);
        $this->assertSame('0xabc123', $token->token);
        $this->assertSame('unknown', $token->identity);
    }

    #[Test]
    public function it_can_be_created_from_string_with_identity(): void
    {
        $token = EntitlementToken::fromString('0xabc123', 'user@test.com');

        $this->assertSame('0xabc123', $token->token);
        $this->assertSame('user@test.com', $token->identity);
    }

    #[Test]
    public function it_can_be_created_from_array_with_snake_case_keys(): void
    {
        $data = [
            'token' => '0xtoken789',
            'identity' => 'voter456',
            'issued_at' => '2024-01-01T12:00:00+00:00',
            'expires_at' => '2024-12-31T23:59:59+00:00',
            'claims' => ['role' => 'admin'],
            'metadata' => ['source' => 'api'],
        ];

        $token = EntitlementToken::fromArray($data);

        $this->assertSame('0xtoken789', $token->token);
        $this->assertSame('voter456', $token->identity);
        $this->assertInstanceOf(DateTimeImmutable::class, $token->issuedAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $token->expiresAt);
        $this->assertSame(['role' => 'admin'], $token->claims);
        $this->assertSame(['source' => 'api'], $token->metadata);
    }

    #[Test]
    public function it_can_be_created_from_array_with_camel_case_keys(): void
    {
        $data = [
            'token' => '0xtoken',
            'identity' => 'user',
            'issuedAt' => '2024-01-01T12:00:00+00:00',
            'expiresAt' => '2024-12-31T23:59:59+00:00',
            'claims' => ['claim' => 'value'],
            'metadata' => ['meta' => 'data'],
        ];

        $token = EntitlementToken::fromArray($data);

        $this->assertSame('0xtoken', $token->token);
        $this->assertSame('user', $token->identity);
        $this->assertInstanceOf(DateTimeImmutable::class, $token->issuedAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $token->expiresAt);
    }

    #[Test]
    public function it_prefers_snake_case_over_camel_case_in_fromArray(): void
    {
        $data = [
            'token' => '0xtoken',
            'identity' => 'snake',
            'issued_at' => '2024-01-01T12:00:00+00:00',
            'issuedAt' => '2023-01-01T12:00:00+00:00',
        ];

        $token = EntitlementToken::fromArray($data);

        $this->assertSame('2024-01-01T12:00:00+00:00', $token->issuedAt?->format(DateTimeImmutable::ATOM));
    }

    #[Test]
    public function it_handles_timestamp_format_in_fromArray(): void
    {
        $timestamp = 1704110400; // 2024-01-01 12:00:00 UTC
        $data = [
            'token' => '0xtoken',
            'identity' => 'user',
            'issued_at' => $timestamp,
            'expires_at' => $timestamp + 86400,
        ];

        $token = EntitlementToken::fromArray($data);

        $this->assertInstanceOf(DateTimeImmutable::class, $token->issuedAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $token->expiresAt);
    }

    #[Test]
    public function it_handles_missing_optional_fields_in_fromArray(): void
    {
        $data = [
            'token' => '0xtoken',
            'identity' => 'user',
        ];

        $token = EntitlementToken::fromArray($data);

        $this->assertNull($token->issuedAt);
        $this->assertNull($token->expiresAt);
        $this->assertSame([], $token->claims);
        $this->assertSame([], $token->metadata);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $expiresAt = new DateTimeImmutable('2024-12-31 23:59:59');

        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            claims: ['role' => 'voter'],
            metadata: ['version' => '1.0']
        );

        $array = $token->toArray();

        $this->assertSame('0xtoken', $array['token']);
        $this->assertSame('user', $array['identity']);
        $this->assertSame($issuedAt->format(DateTimeImmutable::ATOM), $array['issued_at']);
        $this->assertSame($expiresAt->format(DateTimeImmutable::ATOM), $array['expires_at']);
        $this->assertSame(['role' => 'voter'], $array['claims']);
        $this->assertSame(['version' => '1.0'], $array['metadata']);
    }

    #[Test]
    public function it_converts_to_array_with_null_timestamps(): void
    {
        $token = new EntitlementToken('0xtoken', 'user');

        $array = $token->toArray();

        $this->assertNull($array['issued_at']);
        $this->assertNull($array['expires_at']);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $token = new EntitlementToken('0xtoken123', 'user');

        $this->assertSame('0xtoken123', (string) $token);
        $this->assertSame('0xtoken123', $token->__toString());
    }

    #[Test]
    public function it_detects_expired_token(): void
    {
        $expiresAt = new DateTimeImmutable('2020-01-01 00:00:00');
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            expiresAt: $expiresAt
        );

        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isValid());
    }

    #[Test]
    public function it_detects_valid_token(): void
    {
        $expiresAt = new DateTimeImmutable('+1 year');
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            expiresAt: $expiresAt
        );

        $this->assertFalse($token->isExpired());
        $this->assertTrue($token->isValid());
    }

    #[Test]
    public function it_treats_token_without_expiry_as_never_expired(): void
    {
        $token = new EntitlementToken('0xtoken', 'user');

        $this->assertFalse($token->isExpired());
        $this->assertTrue($token->isValid());
    }

    #[Test]
    public function it_checks_expiry_against_custom_time(): void
    {
        $expiresAt = new DateTimeImmutable('2024-06-01 12:00:00');
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            expiresAt: $expiresAt
        );

        $beforeExpiry = new DateTimeImmutable('2024-05-01 12:00:00');
        $afterExpiry = new DateTimeImmutable('2024-07-01 12:00:00');

        $this->assertFalse($token->isExpired($beforeExpiry));
        $this->assertTrue($token->isExpired($afterExpiry));
    }

    #[Test]
    public function it_gets_specific_claim(): void
    {
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            claims: ['role' => 'admin', 'level' => 5]
        );

        $this->assertSame('admin', $token->getClaim('role'));
        $this->assertSame(5, $token->getClaim('level'));
    }

    #[Test]
    public function it_returns_default_for_missing_claim(): void
    {
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            claims: ['role' => 'admin']
        );

        $this->assertNull($token->getClaim('missing'));
        $this->assertSame('default', $token->getClaim('missing', 'default'));
    }

    #[Test]
    #[DataProvider('hasClaimProvider')]
    public function it_detects_if_claim_exists(array $claims, string $key, bool $expected): void
    {
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            claims: $claims
        );

        $this->assertSame($expected, $token->hasClaim($key));
    }

    #[Test]
    public function it_calculates_time_to_expiration(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $expiresAt = new DateTimeImmutable('2024-01-02 12:00:00');

        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            expiresAt: $expiresAt
        );

        $interval = $token->getTimeToExpiration($now);

        $this->assertInstanceOf(DateInterval::class, $interval);
        $this->assertSame(1, $interval->days);
    }

    #[Test]
    public function it_returns_null_for_time_to_expiration_without_expiry(): void
    {
        $token = new EntitlementToken('0xtoken', 'user');

        $this->assertNull($token->getTimeToExpiration());
    }

    #[Test]
    public function it_creates_new_instance_with_additional_claims(): void
    {
        $original = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            claims: ['role' => 'user']
        );

        $modified = $original->withClaims(['level' => 5]);

        // Original unchanged
        $this->assertSame(['role' => 'user'], $original->claims);

        // Modified has merged claims
        $this->assertSame([
            'role' => 'user',
            'level' => 5,
        ], $modified->claims);
    }

    #[Test]
    public function withClaims_merges_and_overwrites_claims(): void
    {
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            claims: ['role' => 'user', 'level' => 1]
        );

        $modified = $token->withClaims([
            'level' => 5,
            'department' => 'IT',
        ]);

        $this->assertSame([
            'role' => 'user',
            'level' => 5,
            'department' => 'IT',
        ], $modified->claims);
    }

    #[Test]
    public function withClaims_preserves_other_properties(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-01');
        $expiresAt = new DateTimeImmutable('2024-12-31');

        $original = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            claims: ['role' => 'user'],
            metadata: ['version' => '1.0']
        );

        $modified = $original->withClaims(['new' => 'claim']);

        $this->assertSame($original->token, $modified->token);
        $this->assertSame($original->identity, $modified->identity);
        $this->assertSame($original->issuedAt, $modified->issuedAt);
        $this->assertSame($original->expiresAt, $modified->expiresAt);
        $this->assertSame($original->metadata, $modified->metadata);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $token = new EntitlementToken('0xtoken', 'user');

        $this->assertPropertyIsReadonly($token, 'token');
        $this->assertPropertyIsReadonly($token, 'identity');
        $this->assertPropertyIsReadonly($token, 'issuedAt');
        $this->assertPropertyIsReadonly($token, 'expiresAt');
        $this->assertPropertyIsReadonly($token, 'claims');
        $this->assertPropertyIsReadonly($token, 'metadata');
    }

    #[Test]
    public function it_roundtrips_through_array_conversion(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $expiresAt = new DateTimeImmutable('2024-12-31 23:59:59');

        $original = new EntitlementToken(
            token: '0xtoken',
            identity: 'user@test.com',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            claims: ['role' => 'admin'],
            metadata: ['version' => '1.0']
        );

        $array = $original->toArray();
        $restored = EntitlementToken::fromArray($array);

        $this->assertSame($original->token, $restored->token);
        $this->assertSame($original->identity, $restored->identity);
        $this->assertSame(
            $original->issuedAt?->format(DateTimeImmutable::ATOM),
            $restored->issuedAt?->format(DateTimeImmutable::ATOM)
        );
        $this->assertSame(
            $original->expiresAt?->format(DateTimeImmutable::ATOM),
            $restored->expiresAt?->format(DateTimeImmutable::ATOM)
        );
        $this->assertSame($original->claims, $restored->claims);
        $this->assertSame($original->metadata, $restored->metadata);
    }

    #[Test]
    public function fromString_throws_exception_for_empty_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entitlement token cannot be empty');

        EntitlementToken::fromString('');
    }

    #[Test]
    public function it_handles_complex_claims(): void
    {
        $complexClaims = [
            'roles' => ['admin', 'moderator'],
            'permissions' => ['read', 'write', 'delete'],
            'settings' => ['theme' => 'dark', 'language' => 'en'],
            'count' => 42,
        ];

        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            claims: $complexClaims
        );

        $this->assertSame($complexClaims, $token->claims);
        foreach ($complexClaims as $key => $value) {
            $this->assertSame($value, $token->getClaim($key));
            $this->assertTrue($token->hasClaim($key));
        }
    }

    #[Test]
    public function it_handles_edge_case_expiry_at_exact_time(): void
    {
        $expiryTime = new DateTimeImmutable('2024-06-01 12:00:00');
        $token = new EntitlementToken(
            token: '0xtoken',
            identity: 'user',
            expiresAt: $expiryTime
        );

        // At exact expiry time
        $this->assertFalse($token->isExpired($expiryTime));

        // One second after expiry
        $afterExpiry = $expiryTime->modify('+1 second');
        $this->assertTrue($token->isExpired($afterExpiry));
    }

    #[Test]
    public function fromArray_handles_empty_array(): void
    {
        $token = EntitlementToken::fromArray([]);

        $this->assertSame('', $token->token);
        $this->assertSame('', $token->identity);
        $this->assertNull($token->issuedAt);
        $this->assertNull($token->expiresAt);
    }

    /**
     * Data provider for hasClaim tests.
     *
     * @return array<string, array{array<string, mixed>, string, bool}>
     */
    public static function hasClaimProvider(): array
    {
        return [
            'existing claim' => [['role' => 'admin'], 'role', true],
            'missing claim' => [['role' => 'admin'], 'level', false],
            'empty claims' => [[], 'anything', false],
            'null value claim' => [['key' => null], 'key', true],
        ];
    }
}
