/* ------------------------------------------------------------------------------
  squirrel-gui imageview.h
  The image viewport: draws one image with zoom/pan and the overlay text.

  Mouse: left drag does the current tool (window/level, pan or zoom); right
  drag is always window/level, middle drag always pans. Wheel steps through
  images, Ctrl+wheel zooms, double-click fits. Keys: Up/Down, PgUp/PgDn,
  Home/End move through the images.
  ------------------------------------------------------------------------------ */

#ifndef IMAGEVIEW_H
#define IMAGEVIEW_H

#include <QImage>
#include <QStringList>
#include <QWidget>

class ImageView : public QWidget {
    Q_OBJECT
public:
    enum class Tool { WindowLevel, Pan, Zoom };

    explicit ImageView(QWidget *parent = nullptr);

    void SetImage(const QImage &img, double pixelAspect);   /* aspect = row spacing / column spacing */
    void ClearImage();
    void SetTool(Tool t) { tool = t; UpdateCursor(); }

    /* overlay: four corners (lines each) and the edge orientation letters */
    void SetOverlay(const QStringList &topLeft, const QStringList &topRight,
                    const QStringList &bottomLeft, const QStringList &bottomRight,
                    const QString &left, const QString &right, const QString &top, const QString &bottom);
    void SetOverlayVisible(bool v) { overlayVisible = v; update(); }
    void SetInverted(bool inv) { inverted = inv; update(); }

    void Fit();
    void ActualSize();
    double ZoomPercent() const;

signals:
    void WindowDrag(int dx, int dy);
    void SliceStep(int delta);
    void SliceJump(bool toEnd);
    void ZoomChanged();

protected:
    void paintEvent(QPaintEvent *e) override;
    void mousePressEvent(QMouseEvent *e) override;
    void mouseMoveEvent(QMouseEvent *e) override;
    void mouseReleaseEvent(QMouseEvent *e) override;
    void mouseDoubleClickEvent(QMouseEvent *e) override;
    void wheelEvent(QWheelEvent *e) override;
    void keyPressEvent(QKeyEvent *e) override;

private:
    double FitScale() const;
    double Scale() const;
    void UpdateCursor();

    QImage image;
    double aspect = 1.0;
    bool fitMode = true;
    double scale = 1.0;        /* screen px per image px (when not fitting) */
    QPointF offset;            /* pan, in screen px */
    Tool tool = Tool::WindowLevel;
    enum class Drag { None, WindowLevel, Pan, Zoom } drag = Drag::None;
    QPoint lastPos;
    bool overlayVisible = true;
    bool inverted = false;
    QStringList cornerTL, cornerTR, cornerBL, cornerBR;
    QString lLabel, rLabel, tLabel, bLabel;
    int wheelAccum = 0;
};

#endif // IMAGEVIEW_H
