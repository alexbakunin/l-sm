<?php

namespace App\Console;

use App\Console\Commands\Author24\CheckDialogsCommand;
use App\Console\Commands\Author24\CheckNotificationsCommand;
use App\Console\Commands\Author24\GetNewOrdersWithoutBidCommand;
use App\Console\Commands\Author24\ParseAccountsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        GetNewOrdersWithoutBidCommand::class,
        CheckDialogsCommand::class,
        ParseAccountsCommand::class,
        CheckNotificationsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('chat:autoresponder')
            ->everyMinute()
            ->runInBackground();

        $schedule->command('queue:check-status 5000')
            ->everyFiveMinutes()
            ->runInBackground()
            ->onOneServer();

        $schedule->command('authors:check-newbie')
            ->everyFiveMinutes()
            ->runInBackground();

        $schedule->command('authors:calculate-capacity')
            ->everyFiveMinutes()
            ->runInBackground();

        $schedule->command('author24:parse-accounts')->everyFiveMinutes()->runInBackground()->withoutOverlapping();
        $schedule->command('author24:check-dialogs')->everyMinute()->runInBackground();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
