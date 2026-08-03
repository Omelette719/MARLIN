import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import leafletImage from 'leaflet-image';

const STATUS_LABEL = {
    belum: 'Belum Dikerjakan',
    urgent: 'Urgent',
    tertunda: 'Tertunda (Kendala)',
    menunggu_validasi: 'Menunggu Validasi',
    revisi: 'Revisi',
    selesai: 'Selesai',
    batal: 'Batal',
};

const KONDISI_LABEL = {
    baik: 'Baik',
    rusak: 'Rusak',
};

const PIN_CARD_WIDTH = 220;

// Colors mirror the priority rules from IMPLEMENTATION_SPEC.md §4, with one
// deliberate deviation: spec says red (urgent/prioritas/tinggi) overrides
// every other color, but menunggu_validasi is checked first here instead —
// once a report is already submitted, the pin should show that progress
// regardless of the SPK's urgency, rather than staying red the whole wait.
// computed client-side per spec's non-functional note (§7): never in the DB query.
function pinColor(pin) {
    const spk = pin.spk;

    if (pin.status === 'menunggu_validasi') {
        return '#22d3ee';
    }

    if (pin.status === 'urgent' || (spk && (spk.prioritas || spk.urgensi === 'tinggi'))) {
        return '#ba1a1a';
    }

    if (pin.kondisi_terkini === 'rusak' || (pin.jenis_pekerjaan === 'perbaikan' && pin.status !== 'selesai')) {
        return '#eab308';
    }

    if ((pin.status === 'selesai' || pin.status === null) && pin.kondisi_terkini === 'baik') {
        return '#004655';
    }

    return '#9ca3af';
}

// Terpasang & kondisi baik is the only "calm" state — no radar-ping warning needed.
function isPinTenang(pin) {
    return (pin.status === 'selesai' || pin.status === null) && pin.kondisi_terkini === 'baik';
}

function pinShapeClass(pin) {
    return pin.bentuk_ikon === 'kotak' ? 'rounded-lg' : 'rounded-full';
}

// Icons are self-colored images (own fill baked in, like a real sign) and are
// rendered directly with a transparent backdrop — no synthetic colored box
// behind them. Status is instead conveyed by a soft pulsing glow that sits
// behind the icon, so it never masks the icon's own artwork.
function markerHtml(pin) {
    const color = pinColor(pin);
    const shape = pinShapeClass(pin);
    const tenang = isPinTenang(pin);

    const iconInner = pin.ikon
        ? `<img src="${pin.ikon}" style="width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 1px 3px rgba(0,0,0,0.45));" />`
        : `<div class="${shape}" style="width:70%;height:70%;margin:15%;background:${color};border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.35);"></div>`;

    const glow = tenang
        ? ''
        : `<div class="marker-radar ${shape}" style="--marker-color:${color}"></div>`;

    return `<div style="position:relative;width:36px;height:36px;">`
        + glow
        + `<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">`
        + iconInner
        + `</div>`
        + `</div>`;
}

// A standalone image (plain color/shape SVG data URI, or the pin's own real
// icon file) representing this marker for the PDF map-image export — see
// unduhPetaGambarPdf(). Kept separate from markerHtml() because the export
// capture (leaflet-image) draws each marker from a single <img>-like source
// rather than the live styled DOM, so it can't reuse the HTML/CSS above
// (divs with a background-color aren't image sources) or the "radar" glow
// animation (meaningless in a still image).
function pinCaptureIconSrc(pin) {
    if (pin.ikon) {
        return pin.ikon;
    }

    const color = pinColor(pin);
    const shape = pin.bentuk_ikon === 'kotak'
        ? '<rect x="2" y="2" width="22" height="22" rx="5" fill="' + color + '" stroke="white" stroke-width="2"/>'
        : '<circle cx="13" cy="13" r="11" fill="' + color + '" stroke="white" stroke-width="2"/>';

    return 'data:image/svg+xml;utf8,' + encodeURIComponent(
        `<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26">${shape}</svg>`
    );
}

function infoRow(label, value) {
    if (! value) {
        return '';
    }

    return `<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:6px 12px;border-top:1px solid #ececec;">`
        + `<span style="flex-shrink:0;color:#71797d;font-size:11.5px;">${label}</span>`
        + `<strong style="flex:1;min-width:0;text-align:right;font-size:11.5px;color:#1f2937;overflow-wrap:break-word;">${value}</strong>`
        + `</div>`;
}

function googleMapsUrl(lat, lng) {
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
}

