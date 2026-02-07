<?php

require 'bootstrap/app.php';

$app = require 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$user = \App\Models\User::create([
    'name' => 'admin',
    'email' => 'admin@gmail.com',
    'password' => bcrypt('12345678'),
    'email_verified_at' => now()
]);

echo "Admin created successfully!\n";
echo "Email: " . $user->email . "\n";
echo "Password: 12345678\n";
