// xRgbToHex r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xRgbToHex(r, g, b)
{
  return xHex((r << 16) | (g << 8) | b, 6, '#');
}
