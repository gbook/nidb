// xHasClass r3, Copyright 2005-2007 Daniel Frechette - modified by Mike Foster
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xHasClass(e, c)
{
  e = xGetElementById(e);
  if (!e || e.className=='') return false;
  var re = new RegExp("(^|\\s)"+c+"(\\s|$)");
  return re.test(e.className);
}
