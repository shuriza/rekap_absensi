import "./bootstrap";
import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("canvas.sparkline").forEach((cnv) => {
        // 1. Parse data‐values menjadi array angka
        const raw = cnv.dataset.values || "";
        const vals = raw
            .split(",")
            .map((s) => parseFloat(s))
            .filter((n) => !isNaN(n));

        // Jika kosong, skip
        if (!vals.length) return;
        // Pastikan minimal 2 poin
        if (vals.length === 1) vals.push(vals[0]);

        // 2. Setup canvas
        const ctx = cnv.getContext("2d");
        const width = cnv.width;
        const height = cnv.height;
        ctx.clearRect(0, 0, width, height);

        // 3. Cari min/max untuk normalisasi
        const min = Math.min(...vals);
        const max = Math.max(...vals);
        const range = max - min || 1;

        // 4. Gambar garis sparkline
        ctx.beginPath();
        vals.forEach((v, i) => {
            const x = (i / (vals.length - 1)) * width;
            const y = height - ((v - min) / range) * height;
            i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.lineWidth = 2;
        ctx.strokeStyle = cnv.dataset.color || "#a0aec0";
        ctx.stroke();
    });
});
