// xAniRgb r1, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xAniRgb(e, p, c, t, a, oe)
{
  if (!(e=xGetElementById(e))) return;
  var c0 = xParseColor(xGetComputedStyle(e, p)); // start colors
  p = xCamelize(p);
  c = xParseColor(c); // target colors
  var d = { r: c.r - c0.r, g: c.g - c0.g, b: c.b - c0.b }; // color displacements
  var fq = 1 / t; // frequency
  if (a) fq *= (Math.PI / 2);
  var t0 = new Date().getTime(); // start time
  var tmr = setInterval(
    function() {
      var et = new Date().getTime() - t0; // elapsed time
      if (et < t) {
        var f = et * fq; // constant velocity
        if (a == 1) f = Math.sin(f); // sine acceleration
        else if (a == 2) f = 1 - Math.cos(f); // cosine acceleration
        f = Math.abs(f);
        e.style[p] = xRgbToHex( // instantaneous colors
          Math.round(f * d.r + c0.r),
          Math.round(f * d.g + c0.g),
          Math.round(f * d.b + c0.b));
      }
      else {
        clearInterval(tmr); // stop iterations
        e.style[p] = c.s; // target color
        if (typeof oe == 'function') oe(); // 'onEnd' handler
        else if (typeof oe == 'string') eval(oe);
      }
    }, 10 // timer interval
  );
}
