# Midnight Laravel Package

Laravel integration for Midnight blockchain with PHP backend and TypeScript frontend support.

## Requirements

- PHP 8.3+, Laravel 12+
- Node.js 18+
- Midnight Bridge Service (Node microservice)
- Redis (recommended for cache/queue)

## Installation

```bash
composer require versiontwo/midnight-laravel
npm install @versiontwo/midnight-laravel
php artisan vendor:publish --tag=midnight-config
```

Configure `.env`:

```env
MIDNIGHT_BRIDGE_BASE_URI=http://127.0.0.1:4100
MIDNIGHT_BRIDGE_API_KEY=your-key
MIDNIGHT_NETWORK=devnet
MIDNIGHT_CACHE_STORE=redis
MIDNIGHT_QUEUE_CONNECTION=redis
```

## Usage

### PHP

```php
use VersionTwo\Midnight\Facades\MidnightContracts;

$txHash = MidnightContracts::call(
    contractAddress: '0x123...',
    entrypoint: 'vote',
    publicArgs: ['proposalId' => 1],
    privateArgs: ['vote' => 'yes', 'proof' => '...']
);
```

### Frontend (TypeScript)

```typescript
import { connectWallet, buildAndSubmitVoteTx } from '@versiontwo/midnight-laravel';

await connectWallet();
const txHash = await buildAndSubmitVoteTx({
    contractAddress: '0x123...',
    candidateId: 1,
    encryptedBallot: '...'
});
```

### Blade

```blade
@midnightWallet(['showBalance' => true])
@midnightVoteForm(['proposalId' => 1, 'options' => ['Yes', 'No']])
```

## Commands

```bash
php artisan midnight:health
php artisan midnight:network:info
php artisan midnight:contract:deploy /path/to/contract.json
php artisan midnight:cache:clear
```

## Testing

```bash
composer test      # PHP tests
npm test          # Frontend tests
```

## License

MIT
