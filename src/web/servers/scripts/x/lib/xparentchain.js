// xParentChain r2, Copyright 2001-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xParentChain(e,delim,bNode)
{
  if (!(e=xGetElementById(e))) return;
  var lim=100, s = "", d = delim || "\n";
  while(e) {
    s += xName(e) + ', ofsL:'+e.offsetLeft + ', ofsT:'+e.offsetTop + d;
    e = e.parentNode;
    if (!lim--) break;
  }
  return s;
}
