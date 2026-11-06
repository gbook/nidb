/* ------------------------------------------------------------------------------
  NIDB moduleCluster.h
  Copyright (C) 2004 - 2026
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

#ifndef MODULECLUSTER_H
#define MODULECLUSTER_H
#include <cmath>
#include "nidb.h"

/* Cluster node monitor. Every minute, ssh's into each enabled cluster_nodes row, reads load/CPU/memory/network/disk
   from /proc, and stores one cluster_node_samples row per node. Samples older than 30 days are deleted.
   Design: doc/cluster-monitor.md */
class moduleCluster
{
public:
    moduleCluster(nidb *n);
    ~moduleCluster();

    int Run();

private:
    /* an enabled node to check */
    struct clusterNode {
        qint64 id = 0;
        QString hostname;
        QString username;
        QString lastStatus;
        QJsonObject prevCounters; /* raw counters from the previous successful check */
    };

    /* the values parsed from one node's output. Rate/percent values are NAN when they can't be computed */
    struct nodeSample {
        double load1 = NAN, load5 = NAN, load15 = NAN;
        int numCores = -1;
        int procsRunning = -1, procsTotal = -1;
        double cpuBusy = NAN, cpuUser = NAN, cpuSystem = NAN, cpuIOWait = NAN, cpuSteal = NAN;
        qint64 memTotalMB = -1, memUsedMB = -1, memCacheMB = -1, swapTotalMB = -1, swapUsedMB = -1;
        double netRx = NAN, netTx = NAN, diskRead = NAN, diskWrite = NAN;
        qint64 uptimeSec = -1;
    };

    QList<clusterNode> GetNodes();
    QStringList SSHArgs(const clusterNode &node);
    bool ParseOutput(const QString &output, qint64 epochMS, QJsonObject &counters, QString &err);
    void ComputeSample(const QJsonObject &counters, const QJsonObject &prev, nodeSample &s);
    bool SaveSample(const clusterNode &node, const QDateTime &sampleTime, const QJsonObject &counters);
    bool SaveFailure(const clusterNode &node, QString status, QString err);
    void PruneSamples();

    nidb *n;
};

#endif // MODULECLUSTER_H
