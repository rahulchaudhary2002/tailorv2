/**
 * Fashion Tailor Pro - Common Authentication Functions
 * Shared utilities for all auth pages
 */

// DOM Ready Function
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fashion Tailor Pro - Auth Module Loaded');
});

// Validation Utilities
const AuthValidation = {
    // Email validation
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Phone validation
    isValidPhone: function(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/\D/g, ''));
    },

    // Password strength checker
    checkPasswordStrength: function(password) {
        let strength = 0;
        
        // Length checks
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 10;
        
        // Character type checks
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[a-z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 15;
        
        return Math.min(strength, 100);
    },

    // Get password strength label
    getPasswordStrengthLabel: function(strength) {
        if (strength <= 40) return { label: 'Weak', color: 'danger', class: 'weak' };
        if (strength <= 60) return { label: 'Fair', color: 'warning', class: 'fair' };
        if (strength <= 80) return { label: 'Good', color: 'warning', class: 'good' };
        return { label: 'Strong', color: 'success', class: 'strong' };
    },

    // Show error message
    showError: function(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            element.style.display = 'flex';
        }
    },

    // Hide error message
    hideError: function(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = 'none';
        }
    },

    // Add error class to input
    markInputError: function(inputId, hasError) {
        const input = document.getElementById(inputId);
        if (input) {
            if (hasError) {
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        }
    }
};

// LocalStorage Utilities
const Storage = {
    // Save user session
    saveUserSession: function(userData) {
        try {
            localStorage.setItem('tailorpro_user', JSON.stringify(userData));
            localStorage.setItem('tailorpro_session', new Date().toISOString());
            return true;
        } catch (error) {
            console.error('Error saving session:', error);
            return false;
        }
    },

    // Get user session
    getUserSession: function() {
        try {
            const userData = localStorage.getItem('tailorpro_user');
            return userData ? JSON.parse(userData) : null;
        } catch (error) {
            console.error('Error getting session:', error);
            return null;
        }
    },

    // Clear user session
    clearUserSession: function() {
        try {
            localStorage.removeItem('tailorpro_user');
            localStorage.removeItem('tailorpro_session');
            return true;
        } catch (error) {
            console.error('Error clearing session:', error);
            return false;
        }
    },

    // Save registration data
    saveRegistration: function(formData) {
        try {
            localStorage.setItem('demo_registration', JSON.stringify(formData));
            return true;
        } catch (error) {
            console.error('Error saving registration:', error);
            return false;
        }
    },

    // Get registration data
    getRegistration: function() {
        try {
            const data = localStorage.getItem('demo_registration');
            return data ? JSON.parse(data) : null;
        } catch (error) {
            console.error('Error getting registration:', error);
            return null;
        }
    }
};

// UI Utilities
const UI = {
    // Show loading state
    showLoading: function(button, text = 'Processing...') {
        if (button) {
            button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
            button.disabled = true;
        }
    },

    // Hide loading state
    hideLoading: function(button, originalHTML) {
        if (button) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    },

    // Show success message
    showSuccess: function(messageId, show = true) {
        const element = document.getElementById(messageId);
        if (element) {
            element.style.display = show ? 'flex' : 'none';
        }
    },

    // Toggle form steps
    toggleFormStep: function(stepId, show = true) {
        const element = document.getElementById(stepId);
        if (element) {
            element.style.display = show ? 'block' : 'none';
            if (show) {
                element.classList.add('active');
            } else {
                element.classList.remove('active');
            }
        }
    },

    // Update step indicators
    updateStepIndicators: function(currentStep, totalSteps = 3) {
        for (let i = 1; i <= totalSteps; i++) {
            const indicator = document.getElementById(`stepIndicator${i}`);
            if (indicator) {
                indicator.classList.remove('active', 'completed');
                
                if (i === currentStep) {
                    indicator.classList.add('active');
                } else if (i < currentStep) {
                    indicator.classList.add('completed');
                }
            }
        }
    },

    // Initialize radio cards
    initRadioCards: function(containerClass, callback) {
        const cards = document.querySelectorAll(`.${containerClass} .radio-card`);
        cards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards in container
                const container = this.closest(`.${containerClass}`);
                container.querySelectorAll('.radio-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Execute callback if provided
                if (callback && typeof callback === 'function') {
                    const value = this.getAttribute('data-value');
                    callback(value);
                }
            });
        });
    }
};

// Demo Data (for testing)
const DemoData = {
    // Fill demo login data
    fillLoginData: function() {
        document.getElementById('username').value = 'admin@tailorpro.com';
        document.getElementById('password').value = 'admin123';
        document.getElementById('role').value = 'admin';
        
        // Select first outlet
        const outletRadio = document.querySelector('input[name="outlet"][value="main"]');
        if (outletRadio) outletRadio.checked = true;
        
        console.log('Demo login data filled');
    },

    // Fill demo registration data
    fillRegistrationData: function() {
        // Step 1
        document.getElementById('firstName').value = 'John';
        document.getElementById('lastName').value = 'Doe';
        document.getElementById('email').value = 'john.doe@tailorpro.com';
        document.getElementById('phone').value = '9876543210';
        document.getElementById('address').value = '123 Fashion Street';
        document.getElementById('city').value = 'Mumbai';
        document.getElementById('state').value = 'Maharashtra';
        document.getElementById('zipCode').value = '400001';
        
        // Step 2
        document.getElementById('username').value = 'johndoe';
        document.getElementById('password').value = 'Demo@1234';
        document.getElementById('confirmPassword').value = 'Demo@1234';
        document.getElementById('securityQuestion').value = 'first_pet';
        document.getElementById('securityAnswer').value = 'Max';
        
        // Trigger password strength update
        const passwordEvent = new Event('input');
        document.getElementById('password').dispatchEvent(passwordEvent);
        
        console.log('Demo registration data filled');
    },

    // Enable demo mode
    enableDemoMode: function() {
        // Double-click on body to fill demo data
        document.body.addEventListener('dblclick', function(e) {
            if (e.target.tagName === 'BODY') {
                if (confirm('Fill demo data for testing?')) {
                    if (window.location.pathname.includes('login')) {
                        DemoData.fillLoginData();
                    } else if (window.location.pathname.includes('register')) {
                        DemoData.fillRegistrationData();
                    }
                }
            }
        });
        
        console.log('Demo mode enabled');
    }
};

// Export utilities for use in other files
window.AuthValidation = AuthValidation;
window.Storage = Storage;
window.UI = UI;
window.DemoData = DemoData;