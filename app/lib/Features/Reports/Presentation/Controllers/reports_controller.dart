import 'package:get/get.dart';
import 'package:shamsi_date/shamsi_date.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

import 'package:uni_project/Core/Constants/routes.dart';

class ReportsController extends GetxController {
  var isLoading = true.obs;
  var totalReservationsToday = 0.obs;
  var dailyIncome = 0.obs;
  var monthlyIncome = 0.obs;
  var popularMeals = [].obs;
  var leastPopularMeals = [].obs;
  void routeMainPage() {
    Get.toNamed(AppRoutes.staffmainpage);
  }

  @override
  void onInit() {
    super.onInit();
    fetchTotalReservations();
    fetchIncomeReport();
    fetchPopularMeals();
  }

  // دریافت تعداد رزروهای امروز
  Future<void> fetchTotalReservations() async {
    try {
      final response = await http.get(Uri.parse(
          "http://127.0.0.1/cafeteria_api/get_total_reservations.php"));
      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        totalReservationsToday.value = data['total_reservations'];
      }
    } catch (e) {
      print("Error: $e");
    }
  }

  // دریافت میزان درآمد روزانه و ماهانه
  Future<void> fetchIncomeReport() async {
    try {
      final response = await http.get(
          Uri.parse("http://127.0.0.1/cafeteria_api/get_income_report.php"));
      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        dailyIncome.value = data['daily_income'];
        monthlyIncome.value = data['monthly_income'];
      }
    } catch (e) {
      print("Error: $e");
    }
  }

  // دریافت لیست غذاهای پرطرفدار و کم‌طرفدار
  Future<void> fetchPopularMeals() async {
    try {
      final response = await http.get(
          Uri.parse("http://127.0.0.1/cafeteria_api/get_popular_meals.php"));
      if (response.statusCode == 200) {
        var data = json.decode(response.body);
        popularMeals.value = data['popular_meals'];
        leastPopularMeals.value = data['least_popular_meals'];
      }
    } catch (e) {
      print("Error: $e");
    } finally {
      isLoading(false);
    }
  }

  String convertToShamsi(String date) {
    DateTime miladiDate = DateTime.parse(date);
    final shamsiDate = miladiDate.toJalali();
    return "${shamsiDate.year}/${shamsiDate.month}/${shamsiDate.day}";
  }
}
