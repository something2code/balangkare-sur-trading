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

const conversation = document.querySelector('#conversation-messages');
const conversationForm = document.querySelector('#conversation-form');
if (conversation && conversationForm) {
  const status = document.querySelector('#conversation-status');
  const input = conversationForm.querySelector('textarea[name="body"]');
  const sendButton = conversationForm.querySelector('button[type="submit"]');
  const conversationId = conversation.dataset.conversationId;
  const currentUserId = Number(conversation.dataset.userId);
  let lastId = Number(conversation.dataset.lastId || 0);
  let polling = false;

  const appendMessage = (message) => {
    const id = Number(message.id);
    if (!id || id <= lastId) return;
    conversation.querySelector('[data-empty-message]')?.remove();
    const article = document.createElement('article');
    const ownMessage = Number(message.sender_id) === currentUserId;
    article.className = `conversation-message${ownMessage ? ' conversation-message-own' : ''}`;
    article.dataset.messageId = String(id);
    const meta = document.createElement('div');
    meta.className = 'conversation-message-meta';
    const name = document.createElement('strong');
    name.textContent = ownMessage ? 'You' : message.sender_name;
    const time = document.createElement('time');
    const date = new Date(String(message.created_at).replace(' ', 'T'));
    time.dateTime = date.toISOString();
    time.textContent = new Intl.DateTimeFormat(undefined, {dateStyle: 'medium', timeStyle: 'short'}).format(date);
    meta.append(name, time);
    const body = document.createElement('p');
    body.textContent = message.body;
    article.append(meta, body);
    conversation.append(article);
    lastId = id;
    conversation.dataset.lastId = String(id);
    conversation.scrollTop = conversation.scrollHeight;
  };

  const pollMessages = async () => {
    if (polling) return;
    polling = true;
    try {
      const url = `${conversation.dataset.endpoint}?conversation_id=${encodeURIComponent(conversationId)}&after_id=${lastId}`;
      const response = await fetch(url, {headers: {'Accept': 'application/json'}});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Could not check for new messages.');
      data.messages.forEach(appendMessage);
      status.textContent = 'Messages update automatically every few seconds.';
    } catch {
      status.textContent = 'Could not check for new messages. Reconnecting…';
    } finally {
      polling = false;
    }
  };

  conversationForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!input.value.trim()) return;
    sendButton.disabled = true;
    status.textContent = 'Sending…';
    try {
      const response = await fetch(conversationForm.action, {method: 'POST', body: new FormData(conversationForm), headers: {'Accept': 'application/json'}});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Could not send your message.');
      appendMessage(data.message);
      input.value = '';
      status.textContent = 'Message sent.';
    } catch (error) {
      status.textContent = error.message || 'Could not send your message. Please try again.';
    } finally {
      sendButton.disabled = false;
      input.focus();
    }
  });

  window.setInterval(pollMessages, 3000);
}
