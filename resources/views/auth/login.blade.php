@extends('layouts.auth')

@section('title', 'Login')

@section('page-specific-style')
<style>
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <!-- Left Panel - Features -->
        <div class="features-panel">
            <div>
                <div class="auth-logo">
                    <div class="auth-logo-icon">
                        <i class="fas fa-vest"></i>
                    </div>
                    <div class="auth-logo-text">
                        <h1>Fashion Tailor Pro</h1>
                        <p>Complete Garment Business Suite</p>
                    </div>
                </div>

                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> Multi-Outlet Management</li>
                    <li><i class="fas fa-check-circle"></i> Custom Garment Workflow</li>
                    <li><i class="fas fa-check-circle"></i> Inventory Tracking</li>
                    <li><i class="fas fa-check-circle"></i> Worker & Production Management</li>
                    <li><i class="fas fa-check-circle"></i> Integrated Billing System</li>
                    <li><i class="fas fa-check-circle"></i> Real-time Analytics</li>
                </ul>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="form-panel">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <form id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                <!-- Email -->
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            value="{{ old('email') }}">
                    </div>
                    @error('email')
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            value="{{ old('password') }}">
                    </div>
                    @error('password')
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- Outlet Selection -->
                <!-- <div class="form-group">
                    <label class="form-label">Select Outlet</label>
                    <div class="radio-card-group">
                        <div class="radio-card" data-value="main">
                            <div class="radio-card-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="radio-card-title">Main Outlet</div>
                            <div class="radio-card-desc">City Center</div>
                            <input type="radio" name="outlet" value="main" class="visually-hidden" checked>
                        </div>

                        <div class="radio-card" data-value="mall">
                            <div class="radio-card-icon">
                                <i class="fas fa-shopping-mall"></i>
                            </div>
                            <div class="radio-card-title">Outlet B</div>
                            <div class="radio-card-desc">Mega Mall</div>
                            <input type="radio" name="outlet" value="mall" class="visually-hidden">
                        </div>

                        <div class="radio-card" data-value="uptown">
                            <div class="radio-card-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="radio-card-title">Outlet C</div>
                            <div class="radio-card-desc">Uptown Plaza</div>
                            <input type="radio" name="outlet" value="uptown" class="visually-hidden">
                        </div>
                    </div>
                </div> -->

                <!-- Remember Me -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>

                <!-- Links -->
                <div class="auth-links">
                    <a href="{{ route('password.request') }}">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection