<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
      \Laravelista\LumenVendorPublish\VendorPublishCommand::class,
      Commands\Luxasia\LuxasiaSkuSync::class, // luxasia
      Commands\Luxasia\LuxasiaPoSync::class, // luxasia
      Commands\Luxasia\LuxasiaReceiptsPoSync::class, // luxasia
      Commands\Luxasia\LuxasiaStockSync::class, // luxasia
      Commands\Luxasia\LuxasiaStockTransferSync::class, // luxasia
      Commands\Luxasia\LuxasiaSalesTransactionSync::class, // luxasia
      Commands\Luxasia\LuxasiaSalesTransactionReturnSync::class, // luxasia


      Commands\Bright\BrightAutoCreateTrip::class, // bright
    ];
    

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
      // luxasia
      $schedule->command('LuxasiaSkuSync:sender')->timezone('Asia/Jakarta')->dailyAt('01:00');
      $schedule->command('LuxasiaStockTransferSync:sender')->timezone('Asia/Jakarta')->dailyAt('01:00');
      $schedule->command('LuxasiaSalesTransactionSync:sender')->timezone('Asia/Jakarta')->dailyAt('01:00');
      $schedule->command('LuxasiaSalesTransactionReturnSync:sender')->timezone('Asia/Jakarta')->dailyAt('01:00');
      $schedule->command('LuxasiaStockSync:sender')->timezone('Asia/Jakarta')->dailyAt('05:41');
      $schedule->command('LuxasiaPoSync:sender')->hourlyAt(33);
      $schedule->command('LuxasiaReceiptsPoSync:sender')->hourlyAt(10);

      // bright
      $schedule->command('BrightAutoCreateTrip:sender')->timezone('Asia/Jakarta')->cron('*/15 08-20 * * 1-6');
    }

    /**
   * Get the timezone that should be used by default for scheduled events.
   *
   * @return \DateTimeZone|string|null
   */
  protected function scheduleTimezone()
  {
      return 'Asia/Jakarta';
  }
 
}
