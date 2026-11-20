/* ------------------------------------------------------------------------------
  squirrel-gui imageview.cpp
  ------------------------------------------------------------------------------ */

#include "imageview.h"

#include <QKeyEvent>
#include <QMouseEvent>
#include <QPainter>
#include <QWheelEvent>
#include <algorithm>
#include <cmath>

ImageView::ImageView(QWidget *parent) : QWidget(parent) {
    setFocusPolicy(Qt::StrongFocus);
    setMinimumSize(200, 200);
    setMouseTracking(false);
    UpdateCursor();
}

void ImageView::SetImage(const QImage &img, double pixelAspect) {
    const bool sizeChanged = img.size() != image.size();
    image = img;
    aspect = (pixelAspect > 0.01 && pixelAspect < 100) ? pixelAspect : 1.0;
    if (sizeChanged && fitMode)
        offset = QPointF();
    update();
}

void ImageView::ClearImage() {
    image = QImage();
    update();
}

void ImageView::SetOverlay(const QStringList &topLeft, const QStringList &topRight, const QStringList &bottomLeft, const QStringList &bottomRight,
                           const QString &left, const QString &right, const QString &top, const QString &bottom) {
    cornerTL = topLeft;
    cornerTR = topRight;
    cornerBL = bottomLeft;
    cornerBR = bottomRight;
    lLabel = left;
    rLabel = right;
    tLabel = top;
    bLabel = bottom;
    update();
}

double ImageView::FitScale() const {
    if (image.isNull())
        return 1.0;
    const double w = image.width();
    const double h = image.height() * aspect;
    return std::min((width() - 16) / w, (height() - 16) / h);
}

double ImageView::Scale() const {
    return fitMode ? FitScale() : scale;
}

double ImageView::ZoomPercent() const {
    return Scale() * 100.0;
}

void ImageView::Fit() {
    fitMode = true;
    offset = QPointF();
    update();
    emit ZoomChanged();
}

void ImageView::ActualSize() {
    fitMode = false;
    scale = 1.0;
    offset = QPointF();
    update();
    emit ZoomChanged();
}

void ImageView::UpdateCursor() {
    switch (tool) {
        case Tool::Pan: setCursor(Qt::OpenHandCursor); break;
        case Tool::Zoom: setCursor(Qt::SizeVerCursor); break;
        default: setCursor(Qt::CrossCursor); break;
    }
}

void ImageView::paintEvent(QPaintEvent *) {
    QPainter p(this);
    const QColor bg = inverted ? Qt::white : Qt::black;
    p.fillRect(rect(), bg);

    if (!image.isNull()) {
        const double s = Scale();
        const QSizeF size(image.width() * s, image.height() * s * aspect);
        const QPointF topLeft(width() / 2.0 - size.width() / 2.0 + offset.x(), height() / 2.0 - size.height() / 2.0 + offset.y());
        p.setRenderHint(QPainter::SmoothPixmapTransform, s < 1.0);
        p.drawImage(QRectF(topLeft, size), image);
    }

    if (!overlayVisible || image.isNull())
        return;

    const QColor text = inverted ? QColor("#111111") : QColor("#E6E6E6");
    const QColor dim = inverted ? QColor("#3B424A") : QColor("#A8ADB5");
    QFont f = font();
    p.setFont(f);
    const QFontMetrics fm(f);
    const int lh = fm.height();
    const int m = 10;

    auto drawLines = [&](const QStringList &lines, bool right, bool bottom, const QColor &c) {
        p.setPen(c);
        for (int i = 0; i < lines.size(); i++) {
            const int row = bottom ? (lines.size() - 1 - i) : i;
            const int y = bottom ? height() - m - row * lh - fm.descent() : m + fm.ascent() + row * lh;
            const int x = right ? width() - m - fm.horizontalAdvance(lines[i]) : m;
            p.drawText(x, y, lines[i]);
        }
    };
    drawLines(cornerTL, false, false, text);
    drawLines(cornerTR, true, false, text);
    drawLines(cornerBL, false, true, dim);
    drawLines(cornerBR, true, true, dim);

    QFont bf = f;
    bf.setWeight(QFont::DemiBold);
    p.setFont(bf);
    p.setPen(dim);
    const QFontMetrics bfm(bf);
    if (!lLabel.isEmpty())
        p.drawText(m, height() / 2 + bfm.ascent() / 2, lLabel);
    if (!rLabel.isEmpty())
        p.drawText(width() - m - bfm.horizontalAdvance(rLabel), height() / 2 + bfm.ascent() / 2, rLabel);
    if (!tLabel.isEmpty())
        p.drawText(width() / 2 - bfm.horizontalAdvance(tLabel) / 2, m + bfm.ascent(), tLabel);
    if (!bLabel.isEmpty())
        p.drawText(width() / 2 - bfm.horizontalAdvance(bLabel) / 2, height() - m - bfm.descent(), bLabel);
}

