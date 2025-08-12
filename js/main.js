// main.js — menu mobile + access + interactions
document.addEventListener("DOMContentLoaded", () => {
  const btnBurger = document.querySelector(".hamburger");
  const hero = document.querySelector(".hero");
  const logo = document.querySelector(".logo");
  if (!btnBurger) return;

  // ---- Menu element (création si absent)
  let navMenu = document.querySelector(".nav-menu");
  if (!navMenu) {
    navMenu = document.createElement("nav");
    navMenu.className = "nav-menu";
    navMenu.setAttribute("aria-hidden", "true");
    navMenu.innerHTML = `
      <ul>
        <li><a href="#hero">Accueil</a></li>
        <li><a href="#about">À propos</a></li>
        <li><a href="#services">Prestations</a></li>
        <li><a href="#portfolio">Portfolio</a></li>
        <li><a href="#avis">Avis</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="#galerie">Galerie Privée</a></li>
      </ul>`;
    document.body.appendChild(navMenu);
  }

  // ---- Scrollbar compensation (évite le "saut" de layout)
  function setScrollbarCompensation(enable) {
    if (enable) {
      const sbw = window.innerWidth - document.documentElement.clientWidth;
      document.documentElement.style.setProperty("--sbw", sbw + "px");
      document.documentElement.classList.add("menu-open");
    } else {
      document.documentElement.classList.remove("menu-open");
      document.documentElement.style.removeProperty("--sbw");
    }
  }

  // ---- Focus trap
  let lastFocusedElement = null;
  let trapHandler = null;
  let firstFocusable = null;
  let lastFocusable = null;

  function setupFocusables(container) {
    const focusables = container.querySelectorAll(
      'a[href], button, textarea, input, select, [tabindex]:not([tabindex="-1"])'
    );
    firstFocusable = focusables[0] || null;
    lastFocusable = focusables[focusables.length - 1] || null;
  }

  function addTrap() {
    removeTrap(); // sécurité
    trapHandler = (e) => {
      if (e.key !== "Tab" || !firstFocusable || !lastFocusable) return;
      if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
          e.preventDefault();
          lastFocusable.focus();
        }
      } else {
        if (document.activeElement === lastFocusable) {
          e.preventDefault();
          firstFocusable.focus();
        }
      }
    };
    document.addEventListener("keydown", trapHandler);
  }

  function removeTrap() {
    if (trapHandler) {
      document.removeEventListener("keydown", trapHandler);
      trapHandler = null;
    }
  }

  // ---- Open / Close
  function openMenu() {
    lastFocusedElement = document.activeElement;

    navMenu.classList.remove("is-closing");
    btnBurger.classList.add("is-active");
    navMenu.classList.add("is-visible");
    btnBurger.setAttribute("aria-expanded", "true");
    btnBurger.setAttribute("aria-pressed", "true");
    navMenu.setAttribute("aria-hidden", "false");

    document.documentElement.style.overflow = "hidden";
    setScrollbarCompensation(true);

    setupFocusables(navMenu);
    addTrap();

    // ← focus sur le conteneur, pas sur le 1er lien
    if (!navMenu.hasAttribute("tabindex"))
      navMenu.setAttribute("tabindex", "-1");
    navMenu.focus();
  }

  function closeMenu() {
    // état "closing" pour animer le fade-out
    navMenu.classList.add("is-closing");
    btnBurger.classList.remove("is-active");
    btnBurger.setAttribute("aria-expanded", "false");
    btnBurger.setAttribute("aria-pressed", "false");
    navMenu.setAttribute("aria-hidden", "true");

    const onEnd = (e) => {
      if (e.propertyName !== "opacity") return;
      navMenu.classList.remove("is-visible", "is-closing");
      document.documentElement.style.overflow = "";
      setScrollbarCompensation(false);
      removeTrap();
      navMenu.removeEventListener("transitionend", onEnd);
      if (
        lastFocusedElement &&
        typeof lastFocusedElement.focus === "function"
      ) {
        lastFocusedElement.focus();
      }
    };
    navMenu.addEventListener("transitionend", onEnd);
  }

  // ---- Events
  btnBurger.addEventListener("click", () => {
    const isOpen = btnBurger.classList.contains("is-active");
    isOpen ? closeMenu() : openMenu();
  });

  // Clic dans l’overlay : ferme (lien OU fond)
  navMenu.addEventListener("click", (e) => {
    if (!e.target.closest("a")) {
      closeMenu();
    } else {
      // lien cliqué : on laisse naviguer puis on ferme
      closeMenu();
    }
  });

  // Esc pour fermer
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && btnBurger.classList.contains("is-active")) {
      e.preventDefault();
      closeMenu();
    }
  });

  // Recalcule la compensation si resize pendant menu ouvert
  window.addEventListener("resize", () => {
    if (btnBurger.classList.contains("is-active"))
      setScrollbarCompensation(true);
  });

  // Couleur du burger après le hero
  // Couleur du burger + affichage logo après le hero
