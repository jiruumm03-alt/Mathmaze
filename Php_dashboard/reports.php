<?php
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");
require_once __DIR__ . '/admin_header.php';
?>

<div class="card" style="max-width:760px;margin-left:auto">
  <h2>Reports</h2>
  <p>Placeholder for report generation.</p>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
