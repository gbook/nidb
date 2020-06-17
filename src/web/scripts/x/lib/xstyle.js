// xStyle r2, Copyright 2007,2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xStyle(p, v)
{
  var i, e;
  for (i = 2; i < arguments.length; ++i) {
    e = xGetElementById(arguments[i]);
    try { e.style[p] = v; }
    catch (ex) {
/*@cc_on
@if (@_jscript_version <= 5.7) // IE7 and down
if(p!='display'){continue;}var s='',t=e.tagName.toLowerCase();switch(t){case'table':case'tr':case'td':case'li':s='block';break;case'caption':s='inline';break;}e.style[p]=s;
@end @*/
    }
  }
}
