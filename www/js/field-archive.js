document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-field-archive]").forEach((archive) => {
    const base = archive.dataset.fieldArchive.replace(/\/$/, "");
    const count = Number(archive.dataset.count || 0);

    for (let i = 1; i <= count; i++) {
      const n = String(i).padStart(2, "0");
      const figure = document.createElement("figure");
      figure.className = "archive-item";

      const img = document.createElement("img");
      img.loading = "lazy";
      img.decoding = "async";
      img.src = `${base}/${n}.webp`;
      img.alt = `Fuscherkarkopf — field capture ${n}`;

      const cap = document.createElement("figcaption");
      cap.innerHTML = `<span>FIELD CAPTURE</span><b>${n}</b>`;

      img.addEventListener("click", () => {
        const modal = document.createElement("div");
        modal.className = "photo-modal";
        modal.innerHTML = `<button aria-label="Zavřít">×</button><img src="${img.src}" alt="${img.alt}"><div>FIELD CAPTURE / ${n}</div>`;
        modal.querySelector("button").addEventListener("click", () => modal.remove());
        modal.addEventListener("click", (e) => { if (e.target === modal) modal.remove(); });
        document.addEventListener("keydown", function esc(e){ if(e.key==="Escape"){ modal.remove(); document.removeEventListener("keydown", esc); }});
        document.body.appendChild(modal);
      });

      figure.append(img, cap);
      archive.appendChild(figure);
    }
  });
});
