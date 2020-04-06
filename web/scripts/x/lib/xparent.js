// xParent r2, Copyright 2001-2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xParent(e, s)
{
  e = xGetElementById(e);
  if (e) {
    e = e.parentNode;
    if (s) {
      while (e && e.nodeName.toLowerCase() != s) e = e.parentNode;
    }
  }
  return e;
}

/* r1
function xParent(e, bNode)
{
  if (!(e=xGetElementById(e))) return null;
  var p=null;
  if (!bNode && xDef(e.offsetParent)) p=e.offsetParent;
  else if (xDef(e.parentNode)) p=e.parentNode;
  else if (xDef(e.parentElement)) p=e.parentElement;
  return p;
}
*/
