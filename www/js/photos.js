
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-photo-series]").forEach(gallery => {
    const base = gallery.dataset.photoSeries.replace(/\/$/, "");
    const count = Number(gallery.dataset.photoCount || 12);
    const label = gallery.dataset.photoLabel || "FIELD PHOTO";
    let loaded = 0;

    const empty = document.createElement("div");
    empty.className = "photo-empty";
    empty.innerHTML = `<span>${label}</span><strong>DROP PHOTOS HERE</strong><small>${base}/01.jpg …</small>`;
    gallery.appendChild(empty);

    for (let i = 1; i <= count; i++) {
      const num = String(i).padStart(2, "0");
      const figure = document.createElement("figure");
      figure.className = "photo-item";
      figure.style.display = "none";

      const img = document.createElement("img");
      img.loading = "lazy";
      img.alt = `${label} ${num}`;
      img.src = `${base}/${num}.jpg`;

      const cap = document.createElement("figcaption");
      cap.textContent = `${label} / ${num}`;

      img.addEventListener("load", () => {
        loaded++;
        figure.style.display = "";
        empty.style.display = "none";
      });

      img.addEventListener("error", () => {
        figure.remove();
      });

      figure.append(img, cap);
      gallery.appendChild(figure);
    }
  });
});
