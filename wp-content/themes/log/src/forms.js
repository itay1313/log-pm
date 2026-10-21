/**
 * Form submission over REST with inline errors. Loaded only on pages that have a [data-endpoint] form.
 * Source tracking fields are filled only after the visitor ticks consent (spec §5, §17).
 */
import { track } from './consent.js';

export function initForms() {
  const params = new URLSearchParams(location.search);
  const source = {
    utm_source: params.get('utm_source') || '',
    utm_medium: params.get('utm_medium') || '',
    utm_campaign: params.get('utm_campaign') || '',
    referrer: document.referrer || '',
    landing: sessionStorage.getItem('log_landing') || location.pathname,
    page: location.pathname,
  };
  try { if (!sessionStorage.getItem('log_landing')) sessionStorage.setItem('log_landing', location.pathname); } catch {}

  for (const form of document.querySelectorAll('form[data-endpoint]')) {
    const consent = form.querySelector('[name=consent]');
    const fillSource = () => {
      for (const [k, v] of Object.entries(source)) {
        const el = form.querySelector(`[name=${k}]`);
        if (el) el.value = consent?.checked ? v : '';
      }
    };
    consent?.addEventListener('change', fillSource);
    // The prefilled project can be removed before sending.
    form.querySelector('.form__context-clear')?.addEventListener('click', (e) => {
      form.querySelector('[name=project]').value = '';
      e.target.closest('.form__context').remove();
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearErrors(form);
      const errorBox = form.querySelector('.form__error');
      const success = form.querySelector('.form__success');
      const button = form.querySelector('[type=submit]');

      // Native checks first, with our own messages
      let firstBad = null;
      for (const el of form.querySelectorAll('[required]')) {
        if (!el.checkValidity()) {
          markError(el, el.type === 'checkbox' ? 'צריך לאשר כדי לשלוח' : el.validity.valueMissing ? 'שדה חובה' : 'הערך לא תקין');
          firstBad ||= el;
        }
      }
      const cv = form.querySelector('input[type=file][name=cv]');
      const file = cv?.files?.[0];
      if (file && !firstBad) {
        if (!/\.(pdf|docx?)$/i.test(file.name)) { markError(cv, 'רק PDF או Word'); firstBad = cv; }
        else if (file.size > 5 * 1024 * 1024) { markError(cv, 'הקובץ גדול מ-5MB'); firstBad = cv; }
      }
      if (firstBad) { firstBad.focus(); return; }

      fillSource();
      button.disabled = true;
      const label = button.textContent;
      button.textContent = 'שולחים…';

      try {
        const isMultipart = form.enctype === 'multipart/form-data';
        const res = await fetch(`${window.LOG.rest}${form.dataset.endpoint}`, {
          method: 'POST',
          headers: isMultipart ? {} : { 'Content-Type': 'application/json' },
          body: isMultipart ? new FormData(form) : JSON.stringify(Object.fromEntries(new FormData(form))),
        });
        const json = await res.json();
        if (!res.ok) {
          const fields = json.data?.fields || {};
          for (const [name, msg] of Object.entries(fields)) {
            const el = form.querySelector(`[name=${name}]`);
            if (el) markError(el, msg);
          }
          errorBox.textContent = json.message || 'משהו השתבש. נסו שוב.';
          errorBox.hidden = false;
          (form.querySelector('.is-invalid') || errorBox).focus?.();
          return;
        }
        form.querySelectorAll('.form__grid, .check, [type=submit], .form__context').forEach((n) => (n.hidden = true));
        success.textContent = json.message;
        success.hidden = false;
        form.querySelectorAll('.form__after').forEach((n) => (n.hidden = false));
        form.dispatchEvent(new CustomEvent('log:sent', { bubbles: true }));
        if (form.dataset.endpoint === 'apply') track('apply_submit', { job: form.querySelector('[name=job]')?.value, source: source.utm_source || 'site' });
        else track('contact_submit', { topic: form.querySelector('[name=topic]')?.value, project: form.querySelector('[name=project]')?.value || undefined });
        success.setAttribute('tabindex', '-1');
        success.focus();
      } catch {
        errorBox.textContent = 'אין חיבור. בדקו את הרשת ונסו שוב.';
        errorBox.hidden = false;
      } finally {
        button.disabled = false;
        button.textContent = label;
      }
    });

    for (const el of form.querySelectorAll('input, select, textarea')) {
      el.addEventListener('input', () => clearError(el));
    }
  }
}

function markError(el, msg) {
  const field = el.closest('.field, .check') || el.parentElement;
  el.classList.add('is-invalid');
  el.setAttribute('aria-invalid', 'true');
  let hint = field.querySelector('.field__hint');
  if (!hint) {
    hint = document.createElement('span');
    hint.className = 'field__hint';
    hint.id = `hint-${el.name}`;
    field.appendChild(hint);
  }
  hint.textContent = msg;
  el.setAttribute('aria-describedby', hint.id);
}
function clearError(el) {
  el.classList.remove('is-invalid');
  el.removeAttribute('aria-invalid');
  (el.closest('.field, .check') || el.parentElement).querySelector('.field__hint')?.remove();
}
function clearErrors(form) {
  form.querySelectorAll('.is-invalid').forEach(clearError);
  const box = form.querySelector('.form__error');
  box.hidden = true;
  box.textContent = '';
}
