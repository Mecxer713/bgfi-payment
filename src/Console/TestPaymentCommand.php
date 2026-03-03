<?php

namespace Mecxer713\BgfiPayment\Console;

use Exception;
use Illuminate\Console\Command;
use Mecxer713\BgfiPayment\Services\BgfiService;

class TestPaymentCommand extends Command
{
    protected $signature = 'bgfi:test {amount=500} {phone=243998760311}';
    protected $description = 'Quick check to confirm the BGFI integration works';

    public function handle(BgfiService $service)
    {
        $this->info('--- BGFI Account Verification ---');

        try {
            $account = $service->checkAccount('243820460800');
            $this->table(['Field', 'Value'], collect($account)->map(fn($v, $k) => [$k, $v])->toArray());

            if (isset($account['customername'])) {
                $this->info('Success: account belongs to ' . $account['customername']);
            }
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
