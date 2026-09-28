<?php
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use App\Models\Source;
use App\Jobs\CollectSource;

Schedule::call(function () {
    Source::query()->where('enabled', true)->where(function ($q) {
        $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
    })->chunkById(50, fn ($sources) => $sources->each(fn ($source) => CollectSource::dispatch($source->id)));
})->everyMinute()->withoutOverlapping();

Schedule::call(function () {
    \App\Models\User::where('email_mode','digest')->select('id')->chunkById(200,
        fn ($users) => $users->each(fn ($user) => \App\Jobs\DailyDigest::dispatch($user->id)));
})->hourly()->withoutOverlapping();

Artisan::command('promo:owner', function () {
    $email = env('OWNER_EMAIL');
    $password = env('OWNER_PASSWORD');
    if (!$email || !$password || strlen($password) < 12) {
        $this->error('Set OWNER_EMAIL and an OWNER_PASSWORD of at least 12 characters.');
        return;
    }
    \App\Models\User::updateOrCreate(['email' => $email], [
        'name' => 'Owner', 'password' => \Illuminate\Support\Facades\Hash::make($password), 'is_owner' => true,
    ]);
    $this->info('Owner account ready.');
});
