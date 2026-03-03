# BGFI Payment RDC - Laravel SDK

A lightweight Laravel package to integrate BGFI Bank RDC (Omnitech) payment and deposit services: OAuth token generation, account verification, mobile money deposit/collect, webhook handling, and quick console checks.

## Requirements
- PHP 8.1+
- Laravel 10, 11 or 12

## Installation
```bash
composer require mecxer713/bgfi-payment
php artisan vendor:publish --tag=bgfi-config
```

## Configuration
Fill your `.env` with the credentials provided by BGFI:

| Key | Description |
| --- | --- |
| `BGFI_BASE_URL` | API base URL (UAT or production). |
| `BGFI_LOGIN` / `BGFI_PASSWORD` | Credentials used for the OAuth token call. |
| `BGFI_CONSUMER_ID` / `BGFI_CONSUMER_SECRET` | Consumer pair provided by BGFI. |
| `BGFI_CURRENCY` | Default currency (e.g. `CDF`). |
| `BGFI_CALLBACK_PATH` | Path for the webhook route (`api/bgfi/callback` by default). |
| `BGFI_REGISTER_CALLBACK_ROUTE` | Set to `false` if you want to register the webhook route yourself. |
| `BGFI_CA_PATH` | Path to the CA bundle used for SSL (default `storage/cert/cacert.pem`). |
| `BGFI_VERIFY_SSL` | Set to `false` only in UAT if the certificate is self-signed. |
| `BGFI_TOKEN_TTL` | Token cache lifetime in seconds (default: `3500`). |

Optional config keys live in `config/bgfi.php`: `default_description`, `return_url`, `user_agent`, `register_callback_route`.

SSL note: place your CA file at `storage/cert/cacert.pem` (or point `BGFI_CA_PATH` to a custom absolute path). For quick sandboxing you can set `BGFI_VERIFY_SSL=false`, but re-enable it in production.

## Usage
Import the facade or inject the `Mecxer713\BgfiPayment\Services\BgfiService`.

```php
use Mecxer713\BgfiPayment\Facades\BgfiPayment;

// Account verification
$response = BgfiPayment::checkAccount('243820460800');

// Deposit to a Rakakash account
$response = BgfiPayment::deposit('243998760311', 10000, 'CDF');

// Initiate a mobile money collection
$response = BgfiPayment::collect([
    'amount'    => 25000,
    'phone'     => '243820460800',
    'reference' => 'ORDER-2026-0001',
    'currency'  => 'CDF',
    'return_url'=> 'https://your-app.test/payment/return',
]);

// Check collection status
$status = BgfiPayment::getCollectStatus('ORDER-2026-0001');
```

## Webhook / Callback
- By default a POST route is registered at `/api/bgfi/callback`.
- Each call dispatches the `Mecxer713\BgfiPayment\Events\BgfiCallbackReceived` event.
- Add a listener to process status updates:

```php
use Mecxer713\BgfiPayment\Events\BgfiCallbackReceived;

class BgfiPaymentHandler
{
    public function handle(BgfiCallbackReceived $event): void
    {
        // $event->payload contains the raw callback data
    }
}
```

If you prefer to register the route yourself, set `BGFI_REGISTER_CALLBACK_ROUTE=false` and define your own endpoint that dispatches the event.

## CLI Smoke Test
Run a quick check against your sandbox credentials:
```bash
php artisan bgfi:test
```

## Testing
```bash
composer test
```

## License
MIT
