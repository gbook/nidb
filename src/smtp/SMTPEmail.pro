#-------------------------------------------------
#
# Project created by QtCreator 2011-08-11T20:59:25
#
#-------------------------------------------------

QT += core network
QT -= gui
CONFIG += c++17 cmdline

TARGET = SMTPEmail

# Build as an application
#TEMPLATE = app

# Build as a library
TEMPLATE = lib
DEFINES += SMTP_BUILD
win32:CONFIG += dll

SOURCES += \
    emailaddress.cpp \
    mimeattachment.cpp \
    mimefile.cpp \
    mimehtml.cpp \
    mimeinlinefile.cpp \
    mimemessage.cpp \
    mimepart.cpp \
    mimetext.cpp \
    smtpclient.cpp \
    quotedprintable.cpp \
    mimemultipart.cpp \
    mimecontentformatter.cpp \

HEADERS  += \
    emailaddress.h \
    mimeattachment.h \
    mimefile.h \
    mimehtml.h \
    mimeinlinefile.h \
    mimemessage.h \
    mimepart.h \
    mimetext.h \
    smtpclient.h \
    SmtpMime \
    quotedprintable.h \
    mimemultipart.h \
    mimecontentformatter.h \
    smtpexports.h

OTHER_FILES += \
    LICENSE \
    README.md

FORMS +=
