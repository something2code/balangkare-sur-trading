<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Privacy and data use';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro">
  <span class="eyebrow" style="color:var(--green)">Prototype notice</span>
  <h1 style="font-size:clamp(2.1rem,4vw,3.4rem)">Privacy and data use</h1>
  <p>This page explains what this capstone prototype stores and what it sends to FarmBot.</p>
</section>
<section class="section" style="padding-top:0">
  <article class="panel">
    <h2 style="font-size:1.5rem">Information stored by this app</h2>
    <p>The app stores account details such as your name, email, phone number, optional address and profile photo. It also stores product listings, order requests, and buyer–farmer messages in its database. Those records remain until the site operator removes them; this prototype does not have self-service account deletion.</p>
    <p>Your address is for your account profile. Your phone number is shown to the other person after an order request so you can coordinate. Product listing details are public. Product conversations are available to the buyer and farmer in that conversation.</p>

    <h2 style="font-size:1.5rem">FarmBot and OpenRouter</h2>
    <p>When you send a FarmBot question, the app sends that question and recent chat context to OpenRouter to generate a reply. Product-related questions may also include current public marketplace listings. FarmBot chat history is not saved in this app’s database, but OpenRouter processes the request under its own terms and data practices.</p>
    <p>Do not include passwords, private addresses, payment details, or other sensitive information in FarmBot questions. FarmBot can be wrong; confirm important farming advice with a local agricultural expert.</p>

    <h2 style="font-size:1.5rem">Before using this for real customers</h2>
    <p>This is a student prototype, not a complete privacy program. The site operator should add a contact for privacy requests, set a retention and deletion process, and review the AI provider’s current data practices before public launch. For a question about this demo, contact the person operating the site.</p>
  </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
