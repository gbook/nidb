TEMPLATE = aux

INSTALLER = installer

INPUT = $$PWD/config/config.xml $$PWD/packages
example.input = INPUT
example.output = $$INSTALLER
win32: {
    example.commands = C:/Qt/Tools/QtInstallerFramework/3.0/bin/binarycreator -c $$PWD/config/config.xml -p $$PWD/packages ${QMAKE_FILE_OUT}
}
else {
    example.commands = /Qt/Tools/QtInstallerFramework/3.1/bin/binarycreator -c $$PWD/config/config.xml -p $$PWD/packages ${QMAKE_FILE_OUT}
}
example.CONFIG += target_predeps no_link combine

QMAKE_EXTRA_COMPILERS += example

OTHER_FILES = README

DISTFILES += \
    nidb.png \
    packages/nidb.config/meta/installscript.js \
    packages/nidb.config/meta/package.xml \
    packages/nidb.other/meta/installscript.js \
    packages/nidb.other/meta/package.xml \
    packages/nidb.programs/meta/installscript.js \
    packages/nidb.programs/meta/package.xml \
    packages/nidb.web/meta/installscript.js \
    packages/nidb.web/meta/package.xml \
    squirrel.png

FORMS +=
