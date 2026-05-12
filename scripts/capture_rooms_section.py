from playwright.sync_api import sync_playwright

def capture_rooms_section(url, output_full, output_section, viewport_width=1920, viewport_height=1080):
    with sync_playwright() as p:
        browser = p.chromium.launch(args=['--ignore-certificate-errors'])
        context = browser.new_context(
            viewport={'width': viewport_width, 'height': viewport_height},
            ignore_https_errors=True
        )
        page = context.new_page()
        page.goto(url, wait_until='networkidle')

        # Full page screenshot first
        page.screenshot(path=output_full, full_page=True)

        # Try to find and scroll to the rooms section
        section = page.locator('text=Pokoje a apartmány').first
        if section.count() > 0:
            section.scroll_into_view_if_needed()
            page.wait_for_timeout(1000)
        else:
            # Fallback: scroll down to find room cards
            page.evaluate("window.scrollBy(0, window.innerHeight * 1.5)")
            page.wait_for_timeout(800)

        # Screenshot of the visible viewport after scrolling
        page.screenshot(path=output_section, full_page=False)
        browser.close()
        print(f"Full page screenshot: {output_full}")
        print(f"Rooms section screenshot: {output_section}")
        print(f"Page title was: {page.title()}")

if __name__ == '__main__':
    capture_rooms_section(
        'https://localhost:8443/ubytovani',
        '/home/honza/dev/tesak-cernava/screenshots/ubytovani_full.png',
        '/home/honza/dev/tesak-cernava/screenshots/ubytovani_rooms_section.png',
    )
