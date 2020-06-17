// xCen r2, Copyright 2009-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xCen(e, r)
{
  if ((e = xGetElementById(e)) == null) return;
  if (!r) { // relative to viewport
    xMoveTo(e,
      xScrollLeft() + (xClientWidth() - e.offsetWidth) / 2,
      xScrollTop() + (xClientHeight() - e.offsetHeight) / 2
    );
  }
  else if (r === true) { // relative to offsetParent
    xMoveTo(e,
      (e.offsetParent.offsetWidth - e.offsetWidth) / 2,
      (e.offsetParent.offsetHeight - e.offsetHeight) / 2
    );
  }
  else { // relative to element r
    if ((r = xGetElementById(r)) == null) return;
    xMoveTo(e,
      xPageX(r) + (r.offsetWidth - e.offsetWidth) / 2,
      xPageY(r) + (r.offsetHeight - e.offsetHeight) / 2
    );
  }
}
