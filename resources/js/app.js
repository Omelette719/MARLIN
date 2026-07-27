import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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

    return `<div style="position:relative;width:220px;background:#fff;border-radius:0.75rem;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.18);">${closeButton}${image}${rows}${buttons}${temuanButton}</div>`;
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

window.initPetaRambu = function (containerId, dataUrl, coordDisplayId, rambuDetailUrlTemplate, focusId, temuanUrlTemplate) {
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

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
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
            let focusMarker = null;

            pins.forEach((pin) => {
                const icon = L.divIcon({
                    html: markerHtml(pin),
                    className: 'marker-icon-wrapper',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18],
                });

                const marker = L.marker([pin.lat, pin.lng], { icon }).addTo(map);

                // direction: 'auto' picks whichever side (left/right) has more
                // room in the current viewport, so the card doesn't open off-screen.
                // offset pushes it away from the pin's own icon — without it the
                // card (and its little direction arrow) sits flush against the
                // icon, covering part of it.
                marker.bindTooltip(pinPopupHtml(pin, rambuDetailUrlTemplate, temuanUrlTemplate), {
                    direction: 'auto',
                    offset: [16, 0],
                    interactive: true,
                    opacity: 1,
                    className: 'rambu-tooltip',
                });

                // bindTooltip's own mouseover/mouseout/click wiring is replaced with a
                // single click-to-toggle: tapping the pin opens the card, tapping it
                // again (or the X button inside it) closes it. No hover involved.
                marker.off('mouseover mouseout click');

                marker.on('click', () => {
                    if (marker.isTooltipOpen()) {
                        marker.closeTooltip();
                    } else {
                        marker.openTooltip();
                    }
                });

                marker.on('tooltipopen', () => {
                    const el = marker.getTooltip()?.getElement();

                    if (! el) {
                        return;
                    }

                    // Leaflet treats an interactive tooltip's element as belonging to
                    // its marker for click purposes, so without this, clicking any
                    // link/button inside the card (Detail Rambu, Google Maps, Lapor
                    // Temuan) also re-fires the marker's own click-to-toggle handler
                    // above — closing the tooltip out from under the click before the
                    // link's navigation can happen.
                    L.DomEvent.disableClickPropagation(el);

                    el.querySelector('.rambu-tooltip-close')?.addEventListener('click', () => marker.closeTooltip());
                });

                if (focusId && pin.id === focusId) {
                    focusMarker = marker;
                }
            });

            if (focusMarker) {
                map.setView(focusMarker.getLatLng(), 17);
                focusMarker.openTooltip();
            }
        });
};
