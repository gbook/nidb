// xCalendar r4, Copyright 2010 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// xCalendar object prototype --------------------------------------------------

function xCalendar(pfx, sel, fmt)
{
  // Private property
  var _i = this; // instance reference

  // Public properties
  this.vis = false; // true if calendar is visible
  this.sel = sel === true; // true if a date has been selected, default = false
  this.inp = null; // the caller's input element
  this.dL = new Date(); // low end of date range
  this.dH = new Date(); // high end of date range
  this.dD = new Date(); // date currently displayed
  this.dS = new Date(); // selected date
  this.pf = !pfx ? 'xc' : pfx; // ID prefix, default = 'xc'
  this.fmt = !fmt ? 3 : fmt; // date format code, default = 3

  // Construct this baby!
  this.ctor();

  // Public methods but only used internally

  /** Day td click listener. */
  this.clkL = function() { xCalendar.clkH(_i, this); };
  /** In-range day td mouseover listener. */
  this.ovrL = function() { xCalendar.ovrH(_i, this); };
  /** In-range day td mouseout listener. */
  this.outL = function() { xCalendar.outH(_i, this); };
  /** Cancel listener. */
  this.canL = function(e) { xCalendar.canH(_i, e); };
  /** Navigation td click listener. */
  this.navL = function() { xCalendar.navH(_i, this); };
}

// xCalendar static data -------------------------------------------------------

xCalendar.sd = {
  // Month names
  mn: ["January","February","March","April","May","June","July","August","September","October","November","December"],
  // Month days
  md: [31,28,31,30,31,30,31,31,30,31,30,31],
  // Table html
  t1: "<table id='X-tbl'><thead><tr id='X-hr1'><th id='X-prv'>&nbsp;</th><th id='X-ttl' colspan='5' title='Select a date by clicking a day. Cancel by pressing ESCape or clicking anywhere outside the calendar.'></th><th id='X-nxt'>&nbsp;</th></tr><tr id='X-hr2'><th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th></tr></thead><tbody id='X-bod'>",
  t2: xStrRepeat('<tr>' + xStrRepeat('<td></td>', 7) + '</tr>', 6) + '</tbody></table>'
};

// xCalendar static methods ----------------------------------------------------

/** Return true if y is a leap year, else false.
See: http://en.wikipedia.org/wiki/Leap_year */
xCalendar.isLY = function(y)
{
  return (y % 4 == 0) && ((y % 100 != 0) || (y % 400 == 0));
};

/** In-range day td mouseover handler. */
xCalendar.ovrH = function(xc, td)
{
  xAddClass(td, xc.pf + '-ovr');
};
/** In-range day td mouseout handler. */
xCalendar.outH = function(xc, td)
{
  xRemoveClass(td, xc.pf + '-ovr');
};

/** In-range day td click handler. */
xCalendar.clkH = function(xc, td)
{
  xc.dD.setDate(parseInt(td.innerHTML));
  xc.dS.setTime(xc.dD.getTime());
  xc.sel = true;
  if (!xc.inp) {return;}
  // Assign the date string to the input element.
  xc.setInpVal(xc.toString());
  // We're done here!
  xc.hide();
};

/** Cancel handler. The calendar is canceled by the ESCape key or a click
outside of the calendar container. Support for cancel on click requires the
caller of 'show' to stopPropagation if called from within a click listener. */
xCalendar.canH = function(xc, ne)
{
  var c, d, e = new xEvent(ne);
  if (e.type == 'keypress') {
    if (e.keyCode == 27) {
      xc.hide();
    }
  }
  else if (e.type == 'click') {
    c = xc.pf + '-con';
    d = e.target;
    while (d && d.id != c && d.nodeName.toLowerCase() != 'body') {
      d = xParent(d);
    }
    if (!d || d.nodeName.toLowerCase() == 'body') {
      xc.hide();
    }
  }
};

