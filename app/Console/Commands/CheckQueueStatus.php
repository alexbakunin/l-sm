<?php

namespace App\Console\Commands;

use App\Services\Alert\AlertService;
use App\Services\Queue\QueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckQueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:check-status {limit=0 : кол-во для отправки ошибки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверить статус очереди (отправить уведомление в телеграм)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QueueService $queueService, AlertService $alertService)
    {
        $limit = (int)$this->input->getArgument('limit');
        $sizes = $queueService->getSize();
        $sum = array_sum(array_column($sizes, 'count'));
        $sentNotifyCacheKey = 'CommandCheckQueueStatusNotifySend';
        $sentNotify = Cache::has($sentNotifyCacheKey);

        $this->getOutput()->info("Всего сообщений: {$sum}");
        foreach ($sizes as $size) {
            $this->getOutput()->info("- {$size['name']}: {$size['count']}");
        }

        if ($limit && $sum > $limit && !$sentNotify) {
            $message = "\xE2\x9D\x97 Много сообщения в очереди \xE2\x9D\x97\n";
            $message .= "Всего сообщений {$sum}";
            foreach ($sizes as $size) {
                $message .= "\n- *{$size['name']}*: {$size['count']}";
            }

            $alertService->sendAdminMessage($message);
            Cache::set($sentNotifyCacheKey, true);
        } elseif ($limit && $sum <= $limit && $sentNotify) {
            $message = "\xE2\x9C\x85 Очередь нормализовалась \xE2\x9C\x85\n";
            $message .= "Всего сообщений {$sum}";
            foreach ($sizes as $size) {
                $message .= "\n- *{$size['name']}*: {$size['count']}";
            }

            $alertService->sendAdminMessage($message);
            Cache::delete($sentNotifyCacheKey);
        }

        return Command::SUCCESS;
    }
}
