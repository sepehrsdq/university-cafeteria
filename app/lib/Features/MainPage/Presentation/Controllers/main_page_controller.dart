import 'package:get/get.dart';
import 'package:uni_project/Core/Constants/routes.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MainPageController extends GetxController {
  var balance = 0.obs;
  var studentNumber = ''.obs;
  final GetConnect _getConnect = GetConnect();

  @override
  void onInit() {
    super.onInit();
    loadStudentNumber();
  }

  Future<void> loadStudentNumber() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    studentNumber.value = prefs.getString('student_number') ?? '';
    if (studentNumber.isNotEmpty) {
      fetchBalance();
    }
  }

  Future<void> fetchBalance() async {
    if (studentNumber.isEmpty) return;

    final url =
        'http://127.0.0.1/cafeteria_api/get_balance.php?student_number=$studentNumber';

    try {
      final response = await _getConnect.get(url);

      if (response.statusCode == 200 && response.body != null) {
        final responseData = response.body;

        if (responseData['status'] == 'success') {
          balance.value = int.tryParse(responseData['balance'].toString()) ?? 0;
        } else {
          print('❌ خطا در دریافت موجودی: ${responseData['message']}');
        }
      } else {
        print('❌ خطا در دریافت اطلاعات - Status Code: ${response.statusCode}');
      }
    } catch (error) {
      print('❌ خطا در ارتباط با سرور: $error');
    }
  }

  Future<void> routeBreakFast() async {
    Get.toNamed(AppRoutes.breakfast);
  }

  void routeSelfCarmandi() {
    Get.toNamed(AppRoutes.selfcarmandi);
  }

  void routeLunch() {
    Get.toNamed(AppRoutes.lunch);
  }

  void routeDinner() {
    Get.toNamed(AppRoutes.dinner);
  }

  void routeTickets() {
    Get.toNamed(AppRoutes.tickets);
  }
}
