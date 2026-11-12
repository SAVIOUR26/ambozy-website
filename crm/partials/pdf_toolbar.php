<?php
/**
 * Toolbar for the printable document pages (quotation / invoice / delivery note).
 * Expects $pdf_filename (e.g. "QUO-2026-0001"). Add ?download=1 to the page URL to start the download automatically.
 */
$_pdf_fn = preg_replace('/[^A-Za-z0-9._-]+/', '-', $pdf_filename ?? 'document');
?>
<style>
  .doc-toolbar{position:fixed;top:16px;right:16px;display:flex;gap:8px;z-index:50;font-family:inherit}
  .doc-toolbar button{border:none;padding:10px 18px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.15);color:#fff}
  .doc-toolbar .tb-pdf{background:#f59e0b}
  .doc-toolbar .tb-print{background:#334155}
  .doc-toolbar .tb-share{background:#16a34a;display:none}
  .doc-toolbar button:disabled{opacity:.6;cursor:wait}
  @media print{.doc-toolbar{display:none}}
  /* keep the PDF library's working copy pinned top-left so phones and wide screens give identical output */
  .html2pdf__container{left:0 !important;right:auto !important;margin:0 !important}
</style>
<div class="doc-toolbar" id="docToolbar">
  <button type="button" class="tb-pdf" id="btnPdf">⬇ Download PDF</button>
  <button type="button" class="tb-share" id="btnShare">📤 Share</button>
  <button type="button" class="tb-print" onclick="window.print()">🖨 Print</button>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function () {
  var FILE = <?= json_encode($_pdf_fn . '.pdf') ?>;
  var btnPdf = document.getElementById('btnPdf'), btnShare = document.getElementById('btnShare');

  // Render a copy of the page pinned to the top-left at a fixed width, so the PDF looks the same on a
  // phone, a laptop or a wide monitor (and nothing is cropped).
  function render(mode) {
    var box = document.createElement('div');
    box.style.cssText = 'position:fixed;left:0;top:0;width:800px;background:#fff;z-index:-1;pointer-events:none';
    var clone = document.querySelector('.page').cloneNode(true);
    clone.style.margin = '0'; clone.style.maxWidth = '800px';
    box.appendChild(clone); document.body.appendChild(box);
    var job = html2pdf().set({
      margin: [8, 8, 8, 8],
      filename: FILE,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, windowWidth: Math.max(800, window.innerWidth) },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
      pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.sig-box', '.sig-area'] }
    }).from(clone);
    var p = mode === 'blob' ? job.outputPdf('blob') : job.save();
    return p.then(function (x) { box.remove(); return x; }, function (e) { box.remove(); throw e; });
  }
  function busy(btn, on, label) { btn.disabled = on; if (label) btn.dataset.l = btn.dataset.l || btn.textContent; btn.textContent = on ? 'Preparing…' : btn.dataset.l; }

  btnPdf.addEventListener('click', function () {
    if (!window.html2pdf) { window.print(); return; }   // library blocked/offline: fall back to the browser's Save as PDF
    busy(btnPdf, true, 1);
    render('save').then(function () { busy(btnPdf, false); }).catch(function () { busy(btnPdf, false); window.print(); });
  });

  // "Share" (WhatsApp, email, …) where the phone/browser can share a file
  function canShareFiles() {
    try { return !!(navigator.canShare && navigator.canShare({ files: [new File([''], 'x.pdf', { type: 'application/pdf' })] })); } catch (e) { return false; }
  }
  if (window.html2pdf && canShareFiles()) {
    btnShare.style.display = 'inline-block';
    btnShare.addEventListener('click', function () {
      busy(btnShare, true, 1);
      render('blob').then(function (blob) {
        return navigator.share({ files: [new File([blob], FILE, { type: 'application/pdf' })], title: FILE.replace('.pdf', '') });
      }).catch(function () {}).then(function () { busy(btnShare, false); });
    });
  }

  <?php if (!empty($_GET['download'])): ?>
  window.addEventListener('load', function () { if (window.html2pdf) { btnPdf.click(); } });
  <?php endif; ?>
})();
</script>
