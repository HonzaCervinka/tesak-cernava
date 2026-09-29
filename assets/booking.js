/* ---- Rezervace – časová osa obsazenosti pokojů ----
 * Řádek = pokoj, sloupec = den. Každá buňka má dvě poloviny: levá horní = noc před
 * tímto dnem, pravá dolní = noc od tohoto dne. Díky tomu den odjezdu jednoho hosta
 * může být dnem příjezdu dalšího (napůl obsazený den).
 */

const WEEKDAYS = ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'];

/* Datumy držíme jako 'YYYY-MM-DD' – porovnávají se jako řetězce a nemají problém s časovým pásmem. */
function iso(date) {
  return date.toISOString().slice(0, 10);
}
function parse(value) {
  const [y, m, d] = value.split('-').map(Number);
  return new Date(Date.UTC(y, m - 1, d));
}
function addDays(value, n) {
  const date = parse(value);
  date.setUTCDate(date.getUTCDate() + n);
  return iso(date);
}
function nightsBetween(from, to) {
  return Math.round((parse(to) - parse(from)) / 86400000);
}
function fmt(value, withYear = false) {
  const d = parse(value);
  return `${d.getUTCDate()}. ${d.getUTCMonth() + 1}.${withYear ? ' ' + d.getUTCFullYear() : ''}`;
}
function plural(n, one, few, many) {
  return n === 1 ? one : (n >= 2 && n <= 4 ? few : many);
}
function money(n) {
  return n.toLocaleString('cs-CZ') + ' Kč';
}

