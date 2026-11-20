/* ------------------------------------------------------------------------------
  squirrel-gui theme.cpp
  ------------------------------------------------------------------------------ */

#include "theme.h"

#include <QApplication>
#include <QPalette>
#include <QStyleFactory>
#include <QStyle>
#include <QStyleHints>

namespace {

Theme::Mode currentMode = Theme::Mode::System;
bool currentDark = false;
bool hintsConnected = false;

QPalette LightPalette() {
    QPalette p;
    p.setColor(QPalette::Window, QColor("#F3F4F6"));
    p.setColor(QPalette::WindowText, QColor("#1D2125"));
    p.setColor(QPalette::Base, QColor("#FFFFFF"));
    p.setColor(QPalette::AlternateBase, QColor("#F8F9FA"));
    p.setColor(QPalette::ToolTipBase, QColor("#FFFFFF"));
    p.setColor(QPalette::ToolTipText, QColor("#1D2125"));
    p.setColor(QPalette::PlaceholderText, QColor("#6B7280"));
    p.setColor(QPalette::Text, QColor("#1D2125"));
    p.setColor(QPalette::Button, QColor("#FFFFFF"));
    p.setColor(QPalette::ButtonText, QColor("#1D2125"));
    p.setColor(QPalette::BrightText, QColor("#B91C1C"));
    p.setColor(QPalette::Highlight, QColor("#2563EB"));
    p.setColor(QPalette::HighlightedText, QColor("#FFFFFF"));
    p.setColor(QPalette::Link, QColor("#1D4ED8"));
    p.setColor(QPalette::Mid, QColor("#D7DBE0"));
    p.setColor(QPalette::Midlight, QColor("#E3E6EA"));
    p.setColor(QPalette::Dark, QColor("#A0A6AE"));
    p.setColor(QPalette::Disabled, QPalette::Text, QColor("#9AA0A8"));
    p.setColor(QPalette::Disabled, QPalette::ButtonText, QColor("#9AA0A8"));
    p.setColor(QPalette::Disabled, QPalette::WindowText, QColor("#9AA0A8"));
    return p;
}

QPalette DarkPalette() {
    QPalette p;
    p.setColor(QPalette::Window, QColor("#1B1D20"));
    p.setColor(QPalette::WindowText, QColor("#E6E8EB"));
    p.setColor(QPalette::Base, QColor("#232629"));
    p.setColor(QPalette::AlternateBase, QColor("#2A2D31"));
    p.setColor(QPalette::ToolTipBase, QColor("#2A2D31"));
    p.setColor(QPalette::ToolTipText, QColor("#E6E8EB"));
    p.setColor(QPalette::PlaceholderText, QColor("#8B9199"));
    p.setColor(QPalette::Text, QColor("#E6E8EB"));
    p.setColor(QPalette::Button, QColor("#2A2D31"));
    p.setColor(QPalette::ButtonText, QColor("#E6E8EB"));
    p.setColor(QPalette::BrightText, QColor("#F87171"));
    p.setColor(QPalette::Highlight, QColor("#3B6FD8"));
    p.setColor(QPalette::HighlightedText, QColor("#FFFFFF"));
    p.setColor(QPalette::Link, QColor("#8AB4F8"));
    p.setColor(QPalette::Mid, QColor("#3A3F45"));
    p.setColor(QPalette::Midlight, QColor("#33373C"));
    p.setColor(QPalette::Dark, QColor("#15171A"));
    p.setColor(QPalette::Shadow, QColor("#000000"));
    p.setColor(QPalette::Disabled, QPalette::Text, QColor("#6B7178"));
    p.setColor(QPalette::Disabled, QPalette::ButtonText, QColor("#6B7178"));
    p.setColor(QPalette::Disabled, QPalette::WindowText, QColor("#6B7178"));
    return p;
}

bool SystemIsDark() {
    return QGuiApplication::styleHints()->colorScheme() == Qt::ColorScheme::Dark;
}

} // namespace

namespace Theme {

void Apply(Mode mode) {
    currentMode = mode;
    /* creating a new style resets the palette, so do it only once */
    if (QApplication::style()->name().compare("fusion", Qt::CaseInsensitive) != 0)
        QApplication::setStyle(QStyleFactory::create("Fusion"));
    currentDark = (mode == Mode::Dark) || (mode == Mode::System && SystemIsDark());
    QApplication::setPalette(currentDark ? DarkPalette() : LightPalette());

    if (!hintsConnected) {
        hintsConnected = true;
        QObject::connect(QGuiApplication::styleHints(), &QStyleHints::colorSchemeChanged, qApp, [](Qt::ColorScheme) {
            if (currentMode == Mode::System)
                Apply(Mode::System);
        });
    }
}

QPalette ViewerPalette() {
    QPalette p = DarkPalette();
    p.setColor(QPalette::Window, QColor("#17191C"));
    p.setColor(QPalette::Base, QColor("#0E0F11"));
    p.setColor(QPalette::Button, QColor("#1E2124"));
    return p;
}

Mode Current() { return currentMode; }
bool IsDark() { return currentDark; }

QString ModeToString(Mode m) {
    switch (m) {
        case Mode::Light: return "light";
        case Mode::Dark: return "dark";
        default: return "system";
    }
}

Mode ModeFromString(const QString &s) {
    if (s == "light") return Mode::Light;
    if (s == "dark") return Mode::Dark;
    return Mode::System;
}

QColor MutedText() {
    return currentDark ? QColor("#A8ADB5") : QColor("#59616B");
}

} // namespace Theme
