// xAniOpacity r1, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xAniOpacity(e, o, t, a, oe)
{
  if (!(e=xGetElementById(e))) return;
  var o0 = xOpacity(e); // start value
  var dx = o - o0; // displacement
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
        xOpacity(e, f * dx + o0); // instantaneous value
      }
      else {
        clearInterval(tmr);
        xOpacity(e, o); // target value
        if (typeof oe == 'function') oe(); // 'onEnd' handler
        else if (typeof oe == 'string') eval(oe);
      }
    }, 10 // timer resolution
  );
}
