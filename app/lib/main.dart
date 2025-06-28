import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Core/Constants/routes.dart';
import 'package:uni_project/Core/Theme/theme_default_configuration.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  // This widget is the root of your application.
  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: 'Uni Food App',
      debugShowCheckedModeBanner: false,
      theme: AppThemes.defaultTheme,
      getPages: AppRoutes.pages,
      initialRoute: AppRoutes.firstpage,
    );
  }
}
