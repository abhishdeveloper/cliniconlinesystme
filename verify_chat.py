from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch()
    context = browser.new_context()
    page = context.new_page()

    try:
        # Login as Patient
        print("Logging in as Patient...")
        page.goto("http://localhost:8000/login.php")
        page.fill("input[name='email']", "patient@clinic.com")
        page.fill("input[name='password']", "patient123")
        page.click("button[type='submit']")

        # Wait for dashboard
        page.wait_for_url("**/patient/dashboard.php")
        print("Logged in.")

        # Navigate to Chat via "Messages" link
        print("Clicking Messages link...")
        with page.expect_navigation():
            # Use specific link in navbar
            page.click("nav .navbar-nav .nav-link[href='/chat.php']")

        print("Navigated to Chat (Empty state).")

        # Open New Chat Modal
        print("Opening New Chat Modal...")
        page.click("button[data-bs-target='#newChatModal']")
        page.wait_for_selector("#newChatModal.show")

        # Click on Dr. Smith
        print("Selecting Dr. Smith...")
        # Assuming Dr. Smith is in the list. The list is fetched from DB.
        # User role='patient', so list = all doctors.
        # Dr. Smith exists in seed data.
        page.click("#newChatModal .list-group-item:has-text('Dr. Smith')")

        # Wait for navigation to chat with user_id=...
        page.wait_for_url("**chat.php?user_id=*")
        print("Chat opened with Dr. Smith.")

        # Send Message
        print("Sending message...")
        page.fill("#message-input", "Hello Doctor via New Chat!")
        page.click("button[type='submit']")

        # Wait for message
        page.wait_for_selector(".bg-primary", timeout=5000)
        print("Message sent.")

        # Take Screenshot
        page.screenshot(path="verification_chat.png", full_page=True)
        print("Screenshot saved.")

    except Exception as e:
        print(f"Error: {e}")
        page.screenshot(path="verification_error.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)