// Popup is styled as a compact card: image spans the full width at the top —
// the rambu's own real photo (survey/after-work), never the jenis_rambu icon
// graphic — followed by structured label/value rows and action buttons.
// SPK/task info is intentionally left out here (kept to the Detail Rambu page)
// to keep the popup short.
function pinPopupHtml(pin, rambuDetailUrlTemplate, temuanUrlTemplate) {
    const statusLabel = pin.status ? (STATUS_LABEL[pin.status] ?? pin.status) : 'Terpasang, tidak ada tugas aktif';
    const kondisiLabel = KONDISI_LABEL[pin.kondisi_terkini] ?? pin.kondisi_terkini;
    const photo = pin.foto ?? pin.ikon;

    const image = photo
        ? `<div style="width:100%;height:95px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;overflow:hidden;">`
            + `<img src="${photo}" style="width:100%;height:100%;object-fit:${pin.foto ? 'cover' : 'contain'};${pin.foto ? '' : 'padding:14px;box-sizing:border-box;'}" /></div>`
        : '';

    let rows = infoRow('Jenis Rambu', pin.jenis_rambu ?? 'Rambu')
        + infoRow('Lokasi', `${pin.wilayah}, ${pin.lokasi}`)
        + infoRow('Koordinat', `${pin.lat.toFixed(6)}, ${pin.lng.toFixed(6)}`);

    // A sign not yet installed has no real-world condition to report yet —
    // showing "Kondisi: Baik" for it would be misleading.
    if (pin.sudah_terpasang) {
        rows += infoRow('Kondisi', kondisiLabel);
    }

    rows += infoRow('Status', statusLabel);

    const rambuDetailUrl = rambuDetailUrlTemplate ? rambuDetailUrlTemplate.replace('__ID__', pin.id) : null;

    const buttons = `<div style="display:flex;gap:6px;padding:8px 12px;border-top:1px solid #ececec;">`
        + (rambuDetailUrl
            ? `<a href="${rambuDetailUrl}" style="flex:1;text-align:center;background:#004655;color:white;border-radius:6px;padding:5px 0;font-size:11.5px;font-weight:600;text-decoration:none;">Detail Rambu</a>`
            : '')
        + `<a href="${googleMapsUrl(pin.lat, pin.lng)}" target="_blank" rel="noopener" style="flex:1;text-align:center;background:#f3f4f6;color:#1f2937;border-radius:6px;padding:5px 0;font-size:11.5px;font-weight:600;text-decoration:none;">Google Maps</a>`
        + `</div>`;

    let temuanButton = '';

    if (temuanUrlTemplate) {
        const temuanUrl = temuanUrlTemplate.replace('__ID__', pin.id);
        temuanButton = `<div style="padding:0 12px 8px;">`
            + `<a href="${temuanUrl}" style="display:block;text-align:center;background:#eab308;color:#1f2937;border-radius:6px;padding:5px 0;font-size:11.5px;font-weight:600;text-decoration:none;">Lapor Temuan Kondisi</a></div>`;
    }

    const closeButton = `<button type="button" class="rambu-tooltip-close" aria-label="Tutup" `
        + `style="position:absolute;top:6px;right:6px;width:22px;height:22px;border-radius:9999px;border:none;`
        + `background:rgba(0,0,0,0.55);color:#fff;font-size:14px;line-height:1;cursor:pointer;`
        + `display:flex;align-items:center;justify-content:center;padding:0;">&times;</button>`;

    return `<div style="position:relative;width:${PIN_CARD_WIDTH}px;background:#fff;border-radius:0.75rem;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.18);">${closeButton}${image}${rows}${buttons}${temuanButton}</div>`;
}

function toDms(value, positiveSuffix, negativeSuffix) {
    const abs = Math.abs(value);
    const degrees = Math.floor(abs);
    const minutesFloat = (abs - degrees) * 60;
    const minutes = Math.floor(minutesFloat);
    const seconds = Math.round((minutesFloat - minutes) * 60);
    const suffix = value >= 0 ? positiveSuffix : negativeSuffix;

    return `${degrees}° ${String(minutes).padStart(2, '0')}' ${String(seconds).padStart(2, '0')}" ${suffix}`;
}

function formatCoordinate(lat, lng) {
    return `${toDms(lat, 'N', 'S')}  ${toDms(lng, 'E', 'W')}`;
}

let mapInstance = null;

