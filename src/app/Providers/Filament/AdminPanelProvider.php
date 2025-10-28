<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages;
use App\Filament\Admin\Pages\Auth\Login as AdminLogin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id("admin")
            ->path("admin")
            ->middleware(["web"])
            ->login(AdminLogin::class)
            ->discoverResources(in: app_path("Filament/Admin/Resources"), for: "App\\Filament\\Admin\\Resources")
            ->discoverPages(in: app_path("Filament/Admin/Pages"), for: "App\\Filament\\Admin\\Pages")
            ->discoverWidgets(in: app_path("Filament/Admin/Widgets"), for: "App\\Filament\\Admin\\Widgets")
            ->pages([
                Pages\Dashboard::class,
            ]);
    }
}
