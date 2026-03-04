# BGFI Payment RDC - PHP SDK (Laravel & Symfony)

SDK pour consommer les APIs BGFI RDC (Omnitech) : verification de compte, depot Rakakash, collecte mobile money et suivi de transaction.

## Installation Laravel
- ```bash
  composer require mecxer713/bgfi-payment
  php artisan vendor:publish --tag=bgfi-config
  ```
- Variables `.env` essentielles :
  ```dotenv
  BGFI_BASE_URL=https://api-uat.bgfi.com
  BGFI_LOGIN=mon_login
  BGFI_PASSWORD=mon_password
  BGFI_CONSUMER_ID=xxxx
  BGFI_CONSUMER_SECRET=xxxx
  BGFI_CURRENCY=CDF
  ```

## Installation Symfony
- ```bash
  composer require mecxer713/bgfi-payment
  ```
- Activer le bundle dans `config/bundles.php` :
  ```php
  return [
      // ...
      Mecxer713\BgfiPayment\Symfony\BgfiPaymentBundle::class => ['all' => true],
  ];
  ```
- Config `config/packages/bgfi_payment.yaml` :
  ```yaml
  bgfi_payment:
    base_url: https://api-uat.bgfi.com
    login: mon_login
    password: mon_password
    consumer_id: xxxx
    consumer_secret: xxxx
    currency: CDF
  ```

## Methodes disponibles
Laravel : importer le facade. Symfony : recuperer le service via l'auto-wiring.
```php
// Laravel
use Mecxer713\BgfiPayment\Facades\BgfiPayment;

// Symfony
use Mecxer713\BgfiPayment\Services\BgfiService;
// $bgfi = $container->get(BgfiService::class);
```

- `checkAccount(string $account, string $type = 'RAKAKASH', array $bankDetails = [])`  
  Verifie l'existence d'un compte.  
  ```php
  $response = BgfiPayment::checkAccount('243820460800'); // Laravel
  // Symfony
  // $response = $bgfi->checkAccount('243820460800');
  ```

- `deposit(string $phone, float $amount, ?string $currency = null)`  
  Effectue un depot vers un compte Rakakash.  
  ```php
  $response = BgfiPayment::deposit('243998760311', 10000, 'CDF');
  // $response = $bgfi->deposit('243998760311', 10000, 'CDF');
  ```

- `collect(array $data)`  
  Demande une collecte mobile money. Champs attendus : `amount`, `phone`, `reference`, `currency`, `return_url` optionnel.  
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
  Recupere le statut d'une collecte existante.  
  ```php
  $status = BgfiPayment::getCollectStatus('ORDER-2026-0001');
  // $status = $bgfi->getCollectStatus('ORDER-2026-0001');
  ```