/** Navigation (previous/next) td click handler. */
xCalendar.navH = function(xc, td)
{
  var d = (td.id.indexOf('-prv') > -1) ? -1 : 1;
  xc.dD.setMonth(xc.dD.getMonth() + d);
  xc.paint();
};

// xCalendar instance methods --------------------------------------------------

/** Constructor. */
xCalendar.prototype.ctor = function()
{
  var e, p = this.pf, ci = p + '-con';
  // Create container
  e = document.createElement('div');
  e.id = ci;
  document.body.appendChild(e);
  e = xGetElementById(ci);
  // Create table
  e.innerHTML = xCalendar.sd.t1.replace(/X/g, p) + xCalendar.sd.t2;
  // Set date defaults
  this.dL.setMonth(0); // dL defaults to Jan 1 of this year
  this.dL.setDate(1);
  this.dH.setMonth(11); // dH defaults to Dec 31 of this year
  this.dH.setDate(31);
  // dC and DS default to "now"
};

/** Destructor. not finished */
xCalendar.prototype.dtor = function()
{
  this.inp = null;
};

/** Set the allowed date range.
 @param l Date object. The low end of the range.
 @param h Date object. The high end of the range.
 @param rs Boolean. Retain the existing selected date. Default = false.
 @return 0=ok, 1=low > high, 2=rs requested but is outside range, was set false.
*/
xCalendar.prototype.setRange = function(l, h, rs)
{
  var r = 0;
  if (l) this.dL.setTime(l.getTime());
  if (h) this.dH.setTime(h.getTime());
  if (this.dL > this.dH) { r = 1; }
  else if (!rs) { this.sel = false; }
  else if (this.dS < this.dL || this.dS > this.dH) {
    this.sel = false;
    this.setInpVal('');
    r = 2;
  }
  return r;
};

/** r4
*/
xCalendar.prototype.toString = function()
{
  var s, y, m, d;
  y = this.dS.getFullYear();
  m = this.dS.getMonth();
  d = this.dS.getDate();
  switch (this.fmt) {
    case 1:
    case 2:
      s = (this.fmt == 1) ? '-' : '/';
      s = (m + 1) + s + d + s + y;
      break;
    case 3:
    case 4:
      s = xCalendar.sd.mn[m];
      if (this.fmt == 4) {s = s.substr(0,3);}
      s = s + ' ' + d + ', ' + y;
      break;
    case 5:
      s = d + ' ' + xCalendar.sd.mn[m].substr(0,3) + ' ' + y;
      break;
  }
  return s;
};

/** r4
*/
xCalendar.prototype.select = function(d)
{
  this.dS.setTime(d.getTime());
  this.sel = true;
};

/** r3
*/
xCalendar.prototype.setInpVal = function(v)
{
  if (this.inp.nodeName.toLowerCase() == 'input') this.inp.value = v;
  else this.inp.innerHTML = v;
};

/** Set the input element which will receive the selected date.
 @param i An ID string or element reference. If it is a text input element the
 date will be assigned to its 'value' property, else the date will be assigned
 to the 'innerHTML' property of the element.
*/
xCalendar.prototype.setInput = function(i)
{
  this.inp = xGetElementById(i);
};

/** Set the date format code.
 @param f An integer, specifying a date format as follows:<br>
 1 = 2-29-2000<br>
 2 = 2/29/2000<br>
 3 = February 29, 2000  (default)<br>
 4 = Feb 29, 2000<br>
 5 = 29 Feb 2000
*/
xCalendar.prototype.setFormat = function(f)
{
  this.fmt = f;
};

/** Show the calendar and register the cancel listeners. If a date has been
selected, that month will be displayed, else the month from the low end of
the range will be displayed. */
xCalendar.prototype.show = function()
{
  if (this.vis) { this.hide(); }
  if (this.sel) { this.dD.setTime(this.dS.getTime()); }
  else { this.dD.setTime(this.dL.getTime()); }
  this.paint();
  xAddEventListener(document, 'keypress', this.canL, false);
  xAddEventListener(document, 'click', this.canL, false);
  xGetElementById(this.pf + '-con').style.display = 'block';
  this.vis = true;
};

