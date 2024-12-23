protected function schedule(Schedule $schedule)
{
    $schedule->command('facebook:refresh-token')->cron('0 0 */50 * *');
}
