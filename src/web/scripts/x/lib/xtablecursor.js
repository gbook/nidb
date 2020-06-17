// xTableCursor r3, Copyright 2004-2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTableCursor(tblId, rowStyle, cellStyle) // object prototype
{
  xTableIterate(tblId,
    function(obj, isRow) {
      if (!isRow) {
        obj.onmouseover = tdOver;
        obj.onmouseout = tdOut;
      }
    }
  );
  function tdOver(e) {
    xAddClass(this, cellStyle);
    var tr = this.parentNode;
    for (var i = 0; i < tr.cells.length; ++i) {
      if (this != tr.cells[i]) xAddClass(tr.cells[i], rowStyle);
    }
  }
  function tdOut(e) {
    xRemoveClass(this, cellStyle);
    var tr = this.parentNode;
    for (var i = 0; i < tr.cells.length; ++i) {
      xRemoveClass(tr.cells[i], rowStyle);
    }
  }
  this.unload = function() {
    xTableIterate(tblId, function(o) { o.onmouseover = o.onmouseout = null; });
  };
}
