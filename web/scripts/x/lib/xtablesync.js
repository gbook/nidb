// xTableSync r2, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTableSync(sT1Id, sT2Id, sEvent, fn)
{
  var t1 = xGetElementById(sT1Id);
  var t2 = xGetElementById(sT2Id);
  sEvent = 'on' + sEvent.toLowerCase();
  t1[sEvent] = t2[sEvent] = function(e)
  {
    e = e || window.event;
    var t = e.target || e.srcElement;
    while (t && t.nodeName.toLowerCase() != 'td') {
      t = t.parentNode;
    }
    if (t) {
      var r = t.parentNode.sectionRowIndex, c = t.cellIndex; // this may not be very cross-browser
      var tbl = xGetElementById(this.id == sT1Id ? sT2Id : sT1Id); // 'this' points to a table
      fn(t, tbl.rows[r].cells[c]);
    }
  };
  // r2
  t1 = t2 = null; // Does this remove the circular refs even tho the closure remains?
  /*
  xAddEventListener(window, 'unload',
    function() {
      t1[sEvent] = t2[sEvent] = null;
      t1 = t2 = null;
    }, false
  );
  */
}
