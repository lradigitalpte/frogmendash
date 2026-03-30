<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestEmailConnection extends Command
{
    protected $signature = 'test:email {email=connect@frogmentec.com}';
    protected $description = 'Test SMTP email connection';

    public function handle()
    {
        $this->info('🔍 Testing SMTP Connection...');
        $this->newLine();

        try {
            $recipient = $this->argument('email');
            
            $this->info("📧 Sending test email to: {$recipient}");
            
            Mail::raw('🎉 SMTP Test Email from FrogMen Dashboard

This is a test email to verify SMTP configuration is working correctly.

✅ SMTP Server: ' . config('mail.mailers.smtp.host') . '
✅ Port: ' . config('mail.mailers.smtp.port') . '  
✅ Encryption: ' . config('mail.mailers.smtp.encryption') . '
✅ Username: ' . config('mail.mailers.smtp.username') . '

Sent at: ' . now()->format('Y-m-d H:i:s T') . '

Best regards,
FrogMen Dashboard', function (Message $message) use ($recipient) {
                $message->to($recipient)
                    ->subject('✅ SMTP Test - FrogMen Dashboard')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info('✅ Email sent successfully!');
            $this->info("📬 Check your inbox at: {$recipient}");
            $this->newLine();
            
            $this->info('📋 SMTP Configuration Summary:');
            $this->info('   Server: ' . config('mail.mailers.smtp.host'));
            $this->info('   Port: ' . config('mail.mailers.smtp.port'));
            $this->info('   Encryption: ' . config('mail.mailers.smtp.encryption'));
            $this->info('   Username: ' . config('mail.mailers.smtp.username'));
            $this->info('   From Address: ' . config('mail.from.address'));
            
        } catch (\Exception $e) {
            $this->error('❌ SMTP Test Failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            
            if (str_contains($e->getMessage(), 'Connection could not be established')) {
                $this->warn('💡 Tips:');
                $this->warn('   - Check if your hosting provider allows outbound SMTP on port 465');
                $this->warn('   - Verify email password is correct');
                $this->warn('   - Some servers may need port 587 with TLS instead of 465 with SSL');
            }
            
            return 1;
        }

        return 0;
    }
}