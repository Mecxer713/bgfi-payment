# BGFI Payment RDC - PHP SDK (Laravel & Symfony)

SDK unique pour consommer les APIs BGFI RDC (Omnitech) côté Laravel (provider + facade) et Symfony (bundle auto-wirable) : vérification de compte, dépôt Rakakash, collecte mobile money et suivi de transaction.

## Prérequis
- PHP 8.1+
- Composer
- Framework: Laravel 10/11/12 ou Symfony 7.x

## Installation Laravel
```bash
composer require mecxer713/bgfi-payment
php artisan vendor:publish --tag=bgfi-config
```
`.env` minimum :
```dotenv
BGFI_BASE_URL=https://api-uat.bgfi.com
BGFI_LOGIN=mon_login
BGFI_PASSWORD=mon_password
BGFI_CONSUMER_ID=xxxx
BGFI_CONSUMER_SECRET=xxxx
BGFI_CURRENCY=CDF
```

## Installation Symfony
```bash
composer require mecxer713/bgfi-payment
```
Activer le bundle (`config/bundles.php`) :
```php
return [
    // ...
    Mecxer713\BgfiPayment\Symfony\BgfiPaymentBundle::class => ['all' => true],
];
```
Configuration (`config/packages/bgfi_payment.yaml`) :
```yaml
bgfi_payment:
  base_url: https://api-uat.bgfi.com
  login: mon_login
  password: mon_password
  consumer_id: xxxx
  consumer_secret: xxxx
  currency: CDF
```

## Configuration avancée (commune)
| Clé | Description |
| --- | --- |
| `base_url` | URL API BGFI (UAT ou prod). |
| `login` / `password` | Identifiants OAuth. |
| `consumer_id` / `consumer_secret` | Couple fourni par BGFI. |
| `currency` | Devise par défaut (`CDF`…). |
| `default_description` | Description par défaut des collectes. |
| `return_url` | URL de retour client après paiement. |
| `verify_ssl` | `true` en prod, `false` possible en UAT. |
| `ca_path` | Chemin vers le bundle CA si certif custom. |
| `token_ttl` | Durée de cache du token en secondes (3500). |
| Laravel seul | `callback_path`, `register_callback_route` pour le webhook auto. |

## Utilisation
Laravel : importer le facade. Symfony : auto-wirer le service.
```php
// Laravel
use Mecxer713\BgfiPayment\Facades\BgfiPayment;

// Symfony
use Mecxer713\BgfiPayment\Services\BgfiService;
// $bgfi = $container->get(BgfiService::class);
```

- `checkAccount(string $account, string $type = 'RAKAKASH', array $bankDetails = [])`  
  Vérifie l'existence d'un compte.  
  ```php
  $response = BgfiPayment::checkAccount('243820460800');          // Laravel
  // $response = $bgfi->checkAccount('243820460800');             // Symfony
  ```

- `deposit(string $phone, float $amount, ?string $currency = null)`  
  Dépôt Rakakash.  
  ```php
  $response = BgfiPayment::deposit('243998760311', 10000, 'CDF');
  // $response = $bgfi->deposit('243998760311', 10000, 'CDF');
  ```

- `collect(array $data)`  
  Collecte mobile money (`amount`, `phone`, `reference`, `currency`, `return_url` optionnel).  
  ```php
  $response = BgfiPayment::collect([
      'amount'    => 25000,
      'phone'     => '243820460800',
      'reference' => 'ORDER-2026-0001',
      'currency'  => 'CDF',
      'return_url'=> 'https://votre-app.test/paiement/retour',
  ]);
  // $response = $bgfi->collect([...]);
  ```

- `getCollectStatus(string $externalRef)`  
  Statut d'une collecte existante.  
  ```php
  $status = BgfiPayment::getCollectStatus('ORDER-2026-0001');
  // $status = $bgfi->getCollectStatus('ORDER-2026-0001');
  ```

## Webhook / Callback
- Laravel : route POST auto `/{callback_path}` (par défaut `api/bgfi/callback`). Désactiver via `BGFI_REGISTER_CALLBACK_ROUTE=false`. L'événement `Mecxer713\BgfiPayment\Events\BgfiCallbackReceived` est dispatché.
- Symfony : exposez votre propre contrôleur qui appelle `BgfiService` ou relaie l'événement selon vos besoins.

## Vérification rapide (Laravel)
```bash
php artisan bgfi:test
```

## Tests
```bash
composer test
```
