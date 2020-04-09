// xTable r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xTable(sTableId, sRoot, sCC, sFR, sFRI, sRCell, sFC, sFCI, sCCell, sTC, sCellT)
{
  var i, ot, cc=null, fcw, frh, root, fr, fri, fc, fci, tc;
  var e, t, tr, a, alen, tmr=null;

  ot = xGetElementById(sTableId); // original table
  if (!ot || !document.createElement || !document.appendChild || !ot.deleteCaption || !ot.deleteTHead) {
    return null;
  }
  fcw = xWidth(ot.rows[1].cells[0]); // get first column width before altering ot
  frh = xHeight(ot.rows[0]); // get first row height before altering ot
  root = document.createElement('div'); // overall container
  root.className = sRoot;
  fr = document.createElement('div'); // frozen-row container
  fr.className = sFR;
  fri = document.createElement('div'); // frozen-row inner container, for column headings
  fri.className = sFRI;
  fr.appendChild(fri);
  root.appendChild(fr);
  fc = document.createElement('div'); // frozen-column container
  fc.className = sFC;
  fci = document.createElement('div'); // frozen-column inner container, for row headings
  fci.className = sFCI;
  fc.appendChild(fci);
  root.appendChild(fc);
  tc = document.createElement('div'); // table container, contains ot
  tc.className = sTC;
  root.appendChild(tc);
  if (ot.caption) {
    cc = document.createElement('div'); // caption container
    cc.className = sCC;
    cc.appendChild(ot.caption.firstChild); // only gets first child
    root.appendChild(cc);
    ot.deleteCaption();
  }
  // Create fr cells (column headings)
  a = ot.rows[0].cells;
  alen = a.length;
  for (i = 1; i < alen; ++i) {
    e = document.createElement('div');
    e.className = sRCell;
    t = document.createElement('table');
    t.className = sCellT;
    tr = t.insertRow(0);
    tr.appendChild(a[1]);
    e.appendChild(t);
    fri.appendChild(e);
  }
  if (ot.tHead) {
    ot.deleteTHead();
  }
  // Create fc cells (row headings)
  a = ot.rows;
  alen = a.length;
  for (i = 0; i < alen; ++i) {
    e = document.createElement('div');
    e.className = sCCell;
    t = document.createElement('table');
    t.className = sCellT;
    tr = t.insertRow(0);
    tr.appendChild(a[i].cells[0]);
    e.appendChild(t);
    fci.appendChild(e);
  }
  ot = ot.parentNode.replaceChild(root, ot);
  tc.appendChild(ot);

  resize();
  root.style.visibility = 'visible';
  xAddEventListener(tc, 'scroll', onScroll, false);
  xAddEventListener(window, 'resize', onResize, false);

  function onScroll()
  {
    xLeft(fri, -tc.scrollLeft);
    xTop(fci, -tc.scrollTop);
  }
  function onResize()
  {
    if (!tmr) {
      tmr = setTimeout(
        function() {
          resize();
          tmr=null;
        }, 500);
    }
  }
  function resize()
  {
    var sum = 0, cch = 0, w, h;
    // caption container
    if (cc) {
      cch = xHeight(cc);
      xMoveTo(cc, 0, 0);
      xWidth(cc, xWidth(root));
    }
    // frozen row
    xMoveTo(fr, fcw, cch);
    xResizeTo(fr, xWidth(root) - fcw, frh);
    xMoveTo(fri, 0, 0);
    xResizeTo(fri, xWidth(ot), frh);
    // frozen col
    xMoveTo(fc, 0, cch + frh);
    xResizeTo(fc, fcw, xHeight(root) - cch);
    xMoveTo(fci, 0, 0);
    xResizeTo(fci, fcw, xHeight(ot));
    // table container
    xMoveTo(tc, fcw, cch + frh);
    xWidth(tc, xWidth(root) - fcw - 1);
    xHeight(tc, xHeight(root) - cch - frh - 1);
    // size and position fr cells
    a = ot.rows[0].cells;
    e = xFirstChild(fri, 'div');
    for (i = 0; i < a.length; ++i) {
      xMoveTo(e, sum, 0);
      w = xWidth(e, xWidth(a[i]));
      h = xHeight(e, frh);
      sum += w;
      xResizeTo(xFirstChild(e, 'table'), w, h);//////////
      e = xNextSib(e, 'div');
    }
    // size and position fc cells
    sum = 0;
    a = ot.rows;
    e = xFirstChild(fci, 'div');
    for (i = 0; i < a.length; ++i) {
      xMoveTo(e, 0, sum);
      w = xWidth(e, fcw);
      h = xHeight(e, xHeight(a[i]));
      sum += h;
      xResizeTo(xFirstChild(e, 'table'), w, h);//////////
      e = xNextSib(e, 'div');
    }
    onScroll();
  } // end resize
} // end xTable
