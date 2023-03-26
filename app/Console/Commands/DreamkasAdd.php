<?php

namespace App\Console\Commands;

use App\Services\CrmApi\CrmApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DreamkasAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dreamkas:add {sum : Сумма} {userId : ID пользователя} {contractId : ID договора} {isRefund=0 : Делаем возврат?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отрправить чек в Дримкассу';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param  CrmApiService  $crmApiService
     *
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function handle(CrmApiService $crmApiService)
    {
        $log = Log::stack(['dreamkas']);

        $sum = (float)$this->input->getArgument('sum');
        $userId = (int)$this->input->getArgument('userId');
        $contractId = (int)$this->input->getArgument('contractId');
        $isRefund = (int)$this->input->getArgument('isRefund');
        $log->debug(
            sprintf('add: Запуск команды: sum: %s | user_id: %s  | contract_id: %s | isRefund: %s' , $sum, $userId, $contractId, $isRefund)
        );

        try {
            $return = $crmApiService->sendDreamkasAdd(
                $sum,
                $userId,
                $contractId,
                $isRefund
            );

            $log->debug("add: Получен ответ: " . json_encode($return, JSON_THROW_ON_ERROR));

            if (!($return['status'] ?? false)) {
                throw new \RuntimeException($return['error'] ?? 'Неизвестная ошибка');
            }

            $log->info(
                sprintf("add: Создан чек: sum: %s | user_id: %s  | contract_id: %s | isRefund: %s", $sum, $userId, $contractId, $isRefund)
            );

        } catch (\Throwable $e) {
            $log->error(
                sprintf(
                    'add: Ошибка: %s | sum: %s | user_id: %s  | contract_id: %s | isRefund: %s',
                    $e->getMessage(),
                    $sum,
                    $userId,
                    $contractId,
                    $isRefund
                )
            );
        }

        return 0;
    }
}
