// xCookie r2, Copyright 2009 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
var xCookie = {
  get: function(name) {
    var c = document.cookie.match(new RegExp('(^|;)\\s*' + name + '=([^;\\s]*)'));  
    return ((c && c.length >= 3) ? unescape(c[2]) : null);  
  },
  set: function(name, value, days, path, domain, secure) {
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + (days * 8.64e7)); // now + days in milliseconds
    }
    document.cookie = name + '=' + escape(value) +
      (days ? ('; expires=' + d.toGMTString()) : '') +
      '; path=' + (path || '/') +
      (domain ? ('; domain=' + domain) : '') +
      (secure ? '; secure' : '');
  },
  del: function(name, path, domain) {
    this.set(name, '', -1, path, domain); // sets expiry to now - 1 day
  },
  obj = function(cn, s, d2) {
    var i, a, b, cv, o = {};
    cv = this.get(cn);
    if (cv) {
      a = cv.split(s);
      for (i = 0; i < a.length; ++i) {
        b = a[i].split(d2);
        o[b[0]] = b[1];
      }
    }
    return o;
  }
};
