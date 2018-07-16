// xAniWH r1, Copyright 2009 Thomas Nabet (babna.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xAniWH(e, w, h, t)
{
  if (!(e=xGetElementById(e))) return;
  var w0 = xWidth(e), h0 = xHeight(e); // start positions
  var dw = w - w0, dh = h - h0; // displacements
  var fq = 1 / t; // frequench
  var t0 = new Date().getTime(); // start time
  var tmr = setInterval(
    function() {
      var wi = w, hi = h;
      var et = new Date().getTime() - t0; // elapsed time
      if (et < t) {
        var f = et * fq; // constant velocity
        wi = f * dw + w0; // instantaneous positions
        hi = f * dh + h0;
      }
      else { clearInterval(tmr); }
      e.style.width = Math.round(wi) + 'px';
      e.style.height = Math.round(hi) + 'px';
    }, 20 // timer resolution
  );
}
