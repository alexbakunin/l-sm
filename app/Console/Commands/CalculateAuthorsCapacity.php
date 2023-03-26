<?php

namespace App\Console\Commands;

use App\Services\AuthorsCapacity\AuthorsCapacityService;
use Illuminate\Console\Command;

class CalculateAuthorsCapacity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authors:calculate-capacity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(AuthorsCapacityService $authorsCapacity)
    {
        $authors = $authorsCapacity->findAuthorsWithCapacity();

        $this->getOutput()->info(sprintf('Найдено авторах с нагрузкой: %d', $authors->count()));

        if (!$authors->count()) {
            return 0;
        }

        $stat = ['updated' => 0, 'skip' => 0];
        $countProcessOrders = $authorsCapacity->getProcessOrdersByAuthorIds($authors->pluck('id'));
        foreach ($authors as $author) {
            $current = $countProcessOrders[$author->id] ?? 0;
            if (!is_null($author->current_capacity) && (int)$author->current_capacity === $current) {
                $stat['skip']++;
                continue;
            }

            $authorsCapacity->setCurrentCapacity($author->id, $current);
            $stat['updated']++;
        }

        $this->getOutput()->info(
            sprintf('Нагрузка обновлена: %d | Пропущено: %d', $stat['updated'], $stat['skip'])
        );

        return 0;
    }
}
