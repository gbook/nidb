/* ------------------------------------------------------------------------------
  squirrel-gui icons.cpp
  Paths are in a 24x24 coordinate space, like common line-icon sets.
  ------------------------------------------------------------------------------ */

#include "icons.h"

#include <QGuiApplication>
#include <QPainter>
#include <QPainterPath>
#include <QPixmap>
#include <functional>

#include "theme.h"

namespace {

QIcon Draw(const QColor &color, const std::function<void(QPainter &)> &paint) {
    QIcon icon;
    for (int size : {16, 24, 32, 48}) {
        const qreal dpr = qApp ? qApp->devicePixelRatio() : 1.0;
        QPixmap pm(int(size * dpr), int(size * dpr));
        pm.setDevicePixelRatio(dpr);
        pm.fill(Qt::transparent);
        QPainter p(&pm);
        p.setRenderHint(QPainter::Antialiasing);
        p.scale(size / 24.0, size / 24.0);
        QPen pen(color, 1.8, Qt::SolidLine, Qt::RoundCap, Qt::RoundJoin);
        p.setPen(pen);
        p.setBrush(Qt::NoBrush);
        paint(p);
        p.end();
        icon.addPixmap(pm);
    }
    return icon;
}

QColor Ink() {
    return Theme::IsDark() ? QColor("#E6E8EB") : QColor("#1D2125");
}

void Folder(QPainter &p) {
    QPainterPath path;
    path.moveTo(3, 7);
    path.quadTo(3, 5, 5, 5);
    path.lineTo(9, 5);
    path.lineTo(11, 7);
    path.lineTo(19, 7);
    path.quadTo(21, 7, 21, 9);
    path.lineTo(21, 17);
    path.quadTo(21, 19, 19, 19);
    path.lineTo(5, 19);
    path.quadTo(3, 19, 3, 17);
    path.closeSubpath();
    p.drawPath(path);
}

} // namespace

namespace Icons {

QIcon ForNode(NodeKind kind) {
    const bool dark = Theme::IsDark();
    switch (kind) {
        case NodeKind::Package:
            return Draw(dark ? QColor("#8AB4F8") : QColor("#2563EB"), [](QPainter &p) {
                p.drawPolygon(QPolygonF({QPointF(21, 8), QPointF(12, 3), QPointF(3, 8), QPointF(3, 16), QPointF(12, 21), QPointF(21, 16)}));
                p.drawPolyline(QPolygonF({QPointF(3, 8), QPointF(12, 13), QPointF(21, 8)}));
                p.drawLine(QPointF(12, 13), QPointF(12, 21));
            });
        case NodeKind::Group:
            return Draw(dark ? QColor("#D4B45A") : QColor("#8A6D1F"), Folder);
        case NodeKind::Subject:
            return Draw(dark ? QColor("#2DD4BF") : QColor("#0F766E"), [](QPainter &p) {
                p.drawEllipse(QPointF(12, 8), 4, 4);
                p.drawArc(QRectF(4, 13, 16, 16), 0, 180 * 16);
            });
        case NodeKind::Study:
            return Draw(dark ? QColor("#A78BFA") : QColor("#6D28D9"), [](QPainter &p) {
                p.drawRoundedRect(QRectF(4, 4, 16, 16), 2, 2);
                p.drawLine(QPointF(8, 4), QPointF(8, 20));
            });
        case NodeKind::Series:
            return Draw(dark ? QColor("#A8ADB5") : QColor("#59616B"), [](QPainter &p) {
                p.drawRoundedRect(QRectF(6, 6, 14, 14), 1.5, 1.5);
                p.drawPolyline(QPolygonF({QPointF(3, 17), QPointF(3, 4), QPointF(4, 3), QPointF(17, 3)}));
            });
        case NodeKind::Experiment:
            return Draw(dark ? QColor("#FDBA74") : QColor("#C2410C"), [](QPainter &p) {
                p.drawPolyline(QPolygonF({QPointF(9, 3), QPointF(9, 9), QPointF(4, 19), QPointF(5, 21), QPointF(19, 21), QPointF(20, 19), QPointF(15, 9), QPointF(15, 3)}));
                p.drawLine(QPointF(8, 3), QPointF(16, 3));
            });
        case NodeKind::Pipeline:
            return Draw(dark ? QColor("#93C5FD") : QColor("#1D4ED8"), [](QPainter &p) {
                p.drawEllipse(QPointF(6, 6), 2.5, 2.5);
                p.drawEllipse(QPointF(18, 18), 2.5, 2.5);
                p.drawPolyline(QPolygonF({QPointF(8.5, 6), QPointF(18, 6), QPointF(18, 15.5)}));
            });
        case NodeKind::GroupAnalysis:
            return Draw(dark ? QColor("#F9A8D4") : QColor("#BE185D"), [](QPainter &p) {
                p.drawLine(QPointF(4, 20), QPointF(20, 20));
                p.drawLine(QPointF(7, 20), QPointF(7, 12));
                p.drawLine(QPointF(12, 20), QPointF(12, 6));
                p.drawLine(QPointF(17, 20), QPointF(17, 14));
            });
        case NodeKind::DataDictionary:
            return Draw(dark ? QColor("#86EFAC") : QColor("#15803D"), [](QPainter &p) {
                p.drawRoundedRect(QRectF(5, 3, 14, 18), 1.5, 1.5);
                p.drawLine(QPointF(9, 8), QPointF(15, 8));
                p.drawLine(QPointF(9, 12), QPointF(15, 12));
                p.drawLine(QPointF(9, 16), QPointF(13, 16));
            });
    }
    return QIcon();
}

QIcon Open() { return Draw(Ink(), Folder); }

QIcon Close() {
    return Draw(Ink(), [](QPainter &p) {
        p.drawLine(QPointF(6, 6), QPointF(18, 18));
        p.drawLine(QPointF(18, 6), QPointF(6, 18));
    });
}

QIcon Validate() {
    return Draw(Ink(), [](QPainter &p) { p.drawPolyline(QPolygonF({QPointF(4, 12), QPointF(9, 17), QPointF(20, 6)})); });
}

QIcon Info() {
    return Draw(Ink(), [](QPainter &p) {
        p.drawEllipse(QPointF(12, 12), 9, 9);
        p.drawLine(QPointF(12, 11), QPointF(12, 17));
        p.drawPoint(QPointF(12, 7.5));
    });
}

QIcon Extract() {
    return Draw(Ink(), [](QPainter &p) {
        p.drawLine(QPointF(12, 4), QPointF(12, 15));
        p.drawPolyline(QPolygonF({QPointF(7, 10), QPointF(12, 15), QPointF(17, 10)}));
        p.drawLine(QPointF(5, 20), QPointF(19, 20));
    });
}

QIcon Search() {
    return Draw(Theme::MutedText(), [](QPainter &p) {
        p.drawEllipse(QPointF(11, 11), 7, 7);
        p.drawLine(QPointF(20, 20), QPointF(16.5, 16.5));
    });
}

} // namespace Icons
