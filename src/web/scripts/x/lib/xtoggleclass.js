// xToggleClass r2, Copyright 2005-2007 Daniel Frechette
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
/* Added by DF, 2005-10-11
 * Toggles a class name on or off for an element
 */
function xToggleClass(e, c) {
  if (!(e = xGetElementById(e)))
    return null;
  if (!xRemoveClass(e, c) && !xAddClass(e, c))
    return false;
  return true;
}