window.initPetaRambu = function (containerId, dataUrl, coordDisplayId, rambuDetailUrlTemplate, focusId, temuanUrlTemplate, hideTenang = false) {
    const container = document.getElementById(containerId);

    if (! container) {
        return;
    }

    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }

    const map = L.map(containerId).setView([-3.3194, 114.5908], 13);
    mapInstance = map;

    // CARTO's basemap tiles, not the raw tile.openstreetmap.org servers —
    // OSM's own tile servers actively throttle/deny non-browser or
    // high-volume traffic per their tile usage policy (confirmed via an
    // `x-blocked` response header when probing them directly), which is
    // exactly what silently broke the PDF map-image export: the canvas
    // capture in unduhPetaGambarPdf() needs every tile to load cleanly
    // through CORS, and OSM's blocking made that fail every time even
    // though the tiles still displayed fine for ordinary browsing.
    // CARTO's tiles are explicitly meant to be used this way (proper
    // Access-Control-Allow-Origin, no blocking) and are still OSM data
    // underneath, just served through a CDN that supports this.
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19,
        crossOrigin: true,
    }).addTo(map);

    if (coordDisplayId) {
        const coordEl = document.getElementById(coordDisplayId);

        if (coordEl) {
            map.on('mousemove', (e) => {
                coordEl.textContent = formatCoordinate(e.latlng.lat, e.latlng.lng);
            });

            map.on('mouseout', () => {
                coordEl.textContent = 'Gerakkan kursor di peta';
            });
        }
    }

    fetch(dataUrl)
        .then((res) => res.json())
        .then((pins) => {
            // Dashboard's compact map only cares about pins needing attention —
            // already-installed-and-fine ("tenang") pins would just be clutter there.
            if (hideTenang) {
                pins = pins.filter((pin) => ! isPinTenang(pin));
            }

            let focusMarker = null;

            pins.forEach((pin) => {
                const icon = L.divIcon({
                    html: markerHtml(pin),
                    className: 'marker-icon-wrapper',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18],
                });

                const marker = L.marker([pin.lat, pin.lng], { icon }).addTo(map);
                // Stashed for unduhPetaGambarPdf() — see pinCaptureIconSrc().
                marker._captureIconSrc = pinCaptureIconSrc(pin);

                // A bound Tooltip's content sits under the same click-dispatch path as
                // the marker itself, so links/buttons inside it never reliably receive
                // their own click — Leaflet's marker click-to-toggle handler always
                // wins the race. bindPopup doesn't have that problem: Popup already
                // calls L.DomEvent.disableClickPropagation on its own container
                // (see Leaflet's Popup._initLayout), and marker+popup click-to-toggle
                // (open if closed, close if open) is Leaflet's own built-in default
                // behavior, so no custom click wiring is needed here at all.
                marker.bindPopup(pinPopupHtml(pin, rambuDetailUrlTemplate, temuanUrlTemplate), {
                    className: 'rambu-tooltip',
                    closeButton: false,
                    autoPan: true,
                    minWidth: PIN_CARD_WIDTH,
                    maxWidth: PIN_CARD_WIDTH,
                });

                // Unlike Tooltip, Popup has no built-in left/right direction:'auto' —
                // it always centers itself above its anchor. Recreate the "open toward
                // whichever side has more room" behavior by hand on every open: measure
                // the marker's on-screen X position against the map's current width,
                // then push the card fully to that side and vertically re-center it on
                // the pin icon (36px tall, anchored at its own center) using its actual
                // rendered height. Recomputed on every open since panning/zooming
                // between opens can change which side has more room.
                marker.on('popupopen', () => {
                    const popup = marker.getPopup();
                    const el = popup?.getElement();

                    if (! el) {
                        return;
                    }

                    const iconRadius = 18;
                    const gap = 10;
                    const point = map.latLngToContainerPoint(marker.getLatLng());
                    const openLeft = point.x > map.getSize().x / 2;
                    const xOffset = (PIN_CARD_WIDTH / 2 + iconRadius + gap) * (openLeft ? -1 : 1);

                    popup.options.offset = L.point(xOffset, el.offsetHeight / 2);
                    popup.update();

                    el.querySelector('.rambu-tooltip-close')?.addEventListener('click', () => marker.closePopup());
                });

                if (focusId && pin.id === focusId) {
                    focusMarker = marker;
                }
            });

            if (focusMarker) {
                map.setView(focusMarker.getLatLng(), 17);
                focusMarker.openPopup();
            }
        });
};