/** Hide the calendar and remove the cancel listeners. */
xCalendar.prototype.hide = function()
{
  if (this.vis) {
    this.vis = false;
    xGetElementById(this.pf + '-con').style.display = 'none';
    xRemoveEventListener(document, 'keypress', this.canL, false);
    xRemoveEventListener(document, 'click', this.canL, false);
  }
};

/** Draw the calendar for this.dD, the date to be displayed. */
xCalendar.prototype.paint = function()
{
  var i, j, b, d, d1, e, p = this.pf, // d is the day going into each td
    c, c1, c2, c3, c4, l1, l2, l3, // class names and listeners
    fd, ld, yD, mD, yL, dL, yH, mH, dH, dS, ckS; // years, months, days

  // Date to display
  yD = this.dD.getFullYear();
  mD = this.dD.getMonth();
  fd = new Date(yD, mD, 1).getDay(); // day of week for the first day of displayed month, 0-6
  ld = xCalendar.sd.md[mD]; // last day of displayed month
  if (ld == 28) {
    if (xCalendar.isLY(yD)) ++ld;
  }
  // Low date of range
  yL = this.dL.getFullYear();
  dL = this.dL.getDate();
  // High date of range
  yH = this.dH.getFullYear();
  dH = this.dH.getDate();
  // Selected date
  dS = this.dS.getDate();
  ckS = (this.sel && yD == this.dS.getFullYear() && mD == this.dS.getMonth());

  // Set prv, nxt, dL and dH according to date range.
  e = xGetElementById(p + '-prv');
  if (yD > yL || (yD == yL && mD > this.dL.getMonth())) {
    e.className = p + '-pir';
    e.onclick = this.navL;
    dL = 0;
  }
  else {
    e.className = p + '-por';
    e.onclick = null;
  }
  e = xGetElementById(p + '-nxt');
  if (yD < yH || (yD == yH && mD < this.dH.getMonth())) {
    e.className = p + '-nir';
    e.onclick = this.navL;
    dH = 32;
  }
  else {
    e.className = p + '-nor';
    e.onclick = null;
  }

  // Set calendar title (month and year).
  xGetElementById(p + '-ttl').innerHTML = xCalendar.sd.mn[mD] + '&nbsp;' + yD;

  // Loop over the td's and create the calendar for the month to be displayed.
  d = 1;
  c1 = p + '-day';
  c2 = ' ' + p + '-dir';
  c3 = ' ' + p + '-dor';
  c4 = ' ' + p + '-sel';
  b = xGetElementById(p + '-bod');
  for (i = 0; i < b.rows.length; ++i) {
    for (j = 0; j < b.rows[i].cells.length; ++j) { // in this loop e is the current td
      e = b.rows[i].cells[j]
      if ((i == 0 && j >= fd) || (i > 0 && d <= ld)) { // valid days in a month
        c = c1;
        if (d >= dL && d <= dH) { // in-range days
          c += c2;
          if (ckS && d == dS) { // the selected day
            c += c4;
          }
          l1 = this.ovrL;
          l2 = this.outL;
          l3 = this.clkL;
        }
        else {
          c += c3;
          l1 = l2 = l3 = null;
        }
        d1 = d++;
      }
      else {
        l1 = l2 = l3 = null;
        c = d1 = '';
      }
      e.onmouseover = l1;
      e.onmouseout = l2;
      e.onclick = l3;
      e.className = c;
      e.innerHTML = d1;
    }
  }

  // Set container position.
  e = xGetElementById(p + '-con');
  xLeft(e, xPageX(this.inp) + xWidth(this.inp) + 4);
  xTop(e, xPageY(this.inp));

}; // end xCalendar.prototype.paint
