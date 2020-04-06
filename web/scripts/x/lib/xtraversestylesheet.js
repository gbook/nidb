// xTraverseStyleSheet r1, Copyright 2006-2007 Ivan Pepelnjak (www.zaplana.net)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// xTraverseStyleSheet (stylesheet, callback)
//   traverses all rules in the stylesheet, calling callback function on each rule.
//   recursively handles stylesheets imported with @import CSS directive
//   stops when the callback function returns true (it has found what it's been looking for)
//
// returns:
//   undefined - problems with CSS-related objects
//   true      - callback function returned true at least once
//   false     - callback function always returned false
function xTraverseStyleSheet(ss,cb) {
  if (!ss) return false;
  var rls = xGetCSSRules(ss) ; if (!rls) return undefined ;
  var result;

  for (var j = 0; j < rls.length; j++) {
    var cr = rls[j];
    if (cr.selectorText) { result = cb(cr); if (result) return true; }
    if (cr.type && cr.type == 3 && cr.styleSheet) xTraverseStyleSheet(cr.styleSheet,cb);
  }
  if (ss.imports) {
    for (var j = 0 ; j < ss.imports.length; j++) {
      if (xTraverseStyleSheet(ss.imports[j],cb)) return true;
    }
  }
  return false;
}
