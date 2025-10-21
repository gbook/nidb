/* ------------------------------------------------------------------------------
  NIDB moduleQC.h
  Copyright (C) 2004 - 2025
  Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
  Olin Neuropsychiatry Research Center, Hartford Hospital
  ------------------------------------------------------------------------------
  GPLv3 License:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  ------------------------------------------------------------------------------ */

#ifndef MODULEQC_H
#define MODULEQC_H
#include "nidb.h"
#include "series.h"
#include "imageio.h"

class moduleQC
{
public:
    moduleQC();
    moduleQC(nidb *n);
    ~moduleQC();

    int Run();
    bool QC(int moduleid, int seriesid, QString modality);
    bool WriteClusterJobFile(QString jobFileName, QString jobName, qint64 clusterRowID, QString localDataPath, QString clusterDataPath, QString entryPoint);

private:
    nidb *n;

    computeCluster GetClusterInfo(qint64 clusterRowID);
    bool ExportSeries(qint64 seriesRowID, QString modality, ExportFormat format, QString outputDir);
};

#endif // MODULEQC_H
