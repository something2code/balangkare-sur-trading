document.querySelector('.menu-toggle')?.addEventListener('click', (event) => {
  const button = event.currentTarget;
  const nav = document.querySelector('.main-nav');
  const open = nav.classList.toggle('is-open');
  button.setAttribute('aria-expanded', String(open));
});

const privacyAnnouncement = document.getElementById('privacy-announcement');
if (privacyAnnouncement) {
  const consentCheckbox = document.getElementById('privacy_consent');
  const continueButton = privacyAnnouncement.querySelector('[data-close-privacy]');
  const updateContinueButton = () => {
    if (continueButton && consentCheckbox) continueButton.disabled = !consentCheckbox.checked;
  };
  consentCheckbox?.addEventListener('change', updateContinueButton);
  consentCheckbox?.addEventListener('invalid', () => {
    if (typeof privacyAnnouncement.showModal === 'function' && !privacyAnnouncement.open) privacyAnnouncement.showModal();
  });
  updateContinueButton();
  document.querySelector('[data-close-privacy]')?.addEventListener('click', () => {
    if (typeof privacyAnnouncement.close === 'function') privacyAnnouncement.close();
    else privacyAnnouncement.removeAttribute('open');
  });
  if (typeof privacyAnnouncement.showModal === 'function') privacyAnnouncement.showModal();
  else privacyAnnouncement.setAttribute('open', '');
}

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

const appendFarmbotInline = (element, text) => {
  text.split(/(\*\*[^*]+\*\*)/g).forEach((part) => {
    if (part.startsWith('**') && part.endsWith('**') && part.length > 4) {
      const strong = document.createElement('strong');
      strong.textContent = part.slice(2, -2);
      element.append(strong);
    } else {
      element.append(document.createTextNode(part));
    }
  });
};

const renderFarmbotAnswer = (bubble, answer) => {
  bubble.replaceChildren();
  let paragraph = null;
  let list = null;
  const lines = answer.replace(/\r\n?/g, '\n').split('\n');
  const splitTableRow = (line) => line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((cell) => cell.trim());

  for (let index = 0; index < lines.length; index += 1) {
    const line = lines[index];
    const headerCells = line.includes('|') ? splitTableRow(line) : [];
    const separatorCells = lines[index + 1]?.includes('|') ? splitTableRow(lines[index + 1]) : [];
    const isTableSeparator = separatorCells.length > 1 && separatorCells.every((cell) => /^:?-{3,}:?$/.test(cell));
    if (headerCells.length > 1 && isTableSeparator) {
      paragraph = null;
      list = null;
      const cards = document.createElement('div');
      cards.className = 'farmbot-table-list';
      index += 2;

      while (index < lines.length && lines[index].includes('|') && lines[index].trim()) {
        const values = splitTableRow(lines[index]);
        const card = document.createElement('div');
        card.className = 'farmbot-table-card';
        headerCells.forEach((header, column) => {
          const field = document.createElement('div');
          field.className = 'farmbot-table-field';
          const label = document.createElement('strong');
          label.textContent = header;
          const value = document.createElement('span');
          appendFarmbotInline(value, values[column] || '—');
          field.append(label, value);
          card.append(field);
        });
        cards.append(card);
        index += 1;
      }
      bubble.append(cards);
      index -= 1;
      continue;
    }

    const bullet = line.match(/^\s*[-*]\s+(.+)$/);
    if (bullet) {
      paragraph = null;
      if (!list) {
        list = document.createElement('ul');
        bubble.append(list);
      }
      const item = document.createElement('li');
      appendFarmbotInline(item, bullet[1]);
      list.append(item);
      continue;
    }

    if (!line.trim()) {
      paragraph = null;
      list = null;
      continue;
    }

    list = null;
    if (!paragraph) {
      paragraph = document.createElement('p');
      bubble.append(paragraph);
    } else {
      paragraph.append(document.createElement('br'));
    }
    appendFarmbotInline(paragraph, line);
  }
};

document.querySelectorAll('[data-farmbot-form]').forEach((farmbotForm) => {
  farmbotForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const input = farmbotForm.querySelector('input[name="question"]');
    const askButton = farmbotForm.querySelector('button[type="submit"]');
    const feed = document.getElementById(farmbotForm.dataset.feed);
    const question = input.value.trim();
    if (!question || !feed) return;
    const history = [...feed.querySelectorAll('.chat-bubble:not(.pending)')].slice(-8).map((bubble) => ({
      role: bubble.classList.contains('user-bubble') ? 'user' : 'assistant',
      content: bubble.textContent.trim(),
    }));
    const userBubble = document.createElement('div');
    userBubble.className = 'chat-bubble user-bubble';
    userBubble.textContent = question;
    feed.append(userBubble);
    input.value = '';
    const pending = document.createElement('div'); pending.className = 'chat-bubble pending'; pending.textContent = 'FarmBot is thinking…'; feed.append(pending);
    if (askButton) askButton.disabled = true;
    try {
      const response = await fetch('/assistant.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({question, history}) });
      const data = await response.json();
      renderFarmbotAnswer(pending, data.answer || 'I could not prepare an answer. Please try again.');
    } catch { pending.textContent = 'I could not connect. Please try again when the app is running.'; }
    pending.classList.remove('pending');
    if (askButton) askButton.disabled = false;
    input.focus();
    feed.scrollTop = feed.scrollHeight;
  });
});

const farmbotLauncher = document.querySelector('#farmbot-launcher');
const farmbotPanel = document.querySelector('#farmbot-panel');
const farmbotClose = document.querySelector('#farmbot-close');
const setFarmbotOpen = (open) => {
  if (!farmbotLauncher || !farmbotPanel) return;
  farmbotPanel.hidden = !open;
  farmbotLauncher.setAttribute('aria-expanded', String(open));
  if (open) farmbotPanel.querySelector('input[name="question"]')?.focus();
  else farmbotLauncher.focus();
};
farmbotLauncher?.addEventListener('click', () => setFarmbotOpen(farmbotPanel.hidden));
farmbotClose?.addEventListener('click', () => setFarmbotOpen(false));
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && farmbotPanel && !farmbotPanel.hidden) setFarmbotOpen(false);
});

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
