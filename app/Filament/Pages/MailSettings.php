<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\Mail;
use App\Models\MailSetting;

class MailSettings extends Page
{
    protected static string $view = 'filament.pages.mail-settings';
    protected static ?string $title = 'Pengaturan Email';

    public array $formData = [];

    public function mount(): void
    {
        $this->formData = MailSetting::firstOrCreate([])->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->model(null)
            ->statePath('formData')
            ->schema([
                Select::make('mailer')
                    ->options([
                        'smtp'      => 'SMTP',
                        'sendmail'  => 'Sendmail',
                        'log'       => 'Log',
                    ])
                    ->required()
                    ->searchable(),
                TextInput::make('host')->label('Host')->required(),
                TextInput::make('port')->label('Port')->required(),
                TextInput::make('username')->label('Username'),
                TextInput::make('password')->label('Password')->password(),
                TextInput::make('encryption')->label('Encryption'),
                TextInput::make('from_address')->label('From Email')->required(),
                TextInput::make('from_name')->label('From Name')->required(),
                Actions::make([
                    Action::make('save')
                        ->label('Simpan')
                        ->action(fn() => $this->save())
                        ->color('primary'),

                    Action::make('test_email')
                        ->label('Test Kirim Email')
                        ->action('sendTestEmail')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Test Email')
                        ->modalDescription('Ini akan mencoba mengirim email percobaan menggunakan pengaturan email saat ini.')
                        ->icon('heroicon-o-paper-airplane'),
                ])
            ]);
    }

    public function save()
    {
        try {
            MailSetting::updateOrCreate([], $this->formData);

            Notification::make()
                ->title('Saved')
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Menangkap error dan mencatatnya
            logger()->error('Failed to save mail settings: ' . $e->getMessage());

            Notification::make()
                ->title('Failed to save mail settings.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function sendTestEmail()
    {
        try {
            $mail = (object) $this->formData;

            config([
                'mail.default'                  => $mail->mailer,
                'mail.mailers.smtp.host'        => $mail->host,
                'mail.mailers.smtp.port'        => $mail->port,
                'mail.mailers.smtp.username'    => $mail->username,
                'mail.mailers.smtp.password'    => $mail->password,
                'mail.mailers.smtp.encryption'  => $mail->encryption,
                'mail.from.address'             => $mail->from_address,
                'mail.from.name'                => $mail->from_name,
                'mail.mailers.smtp.timeout'     => 10, // timeout 10 detik
            ]);

            // Kirim email
            Mail::raw('This is a test email from your application.', function ($message) use ($mail) {
                $message->to($mail->from_address)
                    ->subject('Test Email');
            });

            Notification::make()
                ->title('Test email sent successfully.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            logger()->error('Test email failed: ' . $e->getMessage());

            Notification::make()
                ->title('Failed to send test email')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
