from playwright.sync_api import sync_playwright, expect

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        try:
            # Login
            print("Navigating to Login...")
            page.goto("http://localhost:8000/login.php")
            page.fill("input[name='email']", "admin@example.com")
            page.fill("input[name='password']", "password123")
            page.click("button[type='submit']")

            # Verify dashboard
            expect(page).to_have_url("http://localhost:8000/admin/dashboard.php")
            print("Logged in successfully.")

            # Go to Medicines
            print("Navigating to Medicines...")
            page.goto("http://localhost:8000/admin/medicines.php")

            # Check Page 1
            expect(page.get_by_text("Medicine 01")).to_be_visible()
            expect(page.get_by_text("Medicine 10")).to_be_visible()
            expect(page.get_by_text("Medicine 11")).not_to_be_visible()
            print("Page 1 content verified.")

            # Screenshot Page 1
            page.screenshot(path="verification_page1.png")
            print("Page 1 screenshot saved.")

            # Click Next or Page 2
            print("Navigating to Page 2...")
            # Find page 2 link. Bootstrap pagination uses <ul><li><a class="page-link">2</a></li></ul>
            page.get_by_role("link", name="2", exact=True).click()

            # Check Page 2
            expect(page.get_by_text("Medicine 11")).to_be_visible()
            expect(page.get_by_text("Medicine 20")).to_be_visible()
            print("Page 2 content verified.")

            # Screenshot Page 2
            page.screenshot(path="verification_page2.png")
            print("Page 2 screenshot saved.")
        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="error.png")
        finally:
            browser.close()

if __name__ == "__main__":
    run()
