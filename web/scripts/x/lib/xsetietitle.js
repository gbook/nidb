// xSetIETitle r3, Copyright 2003-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xSetIETitle()
{
  var ua = navigator.userAgent.toLowerCase();
  if (!window.opera && navigator.vendor!='KDE' && document.all && ua.indexOf('msie')!=-1 && !document.layers) {
    var i = ua.indexOf('msie') + 1;
    var v = ua.substr(i + 4, 3);
    document.title = 'IE ' + v + ' - ' + document.title;
  }
}