function initBooking(root) {
  const rooms   = JSON.parse(root.dataset.rooms);
  const maxNights = Number(root.dataset.maxNights); // BookingRequest::MAX_NIGHTS
  // "Dnes" určuje server (česká půlnoc), ne hodiny v počítači hosta; obnovuje se s každým načtením obsazenosti.
  let today = root.dataset.today;
  const grid    = root.querySelector('[data-booking-grid]');
  const scroller = root.querySelector('[data-booking-scroll]');
  const range   = root.querySelector('[data-booking-range]');
  const hint    = root.querySelector('[data-booking-hint]');
  const prevBtn = root.querySelector('[data-booking-prev]');
  const nextBtn = root.querySelector('[data-booking-next]');
  const todayBtn = root.querySelector('[data-booking-today]');
  const panel   = root.querySelector('[data-booking-panel]');
  const form    = root.querySelector('[data-booking-form]');
  const summary = root.querySelector('[data-booking-summary]');
  const formError = root.querySelector('[data-booking-error]');
  const success = root.querySelector('[data-booking-success]');
  const guestsInput = form.querySelector('[name="guests"]');

  const state = {
    from: today,
    days: 14,
    busy: {},       // roomId → [[arrival, departure], ...]
    sel: null,      // { roomId, arrival, departure|null }
    hover: null,    // { roomId, date } – náhled prodloužení pod myší
    drag: null,     // { roomId, anchor, range: [arrival, departure]|null } během táhnutí myší
    view: null,     // co se právě vykresluje jako výběr, viz computeView()
    focusRoom: null,
  };
  let cells = [];   // { el, roomId, date }

  /* ---- Obsazenost ---- */
  function nightBusy(roomId, date) {
    return (state.busy[roomId] || []).some(([a, d]) => a <= date && date < d);
  }
  function rangeFree(roomId, arrival, departure) {
    for (let d = arrival; d < departure; d = addDays(d, 1)) {
      if (nightBusy(roomId, d)) return false;
    }
    return true;
  }
  function roomById(id) {
    return rooms.find(r => r.id === id);
  }

  /* Nejdelší souvislý volný úsek od kotvy směrem k cíli (táhnutí přes obsazený den se zastaví na jeho okraji). */
  function clampRange(roomId, anchor, target) {
    if (target > anchor) {
      let d = anchor;
      while (d < target && !nightBusy(roomId, d) && nightsBetween(anchor, d) < maxNights) d = addDays(d, 1);
      return d > anchor ? [anchor, d] : null;
    }
    if (target < anchor) {
      let a = anchor;
      while (a > target && a > today && !nightBusy(roomId, addDays(a, -1)) && nightsBetween(a, anchor) < maxNights) a = addDays(a, -1);
      return a < anchor ? [a, anchor] : null;
    }
    return null;
  }

  /* Úsek nocí k vykreslení: [from, selTo) jako výběr, [selTo, to) jako náhled. */
  function computeView() {
    const { drag, sel, hover } = state;
    if (drag) {
      return drag.range ? { roomId: drag.roomId, from: drag.range[0], to: drag.range[1], selTo: drag.range[1] } : null;
    }
    if (!sel) return null;
    const selTo = sel.departure || addDays(sel.arrival, 1);
    let to = selTo;
    if (hover && hover.roomId === sel.roomId && hover.date > selTo) {
      const r = clampRange(sel.roomId, sel.arrival, hover.date);
      if (r && r[1] > selTo) to = r[1];
    }
    return { roomId: sel.roomId, from: sel.arrival, to, selTo };
  }

  /* Stav jedné noci pro vykreslení poloviny buňky. */
  function nightState(roomId, date) {
    if (date < today) return 'past';
    const v = state.view;
    if (v && v.roomId === roomId && date >= v.from && date < v.to) return date < v.selTo ? 'sel' : 'preview';
    return nightBusy(roomId, date) ? 'busy' : 'free';
  }

  /* ---- Vykreslení mřížky (při změně okna) ---- */
  function build() {
    const dates = Array.from({ length: state.days }, (_, i) => addDays(state.from, i));
    grid.style.setProperty('--booking-days', state.days);
    grid.innerHTML = '';
    cells = [];

    const corner = document.createElement('div');
    corner.className = 'booking__corner';
    corner.textContent = 'Pokoj';
    grid.append(corner);

    dates.forEach((date, i) => {
      const d = parse(date);
      const head = document.createElement('div');
      const wd = d.getUTCDay();
      head.className = 'booking__day'
        + (wd === 0 || wd === 6 ? ' booking__day--weekend' : '')
        + (date === today ? ' booking__day--today' : '');
      const month = (i === 0 || d.getUTCDate() === 1)
        ? `<span class="booking__month">${d.toLocaleString('cs-CZ', { month: 'short', timeZone: 'UTC' })}</span>` : '';
      head.innerHTML = `${month}<strong>${d.getUTCDate()}</strong><small>${WEEKDAYS[wd]}</small>`;
      grid.append(head);
    });

    rooms.forEach(room => {
      const name = document.createElement('div');
      name.className = 'booking__room';
      name.dataset.roomId = room.id;
      name.innerHTML = `<span class="booking__room-name"></span>`
        + (room.price ? `<small>${room.priceFrom ? 'od ' : ''}${money(room.price)} ${room.priceUnit || ''}</small>` : '');
      name.querySelector('.booking__room-name').textContent = room.name;
      grid.append(name);

      dates.forEach(date => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = 'booking__cell';
        el.dataset.roomId = room.id;
        el.dataset.date = date;
        grid.append(el);
        cells.push({ el, roomId: room.id, date });
      });
    });

    range.textContent = `${fmt(dates[0])} – ${fmt(dates[dates.length - 1], true)}`;
    prevBtn.disabled = state.from <= today;
    paint();
  }

  /* ---- Obarvení buněk (při každé změně výběru / hoveru) ---- */
  function paint() {
    state.view = computeView();
    const activeId = state.drag?.roomId ?? state.sel?.roomId ?? null;
    cells.forEach(({ el, roomId, date }) => {
      const am = nightState(roomId, addDays(date, -1));
      const pm = nightState(roomId, date);
      el.dataset.am = am;
      el.dataset.pm = pm;
      const canArrive = date >= today && !nightBusy(roomId, date);
      const canLeave = date > today && !nightBusy(roomId, addDays(date, -1));
      el.disabled = !canArrive && !canLeave;
      let label = `${roomById(roomId).name}, ${fmt(date, true)}: `;
      if (date < today) label += 'minulost';
      else if (!canArrive && !canLeave) label += 'obsazeno';
      else if (!canArrive) label += 'obsazeno, lze jen odjet';
      else if (!canLeave) label += 'lze jen přijet';
      else label += 'volno';
      el.setAttribute('aria-label', label);
      el.title = label;
    });
    grid.querySelectorAll('.booking__room').forEach(el => {
      const id = Number(el.dataset.roomId);
      el.classList.toggle('is-active', activeId === id || state.focusRoom === id);
    });
    updateHint();
  }

  function updateHint() {
    if (hint.classList.contains('is-warning')) return;
    const sel = state.sel;
    if (!sel) {
      hint.textContent = 'Klikněte v řádku pokoje na den příjezdu a pak na den odjezdu, nebo termín přetáhněte myší.';
    } else if (!sel.departure) {
      hint.textContent = `${roomById(sel.roomId).name}, příjezd ${fmt(sel.arrival)}. Teď klikněte na den odjezdu.`;
    } else {
      const n = nightsBetween(sel.arrival, sel.departure);
      hint.textContent = `${roomById(sel.roomId).name}, ${fmt(sel.arrival)} – ${fmt(sel.departure, true)} (${n} ${plural(n, 'noc', 'noci', 'nocí')}). `
        + 'Kliknutím na jiný den v řádku termín prodloužíte nebo zkrátíte. Údaje vyplňte níže.';
    }
  }

  let hintTimer;
  function flashHint(message, ms = 3000) {
    clearTimeout(hintTimer);
    hint.textContent = message;
    hint.classList.add('is-warning');
    hintTimer = setTimeout(() => { hint.classList.remove('is-warning'); updateHint(); }, ms);
  }

  /* ---- Výběr termínu ---- */
  function setRange(roomId, arrival, departure) {
    state.sel = { roomId, arrival, departure };
    state.hover = null;
    state.focusRoom = null;
    paint();
    openPanel();
  }

  function onCell(roomId, date) {
    const sel = state.sel;

    // Další klik ve stejném řádku upraví rozpracovaný nebo hotový termín.
    if (sel && sel.roomId === roomId && date !== sel.arrival) {
      if (date > sel.arrival) {
        if (nightsBetween(sel.arrival, date) > maxNights) {
          flashHint(`Online lze poptat nejvýše ${maxNights} nocí. Delší pobyt s námi domluvte.`);
          return;
        }
        if (clampRange(roomId, sel.arrival, date)?.[1] === date) {
          setRange(roomId, sel.arrival, date);
          return;
        }
      } else if (sel.departure && clampRange(roomId, sel.departure, date)?.[0] === date) {
        setRange(roomId, date, sel.departure);
        return;
      }
    }

    // Jinak nový příjezd.
    if (date < today || nightBusy(roomId, date)) {
      flashHint(nightBusy(roomId, addDays(date, -1)) || date < today
        ? 'Tento den je obsazený.'
        : 'V tento den lze jen odjet. Jako příjezd vyberte jiný den.');
      return;
    }
    state.sel = { roomId, arrival: date, departure: null };
    state.focusRoom = null;
    closePanel();
    paint();
  }

  function cellAt(x, y) {
    return document.elementFromPoint(x, y)?.closest('.booking__cell') ?? null;
  }

  // Táhnutí jen myší – na dotykovém displeji by blokovalo posouvání stránky, tam se klepe.
  let suppressClick = false;
  grid.addEventListener('pointerdown', e => {
    if (e.pointerType !== 'mouse' || e.button !== 0) return;
    const cell = e.target.closest('.booking__cell');
    if (!cell || cell.disabled) return;
    e.preventDefault();
    state.drag = { roomId: Number(cell.dataset.roomId), anchor: cell.dataset.date, range: null };
    state.hover = null;
  });
  document.addEventListener('pointermove', e => {
    const drag = state.drag;
    if (!drag) return;
    const cell = cellAt(e.clientX, e.clientY);
    if (!cell || Number(cell.dataset.roomId) !== drag.roomId) return;
    const range = clampRange(drag.roomId, drag.anchor, cell.dataset.date);
    if (String(range) !== String(drag.range)) {
      drag.range = range;
      paint();
    }
  });
  document.addEventListener('pointerup', () => {
    const drag = state.drag;
    if (!drag) return;
    state.drag = null;
    suppressClick = true;
    setTimeout(() => { suppressClick = false; }, 0);
    if (drag.range) setRange(drag.roomId, ...drag.range);
    else onCell(drag.roomId, drag.anchor);
  });
  function cancelDrag() {
    if (!state.drag) return;
    state.drag = null;
    paint();
  }
  document.addEventListener('pointercancel', cancelDrag);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') cancelDrag(); });

  // Klepnutí prstem a klávesnice (Enter/mezerník na buňce).
  grid.addEventListener('click', e => {
    if (suppressClick) return;
    const cell = e.target.closest('.booking__cell');
    if (cell && !cell.disabled) onCell(Number(cell.dataset.roomId), cell.dataset.date);
  });

  grid.addEventListener('mouseover', e => {
    if (state.drag || !state.sel) return;
    const cell = e.target.closest('.booking__cell');
    if (!cell) return;
    state.hover = { roomId: Number(cell.dataset.roomId), date: cell.dataset.date };
    paint();
  });
  grid.addEventListener('mouseleave', () => {
    if (state.hover) { state.hover = null; paint(); }
  });

  /* ---- Formulář ---- */
  function openPanel() {
    const room = roomById(state.sel.roomId);
    guestsInput.max = room.capacity > 0 ? room.capacity : '';
    if (room.capacity > 0 && Number(guestsInput.value) > room.capacity) guestsInput.value = room.capacity;
    success.hidden = true;
    form.hidden = false;
    panel.hidden = false;
    renderSummary();
  }
  function closePanel() {
    panel.hidden = true;
  }

  function estimate(room, nights, guests) {
    if (!room.price) return null;
    const perNight = nights === 1 && room.priceSingleNight ? room.priceSingleNight : room.price;
    let total = perNight * nights;
    if (room.perPerson) {
      if (!guests) return { text: `${money(perNight)} × ${nights} ${plural(nights, 'noc', 'noci', 'nocí')} za osobu` };
      total *= guests;
    }
    return { text: money(total), from: room.priceFrom };
  }

  function renderSummary() {
    const { roomId, arrival, departure } = state.sel;
    const room = roomById(roomId);
    const nights = nightsBetween(arrival, departure);
    const price = estimate(room, nights, Number(guestsInput.value) || 0);
    summary.innerHTML = `
      <div><dt>Pokoj</dt><dd data-f="room"></dd></div>
      <div><dt>Termín</dt><dd>${fmt(arrival)} – ${fmt(departure, true)}</dd></div>
      <div><dt>Nocí</dt><dd>${nights}</dd></div>
      ${price ? `<div><dt>Orientační cena</dt><dd>${price.from ? 'od ' : ''}${price.text}</dd></div>` : ''}`;
    summary.querySelector('[data-f="room"]').textContent = room.name;
  }
  guestsInput.addEventListener('input', () => { if (state.sel?.departure) renderSummary(); });

  root.querySelector('[data-booking-reset]').addEventListener('click', () => {
    state.sel = null;
    closePanel();
    paint();
    scroller.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  function showErrors(errors) {
    form.querySelectorAll('.form-input, .form-textarea').forEach(f => f.classList.remove('error'));
    form.querySelectorAll('[data-error-for]').forEach(el => { el.hidden = true; });
    const general = [];
    Object.entries(errors).forEach(([field, message]) => {
      const slot = form.querySelector(`[data-error-for="${field}"]`);
      if (slot) {
        slot.textContent = message;
        slot.hidden = false;
        form.querySelector(`[name="${field}"]`)?.classList.add('error');
      } else {
        general.push(message);
      }
    });
    formError.textContent = general.join(' ');
    formError.hidden = general.length === 0;
    form.querySelector('.error')?.focus();
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!state.sel?.departure) return;
    const data = Object.fromEntries(new FormData(form));
    const payload = {
      ...data,
      consent: form.querySelector('[name="consent"]').checked,
      roomId: state.sel.roomId,
      arrival: state.sel.arrival,
      departure: state.sel.departure,
    };

    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    showErrors({});
    try {
      const res = await fetch(root.dataset.submitUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload),
      });
      const body = await res.json().catch(() => ({}));
      if (res.status === 201) {
        form.reset();
        form.hidden = true;
        success.hidden = false;
        success.focus();
        state.sel = null;
        await load();
      } else if (res.status === 409) {
        state.sel = null;
        closePanel();
        await load();
        flashHint(body.error, 8000);
        scroller.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else if (res.status === 422) {
        showErrors(body.errors || {});
      } else {
        throw new Error();
      }
    } catch {
      showErrors({ _: 'Odeslání se nepovedlo. Zkuste to prosím znovu, nebo nám zavolejte.' });
    } finally {
      submit.disabled = false;
    }
  });

  /* ---- Načtení obsazenosti a posun okna ---- */
  async function load() {
    root.classList.add('is-loading');
    try {
      const res = await fetch(`${root.dataset.availabilityUrl}?from=${state.from}&days=${state.days}`, { headers: { Accept: 'application/json' } });
      const body = await res.json();
      today = body.today;
      state.from = body.from;
      state.busy = body.busy;
    } catch {
      flashHint('Obsazenost se nepodařilo načíst. Zkuste obnovit stránku.');
    } finally {
      root.classList.remove('is-loading');
      build();
    }
  }

  function fitDays() {
    const narrow = window.innerWidth < 640;
    const nameWidth = narrow ? 112 : 200;
    const cellMin = narrow ? 38 : 46;
    return Math.max(7, Math.min(28, Math.floor((scroller.clientWidth - nameWidth) / cellMin)));
  }

  function shift(days) {
    const next = addDays(state.from, days);
    state.from = next < today ? today : next;
    load();
  }
  prevBtn.addEventListener('click', () => shift(-state.days));
  nextBtn.addEventListener('click', () => shift(state.days));
  todayBtn.addEventListener('click', () => { state.from = today; load(); });

  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      const days = fitDays();
      if (days !== state.days) { state.days = days; load(); }
    }, 200);
  });

  /* Tlačítka „Rezervovat“ na kartách pokojů – sjedou na osu a zvýrazní řádek pokoje. */
  document.querySelectorAll('[data-book-room]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      state.focusRoom = Number(btn.dataset.bookRoom);
      if (state.sel && state.sel.roomId !== state.focusRoom) { state.sel = null; closePanel(); }
      paint();
      root.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.replaceState(null, '', '#rezervace');
    });
  });

  // Odkaz „Poptat pokoj“ z úvodní stránky: /ubytovani?pokoj=ID#rezervace zvýrazní řádek pokoje.
  const linkedRoom = Number(new URLSearchParams(location.search).get('pokoj'));
  if (rooms.some(r => r.id === linkedRoom)) state.focusRoom = linkedRoom;

  state.days = fitDays();
  load();
}

const bookingRoot = document.getElementById('rezervace');
if (bookingRoot) initBooking(bookingRoot);
