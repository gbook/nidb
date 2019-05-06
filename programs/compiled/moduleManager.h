#ifndef MODULEMANAGER_H
#define MODULEMANAGER_H
#include "nidb.h"

class moduleManager
{
public:
	moduleManager(nidb *a);
	~moduleManager();
	int Run();
private:
	nidb *n;
};

#endif // MODULEMANAGER_H
