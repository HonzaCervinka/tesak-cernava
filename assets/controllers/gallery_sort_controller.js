import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/*
 * Drag-and-drop ordering of room gallery images.
 * The first item is the cover photo. On drop, the new order of image IDs is
 * POSTed to the reorder endpoint and the "titulní" badge moves to the first item.
 */
export default class extends Controller {
  static values = { url: String, token: String };

  connect() {
    this.sortable = Sortable.create(this.element, {
      animation: 150,
      ghostClass: 'gallery-sort__ghost',
      onEnd: () => this.save(),
    });
    this.refreshBadges();
  }

  disconnect() {
    this.sortable?.destroy();
  }

  async save() {
    const order = Array.from(this.element.children)
      .map((el) => parseInt(el.dataset.imageId, 10))
      .filter((id) => !Number.isNaN(id));

    try {
      const res = await fetch(this.urlValue, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order, _token: this.tokenValue }),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      this.refreshBadges();
    } catch (e) {
      window.location.reload();
    }
  }

  refreshBadges() {
    Array.from(this.element.children).forEach((el, i) => {
      const badge = el.querySelector('[data-gallery-sort-target="badge"]');
      if (badge) badge.hidden = i !== 0;
    });
  }
}
