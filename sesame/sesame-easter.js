// sesame-easter.js — cute pops on background clicks (admin only)
(function start(){
  const boot = () => {
    const EMOJIS = ['🌼','🍰']; // marguerite / madeleine
    const BLOCK_INSIDE = '.card, .topbar, form, table, .grid-tiles, .tile, .uploader';

    const wrap = document.querySelector('.se .wrap');
    if (!wrap) return; // pas connecté → on ne fait rien

    document.addEventListener('click', (ev) => {
      const tgt = ev.target;
      if (tgt.closest(BLOCK_INSIDE)) return;            // on ignore les clics dans l'UI
      if (getSelection()?.toString()) return;           // on ignore si sélection de texte

      // antispam
      const now = Date.now();
      if (document._lastEaster && now - document._lastEaster < 120) return;
      document._lastEaster = now;

      // pop
      const span = document.createElement('span');
      span.className = 'easter-pop';
      span.textContent = Math.random() < 0.5 ? EMOJIS[0] : EMOJIS[1];

      // taille légère responsive
      const base = matchMedia('(min-width: 768px)').matches ? 26 : 22;
      const jitter = Math.random() * 4 - 2;
      span.style.fontSize = (base + jitter) + 'px';

      // position au clic
      span.style.left = ev.pageX + 'px';
      span.style.top  = ev.pageY + 'px';

      document.body.appendChild(span);
      span.addEventListener('animationend', () => span.remove());
    }, { passive: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
