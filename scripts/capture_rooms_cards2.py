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

        # Get the bounding box of the heading "Ubytování pro každého" which is the section heading
        # Use evaluate to find it and get its position
        result = page.evaluate("""
            () => {
                // Find all headings and look for room-related text
                const headings = [...document.querySelectorAll('h1, h2, h3, h4, section, div')];
                for (const el of headings) {
                    const text = el.textContent.trim();
                    if (text.includes('Ubytování pro každého') || text.includes('Pokoje a apartmány') || text.includes('Ubytování pro') ) {
                        const rect = el.getBoundingClientRect();
                        const scrollTop = window.scrollY;
                        return {
                            found: true,
                            text: text.substring(0, 50),
                            tag: el.tagName,
                            top: rect.top + scrollTop,
                            offsetTop: el.offsetTop
                        };
                    }
                }
                return { found: false };
            }
        """)
        print(f"Element search result: {result}")

        if result.get('found'):
            scroll_y = result.get('top', result.get('offsetTop', 0)) - 20
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            page.wait_for_timeout(800)
            print(f"Scrolled to y={scroll_y}")
        else:
            # Based on full-page screenshot, room cards are roughly at 30-50% down the page
            page.evaluate("window.scrollTo(0, document.body.scrollHeight * 0.28)")
            page.wait_for_timeout(800)

        page.screenshot(path=output_path, full_page=False)
        print(f"Screenshot saved: {output_path}")
        browser.close()

if __name__ == '__main__':
    capture_rooms_cards(
        'https://localhost:8443/ubytovani',
        '/home/honza/dev/tesak-cernava/screenshots/ubytovani_room_cards.png',
    )
