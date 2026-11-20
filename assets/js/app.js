document.querySelector('.menu-toggle')?.addEventListener('click', (event) => {
  const button = event.currentTarget;
  const nav = document.querySelector('.main-nav');
  const open = nav.classList.toggle('is-open');
  button.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('[data-confirm]').forEach((form, index) => {
  const dialog = document.createElement('dialog');
  dialog.className = 'confirm-dialog';
  dialog.setAttribute('aria-labelledby', `confirm-dialog-title-${index}`);
  dialog.setAttribute('aria-describedby', `confirm-dialog-message-${index}`);

  const dialogForm = document.createElement('form');
  dialogForm.method = 'dialog';
  dialogForm.className = 'confirm-dialog__content';

  const title = document.createElement('h2');
  title.id = `confirm-dialog-title-${index}`;
  title.className = 'confirm-dialog__title';
  title.textContent = 'Please confirm';

  const message = document.createElement('p');
  message.id = `confirm-dialog-message-${index}`;
  message.className = 'confirm-dialog__message';
  message.textContent = form.dataset.confirm;

  const actions = document.createElement('div');
  actions.className = 'confirm-dialog__actions';

  const keepButton = document.createElement('button');
  keepButton.type = 'submit';
  keepButton.value = 'keep';
  keepButton.className = 'confirm-dialog__keep';
  keepButton.textContent = 'Keep order';

  const confirmButton = document.createElement('button');
  confirmButton.type = 'submit';
  confirmButton.value = 'confirm';
  confirmButton.className = 'confirm-dialog__confirm';
  confirmButton.textContent = 'Yes, cancel';

  actions.append(keepButton, confirmButton);
  dialogForm.append(title, message, actions);
  dialog.append(dialogForm);
  document.body.append(dialog);

  let confirmed = false;
  form.addEventListener('submit', (event) => {
    if (confirmed) {
      confirmed = false;
      return;
    }
    event.preventDefault();
    dialog.showModal();
  });

  dialog.addEventListener('close', () => {
    if (dialog.returnValue === 'confirm') {
      confirmed = true;
      form.requestSubmit();
    }
  });
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const input = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!input) return;
    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(reveal));
    toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
  });
});

const registrationForm = document.querySelector('[data-password-match-form]');
if (registrationForm) {
  const password = registrationForm.querySelector('[data-password-primary]');
  const confirmation = registrationForm.querySelector('[data-password-confirm]');
  const message = registrationForm.querySelector('[data-password-message]');
  const checkPasswordMatch = () => {
    const mismatch = confirmation.value.length > 0 && password.value !== confirmation.value;
    confirmation.setCustomValidity(mismatch ? 'Passwords do not match.' : '');
    message.textContent = mismatch ? 'These passwords do not match yet.' : '';
  };
  password.addEventListener('input', checkPasswordMatch);
  confirmation.addEventListener('input', checkPasswordMatch);
  registrationForm.addEventListener('submit', checkPasswordMatch);
}

const assistantForm = document.querySelector('#assistant-form');
assistantForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const input = assistantForm.querySelector('input[name="question"]');
  const feed = document.querySelector('#assistant-feed');
  const question = input.value.trim();
  if (!question) return;
  feed.insertAdjacentHTML('beforeend', `<div class="chat-bubble user-bubble">${escapeHtml(question)}</div>`);
  input.value = '';
  const pending = document.createElement('div'); pending.className = 'chat-bubble'; pending.textContent = 'Let me think…'; feed.append(pending);
  try {
    const response = await fetch('/assistant.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({question}) });
    const data = await response.json(); pending.textContent = data.answer || 'I could not prepare an answer. Please try again.';
  } catch { pending.textContent = 'I could not connect. Please try again when the app is running.'; }
  feed.scrollTop = feed.scrollHeight;
});

function escapeHtml(value) { const node = document.createElement('span'); node.textContent = value; return node.innerHTML; }
