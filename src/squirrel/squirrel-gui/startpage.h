/* ------------------------------------------------------------------------------
  squirrel-gui startpage.h
  Shown when no package is open: drop area, Open button, recent packages.
  ------------------------------------------------------------------------------ */

#ifndef STARTPAGE_H
#define STARTPAGE_H

#include <QFrame>
#include <QWidget>

class QListWidget;
class QListWidgetItem;
class QPushButton;

/* dashed drop target; drag and drop itself is handled by MainWindow */
class DropArea : public QFrame {
    Q_OBJECT
public:
    explicit DropArea(QWidget *parent = nullptr);
    void SetActive(bool a);

protected:
    void paintEvent(QPaintEvent *e) override;

private:
    bool active = false;
};

class StartPage : public QWidget {
    Q_OBJECT
public:
    explicit StartPage(QWidget *parent = nullptr);
    void Refresh();                    /* reload the recent list */
    void SetDragActive(bool a);

signals:
    void OpenRequested();
    void RecentRequested(const QString &path);

private:
    DropArea *dropArea;
    QListWidget *recentList;
    QPushButton *clearButton;
    QWidget *recentSection;
};

#endif // STARTPAGE_H