void ImageView::mousePressEvent(QMouseEvent *e) {
    setFocus();
    lastPos = e->position().toPoint();
    if (e->button() == Qt::RightButton)
        drag = Drag::WindowLevel;
    else if (e->button() == Qt::MiddleButton)
        drag = Drag::Pan;
    else if (e->button() == Qt::LeftButton)
        drag = tool == Tool::Pan ? Drag::Pan : (tool == Tool::Zoom ? Drag::Zoom : Drag::WindowLevel);
    if (drag == Drag::Pan)
        setCursor(Qt::ClosedHandCursor);
}

void ImageView::mouseMoveEvent(QMouseEvent *e) {
    const QPoint pos = e->position().toPoint();
    const QPoint d = pos - lastPos;
    lastPos = pos;
    switch (drag) {
        case Drag::WindowLevel:
            emit WindowDrag(d.x(), d.y());
            break;
        case Drag::Pan:
            if (fitMode) {
                scale = FitScale();
                fitMode = false;
            }
            offset += QPointF(d);
            update();
            break;
        case Drag::Zoom:
            if (fitMode) {
                scale = FitScale();
                fitMode = false;
            }
            scale = std::clamp(scale * std::pow(1.01, -d.y()), 0.05, 40.0);
            update();
            emit ZoomChanged();
            break;
        default:
            break;
    }
}

void ImageView::mouseReleaseEvent(QMouseEvent *) {
    drag = Drag::None;
    UpdateCursor();
}

void ImageView::mouseDoubleClickEvent(QMouseEvent *) {
    Fit();
}

void ImageView::wheelEvent(QWheelEvent *e) {
    if (e->modifiers() & Qt::ControlModifier) {
        if (fitMode) {
            scale = FitScale();
            fitMode = false;
        }
        scale = std::clamp(scale * (e->angleDelta().y() > 0 ? 1.1 : 1 / 1.1), 0.05, 40.0);
        update();
        emit ZoomChanged();
        return;
    }
    wheelAccum += e->angleDelta().y();
    while (wheelAccum >= 120) {
        emit SliceStep(-1);
        wheelAccum -= 120;
    }
    while (wheelAccum <= -120) {
        emit SliceStep(1);
        wheelAccum += 120;
    }
}

void ImageView::keyPressEvent(QKeyEvent *e) {
    switch (e->key()) {
        case Qt::Key_Up:
        case Qt::Key_Left: emit SliceStep(-1); break;
        case Qt::Key_Down:
        case Qt::Key_Right: emit SliceStep(1); break;
        case Qt::Key_PageUp: emit SliceStep(-10); break;
        case Qt::Key_PageDown: emit SliceStep(10); break;
        case Qt::Key_Home: emit SliceJump(false); break;
        case Qt::Key_End: emit SliceJump(true); break;
        default: QWidget::keyPressEvent(e);
    }
}
