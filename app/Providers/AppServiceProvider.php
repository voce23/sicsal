<?php

namespace App\Providers;

use App\Models\Anticoncepcion;
use App\Models\ControlPrenatal;
use App\Models\CrecimientoInfantil;
use App\Models\MicronutrienteNino;
use App\Models\Parto;
use App\Models\VacunaNino;
use App\Observers\AnticoncepcionObserver;
use App\Observers\ControlPrenatalObserver;
use App\Observers\CrecimientoInfantilObserver;
use App\Observers\MicronutrienteNinoObserver;
use App\Observers\PartoObserver;
use App\Observers\VacunaNinoObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registrarObservers();
    }

    /**
     * Observers de auto-sincronización: registros individuales → tablas Prest*.
     */
    protected function registrarObservers(): void
    {
        VacunaNino::observe(VacunaNinoObserver::class);
        Parto::observe(PartoObserver::class);
        ControlPrenatal::observe(ControlPrenatalObserver::class);
        CrecimientoInfantil::observe(CrecimientoInfantilObserver::class);
        MicronutrienteNino::observe(MicronutrienteNinoObserver::class);
        Anticoncepcion::observe(AnticoncepcionObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
