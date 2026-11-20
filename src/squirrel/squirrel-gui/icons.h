/* ------------------------------------------------------------------------------
  squirrel-gui icons.h
  Small line icons drawn with QPainter, so the app needs no image resources and
  the icons follow the light/dark theme.
  ------------------------------------------------------------------------------ */

#ifndef ICONS_H
#define ICONS_H

#include <QIcon>

#include "packagesnapshot.h"

namespace Icons {

QIcon ForNode(NodeKind kind);
QIcon Open();
QIcon Close();
QIcon Validate();
QIcon Info();
QIcon Extract();
QIcon Search();

} // namespace Icons

#endif // ICONS_H
