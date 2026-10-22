
document.addEventListener("DOMContentLoaded", () => {
  const buttons = document.querySelectorAll("[data-gear-filter]");
  const cards = document.querySelectorAll(".gear2-card[data-cat]");
  buttons.forEach(btn => btn.addEventListener("click", () => {
    buttons.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    const f = btn.dataset.gearFilter;
    cards.forEach(card => {
      const cats = card.dataset.cat || "";
      card.hidden = f !== "all" && !cats.includes(f);
    });
  }));

  document.querySelectorAll("[data-gear-photos]").forEach(gallery => {
    const base = gallery.dataset.gearPhotos.replace(/\/$/, "");
    const count = Number(gallery.dataset.gearCount || 12);
    const label = gallery.dataset.gearLabel || "GEAR";
    let loaded = 0;

    const empty = document.createElement("div");
    empty.className = "gear-photo-empty";
    empty.innerHTML = `<small>FOTOGRAFICKÝ ARCHIV</small><b>Fotografie zatím nejsou zveřejněné</b><span>Technické parametry najdete výše.</span>`;
    gallery.appendChild(empty);

    for (let i=1;i<=count;i++) {
      const n = String(i).padStart(2,"0");
      const fig = document.createElement("figure");
      fig.className = "gear-photo";
      fig.hidden = true;
      const img = document.createElement("img");
      img.loading = "lazy";
      img.decoding = "async";
      img.src = `${base}/${n}.webp`;
      img.alt = `${label} — gear photo ${n}`;
      const cap = document.createElement("figcaption");
      cap.textContent = `${label} / ${n}`;
      img.onload = () => { loaded++; fig.hidden=false; empty.hidden=true; };
      img.onerror = () => fig.remove();
      fig.append(img,cap);
      gallery.appendChild(fig);
    }
  });
});
