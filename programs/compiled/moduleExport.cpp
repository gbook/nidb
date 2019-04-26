#include "moduleExport.h"
#include <QDebug>

moduleExport::moduleExport(nidb *a)
{
	n = a;
}

moduleExport::~moduleExport()
{

}

int moduleExport::Run() {
    qDebug() << "Entering the fileio module";

    return 1;
}
