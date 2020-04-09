// xRemoveClass r3, Copyright 2005-2007 Daniel Frechette - modified by Mike Foster
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xRemoveClass(e, c)
{
  if(!(e=xGetElementById(e))) return false;
  e.className = e.className.replace(new RegExp("(^|\\s)"+c+"(\\s|$)",'g'),
    function(str, p1, p2) { return (p1 == ' ' && p2 == ' ') ? ' ' : ''; }
  );
  return true;
}
