import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class FirstPageController extends GetxController {
  final usernameCtrl = TextEditingController(); // تغییر به نام کاربری
  final passwordCtrl = TextEditingController();
  final obscureText = true.obs;
  var isLoading = false.obs;
  final GetConnect _getConnect = GetConnect();

  void togglePasswordVisibility() {
    obscureText.value = !obscureText.value;
  }

  /// **دریافت نقش و هدایت به صفحه مربوطه**
  Future<void> loginAction() async {
    final username = usernameCtrl.text.trim();
    final password = passwordCtrl.text.trim();

    if (username.isEmpty || password.isEmpty) {
      print("❌ خطا: فیلد نام کاربری یا رمز عبور خالی است.");
      Get.snackbar(
        'خطا',
        'نام کاربری یا رمز عبور خود را وارد کنید',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: Colors.redAccent,
        colorText: Colors.white,
      );
      return;
    }

    final url = 'http://127.0.0.1/cafeteria_api/login.php';

    // بررسی اینکه آیا کاربر دانشجو است یا کارمند
    bool isStudent =
        RegExp(r'^\d+$').hasMatch(username); // اگر فقط عدد بود، دانشجو است

    Map<String, String> requestBody = {
      'password': password,
      isStudent ? 'student_number' : 'staff_username':
          username, // فیلد مناسب ارسال شود
    };

    try {
      isLoading(true);
      final response = await _getConnect.post(url, requestBody);

      print("📡 درخواست ارسال شد به: $url");
      print("📤 اطلاعات ارسال شده: $requestBody");

      if (response.statusCode == 200 && response.body != null) {
        final responseData = response.body;
        print("📥 پاسخ سرور: $responseData");

        if (responseData['status'] == 'success') {
          String role = responseData['role'];

          SharedPreferences prefs = await SharedPreferences.getInstance();

          if (role == 'student') {
            await prefs.setString('student_number', username);
            int studentId =
                int.tryParse(responseData['user_id'].toString()) ?? 0;
            await prefs.setInt('student_id', studentId);
            Get.toNamed(AppRoutes.mainpage);
          } else if (role == 'staff') {
            await prefs.setString('staff_username', username);
            await prefs.setString(
                'staff_name', responseData['staff_name'] ?? 'کارمند');
            int staffId = int.tryParse(responseData['user_id'].toString()) ?? 0;
            await prefs.setInt('staff_id', staffId);

            Get.toNamed(AppRoutes.staffmainpage);
          }
        } else {
          print("❌ ورود ناموفق: ${responseData['message']}");
          Get.snackbar(
            'خطا',
            responseData['message'] ?? 'مشکلی پیش آمده است',
            snackPosition: SnackPosition.BOTTOM,
            backgroundColor: Colors.redAccent,
            colorText: Colors.white,
          );
        }
      } else {
        print("❌ خطای سرور: Status Code = ${response.statusCode}");
        Get.snackbar(
          'خطا',
          'خطای سرور، لطفاً دوباره تلاش کنید',
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: Colors.redAccent,
          colorText: Colors.white,
        );
      }
    } catch (error) {
      print("❌ خطا در برقراری ارتباط با سرور: $error");
      Get.snackbar(
        'خطا',
        'خطا در برقراری ارتباط با سرور',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: Colors.redAccent,
        colorText: Colors.white,
      );
    } finally {
      isLoading(false);
    }
  }
}
