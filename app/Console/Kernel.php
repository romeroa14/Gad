protected function schedule(Schedule $schedule)
{
    $schedule->command('facebook:sync')
            ->everyThirtyMinutes();
}
