<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user with all permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Creating Admin User');
        $this->newLine();

        $firstName = $this->ask('First Name');
        $lastName = $this->ask('Last Name');
        $username = $this->ask('Username');
        $email = $this->ask('Email');
        $password = $this->secret('Password (min 8 characters)');
        $passwordConfirm = $this->secret('Confirm Password');

        // Validate inputs
        if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
            $this->error('❌ All fields are required!');
            return 1;
        }

        if ($password !== $passwordConfirm) {
            $this->error('❌ Passwords do not match!');
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('❌ Password must be at least 8 characters!');
            return 1;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email address!');
            return 1;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('❌ User with this email already exists!');
            return 1;
        }

        if (User::where('username', $username)->exists()) {
            $this->error('❌ User with this username already exists!');
            return 1;
        }

        try {
            // Create admin role if it doesn't exist
            $adminRole = Role::firstOrCreate(['name' => 'Admin']);

            // Create user
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'name' => $firstName . ' ' . $lastName,
                'display_name' => 'Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // Assign admin role
            $user->assignRole('Admin');

            $this->newLine();
            $this->info('✅ Admin user created successfully!');
            $this->newLine();
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $user->name],
                    ['Username', $user->username],
                    ['Email', $user->email],
                    ['Role', 'Admin'],
                    ['Status', 'Active'],
                ]
            );

            $this->newLine();
            $this->info('🔗 You can now login at: /admin/login');
            $this->newLine();

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error creating admin user: ' . $e->getMessage());
            return 1;
        }
    }
}
