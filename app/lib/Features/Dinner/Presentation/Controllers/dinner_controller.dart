import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class DinnerController extends GetxController {
  var dinners = [].obs;
  var isLoading = false.obs;
  var errorMessage = "".obs;
  final GetConnect _getConnect = GetConnect();

  void routeMainPage() {
    Get.toNamed(AppRoutes.mainpage);
  }

  @override
  void onInit() {
    super.onInit();
    Future.delayed(Duration.zero, () async {
      await fetchDinners();
    });
  }

  Future<void> fetchDinners() async {
    try {
      isLoading(true);
      final response = await _getConnect
          .get("http://127.0.0.1/cafeteria_api/get_dinner.php");

      if (response.statusCode == 200 && response.body != null) {
        dinners.value = response.body;
      } else {
        throw Exception("خطا در دریافت اطلاعات");
      }
    } catch (e) {
      print("خطا در دریافت داده‌ها: $e");
    } finally {
      isLoading(false);
    }
  }

  Future<String> reserveMeal(int mealId) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String studentNumber = prefs.getString('student_number') ?? "";

      if (studentNumber.isEmpty) {
        return "خطا: شماره دانشجویی یافت نشد!";
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

        if (responseData['status'] == 'success') {
          return "success";
        } else {
          return responseData['message'] ?? "خطا در رزرو غذا!";
        }
      } else {
        return "خطا در ارتباط با سرور!";
      }
    } catch (e) {
      return "خطای غیرمنتظره: $e";
    }
  }
}
