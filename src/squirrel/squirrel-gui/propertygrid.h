/* ------------------------------------------------------------------------------
  squirrel-gui propertygrid.h
  The overview's grid of "key / value" cells. Re-flows into as many columns as
  fit (about 260 px each).
  ------------------------------------------------------------------------------ */

#ifndef PROPERTYGRID_H
#define PROPERTYGRID_H

#include <QWidget>

#include "packagesnapshot.h"

class QGridLayout;

class PropertyGrid : public QWidget {
    Q_OBJECT
public:
    explicit PropertyGrid(QWidget *parent = nullptr);
    void SetProperties(const PropList &props);

protected:
    void resizeEvent(QResizeEvent *e) override;

private:
    void Reflow();
    QGridLayout *grid;
    QList<QWidget *> cells;
    QList<QWidget *> fillers;
    int columns = 0;
};

#endif // PROPERTYGRID_H
