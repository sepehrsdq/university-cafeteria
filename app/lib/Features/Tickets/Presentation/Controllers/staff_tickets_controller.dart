import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class StaffTicketsController extends GetxController {
  var tickets = [].obs;
  var isLoading = false.obs;
  int? staffId;

  @override
  void onInit() {
    super.onInit();
    loadStaffId();
  }

  Future<void> loadStaffId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    staffId = prefs.getInt('staff_id');
    if (staffId != null) {
      fetchTickets();
    } else {
      Get.snackbar("خطا", "شناسه کارمند یافت نشد");
    }
  }

  Future<void> fetchTickets() async {
    if (staffId == null) return;

    isLoading.value = true;
    try {
      var response = await http.get(
        Uri.parse(
            "http://127.0.0.1/cafeteria_api/get_tickets.php?user_id=$staffId&role=staff"),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        // تبدیل وضعیت تیکت‌ها به فارسی
        tickets.value = data['tickets'].map((ticket) {
          ticket['status_fa'] = (ticket['status'] == 'open') ? "باز" : "بسته";
          return ticket;
        }).toList();
      } else {
        Get.snackbar("خطا", "مشکلی در دریافت تیکت‌ها وجود دارد");
      }
    } catch (e) {
      Get.snackbar("خطا", "اتصال به سرور برقرار نشد");
    } finally {
      isLoading.value = false;
    }
  }
}
