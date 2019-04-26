#ifndef MODULEEXPORT_H
#define MODULEEXPORT_H
#include "nidb.h"

class moduleExport
{
public:
    moduleExport(nidb *a);
    ~moduleExport();
    int Run();
private:
    nidb *n;
};

#endif // MODULEEXPORT_H
