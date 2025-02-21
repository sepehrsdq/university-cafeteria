import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class TicketCreateController extends GetxController {
  TextEditingController subjectController = TextEditingController();
  TextEditingController messageController = TextEditingController();

  var staffList = [].obs;
  var isLoadingStaff = true.obs;
  var selectedStaffId = 0.obs; // ID کارمند انتخاب‌شده
  int studentId = 0;
  void routeMainPage() {
    Get.toNamed(AppRoutes.mainpage);
  }

  @override
  void onInit() {
    super.onInit();
    loadStudentId();
    fetchStaffList();
  }

  // دریافت `student_id` از `shared_preferences`
  Future<void> loadStudentId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    studentId = prefs.getInt('student_id') ?? 0;
  }

  // دریافت لیست کارمندان از سرور
  Future<void> fetchStaffList() async {
    isLoadingStaff.value = true;
    try {
      var response = await http.get(
        Uri.parse("http://127.0.0.1/cafeteria_api/get_staff_list.php"),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        staffList.value =
            List<Map<String, dynamic>>.from(data['staff']).map((staff) {
          return {
            "id": int.parse(staff['id'].toString()), // تبدیل به int
            "name": staff['name'],
          };
        }).toList();
      } else {
        Get.snackbar("خطا", "مشکلی در دریافت لیست کارمندان وجود دارد");
      }
    } catch (e) {
      Get.snackbar("خطا", "اتصال به سرور برقرار نشد");
    } finally {
      isLoadingStaff.value = false;
    }
  }

  // ایجاد تیکت جدید
  Future<void> createTicket() async {
    if (selectedStaffId.value == 0) {
      Get.snackbar("خطا", "لطفاً یک کارمند را انتخاب کنید");
      return;
    }
    if (subjectController.text.isEmpty || messageController.text.isEmpty) {
      Get.snackbar("خطا", "لطفاً موضوع و پیام را وارد کنید");
      return;
    }

    try {
      var response = await http.post(
        Uri.parse("http://127.0.0.1/cafeteria_api/create_ticket.php"),
        body: json.encode({
          "student_id": studentId,
          "staff_id": selectedStaffId.value, // ارسال ID کارمند انتخاب‌شده
          "subject": subjectController.text,
          "message": messageController.text,
        }),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        Get.snackbar("موفقیت", "تیکت شما با موفقیت ثبت شد");
        routeMainPage(); // صفحه لیست تیکت‌ها // صفحه لیست تیکت‌ها
      } else {
        Get.snackbar("خطا", "مشکلی در ثبت تیکت وجود دارد: ${data['message']}");
      }
    } catch (e) {
      Get.snackbar("خطا", "اتصال به سرور برقرار نشد");
    }
  }
}
