// xAddClass r3, Copyright 2005-2007 Daniel Frechette - modified by Mike Foster
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xAddClass(e, c)
{
  if ((e=xGetElementById(e))!=null) {
    var s = '';
    if (e.className.length && e.className.charAt(e.className.length - 1) != ' ') {
      s = ' ';
    }
    if (!xHasClass(e, c)) {
      e.className += s + c;
      return true;
    }
  }
  return false;
}
