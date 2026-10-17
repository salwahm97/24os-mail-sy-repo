/*
 * 24os Mail Sy – tiny runtime shim for the prebuilt Angular apps.
 *  1. Points API calls (hard-coded production/dev URL in the build) to THIS WordPress site.
 *  2. Adds the WordPress REST nonce (X-WP-Nonce) for the logged-in admin dashboard.
 * Works with pretty permalinks and with "plain" permalinks (index.php?rest_route=...).
 */
(function () {
  var BASES = [
    'https://example.com/wp-json/24os-mail-sy/v1',
    'http://localhost/wordpress/wp-json/24os-mail-sy/v1'
  ];
  var cfg = window.OS24_MAIL_SY || {};
  var root = cfg.root || '';

  if (!root) { // iframes (order form / tracker): ?rest=<REST root> given by the shortcode
    try {
      var p = new URLSearchParams(location.search).get('rest');
      if (p) {
        var u = new URL(p, location.href);
        if (u.origin === location.origin) { root = u.href; } // same origin only
      }
    } catch (e) {}
  }
  if (!root) { // fallback: derive from /wp-content/ location
    var i = location.pathname.indexOf('/wp-content/');
    if (i > -1) { root = location.origin + location.pathname.slice(0, i) + '/index.php?rest_route=/24os-mail-sy/v1'; }
  }
  if (!root) { return; }
  root = root.replace(/\/+$/, '');

  function rewrite(url) {
    var str = String(url);
    for (var k = 0; k < BASES.length; k++) {
      if (str.indexOf(BASES[k]) === 0) {
        var rest = str.slice(BASES[k].length);
        if (root.indexOf('?') > -1) { rest = rest.replace('?', '&'); }
        return root + rest;
      }
    }
    return null;
  }

  var xhrOpen = XMLHttpRequest.prototype.open;
  var xhrSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (method, url) {
    var nu = rewrite(url);
    if (nu !== null) { arguments[1] = nu; this.__os24 = true; }
    return xhrOpen.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function () {
    if (this.__os24 && cfg.nonce) {
      try { this.setRequestHeader('X-WP-Nonce', cfg.nonce); } catch (e) {}
    }
    return xhrSend.apply(this, arguments);
  };

  if (window.fetch) {
    var f = window.fetch;
    window.fetch = function (input, init) {
      var url = typeof input === 'string' ? input : (input && input.url);
      var nu = url ? rewrite(url) : null;
      if (nu !== null) {
        init = init || {};
        init.headers = new Headers(init.headers || (typeof input !== 'string' && input.headers) || {});
        if (cfg.nonce) { init.headers.set('X-WP-Nonce', cfg.nonce); }
        input = typeof input === 'string' ? nu : new Request(nu, input);
      }
      return f.call(this, input, init);
    };
  }
})();
