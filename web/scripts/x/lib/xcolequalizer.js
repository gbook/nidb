// xColEqualizer r1, Original by moi. Modified by Mike Foster.
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xColEqualizer()
{
  var i, j, h = [];
  // get heights of columns' child elements
  for (i = 1, j = 0; i < arguments.length; i += 2, ++j)
  {
    h[j] = xHeight(arguments[i]);
  }
  h.sort(d);
  // set heights of column elements
  for (i = 0; i < arguments.length; i += 2)
  {
    xHeight(arguments[i], h[0]);
  }
  return h[0];
  // for a descending sort
  function d(a,b)
  {
    return b-a;
  }
}
