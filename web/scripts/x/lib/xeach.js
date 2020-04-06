// xEach r1, Copyright 2006-2007 Daniel Frechette
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
/**
 * Access each element of a collection sequentially (by its numeric index)
 * and do something with it.
 * @param c - Array/Obj - A collection of elements
 * @param f - Func      - Function to execute for each element.
 *                        Arguments: item, index, number of items
 * @param s - Int       - Start index. A number between 0 and collection size - 1. (optional)
 * @return Nothing
 */
function xEach(c, f, s) {
  var l = c.length;
  for (var i=(s || 0); i < l; i++) {
    f(c[i], i, l);
  }
};
