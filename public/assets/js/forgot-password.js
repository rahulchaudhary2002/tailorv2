/**
 * Fashion Tailor Pro - Forgot Password Page JavaScript
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("Forgot password page loaded");

    // State variables
    let timerInterval;
    let timeLeft = 900; // 15 minutes in seconds
    let userEmail = "";

    // Get DOM elements
    const step1 = document.getElementById("step1");
    const step2 = document.getElementById("step2");
    const step3 = document.getElementById("step3");
    const successMessage = document.getElementById("successMessage");

    // Initialize OTP inputs
    initializeOTPInputs();

    // Form submissions
    const emailForm = document.getElementById("emailForm");
    const otpForm = document.getElementById("otpForm");
    const passwordForm = document.getElementById("passwordForm");

    if (emailForm) {
        emailForm.addEventListener("submit", handleEmailSubmit);
    }

    if (otpForm) {
        otpForm.addEventListener("submit", handleOTPSubmit);
    }

    if (passwordForm) {
        passwordForm.addEventListener("submit", handlePasswordSubmit);
    }

    // Resend OTP
    const resendLink = document.getElementById("resendCode");
    if (resendLink) {
        resendLink.addEventListener("click", handleResendOTP);
    }

    // Change email
    const changeEmailLink = document.getElementById("changeEmail");
    if (changeEmailLink) {
        changeEmailLink.addEventListener("click", handleChangeEmail);
    }

    // Password strength initialization
    const passwordInput = document.getElementById("newPassword");
    if (passwordInput) {
        passwordInput.addEventListener("input", handlePasswordInput);
    }

    // Functions
    function initializeOTPInputs() {
        const otpInputs = document.querySelectorAll(".otp-input");

        otpInputs.forEach((input, index) => {
            input.addEventListener("input", function () {
                // Move to next input if current is filled
                if (this.value.length === 1 && index < 5) {
                    otpInputs[index + 1].focus();
                }

                // Auto-submit if all fields filled
                const allFilled = Array.from(otpInputs).every(
                    (inp) => inp.value.length === 1,
                );
                if (allFilled && index === 5) {
                    otpForm.dispatchEvent(new Event("submit"));
                }
            });

            // Handle backspace
            input.addEventListener("keydown", function (e) {
                if (
                    e.key === "Backspace" &&
                    this.value.length === 0 &&
                    index > 0
                ) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    }

    function handleEmailSubmit(e) {
        e.preventDefault();

        const email = document.getElementById("email").value.trim();
        const emailError = document.getElementById("emailError");

        // Reset error
        AuthValidation.hideError("emailError");
        AuthValidation.markInputError("email", false);

        // Validate email
        if (!email || !AuthValidation.isValidEmail(email)) {
            AuthValidation.showError(
                "emailError",
                "Please enter a valid email address",
            );
            AuthValidation.markInputError("email", true);
            return;
        }

        // Store email
        userEmail = email;
        document.getElementById("userEmail").textContent = email;

        // Show loading state
        const submitButton = emailForm.querySelector(".btn-primary");
        const originalButtonHTML = submitButton.innerHTML;
        UI.showLoading(submitButton, "Sending...");

        fetch("/forgot-password", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ email }),
        })
            .then((response) => response.json())
            .then(() => {
                UI.hideLoading(submitButton, originalButtonHTML);
                // Move to step 2
                step1.style.display = "none";
                step2.style.display = "block";
                UI.updateStepIndicators(2);

                // Start timer
                startTimer();

                // Focus first OTP input
                document.querySelector('.otp-input[data-index="1"]').focus();
            })
            .catch((error) => {
                UI.hideLoading(submitButton, originalButtonHTML);
                AuthValidation.showError(
                    "emailError",
                    "An error occurred. Please try again.",
                );
                AuthValidation.markInputError("email", true);
                console.error("AJAX error:", error);
            });
    }

    function handleOTPSubmit(e) {
        e.preventDefault();

        const email = document.getElementById("email").value.trim();
        // Collect OTP
        let enteredOTP = "";
        document.querySelectorAll(".otp-input").forEach((input) => {
            enteredOTP += input.value;
        });

        // Validate OTP length
        if (enteredOTP.length !== 6) {
            AuthValidation.showError("otpError", "Please enter all 6 digits");
            return;
        }

        fetch("/verify-code", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ email, code: enteredOTP }),
        })
            .then((response) => response.json())
            .then(() => {
                // Clear timer
                if (timerInterval) clearInterval(timerInterval);

                // Move to step 3
                step2.style.display = "none";
                step3.style.display = "block";
                UI.updateStepIndicators(3);

                // Auto-focus new password field
                document.getElementById("newPassword").focus();
            })
            .catch((error) => {
                AuthValidation.showError(
                    "otpError",
                    "Invalid verification code",
                );
            });
    }

    function handlePasswordSubmit(e) {
        e.preventDefault();

        const email = document.getElementById("email").value.trim();
        const newPassword = document.getElementById("newPassword").value;
        const confirmPassword =
            document.getElementById("confirmPassword").value;
        const confirmError = document.getElementById("confirmError");

        // Reset error
        AuthValidation.hideError("confirmError");

        // Check password strength
        const strength = AuthValidation.checkPasswordStrength(newPassword);
        if (strength < 80) {
            alert(
                "Please use a stronger password. All requirements must be met.",
            );
            return;
        }

        // Check password match
        if (newPassword !== confirmPassword) {
            AuthValidation.showError("confirmError", "Passwords do not match");
            return;
        }

        // Show loading state
        const submitButton = passwordForm.querySelector(".btn-primary");
        UI.showLoading(submitButton, "Resetting...");

        fetch("/reset-password", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                email,
                password: newPassword,
                password_confirmation: confirmPassword,
            }),
        })
            .then((response) => response.json())
            .then(() => {
                // Show success message
                step3.style.display = "none";
                successMessage.style.display = "flex";
            })
            .catch((error) => {
                AuthValidation.showError(
                    "otpError",
                    "An error occurred. Please try again.",
                );
            });
    }

    function handleResendOTP(e) {
        e.preventDefault();

        fetch("/forgot-password", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ email }),
        })
            .then((response) => response.json())
            .then(() => {
                // Reset OTP fields
                document.querySelectorAll(".otp-input").forEach((input) => {
                    input.value = "";
                });

                // Clear errors
                AuthValidation.hideError("otpError");

                // Restart timer
                startTimer();

                // Focus first OTP input
                document.querySelector('.otp-input[data-index="1"]').focus();
            })
            .catch((error) => {
                UI.hideLoading(submitButton, originalButtonHTML);
                AuthValidation.showError(
                    "emailError",
                    "An error occurred. Please try again.",
                );
                AuthValidation.markInputError("email", true);
                console.error("AJAX error:", error);
            });
    }

    function handleChangeEmail(e) {
        e.preventDefault();

        // Clear timer
        if (timerInterval) clearInterval(timerInterval);

        // Go back to step 1
        step2.style.display = "none";
        step1.style.display = "block";
        UI.updateStepIndicators(1);

        // Clear OTP fields
        document.querySelectorAll(".otp-input").forEach((input) => {
            input.value = "";
        });

        // Focus email field
        document.getElementById("email").focus();
    }

    function handlePasswordInput() {
        const strength = AuthValidation.checkPasswordStrength(this.value);
        const strengthLabel = AuthValidation.getPasswordStrengthLabel(strength);

        // Update strength bar
        const strengthBar = document.getElementById("strengthBar");
        const strengthText = document.getElementById("strengthText");

        if (strengthBar) {
            strengthBar.style.width = `${strength}%`;
            strengthBar.style.backgroundColor = `var(--${strengthLabel.color})`;
        }

        if (strengthText) {
            strengthText.textContent = `Password strength: ${strengthLabel.label}`;
            strengthText.className = `strength-text ${strengthLabel.class}`;
        }

        // Update requirement indicators
        updatePasswordRequirements(this.value);
    }

    function updatePasswordRequirements(password) {
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password),
        };

        document.getElementById("reqLength").style.color = requirements.length
            ? "var(--success)"
            : "var(--danger)";
        document.getElementById("reqUppercase").style.color =
            requirements.uppercase ? "var(--success)" : "var(--danger)";
        document.getElementById("reqLowercase").style.color =
            requirements.lowercase ? "var(--success)" : "var(--danger)";
        document.getElementById("reqNumber").style.color = requirements.number
            ? "var(--success)"
            : "var(--danger)";
        document.getElementById("reqSpecial").style.color = requirements.special
            ? "var(--success)"
            : "var(--danger)";
    }

    function startTimer() {
        timeLeft = 900; // Reset to 15 minutes
        updateTimerDisplay();

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById("timer").innerHTML = "Code expired";
                document.getElementById("timer").style.color = "var(--danger)";

                // Disable OTP inputs
                document.querySelectorAll(".otp-input").forEach((input) => {
                    input.disabled = true;
                });
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.getElementById("timer").innerHTML =
            `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
    }
});
