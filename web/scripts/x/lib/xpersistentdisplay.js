// xPersistentDisplay r1, Copyright 2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
var xPersistentDisplay = new function()
{
  xAddEventListener(window, 'load',
    function() {
      xEachN('xpd-chk-', 0, get(),
        function(c, n, a) {
          var p = per(a, n);
          if (p !== 0) {
            c.checked = p;
          }
          c._xpd_index = n;
          c.onclick = clk;
          c.onclick();
        }
      );
    }
  );
  // checkbox click handler
  function clk() {
    var i = this._xpd_index, v = this.checked;
    dsp(i, v);
    set(i, v);
  }
  // set display to block or none
  function dsp(n, b) {
    var a, i, v = (b ? 'block' : 'none');
    a = xGetElementsByClassName('xpd-ele-' + n);
    if (a && a.length) {
      for (i = 0; i < a.length; ++i) {
        a[i].style.display = v;
      }
      return true;
    }
    return false;
  }
  // is n persistent?
  function per(a, n) {
    if (a && n < a.length) {
      return (a[n] == 'true');
    }
    return 0;
  }
  // get cookie array
  function get() {
    var cs, ca = null;
    cs = xCookie.get('xpd-list');
    if (cs) {
      ca = cs.split(',');
    }
    return ca;
  }
  // save value at index in cookie
  function set(i, v) {
    var ca = get();
    if (!ca) {
      ca = [];
    }
    ca[1 * i] = v;
    xCookie.set('xpd-list', ca.join(','), 365);
  }
};
