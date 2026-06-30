import { Controller } from '@hotwired/stimulus';

/*
 * Reusable Symfony CollectionType add/remove rows.
 * - container target holds the rows
 * - prototype value is the form's data-prototype HTML (with __name__ placeholder)
 * - "add" action clones the prototype; per-row "remove" action deletes the row
 */
export default class extends Controller {
  static targets = ['container'];
  static values = { prototype: String, index: Number };

  connect() {
    if (!this.hasIndexValue) {
      this.indexValue = this.containerTarget.querySelectorAll('[data-collection-item]').length;
    }
  }

  add(event) {
    event.preventDefault();
    const html = this.prototypeValue.replace(/__name__/g, this.indexValue);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const row = wrapper.firstElementChild;
    this.containerTarget.appendChild(row);
    this.indexValue++;
  }

  remove(event) {
    event.preventDefault();
    const row = event.target.closest('[data-collection-item]');
    if (row) row.remove();
  }
}
