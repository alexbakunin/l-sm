<?php

namespace App\Console\Commands\Author24;

use App\Models\Author24\Account;
use App\Services\Settings\SettingsService;
use Illuminate\Console\Command;

class ParseAccountsCommand extends Command
{
    protected $signature = 'author24:parse-accounts';

    protected $description = 'Command description';

    public function handle(SettingsService $settingsService)
    {
        $enabled = (int)$settingsService->getAuthor24Enabled()?->value
            ?: 0;
        if ($enabled == 0) {
            $this->error('Author24 disabled');
            return;
        }

        $accounts = Account::where(['in_work' => 1, 'active' => 1])->get();
        foreach ($accounts as $account) {
            $this->info(sprintf("Account: %s(%s)", $account->name, $account->email));
            $this->call('author24:get-new-orders-without-bid', [
                'accountID' => $account->id
            ]);
            $this->info('Sleep 10 seconds before next account');
            sleep(10);
        }
    }
}
