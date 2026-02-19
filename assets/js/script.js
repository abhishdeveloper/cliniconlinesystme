// Custom Scripts
console.log('Clinic System Loaded');

// Password Visibility Toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the input field within the same input-group
            const input = this.parentElement.querySelector('input');

            if (input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                // Toggle the icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }

                // Update Aria Label
                const label = type === 'password' ? 'Show password' : 'Hide password';
                this.setAttribute('aria-label', label);
            }
        });
    });
});
