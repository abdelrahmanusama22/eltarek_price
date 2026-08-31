<?php

namespace App\Livewire\Sales;

use App\Models\Branch;
use App\Models\SalesUser;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';
    public $branch_id = '';
    public $password = '';
    public $password_confirmation = '';
    
    public $registered = false;

    public function register()
    {
        $throttleKey = 'sales-register|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:sales_users',
            'phone' => 'required|string|max:20',
            'branch_id' => 'required|exists:branches,id',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $salesUser = SalesUser::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch_id' => $this->branch_id,
            'username' => explode('@', $this->email)[0] . rand(1000, 9999), // Generate a fallback username
            'password' => Hash::make($this->password),
            'is_approved' => false,
        ]);

        // Send Filament Notification to Admins
        $admins = User::all(); // Assuming all Users in User model are admins, or filter if necessary
        foreach ($admins as $admin) {
            Notification::make()
                ->title('New Sales Rep Registered')
                ->body("{$salesUser->name} has registered and is pending approval.")
                ->success()
                ->sendToDatabase($admin);
        }

        RateLimiter::clear($throttleKey);

        $this->registered = true;
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.sales.register', [
            'branches' => Branch::all()
        ]);
    }
}
