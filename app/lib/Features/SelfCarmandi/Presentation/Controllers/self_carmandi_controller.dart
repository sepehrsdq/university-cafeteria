import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class SelfCarmandiController extends GetxController {
  var meals = [].obs;
  var isLoading = false.obs;
  var errorMessage = "".obs; // ذخیره پیام خطا
  final GetConnect _getConnect = GetConnect();

  void routeMainPage() {
    Get.toNamed(AppRoutes.mainpage);
  }

  @override
  void onInit() {
    super.onInit();
    Future.delayed(Duration.zero, () async {
      await fetchSelfCarmandi();
    });
  }

  Future<void> fetchSelfCarmandi() async {
    try {
      isLoading(true);
      final response = await _getConnect
          .get("http://127.0.0.1/cafeteria_api/get_self_carmandi.php");

      if (response.statusCode == 200 && response.body != null) {
        meals.value = response.body;
      } else {
        throw Exception("خطا در دریافت اطلاعات");
      }
    } catch (e) {
      print("خطا در دریافت داده‌ها: $e");
    } finally {
      isLoading(false);
    }
  }

  Future<void> reserveMeal(int mealId) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String studentNumber = prefs.getString('student_number') ?? "";
      errorMessage.value = ""; // پاک کردن خطای قبلی

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

      if (response.statusCode == 200 && response.body != null) {
        final responseData = response.body;
        if (responseData['status'] != 'success') {
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
