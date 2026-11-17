/* Op Android opent "Bestand kiezen" de bestandsbeheerder (met mappen en sorteren) in plaats van de Foto's-lijst.
   Dat gebeurt als het uploadvak geen "image/*" of "video/*" vraagt maar de bestandstypes bij naam. */
(function () {
    if (!/Android/i.test(navigator.userAgent)) return;
    var MAP = {
        'image/*': '.jpg,.jpeg,.png,.webp,.gif,.svg,.avif,.bmp',
        'image/jpeg': '.jpg,.jpeg', 'image/png': '.png', 'image/webp': '.webp', 'image/gif': '.gif', 'image/svg+xml': '.svg',
        'video/*': '.mp4,.webm,.mov,.m4v', 'video/mp4': '.mp4,.m4v', 'video/webm': '.webm'
    };
    function fix(inp) {
        if (inp.dataset.fp) return; inp.dataset.fp = '1';
        var a = inp.getAttribute('accept'); if (!a || !/(image|video)\//.test(a)) return;
        var out = [];
        a.split(',').forEach(function (t) {
            t = t.trim(); if (!t) return;
            (MAP[t] ? MAP[t].split(',') : [t]).forEach(function (x) { if (x.charAt(0) === '.' && out.indexOf(x) < 0) out.push(x); });
        });
        if (out.length) inp.setAttribute('accept', out.join(','));
    }
    function all() { document.querySelectorAll('input[type=file]').forEach(fix); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', all); else all();
    try { new MutationObserver(all).observe(document.documentElement, { childList: true, subtree: true }); } catch (e) {}
})();
