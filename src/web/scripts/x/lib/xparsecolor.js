// xParseColor r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xParseColor(c)
{
  var o = {};
  if (xStr(c)) {
    if (c.indexOf('rgb')!=-1) {
      var a = c.match(/(\d*)\s*,\s*(\d*)\s*,\s*(\d*)/);
      o.r = parseInt(a[1]) || 0;
      o.g = parseInt(a[2]) || 0;
      o.b = parseInt(a[3]) || 0;
      o.n = (o.r << 16) | (o.g << 8) | o.b;
    }
    else {
      pn(parseInt(c.substr(1), 16));
    }
  }
  else {
    pn(c);
  }
  o.s = xHex(o.n, 6, '#');
  return o;
  function pn(n) { // parse num
    o.n = n || 0;
    o.r = (o.n & 0xFF0000) >> 16;
    o.g = (o.n & 0xFF00) >> 8;
    o.b = o.n & 0xFF;
  }
}
