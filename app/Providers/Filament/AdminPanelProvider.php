<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Green,
            ])
            ->brandName('SIMUES')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Configuración')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Padrón Poblacional')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Salud Infantil')
                    ->icon(Heroicon::OutlinedFaceSmile)
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Salud Materna')
                    ->icon(Heroicon::OutlinedHeart)
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Prestaciones Mensuales')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Estadísticas')
                    ->icon(Heroicon::OutlinedChartBarSquare)
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Informes')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->collapsed(),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => app(\Illuminate\Foundation\Vite::class)('resources/js/app.js'),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn () => new HtmlString('
                    <script>
                        document.addEventListener("alpine:initialized", () => {
                            const store = Alpine.store("sidebar");
                            if (!store) return;

                            store.toggleCollapsedGroup = function(group) {
                                const groups = Array.isArray(this.collapsedGroups) ? this.collapsedGroups : [];
                                const isCollapsed = groups.includes(group);

                                if (isCollapsed) {
                                    // Expanding: collapse all others
                                    const allGroups = [...document.querySelectorAll("[data-group-label]")]
                                        .map(el => el.dataset.groupLabel);
                                    this.collapsedGroups = allGroups.filter(g => g !== group);
                                } else {
                                    // Collapsing this group
                                    this.collapsedGroups = groups.concat(group);
                                }
                            };
                        });
                    </script>
                ')
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
