/*
 * Builds resources/img/pacific-land.svg from Natural Earth 1:50m land (public domain),
 * as packaged by world-atlas@2.0.2 (land-50m.json, TopoJSON). One-off; the output is committed.
 *
 *   curl -sL https://cdn.jsdelivr.net/npm/world-atlas@2.0.2/land-50m.json -o /tmp/land-50m.json
 *   node scripts/map/pacific.mjs /tmp/land-50m.json > resources/img/pacific-land.svg
 *
 * Projection: equirectangular centred on the Pacific (115°E to 65°W, 55°N to 0°), x scaled by
 * cos 30° so mid-latitudes keep their shape. The same function lives in App\Support\PacificMap so
 * the office and work-site markers drawn over it line up with the coastline. The site shows the
 * file as a CSS mask, so the land takes the theme's colour.
 */
import fs from 'node:fs';

const topo = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const [sx, sy] = topo.transform.scale;
const [tx, ty] = topo.transform.translate;
const arcs = topo.arcs.map((arc) => {
    let x = 0, y = 0;
    return arc.map(([dx, dy]) => { x += dx; y += dy; return [x * sx + tx, y * sy + ty]; });
});
const W0 = 115, W1 = 295, N = 55, S = 0, K = 10 * Math.cos(Math.PI / 6), KY = 10;
const lon360 = (lon) => (lon < -30 ? lon + 360 : lon);
const project = ([lon, lat]) => [(lon360(lon) - W0) * K, (N - lat) * KY];
const W = Math.round((W1 - W0) * K), H = (N - S) * KY;

const ring = (ids) => ids.flatMap((i, n) => {
    const a = i < 0 ? [...arcs[~i]].reverse() : arcs[i];
    return n ? a.slice(1) : a;
});
const geoms = topo.objects.land.geometries ?? [topo.objects.land];
const polys = geoms.flatMap((g) => (g.type === 'Polygon' ? [g.arcs] : g.type === 'MultiPolygon' ? g.arcs : []));
let d = '';
for (const poly of polys) {
    for (const r of poly) {
        const pts = ring(r).map(project);
        const xs = pts.map((p) => p[0]), ys = pts.map((p) => p[1]);
        if (Math.max(...xs) < -20 || Math.min(...xs) > W + 20 || Math.max(...ys) < -20 || Math.min(...ys) > H + 20) continue;
        if (Math.max(...xs) - Math.min(...xs) > W * 1.5) continue; // a ring that wraps the globe
        let out = [], px = null, py = null;
        for (const [x, y] of pts) {
            const rx = Math.round(x), ry = Math.round(y);
            if (rx === px && ry === py) continue;
            out.push([rx, ry]); px = rx; py = ry;
        }
        if (out.length < 3) {
            // A small island: keep it as a dot so Guam, Saipan and Yap still show.
            const [x, y] = out[0];
            d += `M${x - 2} ${y}a2 2 0 1 0 4 0a2 2 0 1 0-4 0z`;
            continue;
        }
        d += 'M' + out[0].join(' ');
        for (let i = 1; i < out.length; i++) d += 'l' + (out[i][0] - out[i - 1][0]) + ' ' + (out[i][1] - out[i - 1][1]);
        d += 'z';
    }
}
d = d.replace(/ -/g, '-');
process.stdout.write(`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}"><!-- Natural Earth 1:50m land (public domain), via scripts/map/pacific.mjs --><path d="${d}"/></svg>\n`);
console.error(`viewBox 0 0 ${W} ${H}, ${d.length} bytes`);
