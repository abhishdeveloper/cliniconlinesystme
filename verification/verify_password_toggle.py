from playwright.sync_api import sync_playwright, expect

def verify_password_toggle():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # 1. Login Page Verification
        print("Verifying Login Page...")
        page.goto("http://localhost:8000/login.php")

        # Locate password input and toggle button
        password_input = page.locator("#password")
        toggle_button = page.locator(".toggle-password")

        # Check initial state
        expect(password_input).to_have_attribute("type", "password")
        expect(toggle_button).to_have_attribute("aria-label", "Show password")

        # Type password
        password_input.fill("secret123")

        # Click toggle
        toggle_button.click()

        # Check toggled state
        expect(password_input).to_have_attribute("type", "text")
        expect(toggle_button).to_have_attribute("aria-label", "Hide password")

        page.screenshot(path="verification/login_toggled.png")
        print("Login Page Verified!")

        # 2. Register Page Verification
        print("Verifying Register Page...")
        page.goto("http://localhost:8000/register.php")

        # Password field
        pwd_input = page.locator("#password")
        pwd_toggle = page.locator(".input-group").filter(has=pwd_input).locator(".toggle-password")

        pwd_input.fill("secret123")
        pwd_toggle.click()
        expect(pwd_input).to_have_attribute("type", "text")

        # Confirm Password field
        conf_input = page.locator("#confirm_password")
        conf_toggle = page.locator(".input-group").filter(has=conf_input).locator(".toggle-password")

        conf_input.fill("secret123")
        conf_toggle.click()
        expect(conf_input).to_have_attribute("type", "text")

        page.screenshot(path="verification/register_toggled.png")
        print("Register Page Verified!")

        browser.close()

if __name__ == "__main__":
    verify_password_toggle()
