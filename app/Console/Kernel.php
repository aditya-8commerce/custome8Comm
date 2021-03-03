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
      Commands\Luxasia\LuxasiaSkuSync::class,
      Commands\Luxasia\LuxasiaPoSync::class,
      Commands\Luxasia\LuxasiaReceiptsPoSync::class,
      Commands\Luxasia\LuxasiaStockSync::class,
      Commands\Luxasia\LuxasiaStockTransferSync::class,
      Commands\Luxasia\LuxasiaSalesTransactionSync::class,
      Commands\Luxasia\LuxasiaSalesTransactionReturnSync::class,


      Commands\Bright\BrightAutoCreateTrip::class,

      /**
       * Courier
       */

      Commands\Courier\JneTrip::class,

      /**
       * Orders
       */

      // Commands\Orders\AutoCloseOrder::class,

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
      $schedule->command('LuxasiaStockSync:sender')->timezone('Asia/Jakarta')->dailyAt('01:00');
      $schedule->command('LuxasiaPoSync:sender')->hourlyAt(33);
      $schedule->command('LuxasiaReceiptsPoSync:sender')->hourlyAt(10);

      // bright
      $schedule->command('BrightAutoCreateTrip:sender')->timezone('Asia/Jakarta')->cron('*/15 08-20 * * 1-6');

      // Courier
      // $schedule->command('JneTrip:sender')->hourlyAt(10);

      // Orders
      // $schedule->command('AutoCloseOrder:sender')->hourlyAt(15);
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