if (hero && logo) {
  window.addEventListener(
    "scroll",
    () => {
      const pastHero = hero.getBoundingClientRect().bottom <= 0;
      btnBurger.classList.toggle("scrolled", pastHero);
      logo.classList.toggle("is-scrolled", pastHero); // ← apparition logo
    },
    { passive: true }
  );
}

});


// --------------------------------------------------
// parallax minimal (rAF + IO pour ne calculer que si visible)
// --- Parallax minimal (rAF + IO)
const heroEl = document.querySelector(".hero");
const bg = document.querySelector(".hero-bg");
if (heroEl && bg) {
  // active le mode parallax (désactive le background fallback en CSS)
  heroEl.classList.add("has-parallax");

  let ticking = false;
  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const rect = heroEl.getBoundingClientRect();
      // amplitude douce (30–40 px)
      const progress = Math.max(0, Math.min(1, -rect.top / window.innerHeight));
      bg.style.setProperty("--py", (progress * 36).toFixed(1) + "px");
      ticking = false;
    });
  };

  const io = new IntersectionObserver(
    ([e]) => {
      if (e.isIntersecting) {
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });
      } else {
        window.removeEventListener("scroll", onScroll);
      }
    },
    { threshold: 0 }
  );
  io.observe(heroEl);
}

// --------------------------------------------------
// Bloc "En savoir plus" (inchangé)
(function () {
  const btn = document.querySelector(".btn-toggle-more");
  const panel = document.getElementById("about-more");
  if (!btn || !panel) return;

  const setMaxHeight = (el, toExpand) => {
    if (toExpand) {
      el.hidden = false;
      const full = el.scrollHeight;
      el.style.maxHeight = full + "px";
    } else {
      const full = el.scrollHeight;
      el.style.maxHeight = full + "px";
      requestAnimationFrame(() => {
        el.style.maxHeight = "0px";
      });
    }
  };

  btn.addEventListener("click", () => {
    const isOpen = btn.getAttribute("aria-expanded") === "true";
    btn.setAttribute("aria-expanded", String(!isOpen));
    btn.textContent = isOpen ? "En\u00a0savoir\u00a0plus" : "Réduire";
    setMaxHeight(panel, !isOpen);
    panel.classList.toggle("open", !isOpen);
    if (isOpen) {
      panel.addEventListener(
        "transitionend",
        () => {
          if (panel.style.maxHeight === "0px") panel.hidden = true;
        },
        { once: true }
      );
    }
  });
})();

// --------------------------------------------------
// --------------------------------------------------
// Photo strip — fade-in + démarrage seulement si visible & images prêtes
(function () {
  const strip = document.querySelector(".photo-strip");
  if (!strip) return;

  const track = strip.querySelector(".strip-track");
  if (!track) return;

  // Fade-in progressif quand chaque image est chargée
  const markLoaded = (img) => (img.dataset.loaded = "true");
  track.querySelectorAll("img").forEach((img) => {
    if (img.complete) {
      markLoaded(img);
    } else {
      img.addEventListener("load", () => markLoaded(img), { once: true });
      img.addEventListener("error", () => markLoaded(img), { once: true });
    }
  });

  // Ne lance l’animation que quand la strip est visible
  // ET quand les 3 premières images sont décodées (évite le "saut")
  const firstImgs = Array.from(track.querySelectorAll("img")).slice(0, 3);
  const decodeImg = (img) =>
    img.decode ? img.decode().catch(() => {}) : Promise.resolve();

  let ready = false;
  Promise.all(firstImgs.map(decodeImg)).then(() => {
    ready = true;
    // si déjà visible au moment où ça devient prêt, on démarre
    if (strip._isIntersecting) strip.classList.add("is-running");
  });

  const io = new IntersectionObserver(
    ([entry]) => {
      strip._isIntersecting = entry.isIntersecting;
      strip.classList.toggle("is-active", entry.isIntersecting);
      // on ne lance que si visible *et* prêt
      if (entry.isIntersecting && ready) {
        strip.classList.add("is-running");
      } else {
        strip.classList.remove("is-running");
      }
    },
    { threshold: 0 }
  );

  io.observe(strip);
})();

