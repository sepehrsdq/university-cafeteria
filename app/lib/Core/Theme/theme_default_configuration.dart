import 'package:flutter/material.dart';

class AppThemes {
  static ThemeData get defaultTheme => ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Color(0xFFF9D4D4)),
        scaffoldBackgroundColor: Colors.white,
        useMaterial3: true,
      );
}
