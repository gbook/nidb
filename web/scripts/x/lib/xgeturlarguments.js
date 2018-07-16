// xGetURLArguments r3, Copyright 2001-2009 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xGetURLArguments()
{
  var j, p, nv, a = [], i = location.href.indexOf('?');
  if (i != -1) {
    p = location.href.substring(i+1, location.href.length).split('&'); // pairs
    for (j = 0; j < p.length; j++) {
      nv = p[j].split('='); // name/value
      a[nv[0]] = nv[1];
    }
  }
  return a;
}
/* r2
function xGetURLArguments()
{
  var idx = location.href.indexOf('?');
  var params = new Array();
  if (idx != -1) {
    var pairs = location.href.substring(idx+1, location.href.length).split('&');
    for (var i=0; i<pairs.length; i++) {
      nameVal = pairs[i].split('=');
      params[i] = nameVal[1];
      params[nameVal[0]] = nameVal[1];
    }
  }
  return params;
}
*/