// --------------------------------------------------
// Ajuste la hauteur mini de la section "Galerie"
function fitGallery() {
  const footer = document.querySelector(".site-footer");
  const gallery = document.getElementById("galerie");
  if (!footer || !gallery) return;
  const h = footer.offsetHeight;
  gallery.style.minHeight = `calc(100vh - ${h}px)`;
}
window.addEventListener("load", fitGallery);
window.addEventListener("resize", fitGallery);

// --------------------------------------------------
// --- Bandeau de statut formulaire (success/error) ---
document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(location.search);
  const status = params.get("status"); // "error" | "send_error" | null
  if (!status) return;

  const contact = document.querySelector("#contact .container");
  if (!contact) return;

  const box = document.createElement("div");
  box.className = `notice ${status === "error" ? "is-error" : "is-fail"}`;
  box.setAttribute("role", "status");
  box.setAttribute("aria-live", "polite");
  box.innerHTML = `
    <strong>${
      status === "error"
        ? "Il manque des infos."
        : "Envoi impossible pour le moment."
    }</strong>
    <span>${
      status === "error"
        ? "Vérifie ton nom, ton e-mail et ton message, puis renvoie le formulaire."
        : "Réessaie plus tard ou écris-moi directement : "
    }
      ${
        status === "send_error"
          ? `<a href="mailto:bonjour@madeleineetmarguerite.fr">bonjour@madeleineetmarguerite.fr</a>`
          : ""
      }
    </span>
    <button class="notice-close" aria-label="Fermer l’alerte">×</button>
  `;
  contact.prepend(box);

  // focus non-visible (pas de halo), mais utile pour lecteurs d’écran
  box.setAttribute("tabindex", "-1");
  box.focus({ preventScroll: true });
  box.scrollIntoView({ behavior: "smooth", block: "start" });

  box
    .querySelector(".notice-close")
    ?.addEventListener("click", () => box.remove());
});


// ============ Typed Engine (factorisé) ============
(function () {
  // Respecte la préférence "réduire les animations"
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function sleep(ms){ return new Promise(r => setTimeout(r, ms)); }

  async function runTyped(el, words, {
    typeDelay = 55,
    eraseDelay = 35,
    holdDelay = 1200,
    loopDelay = 400,
    initial = words[0] || ""
  } = {}) {
    if (!el) return;
    el.setAttribute('aria-live', 'polite');

    if (reduced) { el.textContent = initial; return; }

    let i = 0, stop = false;
    const onHide = () => { stop = true; };
    window.addEventListener('pagehide', onHide, { once: true });

    const typeWord = async (w) => {
      el.textContent = "";
      for (let k = 0; k < w.length && !stop; k++) {
        el.textContent += w[k];
        await sleep(typeDelay);
      }
    };
    const eraseWord = async () => {
      for (let k = el.textContent.length; k >= 0 && !stop; k--) {
        el.textContent = el.textContent.slice(0, k);
        await sleep(eraseDelay);
      }
    };

    // Fallback initial discret
    if (!el.textContent) el.textContent = initial;

    while (!stop) {
      const w = words[i % words.length];
      if (el.textContent) el.textContent = "";
      await typeWord(w);
      await sleep(holdDelay);
      await eraseWord();
      await sleep(loopDelay);
      i++;
    }
  }

  // -------- Instances --------
  // Hero
  runTyped(
    document.getElementById('typed'),
    [
      "authentiques",
      "vrais",
      "sincères",
      "pudiques",
      "qui font sourire",
      "qui font rire",
      "qui font pleurer",
      "qui font battre le coeur",
      "qui font le quotidien",
      "qui font l'amour",
      "qui font la vie"
    ],
    { initial: "authentiques" }
  );

  // Contact (point collé après le span dans le HTML)
  runTyped(
    document.getElementById('typed-contact'),
    ["vraies","authentiques","sincères","lumineuses"],
    { initial: "vraies" }
  );
})();

