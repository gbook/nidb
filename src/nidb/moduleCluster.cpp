/* ------------------------------------------------------------------------------
  NIDB moduleCluster.cpp
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

#include <QSqlQuery>
#include <QElapsedTimer>
#include "moduleCluster.h"


/* the command run on each node. It only reads /proc, and is written to work in sh and csh login shells.
   Each section starts with a ==name== marker line, and ==end== confirms the output is complete */
static const QString remoteCommand =
    "echo ==loadavg==; cat /proc/loadavg; "
    "echo ==cpu==; grep '^cpu ' /proc/stat; "
    "echo ==meminfo==; grep -E '^(MemTotal|MemFree|MemAvailable|Buffers|Cached|SwapTotal|SwapFree):' /proc/meminfo; "
    "echo ==cores==; grep -c '^processor' /proc/cpuinfo; "
    "echo ==uptime==; cat /proc/uptime; "
    "echo ==netdev==; grep ':' /proc/net/dev; "
    "echo ==diskstats==; grep -E ' (sd[a-z]+|vd[a-z]+|xvd[a-z]+|hd[a-z]+|nvme[0-9]+n[0-9]+|mmcblk[0-9]+) ' /proc/diskstats; "
    "echo ==end==";

static const int maxParallel = 20;          /* most ssh processes running at once */
static const int checkTimeoutMS = 20000;    /* a node that hasn't answered by now is marked unreachable */
static const int maxRateGapSec = 300;       /* rates aren't computed if the previous sample is older than this */


