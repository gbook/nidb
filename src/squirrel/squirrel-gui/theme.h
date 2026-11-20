/* ------------------------------------------------------------------------------
  squirrel-gui theme.h
  Light / dark / follow-system themes on the Fusion style, which looks the same
  on Linux and Windows.
  ------------------------------------------------------------------------------ */

#ifndef THEME_H
#define THEME_H

#include <QColor>
#include <QPalette>
#include <QString>

namespace Theme {

enum class Mode { System, Light, Dark };

void Apply(Mode mode);         /* sets the style and palette for the whole app */
Mode Current();
bool IsDark();                 /* what is actually showing */

QString ModeToString(Mode m);
Mode ModeFromString(const QString &s);

/* the image viewer stays dark in both themes */
QPalette ViewerPalette();

/* secondary text colour for the current theme */
QColor MutedText();

} // namespace Theme

#endif // THEME_H
