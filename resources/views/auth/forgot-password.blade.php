@extends('layouts.auth')

@section('title', 'Forgot Password')

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
                    <li><i class="fas fa-shield-alt"></i> Secure Account Recovery</li>
                    <li><i class="fas fa-envelope"></i> Email Verification</li>
                    <li><i class="fas fa-mobile-alt"></i> OTP Authentication</li>
                    <li><i class="fas fa-lock"></i> Password Strength Check</li>
                    <li><i class="fas fa-clock"></i> Real-time Timer</li>
                    <li><i class="fas fa-check-circle"></i> Instant Reset</li>
                </ul>
            </div>

            <div class="testimonial">
                <p>"The password recovery system is seamless and secure. Got my account back in minutes without any hassle."</p>
                <div class="testimonial-author">
                    <div class="testimonial-author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Priya Tailors</div>
                        <div style="font-size: 12px; opacity: 0.9;">Outlet Manager</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Forgot Password Form -->
        <div class="form-panel">
            <a href="{{ route('login') }}" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>

            <div class="auth-logo" style="justify-content: center; margin-bottom: 20px;">
                <div class="auth-logo-icon" style="width: 50px; height: 50px; font-size: 24px;">
                    <i class="fas fa-key"></i>
                </div>
                <div class="auth-logo-text">
                    <h1 style="font-size: 24px;">Password Recovery</h1>
                </div>
            </div>

            <!-- Step Indicators -->
            <div class="step-indicators">
                <div class="step active" id="stepIndicator1">
                    <div class="step-number">1</div>
                    <div class="step-label">Enter Email</div>
                    <div class="step-line"></div>
                </div>
                <div class="step" id="stepIndicator2">
                    <div class="step-number">2</div>
                    <div class="step-label">Verify OTP</div>
                    <div class="step-line"></div>
                </div>
                <div class="step" id="stepIndicator3">
                    <div class="step-number">3</div>
                    <div class="step-label">New Password</div>
                </div>
            </div>

            <!-- Step 1: Email Input -->
            <div id="step1" class="form-step active">
                <div class="auth-header">
                    <h2>Reset Your Password</h2>
                    <p>Enter your registered email address to receive a verification code.</p>
                </div>

                <form id="emailForm">
                    <div class="form-group">
                        <label class="form-label required">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email"
                                class="form-control"
                                id="email"
                                placeholder="Enter your registered email">
                        </div>
                        <div id="emailError" class="error-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i> Please enter a valid email address
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i>
                        Send Verification Code
                    </button>
                </form>

                <div class="auth-links">
                    <p>Remember your password? <a href="{{ route('login') }}">Back to Login</a></p>
                </div>
            </div>

            <!-- Step 2: OTP Verification -->
            <div id="step2" class="form-step">
                <div class="auth-header">
                    <h2>Verify Your Identity</h2>
                    <p>Enter the 6-digit code sent to <span id="userEmail" style="font-weight: 600; color: var(--primary);"></span></p>
                </div>

                <form id="otpForm">
                    <div class="otp-inputs">
                        <input type="text" class="otp-input" maxlength="1" data-index="1" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="2" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="3" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="4" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="5" autocomplete="off">
                        <input type="text" class="otp-input" maxlength="1" data-index="6" autocomplete="off">
                    </div>

                    <div id="otpError" class="error-message" style="display: none; justify-content: center;">
                        <i class="fas fa-exclamation-circle"></i> Invalid verification code
                    </div>

                    <div class="timer" id="timer">02:00</div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check-circle"></i>
                        Verify Code
                    </button>

                    <div class="auth-links" style="margin-top: 20px;">
                        <p>Didn't receive the code? <a href="#" id="resendCode">Resend Code</a></p>
                        <p>Want to use a different email? <a href="#" id="changeEmail">Change Email</a></p>
                    </div>
                </form>
            </div>

            <!-- Step 3: New Password -->
            <div id="step3" class="form-step">
                <div class="auth-header">
                    <h2>Create New Password</h2>
                    <p>Your new password must be different from previously used passwords.</p>
                </div>

                <form id="passwordForm">
                    <div class="form-group">
                        <label class="form-label required">New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password"
                                class="form-control"
                                id="newPassword"
                                placeholder="Enter new password"
                                required>
                        </div>
                        <div class="password-strength">
                            <div class="strength-text" id="strengthText">Password strength: None</div>
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Confirm New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password"
                                class="form-control"
                                id="confirmPassword"
                                placeholder="Confirm new password"
                                required>
                        </div>
                        <div id="confirmError" class="error-message" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i> Passwords do not match
                        </div>
                    </div>

                    <div class="password-requirements">
                        <p style="font-weight: 600; margin-bottom: 10px; color: var(--dark);">Password Requirements:</p>
                        <ul>
                            <li id="reqLength">At least 8 characters</li>
                            <li id="reqUppercase">One uppercase letter</li>
                            <li id="reqLowercase">One lowercase letter</li>
                            <li id="reqNumber">One number</li>
                            <li id="reqSpecial">One special character</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-save"></i>
                        Reset Password
                    </button>
                </form>
            </div>

            <!-- Success Message -->
            <div id="successMessage" class="success-message">
                <i class="fas fa-check-circle" style="font-size: 32px;"></i>
                <div class="success-message-content">
                    <h3 style="margin-bottom: 5px; color: var(--success);">Password Reset Successful!</h3>
                    <p>Your password has been reset successfully. You can now login with your new password.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary" style="margin-top: 15px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-specific-script')
<script src="{{ asset('assets/js/forgot-password.js') }}"></script>
@endsection