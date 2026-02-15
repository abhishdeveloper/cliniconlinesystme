// Custom Scripts
console.log('Clinic System Loaded');

document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');

                const currentLabel = this.getAttribute('aria-label') || '';
                if (currentLabel.includes('Show')) {
                    this.setAttribute('aria-label', currentLabel.replace('Show', 'Hide'));
                }
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');

                const currentLabel = this.getAttribute('aria-label') || '';
                if (currentLabel.includes('Hide')) {
                    this.setAttribute('aria-label', currentLabel.replace('Hide', 'Show'));
                }
            }
        });
    });
});
