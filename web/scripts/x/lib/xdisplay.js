// xDisplay r3, Copyright 2003-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// This was alternative 1:
function xDisplay(e,s)
{
  if ((e=xGetElementById(e)) && e.style && xDef(e.style.display)) {
    if (xStr(s)) {
      try { e.style.display = s; }
      catch (ex) { e.style.display = ''; } // Will this make IE use a default value
    }                                      // appropriate for the element?
    return e.style.display;
  }
  return null;
}
