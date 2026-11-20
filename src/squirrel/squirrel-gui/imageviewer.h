/* ------------------------------------------------------------------------------
  squirrel-gui imageviewer.h
  The Image tab: toolbar, series strip, viewport, image slider and the DICOM
  header of the image on screen. One image at a time.

  The 3D plane buttons and the volume slider are placeholders until NIfTI
  (3D/4D) support is added; they are disabled for DICOM.
  ------------------------------------------------------------------------------ */

#ifndef IMAGEVIEWER_H
#define IMAGEVIEWER_H

#include <QWidget>

#include "dicomslice.h"
#include "imageview.h"

class QAction;
class QActionGroup;
class QLabel;
class QLineEdit;
class QListWidget;
class QPushButton;
class QSlider;
class QStackedWidget;
class QTableWidget;
class QTimer;
class QWidget;

class ImageViewer : public QWidget {
    Q_OBJECT
public:
    struct SeriesEntry {
        int node;
        QString label;
        qint64 count;
    };

    explicit ImageViewer(QWidget *parent = nullptr);

    void SetSeriesList(const QList<SeriesEntry> &entries, int currentNode);
    void ShowMessage(const QString &text);
    void SetSlices(int node, const SliceList &slices);
    int CurrentNode() const { return node; }
    void Stop();                       /* stop playback (tab hidden, package closed) */

signals:
    void SeriesRequested(int node);

private:
    enum class WindowMode { FromFile, Auto, Full, Manual };

    void GoTo(int index);
    void LoadCurrent();
    void Render();
    void UpdateOverlay();
    void PopulateHeader();
    void SetWindowMode(WindowMode m);

    /* toolbar */
    QActionGroup *toolGroup;
    QActionGroup *presetGroup;
    QAction *presetFile, *presetAuto, *presetFull;
    QAction *invertAction, *overlayAction, *headerAction;

    QListWidget *seriesList;
    QStackedWidget *stack;
    QLabel *message;
    ImageView *view;
    QPushButton *prevButton, *nextButton, *playButton;
    QSlider *slider;
    QLabel *position;
    QWidget *headerPanel;
    QLineEdit *headerFilter;
    QTableWidget *headerTable;
    QLabel *headerTitle;
    QTimer *cine;

    int node = -1;
    SliceList slices;
    int current = 0;
    DicomSlice slice;
    bool sliceOk = false;
    WindowMode windowMode = WindowMode::FromFile;
    double center = 0, width = 1;
    double sliceMin = 0, sliceMax = 1;
};

#endif // IMAGEVIEWER_H
