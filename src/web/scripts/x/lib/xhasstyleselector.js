// xHasStyleSelector r1, Copyright 2006-2007 Ivan Pepelnjak (www.zaplana.net)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
// xHasStyleSelector(styleSelectorString)
//   checks whether any of the stylesheets attached to the document contain the definition of the specified
//   style selector (simple string matching at the moment)
//
// returns:
//   undefined  - style sheet scripting not supported by the browser
//   true/false - found/not found
function xHasStyleSelector(ss) {
  if (! xHasStyleSheets()) return undefined ;

  function testSelector(cr) {
    return cr.selectorText.indexOf(ss) >= 0;
  }
  return xTraverseDocumentStyleSheets(testSelector);
}
