// xTraverseDocumentStyleSheets r1, Copyright 2006-2007 Ivan Pepelnjak (www.zaplana.net)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// xTraverseDocumentStyleSheets(callback)
//   traverses all stylesheets attached to a document (linked as well as internal)
function xTraverseDocumentStyleSheets(cb) {
  var ssList = document.styleSheets; if (!ssList) return undefined;

  for (i = 0; i < ssList.length; i++) {
    var ss = ssList[i] ; if (! ss) continue;
    if (xTraverseStyleSheet(ss,cb)) return true;
  }
  return false;
}
