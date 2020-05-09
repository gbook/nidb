// xGetElementsByClassName r7, Copyright 2002-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xGetElementsByClassName(c,p,t,f)
{
  var r=[], re, e, i, l;
  re = new RegExp("(^|\\s)"+c+"(\\s|$)");
//  var e = p.getElementsByTagName(t);
  e = xGetElementsByTagName(t,p); // See xml comments.
  for (i=0, l=e.length; i<l; ++i) {
    if (re.test(e[i].className)) {
      r[r.length] = e[i];
      if (f) f(e[i]);
    }
  }
  return r;
}
