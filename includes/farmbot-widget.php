<div class="farmbot-widget">
  <section class="farmbot-panel" id="farmbot-panel" role="dialog" aria-labelledby="farmbot-panel-title" aria-modal="false" hidden>
    <header class="farmbot-panel-header">
      <div><strong id="farmbot-panel-title">FarmBot</strong><small>Marketplace and general farming help</small></div>
      <button class="farmbot-close" id="farmbot-close" type="button" aria-label="Close FarmBot chat">×</button>
    </header>
    <div class="chat-feed farmbot-widget-feed" id="farmbot-widget-feed" aria-live="polite" aria-label="FarmBot conversation">
      <div class="chat-bubble">Hello! I’m FarmBot. Ask about the market, current listings, or general farming questions.</div>
    </div>
    <form class="chat-form farmbot-widget-form" id="farmbot-widget-form" data-farmbot-form data-feed="farmbot-widget-feed">
      <input name="question" maxlength="1000" placeholder="Ask FarmBot…" aria-label="Ask FarmBot a question" required>
      <button class="button" type="submit">Ask</button>
    </form>
  </section>
  <button class="farmbot-launcher" id="farmbot-launcher" type="button" aria-label="Open FarmBot chat" aria-expanded="false" aria-controls="farmbot-panel">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6a7.5 7.5 0 1 1 17-3.5Z"/><path d="M8 11h8M8 14h5"/></svg>
  </button>
</div>
