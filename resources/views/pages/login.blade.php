@extends('layouts.app')
@section('title', 'Login Page')
@section('content')
    <h1>Login to Cinema 🎉</h1>
    <form method="POST" action="/login">
        @csrf
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
@endsection
