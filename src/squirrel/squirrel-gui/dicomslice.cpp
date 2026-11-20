/* ------------------------------------------------------------------------------
  squirrel-gui dicomslice.cpp
  ------------------------------------------------------------------------------ */

#include "dicomslice.h"

#include "dcmtk/config/osconfig.h"
#include "dcmtk/dcmdata/dctk.h"
#include "dcmtk/dcmdata/dcrledrg.h"
#include "dcmtk/dcmimage/diregist.h"   /* colour image support for DicomImage */
#include "dcmtk/dcmimgle/dcmimage.h"
#include "dcmtk/dcmjpeg/djdecode.h"
#include "dcmtk/dcmjpls/djdecode.h"

#include <QFileInfo>
#include <algorithm>
#include <cmath>

namespace {

QString Str(DcmItem *ds, const DcmTagKey &key) {
    OFString v;
    if (ds->findAndGetOFStringArray(key, v).good())
        return QString::fromUtf8(v.c_str()).trimmed();
    return QString();
}

double Num(DcmItem *ds, const DcmTagKey &key, unsigned long pos = 0, double def = 0) {
    Float64 v = 0;
    if (ds->findAndGetFloat64(key, v, pos).good())
        return v;
    return def;
}

} // namespace

/* ------------------------------------------------------------ */
void RegisterDicomCodecs() {
    DJDecoderRegistration::registerCodecs();
    DJLSDecoderRegistration::registerCodecs();
    DcmRLEDecoderRegistration::registerCodecs();
}

/* ------------------------------------------------------------ */
SliceList ScanDicomSeries(const QStringList &files) {
    struct Entry {
        QString file;
        int instance;
        double location;
        int frames;
    };
    QList<Entry> entries;

    for (const QString &f : files) {
        DcmFileFormat ff;
        /* maxReadLength: large values (pixel data) are not loaded, so this is fast */
        if (ff.loadFile(QFileInfo(f).absoluteFilePath().toLocal8Bit().constData(), EXS_Unknown, EGL_noChange, 1024).bad())
            continue;
        DcmDataset *ds = ff.getDataset();
        if (!ds->tagExists(DCM_PixelData))
            continue;
        Sint32 instance = 0, frames = 1;
        ds->findAndGetSint32(DCM_InstanceNumber, instance);
        ds->findAndGetSint32(DCM_NumberOfFrames, frames);
        double location = Num(ds, DCM_SliceLocation, 0, 0);
        entries.append({f, int(instance), location, std::max(1, int(frames))});
    }

    std::sort(entries.begin(), entries.end(), [](const Entry &a, const Entry &b) {
        if (a.instance != b.instance)
            return a.instance < b.instance;
        if (a.location != b.location)
            return a.location < b.location;
        return a.file < b.file;
    });

    SliceList slices;
    for (const Entry &e : entries)
        for (int fr = 0; fr < e.frames; fr++)
            slices.append({e.file, fr});
    return slices;
}

/* ------------------------------------------------------------ */
QString OrientationLabel(const QVector3D &dir) {
    struct Axis {
        float mag;
        QChar letter;
    };
    QList<Axis> axes = {
        {std::fabs(dir.x()), dir.x() > 0 ? QChar('L') : QChar('R')},
        {std::fabs(dir.y()), dir.y() > 0 ? QChar('P') : QChar('A')},
        {std::fabs(dir.z()), dir.z() > 0 ? QChar('S') : QChar('I')},
    };
    std::sort(axes.begin(), axes.end(), [](const Axis &a, const Axis &b) { return a.mag > b.mag; });
    QString label;
    for (const Axis &a : axes)
        if (a.mag > 0.25f)
            label += a.letter;
    return label;
}

/* ------------------------------------------------------------ */
DicomSlice::DicomSlice() = default;
DicomSlice::~DicomSlice() = default;

