import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

import 'package:shamsi_date/shamsi_date.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class StudentSelfController extends GetxController {
  var reservations = [].obs;
  var isLoading = true.obs;
  var searchText = ''.obs;

  void routeMainPage() {
    Get.toNamed(AppRoutes.staffmainpage);
  }

// تبدیل تاریخ میلادی به شمسی
  String convertToShamsi(String date) {
    try {
      DateTime gregorianDate = DateTime.parse(date);
      final jalali = Jalali.fromDateTime(gregorianDate);
      return '${jalali.year}/${jalali.month}/${jalali.day}';
    } catch (e) {
      return 'نامعتبر';
    }
  }

  // تبدیل نوع سرو غذا به فارسی
  String convertServeType(String type) {
    switch (type) {
      case 'self':
        return 'داخل سلف';
      case 'packaged':
        return 'بسته‌بندی';
      default:
        return 'نامشخص';
    }
  }

  @override
  void onInit() {
    fetchReservations();
    super.onInit();
  }

  Future<void> fetchReservations({String query = ''}) async {
    try {
      isLoading(true);
      final url = Uri.parse(
          'http://127.0.0.1/cafeteria_api/get_reservations.php?search=$query');
      final response = await http.get(url);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          reservations.assignAll(data['reservations']);
        } else {
          reservations.clear();
        }
      }
    } catch (e) {
      print('Error fetching reservations: $e');
    } finally {
      isLoading(false);
    }
  }

  void search(String query) {
    searchText.value = query;
    fetchReservations(query: query);
  }
}
