// Custom Scripts
console.log('Clinic System Loaded');

document.addEventListener('DOMContentLoaded', function() {
    // Password Visibility Toggle
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the input field within the same input-group
            const inputGroup = this.closest('.input-group');
            if (inputGroup) {
                const input = inputGroup.querySelector('input');
                const icon = this.querySelector('i');

                if (input && icon) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        // Update aria-label for accessibility
                        // We check the current label to see if we need to replace specific text
                        const currentLabel = this.getAttribute('aria-label');
                        if (currentLabel && currentLabel.includes('Show')) {
                            this.setAttribute('aria-label', currentLabel.replace('Show', 'Hide'));
                        } else {
                            this.setAttribute('aria-label', 'Hide password');
                        }
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');

                        const currentLabel = this.getAttribute('aria-label');
                        if (currentLabel && currentLabel.includes('Hide')) {
                            this.setAttribute('aria-label', currentLabel.replace('Hide', 'Show'));
                        } else {
                            this.setAttribute('aria-label', 'Show password');
                        }
                    }
                }
            }
        });
    });
});
