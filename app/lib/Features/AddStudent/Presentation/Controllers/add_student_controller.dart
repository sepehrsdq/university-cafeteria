import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:uni_project/Core/Constants/routes.dart';

class AddStudentController extends GetxController {
  TextEditingController nameController = TextEditingController();
  TextEditingController studentNumberController = TextEditingController();
  TextEditingController passwordController = TextEditingController();
  RxBool isLoading = false.obs;
  void routeMainPage() {
    Get.toNamed(AppRoutes.staffmainpage);
  }

  Future<void> addStudent() async {
    if (nameController.text.isEmpty ||
        studentNumberController.text.isEmpty ||
        passwordController.text.isEmpty) {
      Get.snackbar("خطا", "همه فیلدها را پر کنید",
          backgroundColor: Colors.red, colorText: Colors.white);
      return;
    }

    isLoading.value = true;
    final url = Uri.parse("http://127.0.0.1/cafeteria_api/register.php");

    final response = await http.post(
      url,
      body: jsonEncode({
        "name": nameController.text,
        "student_number": studentNumberController.text,
        "password": passwordController.text,
      }),
    );

    isLoading.value = false;

    if (response.statusCode == 200) {
      final responseData = jsonDecode(response.body);
      if (responseData["success"] == true) {
        Get.snackbar("موفق", responseData["message"],
            backgroundColor: Colors.green, colorText: Colors.white);
        nameController.clear();
        studentNumberController.clear();
        passwordController.clear();
        routeMainPage();
      } else {
        Get.snackbar("خطا", responseData["message"],
            backgroundColor: Colors.red, colorText: Colors.white);
      }
    } else {
      Get.snackbar("خطا", "ثبت دانشجو ناموفق بود",
          backgroundColor: Colors.red, colorText: Colors.white);
    }
  }
}
