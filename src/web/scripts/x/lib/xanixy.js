// xAniXY r1, Copyright 2006-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xAniXY(e, x, y, t)
{
  if (!(e=xGetElementById(e))) return;
  var x0 = xLeft(e), y0 = xTop(e); // start positions
  var dx = x - x0, dy = y - y0; // displacements
  var fq = 1 / t; // frequency
  var t0 = new Date().getTime(); // start time
  var tmr = setInterval(
    function() {
      var xi = x, yi = y;
      var et = new Date().getTime() - t0; // elapsed time
      if (et < t) {
        var f = et * fq; // constant velocity
        xi = f * dx + x0; // instantaneous positions
        yi = f * dy + y0;
      }
      else { clearInterval(tmr); }
      e.style.left = Math.round(xi) + 'px';
      e.style.top = Math.round(yi) + 'px';
    }, 10 // timer resolution
  );
}
