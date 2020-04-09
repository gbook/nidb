// xTimes r2, Copyright 2006-2007 Daniel Frechette
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
/**
 * Call a function n times.
 * @param n - Int  - Number of times f is called
 * @param f - Func - Function to execute (can accept an index value from 0 to n-1)
 * @param s - Int  - Start index (0 if null or not present)
 * @return Nothing
 */
function xTimes(n, f, s) {
  s = s || 0;
  n = n + s;
  for (var i=s; i < n; i++)
    f(i);
};
