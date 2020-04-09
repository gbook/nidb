// xGetElementsByAttribute r3, Copyright 2002-2011 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xGetElementsByAttribute(sTag, sAtt, sRE, fn)
{
  var a, l, list, found=[], re=new RegExp(sRE, 'i');
  list = xGetElementsByTagName(sTag);
  for (var i=0, l=list.length; i<l; ++i) {
    a = list[i].getAttribute(sAtt);
    if (!a) {a = list[i][sAtt];}
    if (typeof(a)==='string' && a.search(re)!==-1) {
      found[found.length] = list[i];
      if (fn) fn(list[i]);
    }
  }
  return found;
}
