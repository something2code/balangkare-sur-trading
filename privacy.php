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
  <article class="panel privacy-content"><?php require __DIR__ . '/includes/privacy-notice-content.php'; ?></article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
