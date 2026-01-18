<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function submit()
    {
        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'Invalid email or password');
            return;
        }

        session()->regenerate();
        return redirect('/dashboard');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
?>

<form wire:submit.prevent="submit">
    <h2>Login</h2>

    <input type="email" wire:model.defer="email" placeholder="Email">
    @error('email') <p>{{ $message }}</p> @enderror

    <input type="password" wire:model.defer="password" placeholder="Password">
    @error('password') <p>{{ $message }}</p> @enderror

    <button type="submit">Login</button>
</form>
