// xTrim r2
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTrim(s) {
  var i, w = /\s/;
  s = s.replace(/^\s\s*/, '');
  i = s.length;
  while (w.test(s.charAt(--i)));
  return s.slice(0, i + 1);
  // r1:
  //return s.replace(/^\s+|\s+$/g, '');
}