// Rasterizes the currently-live map (via leaflet-image, which redraws each
// tile/marker onto a canvas by walking Leaflet's internal layer objects)
// and POSTs it together with exportUrl's own query-string filters to the
// PDF export endpoint, then triggers a normal file download from the
// response. A plain <a href> link can't do this — the endpoint needs the
// actual pixels of what's on screen right now, not just the filter params,
// since dompdf can't render Leaflet itself.
//
// Two other approaches were tried and rejected before landing here, both
// for concrete reasons hit in testing rather than guesses:
// - Screenshotting the map container's DOM directly (html2canvas, then
//   modern-screenshot) sidesteps leaflet-image's marker bug below, but
//   both choked on Tailwind v4's `oklch()` colors, which show up in this
//   app's computed styles everywhere (even a 0px, inherited border-color
//   counts). html2canvas's bundled CSS parser threw outright on them.
//   modern-screenshot's SVG-serialization approach didn't throw, but the
//   resulting `data:image/svg+xml` silently failed to load as an image at
//   all — the capture "succeeded" with a blank canvas every time.
// - leaflet-image itself crashes on this app's pins specifically: it
//   assumes every marker icon is an `<img>`-backed L.Icon and reads
//   `marker._icon.src` unconditionally, but our pins use L.divIcon (a
//   colored circle drawn with CSS, not an image file) — `_icon` is a plain
//   `<div>` with no `.src`, so it threw `Cannot read properties of
//   undefined (reading 'match')` on every export. Fixed below by giving
//   every marker a real image source (its own icon file, or a synthesized
//   circle/rounded-square SVG matching pinColor()) before capture — see
//   pinCaptureIconSrc() and the marker creation loop above, which stashes
//   it as `marker._captureIconSrc`.
//
// Falls back to a PDF with no map image (rather than failing the whole
// export) if the capture itself errors out or times out.
window.unduhPetaGambarPdf = function (exportUrl, tombolId) {
    const tombol = tombolId ? document.getElementById(tombolId) : null;
    const teksAsli = tombol?.textContent;

    const setSedangProses = (pesan) => {
        if (tombol) {
            tombol.disabled = true;
            tombol.textContent = pesan;
        }
    };

    const selesai = () => {
        if (tombol) {
            tombol.disabled = false;
            tombol.textContent = teksAsli;
        }
    };

    if (! mapInstance) {
        window.open(exportUrl, '_blank');
        return;
    }

    setSedangProses('Menyiapkan gambar peta...');

    // DivIcon markers' _icon is a <div>, which never has a real .src to
    // begin with — set one now (harmless expando property, doesn't touch
    // rendering) so leaflet-image's marker handling has something to load
    // instead of crashing on undefined.
    mapInstance.eachLayer((layer) => {
        if (layer instanceof L.Marker && layer._captureIconSrc && layer._icon && ! layer._icon.src) {
            layer._icon.src = layer._captureIconSrc;
        }
    });

    let selesaiDipanggil = false;

    const jatuhKeFallback = (alasan) => {
        if (selesaiDipanggil) {
            return;
        }

        selesaiDipanggil = true;
        console.error('Gagal mengambil gambar peta, PDF akan dibuat tanpa gambar peta:', alasan);
        kirimPetaPdf(exportUrl, null, setSedangProses, selesai);
    };

    // leaflet-image has no timeout of its own — if even a single tile or
    // marker image never finishes loading, its callback simply never
    // fires. Race it against a timeout so the export always completes one
    // way or another instead of leaving the button stuck on "Menyiapkan
    // gambar peta..." forever.
    const timeoutId = setTimeout(() => jatuhKeFallback('timeout menyiapkan gambar peta'), 15000);

    leafletImage(mapInstance, (err, canvas) => {
        if (selesaiDipanggil) {
            return;
        }

        clearTimeout(timeoutId);

        if (err || ! canvas) {
            jatuhKeFallback(err);
            return;
        }

        try {
            canvas.toBlob((blob) => {
                selesaiDipanggil = true;
                kirimPetaPdf(exportUrl, blob, setSedangProses, selesai);
            }, 'image/png');
        } catch (e) {
            // Canvas tainted by a tile/marker image that loaded without
            // valid CORS headers — toBlob() throws synchronously in that case.
            jatuhKeFallback(e);
        }
    });
};

function kirimPetaPdf(exportUrl, gambarBlob, setSedangProses, selesai) {
    setSedangProses('Membuat PDF...');

    const formData = new FormData();

    if (gambarBlob) {
        formData.append('gambar_peta', gambarBlob, 'peta.png');
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(exportUrl, {
        method: 'POST',
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        body: formData,
    })
        .then((res) => {
            if (! res.ok) {
                throw new Error('Server merespons dengan status ' + res.status);
            }

            return res.blob();
        })
        .then((blob) => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'peta-rambu-' + new Date().toISOString().slice(0, 10) + '.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch((e) => {
            alert('Gagal mengunduh PDF peta: ' + e.message);
        })
        .finally(selesai);
}
