import os
import re

files = [
    'admin/dashboard.php',
    'admin/medicines.php',
    'doctor/create_prescription.php',
    'doctor/dashboard.php',
    'doctor/schedule.php',
    'login.php',
    'patient/dashboard.php',
    'register.php'
]

for filepath in files:
    if not os.path.exists(filepath):
        print(f"Skipping {filepath} (not found)")
        continue

    with open(filepath, 'r') as f:
        content = f.read()

    # Pattern 1: $error = "...: " . $e->getMessage();
    # Replacement: error_log("Error: " . $e->getMessage()); $error = "An unexpected error occurred.";
    def replace_error_assign(match):
        orig_msg = match.group(1) # e.g. "Database error: "
        return f'error_log("{orig_msg}" . $e->getMessage()); $error = "An unexpected error occurred.";'

    content = re.sub(r'\$error = "(.*?: )" \. \$e->getMessage\(\);', replace_error_assign, content)

    # Pattern 2: setFlashMessage('danger', "...: " . $e->getMessage(), 'danger');
    # Replacement: error_log("Error: " . $e->getMessage()); setFlashMessage('danger', "An unexpected error occurred.", 'danger');
    def replace_flash_msg(match):
        prefix = match.group(1) # e.g. "Error cancelling appointment: "
        return f'error_log("{prefix}" . $e->getMessage()); setFlashMessage(\'danger\', "An unexpected error occurred.", \'danger\');'

    content = re.sub(r"setFlashMessage\('danger', \"(.*?)\" \. \$e->getMessage\(\), 'danger'\);", replace_flash_msg, content)

    # Pattern 3: echo "<tr>...: " . $e->getMessage() . "</td></tr>";
    # This is harder because of HTML.
    # doctor/dashboard.php: echo "<tr><td colspan='5' class='text-danger'>Error loading appointments: " . $e->getMessage() . "</td></tr>";
    content = re.sub(r'echo "<tr><td colspan=\'\d+\' class=\'text-danger\'>(.*?: )" \. \$e->getMessage\(\) \. "</td></tr>";',
                     lambda m: f'error_log("{m.group(1)}" . $e->getMessage()); echo "<tr><td colspan=\'5\' class=\'text-danger\'>An unexpected error occurred.</td></tr>";', content)

    # patient/dashboard.php: echo "<tr><td colspan='4' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
    content = re.sub(r'echo "<tr><td colspan=\'4\' class=\'text-danger\'>Error: " \. \$e->getMessage\(\) \. "</td></tr>";',
                     lambda m: f'error_log("Error: " . $e->getMessage()); echo "<tr><td colspan=\'4\' class=\'text-danger\'>An unexpected error occurred.</td></tr>";', content)


    with open(filepath, 'w') as f:
        f.write(content)
    print(f"Processed {filepath}")
