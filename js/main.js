// menu
// main.js
document.addEventListener("DOMContentLoaded", () => {
  const btnBurger = document.querySelector(".hamburger");
  let navMenu = document.querySelector(".nav-menu"); // ← let ici
  const hero = document.querySelector("#hero");

  // Si la nav n'existe pas dans le HTML, on peut l'injecter dynamiquement :
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

  // Scroll listener pour colorer le hamburger
  window.addEventListener("scroll", () => {
    // true quand le bas du hero est au-dessus du viewport
    const pastHero = hero.getBoundingClientRect().bottom <= 0;
    btnBurger.classList.toggle("scrolled", pastHero);
  });

  btnBurger.addEventListener("click", () => {
    const expanded = btnBurger.getAttribute("aria-expanded") === "true";
    btnBurger.setAttribute("aria-expanded", String(!expanded));
    navMenu.setAttribute("aria-hidden", String(expanded));

    btnBurger.classList.toggle("is-active");
    navMenu.classList.toggle("is-visible");
  });
});

/*--------------------------------------------------
  main.js — interactions de base (v2)
  • Smooth collapsible "En savoir plus"
--------------------------------------------------*/
(function () {
  const btn = document.querySelector(".btn-toggle-more");
  const panel = document.getElementById("about-more");
  if (!btn || !panel) return;

  // Utility to set max-height for smooth animation
  const setMaxHeight = (el, toExpand) => {
    if (toExpand) {
      el.hidden = false;
      const fullHeight = el.scrollHeight;
      el.style.maxHeight = fullHeight + "px";
    } else {
      const fullHeight = el.scrollHeight; // current height before collapsing
      el.style.maxHeight = fullHeight + "px";
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
      // hide after animation ends to remove from tab order
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

/* Ajuste la hauteur mini de la galerie pour remplir le viewport */
function fitGallery() {
  const footer = document.querySelector(".site-footer");
  const gallery = document.getElementById("galerie");
  if (!footer || !gallery) return;

  const h = footer.offsetHeight;
  gallery.style.minHeight = `calc(100vh - ${h}px)`;
}

window.addEventListener("load", fitGallery);
window.addEventListener("resize", fitGallery);
