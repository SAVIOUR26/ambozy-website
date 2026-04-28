<?php
/**
 * CRM shared layout — header + sidebar
 * Required variables (set before including):
 *   $page_title  string  — shown in <title> and top bar
 *   $active_nav  string  — nav key: dashboard|leads|clients|quotes|orders|invoices|catalog|reports
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_login();

$_admin_name = $_SESSION['admin_user'] ?? 'Admin';
$_active     = $active_nav ?? '';
$_flash      = get_flash();

// New-lead badge count
$_new_leads = 0;
if ($pdo) {
    $_new_leads = (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
}

// Overdue invoice count
$_overdue = 0;
if ($pdo) {
    $_overdue = (int) $pdo->query(
        "SELECT COUNT(*) FROM invoices WHERE status NOT IN ('paid','cancelled') AND due_date < CURDATE()"
    )->fetchColumn();
}

function _nav(string $href, string $key, string $active, string $icon, string $label, int $badge = 0): void { ?>
  <a href="<?= $href ?>"
     class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
            <?= $key === $active
                ? 'bg-amber-500/20 text-amber-400'
                : 'text-slate-400 hover:text-white hover:bg-slate-700/60' ?>">
    <?= $icon ?>
    <span><?= $label ?></span>
    <?php if ($badge > 0): ?>
      <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[1.2rem] text-center">
        <?= $badge ?>
      </span>
    <?php endif; ?>
  </a>
<?php }

$_icons = [
  'dashboard' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
  'leads'     => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
  'clients'   => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
  'quotes'    => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
  'orders'    => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
  'invoices'  => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
  'catalog'   => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>',
  'reports'   => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'CRM') ?> — Ambozy CRM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="h-full" x-data="{ sidebar: false }">

<!-- Mobile overlay -->
<div x-show="sidebar" x-cloak x-transition.opacity
     class="fixed inset-0 bg-black/60 z-20 lg:hidden"
     @click="sidebar = false"></div>

<div class="flex h-screen overflow-hidden">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside x-bind:class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
         class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-900 flex flex-col
                transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 shrink-0">

    <!-- Brand -->
    <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-700/80 shrink-0">
      <div class="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center shrink-0">
        <span class="text-white font-bold text-sm">AG</span>
      </div>
      <div class="min-w-0">
        <p class="text-white font-semibold text-sm leading-tight truncate">Ambozy CRM</p>
        <p class="text-slate-400 text-xs truncate">Graphics Solutions</p>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

      <?php _nav('/crm/dashboard.php', 'dashboard', $_active, $_icons['dashboard'], 'Dashboard') ?>

      <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sales</p>
      <?php _nav('/crm/leads/',   'leads',   $_active, $_icons['leads'],   'Leads',      $_new_leads) ?>
      <?php _nav('/crm/clients/', 'clients', $_active, $_icons['clients'], 'Clients') ?>
      <?php _nav('/crm/quotes/',  'quotes',  $_active, $_icons['quotes'],  'Quotations') ?>

      <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Operations</p>
      <?php _nav('/crm/orders/',   'orders',   $_active, $_icons['orders'],   'Orders') ?>
      <?php _nav('/crm/invoices/', 'invoices', $_active, $_icons['invoices'], 'Invoices', $_overdue) ?>

      <p class="px-3 pt-4 pb-1 text-xs font-semibold text-slate-500 uppercase tracking-wider">Setup</p>
      <?php _nav('/crm/catalog/', 'catalog', $_active, $_icons['catalog'], 'Price Catalog') ?>
      <?php _nav('/crm/reports/', 'reports', $_active, $_icons['reports'], 'Reports') ?>

    </nav>

    <!-- User strip -->
    <div class="border-t border-slate-700/80 px-4 py-3 shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-amber-500/20 text-amber-400 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
          <?= strtoupper(substr($_admin_name, 0, 1)) ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-white text-sm font-medium truncate"><?= htmlspecialchars($_admin_name) ?></p>
          <a href="/crm/logout.php" class="text-slate-400 hover:text-red-400 text-xs transition-colors">Sign out</a>
        </div>
        <a href="/" target="_blank" title="View website"
           class="p-1.5 text-slate-500 hover:text-white rounded transition-colors shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- ── Main area ────────────────────────────────────────── -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

    <!-- Top bar -->
    <header class="h-16 bg-white border-b border-gray-200 flex items-center px-5 gap-4 shrink-0 z-10">
      <button @click="sidebar = !sidebar"
              class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <h1 class="text-base font-semibold text-gray-900 flex-1"><?= htmlspecialchars($page_title ?? '') ?></h1>
      <!-- Optional right-side slot -->
      <?php if (!empty($header_actions)): ?>
        <div class="flex items-center gap-2"><?= $header_actions ?></div>
      <?php endif; ?>
    </header>

    <!-- Flash message -->
    <?php if ($_flash): ?>
      <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
           x-transition
           class="mx-5 mt-4 px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-2
                  <?= $_flash['type'] === 'success'
                      ? 'bg-green-50 text-green-800 border border-green-200'
                      : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <?= $_flash['type'] === 'success'
            ? '<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
            : '<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>' ?>
        <?= htmlspecialchars($_flash['msg']) ?>
      </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-5">
