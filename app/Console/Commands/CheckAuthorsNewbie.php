<?php

namespace App\Console\Commands;

use App\Services\AuthorsCheckNewbie\AuthorsCheckNewbieService;
use Illuminate\Console\Command;

class CheckAuthorsNewbie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authors:check-newbie';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновление состояния статус "Новый" у автора';

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
    public function handle(AuthorsCheckNewbieService $authorsCheckNewbie)
    {
        $authors = $authorsCheckNewbie->getNewbieAuthors();

        $this->getOutput()->info(sprintf('Найдено новых авторах: %d', $authors->count()));

        if (!$authors->count()) {
            return 0;
        }

        $stat = ['clear' => 0, 'capacity' => 0];
        $countEndedOrders = $authorsCheckNewbie->getCountEndedAuthorsOrders($authors->pluck('id'));
        foreach ($authors as $author) {
            $count = $countEndedOrders[$author->id] ?? 0;
            if ($count >= 5) {
                $authorsCheckNewbie->clearNewbie($author->id, $author->capacity);
                $stat['clear']++;
            } elseif (is_null($author->capacity)) {
                $authorsCheckNewbie->setNewbieCapacity($author->id);
                $stat['capacity']++;
            }
        }

        $this->getOutput()->info(
            sprintf('Ушли из новичков: %d | установлена нагрузка для новичков: %d', $stat['clear'], $stat['capacity'])
        );

        return 0;
    }
}
