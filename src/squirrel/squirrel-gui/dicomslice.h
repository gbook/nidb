/* ------------------------------------------------------------------------------
  squirrel-gui dicomslice.h
  DICOM loading for the image viewer, using DCMTK.

  ScanDicomSeries() reads only the headers of a series' files and returns the
  images in display order. DicomSlice loads one image (file + frame) and
  renders it to a QImage with a given window/level.
  ------------------------------------------------------------------------------ */

#ifndef DICOMSLICE_H
#define DICOMSLICE_H

#include <QImage>
#include <QList>
#include <QString>
#include <QVector3D>
#include <memory>

#include "packageworker.h"   /* SliceRef, SliceList */

class DcmFileFormat;
class DicomImage;

/* call once at startup: JPEG, JPEG-LS and RLE decoders */
void RegisterDicomCodecs();

/* headers only; files that are not DICOM images are skipped */
SliceList ScanDicomSeries(const QStringList &files);

struct HeaderRow {
    QString tag;
    QString name;
    QString value;
};

class DicomSlice {
public:
    DicomSlice();
    ~DicomSlice();

    bool Load(const SliceRef &ref, QString &error);
    bool IsLoaded() const { return image != nullptr; }
    bool IsMonochrome() const;

    /* window values */
    bool HasFileWindow() const { return fileWindowValid; }
    double FileCenter() const { return fileCenter; }
    double FileWidth() const { return fileWidth; }
    void MinMax(double &minValue, double &maxValue) const;      /* pixel values present */
    void FullRange(double &minValue, double &maxValue) const;   /* representable range */

    QImage Render(double center, double width, bool invert) const;
    QList<HeaderRow> Header() const;

    /* overlay information */
    QString patientID, studyDate, manufacturer, model;
    QString seriesNumber, seriesDescription;
    QString instanceNumber, sliceLocation;
    int rows = 0, columns = 0;
    double pixelSpacingRow = 0, pixelSpacingCol = 0, sliceThickness = 0;
    bool hasOrientation = false;
    QVector3D rowDir, colDir;   /* ImageOrientationPatient */

private:
    std::unique_ptr<DcmFileFormat> file;
    std::unique_ptr<DicomImage> image;
    bool fileWindowValid = false;
    double fileCenter = 0, fileWidth = 0;
};

/* patient-orientation letters (e.g. "L", "AP", "SL") for a direction vector */
QString OrientationLabel(const QVector3D &dir);

#endif // DICOMSLICE_H
