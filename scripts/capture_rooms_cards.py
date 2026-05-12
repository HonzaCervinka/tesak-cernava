from playwright.sync_api import sync_playwright

def capture_rooms_cards(url, output_path, viewport_width=1920, viewport_height=1080):
    with sync_playwright() as p:
        browser = p.chromium.launch(args=['--ignore-certificate-errors'])
        context = browser.new_context(
            viewport={'width': viewport_width, 'height': viewport_height},
            ignore_https_errors=True
        )
        page = context.new_page()
        page.goto(url, wait_until='networkidle')

        # Try multiple selectors to locate the rooms section heading
        selectors = [
            'h2:has-text("Pokoje")',
            'h2:has-text("apartmány")',
            '*:has-text("Ubytování pro každého")',
            '*:has-text("Pokoje a apartmány")',
            '.room-card',
            '.accommodation',
            'section:has(img)',
        ]

        scrolled = False
        for sel in selectors:
            try:
                el = page.locator(sel).first
                if el.count() > 0:
                    el.scroll_into_view_if_needed()
                    page.wait_for_timeout(800)
                    print(f"Scrolled to: {sel}")
                    scrolled = True
                    break
            except Exception as e:
                print(f"Selector {sel} failed: {e}")
                continue

        if not scrolled:
            # Check page content for clues
            content = page.content()
            print("Looking for room section in page source...")
            if 'Pokoje' in content or 'pokoj' in content.lower():
                print("Found 'Pokoje' in page content")
            page.evaluate("window.scrollTo(0, document.body.scrollHeight * 0.3)")
            page.wait_for_timeout(800)

        page.screenshot(path=output_path, full_page=False)
        print(f"Screenshot saved: {output_path}")
        browser.close()

if __name__ == '__main__':
    capture_rooms_cards(
        'https://localhost:8443/ubytovani',
        '/home/honza/dev/tesak-cernava/screenshots/ubytovani_room_cards.png',
    )
