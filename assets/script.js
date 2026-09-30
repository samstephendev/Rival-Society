document.addEventListener("DOMContentLoaded", () => {
  const header   = document.getElementById("header");
  const bar      = document.querySelector(".bar");
  const sections = document.querySelectorAll("section");
  const navItems = document.querySelectorAll(".nav-item");

  if (!header || !bar) return;

  let lastScroll = window.scrollY;
  const stickyOffset = bar.offsetTop;

  window.addEventListener("scroll", () => {
    const currentScroll = window.scrollY;

    /* =========================
       HEADER COLLAPSE / EXPAND
    ========================= */
    if (currentScroll > lastScroll && currentScroll > 60) {
      header.classList.add("collapsed");
    } else if (currentScroll < lastScroll && currentScroll < 200) {
      header.classList.remove("collapsed");
    }

    lastScroll = currentScroll;

    /* =========================
       STICKY NAVBAR
    ========================= */
    if (currentScroll >= stickyOffset) {
      bar.classList.add("fixed");
    } else {
      bar.classList.remove("fixed");
    }

    /* =========================
       ACTIVE NAV ITEM
    ========================= */
    let currentSection = "";

    sections.forEach(section => {
      const sectionTop = section.offsetTop - 140;
      if (currentScroll >= sectionTop) {
        currentSection = section.id;
      }
    });

    navItems.forEach(item => {
      item.classList.toggle(
        "active",
        item.dataset.target === currentSection
      );
    });
  });

  /* =========================
     NAV CLICK SMOOTH SCROLL
  ========================= */
  navItems.forEach(item => {
    item.addEventListener("click", e => {
      e.preventDefault();

      const id = item.dataset.target;
      const target = document.getElementById(id);
      if (!target) return;

      if (header) {
        header.classList.remove("expanded");
      }

      const headerHeight = header ? header.offsetHeight : 0;
      const navHeight = bar ? bar.offsetHeight : 0;
      const offset = headerHeight + navHeight;

      const y =
        target.getBoundingClientRect().top +
        window.pageYOffset -
        offset;

      window.scrollTo({
        top: y,
        behavior: "smooth"
      });
    });
  });
});