bool DicomSlice::Load(const SliceRef &ref, QString &error) {
    image.reset();
    file = std::make_unique<DcmFileFormat>();
    OFCondition c = file->loadFile(QFileInfo(ref.file).absoluteFilePath().toLocal8Bit().constData());
    if (c.bad()) {
        error = QString("Could not read %1: %2").arg(QFileInfo(ref.file).fileName(), c.text());
        file.reset();
        return false;
    }
    DcmDataset *ds = file->getDataset();

    auto img = std::make_unique<DicomImage>(ds, ds->getOriginalXfer(), 0UL, (unsigned long)ref.frame, 1UL);
    if (img->getStatus() != EIS_Normal) {
        error = QString("Could not decode %1: %2").arg(QFileInfo(ref.file).fileName(), DicomImage::getString(img->getStatus()));
        file.reset();
        return false;
    }
    image = std::move(img);

    patientID = Str(ds, DCM_PatientID);
    studyDate = Str(ds, DCM_StudyDate);
    if (studyDate.size() == 8)
        studyDate = studyDate.left(4) + "-" + studyDate.mid(4, 2) + "-" + studyDate.right(2);
    manufacturer = Str(ds, DCM_Manufacturer);
    model = Str(ds, DCM_ManufacturerModelName);
    seriesNumber = Str(ds, DCM_SeriesNumber);
    seriesDescription = Str(ds, DCM_SeriesDescription);
    instanceNumber = Str(ds, DCM_InstanceNumber);
    sliceLocation = Str(ds, DCM_SliceLocation);
    rows = int(image->getHeight());
    columns = int(image->getWidth());
    pixelSpacingRow = Num(ds, DCM_PixelSpacing, 0);
    pixelSpacingCol = Num(ds, DCM_PixelSpacing, 1);
    sliceThickness = Num(ds, DCM_SliceThickness);

    hasOrientation = ds->tagExists(DCM_ImageOrientationPatient);
    if (hasOrientation) {
        rowDir = QVector3D(float(Num(ds, DCM_ImageOrientationPatient, 0)), float(Num(ds, DCM_ImageOrientationPatient, 1)), float(Num(ds, DCM_ImageOrientationPatient, 2)));
        colDir = QVector3D(float(Num(ds, DCM_ImageOrientationPatient, 3)), float(Num(ds, DCM_ImageOrientationPatient, 4)), float(Num(ds, DCM_ImageOrientationPatient, 5)));
    }

    fileWindowValid = ds->tagExists(DCM_WindowCenter) && ds->tagExists(DCM_WindowWidth);
    if (fileWindowValid) {
        fileCenter = Num(ds, DCM_WindowCenter);
        fileWidth = Num(ds, DCM_WindowWidth);
        fileWindowValid = fileWidth > 0;
    }
    return true;
}

bool DicomSlice::IsMonochrome() const {
    return image && image->isMonochrome();
}

void DicomSlice::MinMax(double &minValue, double &maxValue) const {
    minValue = 0;
    maxValue = 1;
    if (image)
        image->getMinMaxValues(minValue, maxValue, 0);
}

void DicomSlice::FullRange(double &minValue, double &maxValue) const {
    minValue = 0;
    maxValue = 1;
    if (image)
        image->getMinMaxValues(minValue, maxValue, 1);
}

QImage DicomSlice::Render(double center, double width, bool invert) const {
    if (!image)
        return QImage();
    const int w = int(image->getWidth());
    const int h = int(image->getHeight());
    QImage out;
    if (image->isMonochrome()) {
        image->setWindow(center, std::max(1.0, width));
        const void *data = image->getOutputData(8, 0, 0);
        if (!data)
            return QImage();
        out = QImage(static_cast<const uchar *>(data), w, h, w, QImage::Format_Grayscale8).copy();
    }
    else {
        const void *data = image->getOutputData(8, 0, 0);
        if (!data)
            return QImage();
        out = QImage(static_cast<const uchar *>(data), w, h, w * 3, QImage::Format_RGB888).copy();
    }
    if (invert)
        out.invertPixels();
    return out;
}

QList<HeaderRow> DicomSlice::Header() const {
    QList<HeaderRow> rows;
    if (!file)
        return rows;
    DcmDataset *ds = file->getDataset();
    for (unsigned long i = 0; i < ds->card(); i++) {
        DcmElement *e = ds->getElement(i);
        if (!e)
            continue;
        DcmTag tag(e->getTag());
        HeaderRow r;
        r.tag = QString("(%1,%2)").arg(tag.getGTag(), 4, 16, QChar('0')).arg(tag.getETag(), 4, 16, QChar('0')).toUpper();
        r.name = QString::fromLatin1(tag.getTagName());
        const DcmEVR vr = e->ident();
        if (tag == DCM_PixelData) {
            r.value = "(pixel data)";
        }
        else if (vr == EVR_SQ) {
            r.value = QString("(sequence, %1 items)").arg(static_cast<DcmSequenceOfItems *>(e)->card());
        }
        else if ((vr == EVR_OB || vr == EVR_OW || vr == EVR_UN || vr == EVR_OF || vr == EVR_OD) && e->getLength() > 64) {
            r.value = QString("(binary, %1 bytes)").arg(e->getLength());
        }
        else {
            OFString v;
            e->getOFStringArray(v);
            r.value = QString::fromUtf8(v.c_str());
            if (r.value.size() > 256)
                r.value = r.value.left(256) + "…";
        }
        rows.append(r);
    }
    return rows;
}
