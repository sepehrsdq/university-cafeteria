import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class FoodManagementController extends GetxController {
  var foods = [].obs;
  var selectedType = "breakfast".obs;
  var filteredFoods = [].obs;
  var isLoading = false.obs;
  var selectedFilter = "".obs;
  final GetConnect _getConnect = GetConnect();

  void routeMainPage() {
    Get.toNamed(AppRoutes.staffmainpage);
  }

  void changeType(String type) {
    selectedType.value = type;
  }

  @override
  void onInit() {
    super.onInit();
    fetchFoods(); // هنگام ورود به صفحه، داده‌ها را از سرور دریافت کن
  }

  Future<void> fetchFoods() async {
    try {
      isLoading(true);
      final response =
          await _getConnect.get("http://127.0.0.1/cafeteria_api/get_foods.php");

      if (response.statusCode == 200 && response.body != null) {
        foods.value = response.body;
        applyFilter();
      } else {
        throw Exception("خطا در دریافت اطلاعات");
      }
    } catch (e) {
      print("❌ خطا در دریافت داده‌ها: $e");
    } finally {
      isLoading(false);
    }
  }

  void filterFoods(String type) {
    selectedFilter.value = type;
    fetchFoods(); // دریافت دوباره داده‌ها از سرور
  }

  void applyFilter() {
    if (selectedFilter.value.isEmpty) {
      filteredFoods.value = foods;
    } else {
      filteredFoods.value =
          foods.where((food) => food["type"] == selectedFilter.value).toList();
    }
  }

  Future<bool> updateFood(
      {required int id,
      required String name,
      required int price,
      required String imageUrl}) async {
    try {
      final response = await _getConnect
          .post("http://127.0.0.1/cafeteria_api/update_food.php", {
        "id": id.toString(),
        "name": name,
        "price": price.toString(),
        "image_url": imageUrl,
      });

      if (response.statusCode == 200 && response.body["status"] == "success") {
        Get.snackbar(
          "موفقیت 🎉",
          "غذا با موفقیت ویرایش شد!",
          backgroundColor: Colors.green,
          colorText: Colors.white,
          snackPosition: SnackPosition.BOTTOM,
          duration: const Duration(seconds: 2),
        );
        await Future.delayed(const Duration(seconds: 1));
        routeMainPage();
        // برگشت به صفحه مدیریت غذا
        return true;
      } else {
        Get.snackbar(
          "خطا ❌",
          response.body["message"] ?? "خطا در ویرایش غذا!",
          backgroundColor: Colors.red,
          colorText: Colors.white,
        );
        return false;
      }
    } catch (e) {
      debugPrint("❌ خطا در ویرایش غذا: $e");
      Get.snackbar("خطا", "مشکلی پیش آمده است!", backgroundColor: Colors.red);
      return false; // اگر خطایی پیش بیاد، مقدار false برگردون
    }
  }

  Future<bool> addFood({
    required String name,
    required String type,
    required int price,
    required String date,
    required String imageUrl,
  }) async {
    try {
      final response = await _getConnect.post(
        "http://127.0.0.1/cafeteria_api/add_food.php",
        {
          "name": name,
          "type": type,
          "price": price.toString(),
          "date": date,
          "image_url": imageUrl,
        },
      );

      debugPrint("📥 پاسخ سرور: ${response.body}");

      if (response.statusCode == 200 && response.body["status"] == "success") {
        Get.snackbar(
          "موفقیت 🎉",
          "غذا با موفقیت اضافه شد!",
          backgroundColor: Colors.green,
          colorText: Colors.white,
          snackPosition: SnackPosition.BOTTOM,
          duration: const Duration(seconds: 2),
        );
        await Future.delayed(const Duration(seconds: 1));
        routeMainPage();
        // برگشت به صفحه مدیریت غذا
        return true;
      } else {
        Get.snackbar(
          "خطا ❌",
          response.body["message"] ?? "خطا در افزودن غذا!",
          backgroundColor: Colors.red,
          colorText: Colors.white,
        );
        return false;
      }
    } catch (e) {
      debugPrint("❌ خطا در افزودن غذا: $e");
      Get.snackbar("خطا", "مشکلی پیش آمده است!", backgroundColor: Colors.red);
      return false; // اگر خطایی پیش بیاد، مقدار false برگردون
    }
  }

  Future<void> deleteFood(int id) async {
    try {
      final response = await _getConnect.post(
          "http://127.0.0.1/cafeteria_api/delete_food.php",
          {"id": id.toString()});

      if (response.body["status"] == "success") {
        Get.snackbar("موفقیت", "غذا حذف شد!",
            backgroundColor: Colors.green, colorText: Colors.white);
        fetchFoods();
      } else {
        Get.snackbar("خطا", response.body["message"] ?? "خطا در حذف غذا!",
            backgroundColor: Colors.red, colorText: Colors.white);
      }
    } catch (e) {
      Get.snackbar("خطا", "مشکلی پیش آمده است",
          backgroundColor: Colors.red, colorText: Colors.white);
    }
  }
}
