from playwright.sync_api import sync_playwright

def verify_password_toggle():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Login Page
        page.goto("http://localhost:3000/login.php")
        page.fill("#password", "secret123")
        page.screenshot(path="verification/login_masked.png")

        # Toggle password
        page.click(".toggle-password")
        page.wait_for_timeout(500) # Wait for potential transition
        page.screenshot(path="verification/login_visible.png")

        # Register Page
        page.goto("http://localhost:3000/register.php")
        page.fill("#password", "secret123")
        page.fill("#confirm_password", "secret123")
        page.screenshot(path="verification/register_masked.png")

        # Toggle both passwords
        buttons = page.query_selector_all(".toggle-password")
        for button in buttons:
            button.click()

        page.wait_for_timeout(500)
        page.screenshot(path="verification/register_visible.png")

        browser.close()

if __name__ == "__main__":
    verify_password_toggle()
