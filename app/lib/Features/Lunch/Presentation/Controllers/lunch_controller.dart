import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class LunchController extends GetxController {
  var lunches = [].obs;
  var isLoading = false.obs;
  final GetConnect _getConnect = GetConnect();
  var errorMessage = "".obs; // برای نمایش پیام خطا

  void routeMainPage() {
    Get.toNamed(AppRoutes.mainpage);
  }

  @override
  void onInit() {
    super.onInit();
    fetchLunches();
  }

  Future<void> fetchLunches() async {
    try {
      isLoading(true);
      final response =
          await _getConnect.get("http://127.0.0.1/cafeteria_api/get_lunch.php");

      if (response.statusCode == 200 && response.body != null) {
        lunches.value = response.body;
      } else {
        throw Exception("خطا در دریافت اطلاعات");
      }
    } catch (e) {
      print("❌ خطا در دریافت داده‌ها: $e");
    } finally {
      isLoading(false);
    }
  }

  Future<void> reserveMeal(int mealId) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String studentNumber = prefs.getString('student_number') ?? "";

      if (studentNumber.isEmpty) {
        errorMessage.value = "خطا: شماره دانشجویی یافت نشد!";
        return;
      }

      final response = await _getConnect.post(
        "http://127.0.0.1/cafeteria_api/reserve_meal.php",
        {
          "student_number": studentNumber,
          "meal_id": mealId.toString(),
          "meal_type": "1"
        },
      );

      debugPrint("📌 پاسخ API: ${response.body}");

      if (response.statusCode == 200 && response.body != null) {
        final responseData = response.body;

        if (responseData['status'] == 'success') {
          errorMessage.value = ""; // پاک کردن پیام خطا در صورت موفقیت
        } else {
          errorMessage.value = responseData['message'] ?? "خطا در رزرو غذا!";
        }
      } else {
        errorMessage.value = "خطا در ارتباط با سرور!";
      }
    } catch (e) {
      errorMessage.value = "خطای غیرمنتظره: $e";
    }
  }
}
