// Matric number autocomplete
// Usage: initMatricAutocomplete(inputId, matricList)
//   inputId    — id of the text input
//   matricList — array of known matric strings (may be empty)

function initMatricAutocomplete(inputId, matricList) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const hasL    = matricList.length > 0;
  const wrapper = input.parentElement;
  wrapper.style.position = 'relative';

  // Dropdown list
  const drop = document.createElement('ul');
  drop.className = 'matric-dropdown';
  drop.setAttribute('role', 'listbox');
  drop.style.display = 'none';
  wrapper.appendChild(drop);

  // Not-in-list notice (with optional Add button)
  const notice = document.createElement('p');
  notice.className = 'matric-notice';
  notice.style.display = 'none';
  wrapper.appendChild(notice);

  let activeIdx = -1;

  function close() {
    drop.style.display = 'none';
    drop.innerHTML = '';
    activeIdx = -1;
  }

  function setActive(idx) {
    drop.querySelectorAll('.matric-option').forEach((el, i) =>
      el.classList.toggle('active', i === idx));
    activeIdx = idx;
  }

  function select(value) {
    input.value = value;
    close();
    updateNotice();
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  const MATRIC_RE = /^\d{8}$/;

  function updateNotice() {
    const val = input.value.trim();
    if (!val) {
      input.classList.remove('matric-unknown');
      notice.style.display = 'none';
      return;
    }

    // Show format error immediately if non-digits entered or value exceeds 8 chars
    const badFormat = /\D/.test(val) || val.length > 8;
    if (badFormat) {
      input.classList.add('matric-unknown');
      notice.textContent = 'Matric number must be exactly 8 digits.';
      notice.style.display = 'block';
      return;
    }

    // Still typing valid digits but fewer than 8 — stay silent
    if (!MATRIC_RE.test(val)) {
      input.classList.remove('matric-unknown');
      notice.style.display = 'none';
      return;
    }

    // Exactly 8 digits — check against list
    if (!hasL) {
      input.classList.remove('matric-unknown');
      notice.style.display = 'none';
      return;
    }
    const known = matricList.some(m => m === val);
    input.classList.toggle('matric-unknown', !known);
    if (!known) {
      notice.innerHTML =
        'Not in the student list \u2014 you can still proceed. ' +
        '<button type="button" class="matric-add-btn">Add to list</button>';
      notice.style.display = 'block';
      notice.querySelector('.matric-add-btn').addEventListener('click', () => addToList(val));
    } else {
      notice.style.display = 'none';
    }
  }

  async function addToList(matric) {
    const btn = notice.querySelector('.matric-add-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Adding\u2026'; }
    try {
      const res  = await fetch('add_matric.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ matric }),
      });
      const json = await res.json();
      if (json.ok) {
        if (!matricList.includes(matric)) matricList.push(matric);
        input.classList.remove('matric-unknown');
        notice.style.display = 'none';
      } else {
        if (btn) { btn.disabled = false; btn.textContent = 'Add to list'; }
      }
    } catch (_) {
      if (btn) { btn.disabled = false; btn.textContent = 'Add to list'; }
    }
  }

  input.addEventListener('input', () => {
    const val = input.value.trim().toLowerCase();
    if (!hasL || !val) { close(); updateNotice(); return; }

    const matches = matricList
      .filter(m => m.toLowerCase().startsWith(val))
      .sort()
      .slice(0, 10);

    if (!matches.length) { close(); updateNotice(); return; }

    drop.innerHTML = '';
    activeIdx = -1;
    matches.forEach(m => {
      const li = document.createElement('li');
      li.className = 'matric-option';
      li.setAttribute('role', 'option');
      li.dataset.value = m;
      li.innerHTML = '<mark>' + m.slice(0, val.length) + '</mark>' + m.slice(val.length);
      li.addEventListener('mousedown', e => { e.preventDefault(); select(m); });
      drop.appendChild(li);
    });
    drop.style.display = 'block';
    updateNotice();
  });

  input.addEventListener('keydown', e => {
    const items = drop.querySelectorAll('.matric-option');
    if (drop.style.display === 'none' || !items.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault(); setActive(Math.min(activeIdx + 1, items.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault(); setActive(Math.max(activeIdx - 1, 0));
    } else if (e.key === 'Enter' && activeIdx >= 0) {
      e.preventDefault(); select(items[activeIdx].dataset.value);
    } else if (e.key === 'Escape') {
      close();
    }
  });

  input.addEventListener('blur', () => {
    setTimeout(close, 150);
    updateNotice();
  });
}