/* ---------------------------------------------------------- */
/* --------- moduleCluster ---------------------------------- */
/* ---------------------------------------------------------- */
moduleCluster::moduleCluster(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleCluster --------------------------------- */
/* ---------------------------------------------------------- */
moduleCluster::~moduleCluster()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check every enabled cluster node once, in parallel, and store a sample for each node that answered
 * @return 1 if the log should be kept (a node's status changed, or there was an error), 0 otherwise
 */
int moduleCluster::Run() {
    n->Log("Entering the cluster node monitor module");
    n->ModuleRunningCheckIn();

    bool keepLog = false;
    QList<clusterNode> nodes = GetNodes();
    n->Log(QString("Found [%1] enabled cluster nodes").arg(nodes.size()));

    /* every sample from this run gets the same time, the start of the current minute */
    QDateTime sampleTime = QDateTime::currentDateTime();
    sampleTime.setTime(QTime(sampleTime.time().hour(), sampleTime.time().minute(), 0));

    struct runningCheck {
        int nodeIndex;
        QProcess *proc;
        QElapsedTimer timer;
    };
    QList<runningCheck> running;
    int next = 0;
    int numOK = 0;
    int numFailed = 0;

    /* keep up to maxParallel ssh processes running. There's no event loop, so each running process is polled with a
       short waitForFinished(), which also reads its output so a large output can't fill the pipe and stall ssh */
    while ((next < nodes.size()) || (running.size() > 0)) {
        while ((running.size() < maxParallel) && (next < nodes.size())) {
            runningCheck r;
            r.nodeIndex = next++;
            r.proc = new QProcess();
            r.proc->setProgram("ssh");
            r.proc->setArguments(SSHArgs(nodes[r.nodeIndex]));
            r.proc->start();
            r.timer.start();
            running.append(r);
        }

        for (int i=running.size()-1; i>=0; i--) {
            runningCheck &r = running[i];

            bool finished = (r.proc->state() == QProcess::NotRunning) || r.proc->waitForFinished(25);
            bool timedOut = false;
            if (!finished && (r.timer.elapsed() > checkTimeoutMS)) {
                r.proc->kill();
                r.proc->waitForFinished(2000);
                timedOut = true;
            }
            if (!finished && !timedOut)
                continue;

            const clusterNode &node = nodes[r.nodeIndex];
            QString output = QString::fromUtf8(r.proc->readAllStandardOutput());
            QString stdErr = QString::fromUtf8(r.proc->readAllStandardError()).trimmed();

            QString status;
            QString msg;
            QJsonObject counters;
            if (timedOut) {
                status = "unreachable";
                msg = QString("No answer after %1 seconds").arg(checkTimeoutMS/1000);
            }
            else if (r.proc->error() == QProcess::FailedToStart) {
                status = "error";
                msg = "Unable to run ssh: " + r.proc->errorString();
            }
            else if ((r.proc->exitStatus() == QProcess::CrashExit) || (r.proc->exitCode() == 255)) {
                /* ssh exits with 255 when it can't connect or authenticate */
                status = "unreachable";
                msg = (stdErr == "") ? "ssh failed (exit code 255)" : stdErr;
            }
            else if (ParseOutput(output, QDateTime::currentMSecsSinceEpoch(), counters, msg)) {
                status = "ok";
            }
            else {
                status = "error";
                if (stdErr != "") msg += " [" + stdErr + "]";
            }

            if (status == "ok") {
                if (!SaveSample(node, sampleTime, counters)) keepLog = true;
                numOK++;
            }
            else {
                if (!SaveFailure(node, status, msg)) keepLog = true;
                n->Log(QString("Node [%1] %2: %3").arg(node.hostname).arg(status).arg(msg));
                numFailed++;
            }

            if (status != node.lastStatus) {
                n->Log(QString("Node [%1] status changed from [%2] to [%3]").arg(node.hostname).arg(node.lastStatus).arg(status));
                keepLog = true;
            }

            delete r.proc;
            running.removeAt(i);
        }
    }

    PruneSamples();

    n->Log(QString("Checked [%1] nodes: [%2] ok, [%3] failed").arg(nodes.size()).arg(numOK).arg(numFailed));

    return keepLog ? 1 : 0;
}


/* ---------------------------------------------------------- */
/* --------- GetNodes --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get the enabled nodes. The ssh username is the node's own, or the cluster's submit host username if blank.
   Nodes with an invalid hostname or username are marked as errors and not returned
 * @return list of nodes to check
 */
QList<moduleCluster::clusterNode> moduleCluster::GetNodes() {
    QList<clusterNode> nodes;

    /* hostnames and usernames are passed to ssh as arguments, so they must not look like options */
    static const QRegularExpression validHost("^[A-Za-z0-9][A-Za-z0-9.\\-]*$");
    static const QRegularExpression validUser("^[A-Za-z0-9_][A-Za-z0-9_.\\-]*$");

    QSqlQuery q;
    q.prepare("select n.clusternode_id, n.hostname, n.ssh_username, c.submithost_username, n.last_status, n.last_counters from cluster_nodes n left join compute_cluster c on n.computecluster_id = c.computecluster_id where n.enabled = 1 order by n.clusternode_id");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        clusterNode node;
        node.id = q.value("clusternode_id").toLongLong();
        node.hostname = q.value("hostname").toString().trimmed();
        node.username = q.value("ssh_username").toString().trimmed();
        if (node.username == "")
            node.username = q.value("submithost_username").toString().trimmed();
        node.lastStatus = q.value("last_status").toString();
        node.prevCounters = QJsonDocument::fromJson(q.value("last_counters").toString().toUtf8()).object();

        if (!validHost.match(node.hostname).hasMatch()) {
            SaveFailure(node, "error", "Invalid hostname");
            continue;
        }
        if ((node.username != "") && !validUser.match(node.username).hasMatch()) {
            SaveFailure(node, "error", "Invalid ssh username");
            continue;
        }
        nodes.append(node);
    }

    return nodes;
}


/* ---------------------------------------------------------- */
/* --------- SSHArgs ---------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief The ssh arguments to run the remote command on a node. BatchMode makes ssh fail instead of asking for a password,
   and new host keys are accepted automatically (changed ones are still rejected)
 */
QStringList moduleCluster::SSHArgs(const clusterNode &node) {
    QString dest = node.hostname;
    if (node.username != "")
        dest = node.username + "@" + node.hostname;

    QStringList args;
    args << "-n" << "-T"
         << "-o" << "BatchMode=yes"
         << "-o" << "ConnectTimeout=5"
         << "-o" << "StrictHostKeyChecking=accept-new"
         << "-o" << "LogLevel=ERROR"
         << "--" << dest << remoteCommand;
    return args;
}


/* ---------------------------------------------------------- */
/* --------- ParseOutput ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Parse the remote command's output into the raw values (counters and current values) for this check
 * @param output stdout of the remote command
 * @param epochMS time the check finished, used to compute rates on the next check
 * @param counters the parsed values
 * @param err reason the output couldn't be parsed
 * @return true if the required sections were parsed
 */
bool moduleCluster::ParseOutput(const QString &output, qint64 epochMS, QJsonObject &counters, QString &err) {
    /* split the output into its sections */
    QMap<QString, QStringList> sections;
    QString current;
    bool complete = false;
    for (QString line : output.split('\n')) {
        line = line.trimmed();
        if (line.startsWith("==") && line.endsWith("==") && (line.size() > 4)) {
            current = line.mid(2, line.size() - 4);
            if (current == "end") complete = true;
            continue;
        }
        if ((current != "") && (line != ""))
            sections[current].append(line);
    }
    if (!complete) {
        err = "Incomplete output from node";
        return false;
    }

    counters = QJsonObject();
    counters["t"] = (double)epochMS;

    /* loadavg: 0.52 0.58 0.59 2/1234 5678 */
    QStringList p = sections["loadavg"].value(0).split(' ', Qt::SkipEmptyParts);
    if (p.size() < 4) {
        err = "Unable to read /proc/loadavg";
        return false;
    }
    counters["load1"] = p[0].toDouble();
    counters["load5"] = p[1].toDouble();
    counters["load15"] = p[2].toDouble();
    QStringList procs = p[3].split('/');
    if (procs.size() == 2) {
        counters["procs_running"] = procs[0].toInt();
        counters["procs_total"] = procs[1].toInt();
    }

    /* cpu: cpu user nice system idle iowait irq softirq steal [guest guest_nice]. guest time is already included in user */
    p = sections["cpu"].value(0).split(' ', Qt::SkipEmptyParts);
    if ((p.size() < 5) || (p[0] != "cpu")) {
        err = "Unable to read /proc/stat";
        return false;
    }
    double c[8] = {0, 0, 0, 0, 0, 0, 0, 0};
    for (int i=0; (i<8) && (i+1<p.size()); i++)
        c[i] = p[i+1].toDouble();
    counters["cpu_user"] = c[0] + c[1];
    counters["cpu_system"] = c[2] + c[5] + c[6];
    counters["cpu_idle"] = c[3];
    counters["cpu_iowait"] = c[4];
    counters["cpu_steal"] = c[7];
    counters["cpu_total"] = c[0] + c[1] + c[2] + c[3] + c[4] + c[5] + c[6] + c[7];

    /* meminfo, in kB */
    QHash<QString, double> mem;
    for (const QString &line : sections["meminfo"]) {
        QStringList m = line.split(QRegularExpression("[:\\s]+"), Qt::SkipEmptyParts);
        if (m.size() >= 2)
            mem[m[0]] = m[1].toDouble();
    }
    if (!mem.contains("MemTotal")) {
        err = "Unable to read /proc/meminfo";
        return false;
    }
    /* MemAvailable isn't in kernels before 3.14 */
    double avail = mem.contains("MemAvailable") ? mem["MemAvailable"] : (mem.value("MemFree") + mem.value("Buffers") + mem.value("Cached"));
    counters["mem_total_kb"] = mem["MemTotal"];
    counters["mem_avail_kb"] = avail;
    counters["mem_cache_kb"] = mem.value("Buffers") + mem.value("Cached");
    if (mem.contains("SwapTotal")) {
        counters["swap_total_kb"] = mem["SwapTotal"];
        counters["swap_free_kb"] = mem.value("SwapFree");
    }

    /* number of cores */
    bool ok;
    int cores = sections["cores"].value(0).toInt(&ok);
    if (ok && (cores > 0))
        counters["cores"] = cores;

    /* uptime: 12345.67 23456.78 */
    double uptime = sections["uptime"].value(0).split(' ', Qt::SkipEmptyParts).value(0).toDouble(&ok);
    if (ok)
        counters["uptime"] = uptime;

    /* network: "  eth0: rxbytes rxpackets ... (8 rx fields) txbytes ...". Virtual and loopback interfaces are skipped */
    static const QRegularExpression skipIface("^(lo|veth|docker|br-|virbr|vnet|cali|flannel|cni|tun|tap)");
    if (sections.contains("netdev")) {
        double rx = 0, tx = 0;
        for (const QString &line : sections["netdev"]) {
            int colon = line.indexOf(':');
            if (colon < 1) continue;
            QString iface = line.left(colon).trimmed();
            if (skipIface.match(iface).hasMatch()) continue;
            QStringList f = line.mid(colon+1).split(' ', Qt::SkipEmptyParts);
            if (f.size() < 9) continue;
            rx += f[0].toDouble();
            tx += f[8].toDouble();
        }
        counters["net_rx"] = rx;
        counters["net_tx"] = tx;
    }

    /* disks: major minor name reads merged sectorsread ms writes merged sectorswritten ... (whole disks only, filtered on the node) */
    if (sections.contains("diskstats")) {
        double rd = 0, wr = 0;
        for (const QString &line : sections["diskstats"]) {
            QStringList f = line.split(' ', Qt::SkipEmptyParts);
            if (f.size() < 10) continue;
            rd += f[5].toDouble();
            wr += f[9].toDouble();
        }
        counters["disk_read"] = rd * 512.0;
        counters["disk_write"] = wr * 512.0;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ComputeSample ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Compute the values to store from this check's raw values. CPU, network and disk are cumulative counters, so they
   are computed from the change since the previous check. They're left as NAN if there's no usable previous check: none yet,
   too long ago, the node rebooted, or the counter went down
 */
void moduleCluster::ComputeSample(const QJsonObject &cur, const QJsonObject &prev, nodeSample &s) {
    s.load1 = cur["load1"].toDouble(NAN);
    s.load5 = cur["load5"].toDouble(NAN);
    s.load15 = cur["load15"].toDouble(NAN);
    s.numCores = cur["cores"].toInt(-1);
    s.procsRunning = cur["procs_running"].toInt(-1);
    s.procsTotal = cur["procs_total"].toInt(-1);
    s.uptimeSec = cur.contains("uptime") ? (qint64)cur["uptime"].toDouble() : -1;

    double memTotal = cur["mem_total_kb"].toDouble();
    s.memTotalMB = qRound64(memTotal / 1024.0);
    s.memUsedMB = qRound64((memTotal - cur["mem_avail_kb"].toDouble()) / 1024.0);
    s.memCacheMB = qRound64(cur["mem_cache_kb"].toDouble() / 1024.0);
    if (cur.contains("swap_total_kb")) {
        s.swapTotalMB = qRound64(cur["swap_total_kb"].toDouble() / 1024.0);
        s.swapUsedMB = qRound64((cur["swap_total_kb"].toDouble() - cur["swap_free_kb"].toDouble()) / 1024.0);
    }

    /* is the previous check usable for rates? */
    if (!prev.contains("t")) return;
    double dt = (cur["t"].toDouble() - prev["t"].toDouble()) / 1000.0;
    if ((dt < 10) || (dt > maxRateGapSec)) return;
    if (cur.contains("uptime") && prev.contains("uptime") && (cur["uptime"].toDouble() < prev["uptime"].toDouble())) return;

    /* change in a counter, or NAN if it's missing or went down */
    auto delta = [&](const char *key) -> double {
        if (!cur.contains(key) || !prev.contains(key)) return NAN;
        double d = cur[key].toDouble() - prev[key].toDouble();
        return (d < 0) ? NAN : d;
    };

    double dTotal = delta("cpu_total");
    if (!std::isnan(dTotal) && (dTotal > 0)) {
        double dUser = delta("cpu_user");
        double dSystem = delta("cpu_system");
        double dIdle = delta("cpu_idle");
        double dIOWait = delta("cpu_iowait");
        double dSteal = delta("cpu_steal");
        /* the iowait counter can go down on some kernels, so it's checked on its own */
        if (!std::isnan(dUser) && !std::isnan(dSystem) && !std::isnan(dIdle) && !std::isnan(dSteal)) {
            s.cpuUser = 100.0 * dUser / dTotal;
            s.cpuSystem = 100.0 * dSystem / dTotal;
            s.cpuSteal = 100.0 * dSteal / dTotal;
            s.cpuIOWait = std::isnan(dIOWait) ? NAN : (100.0 * dIOWait / dTotal);
            s.cpuBusy = 100.0 * (dTotal - dIdle - (std::isnan(dIOWait) ? 0 : dIOWait)) / dTotal;
            if (s.cpuBusy < 0) s.cpuBusy = 0;
        }
    }

    s.netRx = delta("net_rx") / dt;
    s.netTx = delta("net_tx") / dt;
    s.diskRead = delta("disk_read") / dt;
    s.diskWrite = delta("disk_write") / dt;
}


/* ---------------------------------------------------------- */
/* --------- SaveSample ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Store a sample for a node that answered, and mark the node ok
 * @return true if saved
 */
bool moduleCluster::SaveSample(const clusterNode &node, const QDateTime &sampleTime, const QJsonObject &counters) {
    nodeSample s;
    ComputeSample(counters, node.prevCounters, s);

    /* NAN and negative values are stored as null */
    auto dbl = [](double v) { return std::isnan(v) ? QVariant(QMetaType::fromType<double>()) : QVariant(v); };
    auto num = [](qint64 v) { return (v < 0) ? QVariant(QMetaType::fromType<qint64>()) : QVariant(v); };

    bool ok = true;
    QSqlQuery q;
    /* insert ignore, so a second run in the same minute doesn't add another sample */
    q.prepare("insert ignore into cluster_node_samples (clusternode_id, sample_time, load1, load5, load15, num_cores, procs_running, procs_total, cpu_busy_pct, cpu_user_pct, cpu_system_pct, cpu_iowait_pct, cpu_steal_pct, mem_total_mb, mem_used_mb, mem_cache_mb, swap_total_mb, swap_used_mb, net_rx_bps, net_tx_bps, disk_read_bps, disk_write_bps, uptime_sec) values (:id, :time, :load1, :load5, :load15, :cores, :procsrunning, :procstotal, :cpubusy, :cpuuser, :cpusystem, :cpuiowait, :cpusteal, :memtotal, :memused, :memcache, :swaptotal, :swapused, :netrx, :nettx, :diskread, :diskwrite, :uptime)");
    q.bindValue(":id", node.id);
    /* bound as a local time string. A QDateTime is converted to UTC by the Qt MySQL driver, but sample_time is compared
       to now() (local) everywhere else */
    q.bindValue(":time", sampleTime.toString("yyyy-MM-dd HH:mm:ss"));
    q.bindValue(":load1", dbl(s.load1));
    q.bindValue(":load5", dbl(s.load5));
    q.bindValue(":load15", dbl(s.load15));
    q.bindValue(":cores", num(s.numCores));
    q.bindValue(":procsrunning", num(s.procsRunning));
    q.bindValue(":procstotal", num(s.procsTotal));
    q.bindValue(":cpubusy", dbl(s.cpuBusy));
    q.bindValue(":cpuuser", dbl(s.cpuUser));
    q.bindValue(":cpusystem", dbl(s.cpuSystem));
    q.bindValue(":cpuiowait", dbl(s.cpuIOWait));
    q.bindValue(":cpusteal", dbl(s.cpuSteal));
    q.bindValue(":memtotal", num(s.memTotalMB));
    q.bindValue(":memused", num(s.memUsedMB));
    q.bindValue(":memcache", num(s.memCacheMB));
    q.bindValue(":swaptotal", num(s.swapTotalMB));
    q.bindValue(":swapused", num(s.swapUsedMB));
    q.bindValue(":netrx", dbl(s.netRx));
    q.bindValue(":nettx", dbl(s.netTx));
    q.bindValue(":diskread", dbl(s.diskRead));
    q.bindValue(":diskwrite", dbl(s.diskWrite));
    q.bindValue(":uptime", num(s.uptimeSec));
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &ok);

    /* the raw counters are kept for computing the next check's rates */
    bool ok2 = true;
    q.prepare("update cluster_nodes set last_checked = now(), last_seen = now(), last_status = 'ok', last_error = null, last_counters = :counters where clusternode_id = :id");
    q.bindValue(":counters", QString::fromUtf8(QJsonDocument(counters).toJson(QJsonDocument::Compact)));
    q.bindValue(":id", node.id);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &ok2);

    return ok && ok2;
}


/* ---------------------------------------------------------- */
/* --------- SaveFailure ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Mark a node as unreachable or error. The previous counters are kept, but are only used if the node answers again
   within maxRateGapSec
 * @return true if saved
 */
bool moduleCluster::SaveFailure(const clusterNode &node, QString status, QString err) {
    bool ok = true;
    QSqlQuery q;
    q.prepare("update cluster_nodes set last_checked = now(), last_status = :status, last_error = :err where clusternode_id = :id");
    q.bindValue(":status", status);
    q.bindValue(":err", err.left(2000));
    q.bindValue(":id", node.id);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &ok);
    return ok;
}


/* ---------------------------------------------------------- */
/* --------- PruneSamples ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Delete samples older than 30 days. Runs every minute, so each run only removes about one row per node
 */
void moduleCluster::PruneSamples() {
    QSqlQuery q;
    q.prepare("delete from cluster_node_samples where sample_time < date_sub(now(), interval 30 day) limit 50000");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.numRowsAffected() > 0)
        n->Debug(QString("Deleted [%1] samples older than 30 days").arg(q.numRowsAffected()));
}
