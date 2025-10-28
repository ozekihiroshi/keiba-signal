<?php
namespace App\Filament\Admin\Pages\Auth;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextInput;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('email')->label('Email')->email()->required()->autofocus(),
            TextInput::make('password')->label('Password')->password()->required(),
        ]);
    }
}
