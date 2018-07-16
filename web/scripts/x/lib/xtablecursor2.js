// xTableCursor2 r1, gebura's enhancement of xTableCursor
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTableCursor2(tblId, rowStyle, cellStyle, rowClicStyle, cellClicStyle) // object prototype
{
  xTableIterate(tblId,
    function(obj, isRow) {
      if (!isRow) {
        obj.onmouseover = tdOver;
        obj.onmouseout = tdOut;
        obj.onclick = tdClic;
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

  function tdClic(e) {
    var table = this.parentNode.parentNode.rows;
    for (var i= 0;i < table.length; i++) {
      var tr = table[i];
      for (var j = 0; j < tr.cells.length; j++) {
        xRemoveClass(tr.cells[j], rowClicStyle);
        xRemoveClass(tr.cells[j], cellClicStyle);
      }
    }
    xAddClass(this, cellClicStyle);
    var tr = this.parentNode;
    for (var i = 0; i < tr.cells.length; ++i) {
      if (this != tr.cells[i]) xAddClass(tr.cells[i], rowClicStyle);
    }
  }
  this.unload = function() {
    xTableIterate(tblId, function(o) { o.onmouseover = o.onmouseout = null; });
  };
}
