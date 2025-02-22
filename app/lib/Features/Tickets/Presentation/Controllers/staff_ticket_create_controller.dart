import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class StaffTicketCreateController extends GetxController {
  TextEditingController subjectController = TextEditingController();
  TextEditingController messageController = TextEditingController();

  var studentList = [].obs;
  var isLoadingStudents = true.obs;
  var selectedStudentId = 0.obs; // ID دانشجوی انتخاب‌شده
  int staffId = 0;

  void routeMainPage() {
    Get.toNamed(AppRoutes.staffmainpage);
  }

  @override
  void onInit() {
    super.onInit();
    loadStaffId();
    fetchStudentList();
  }

  // دریافت `staff_id` از `shared_preferences`
  Future<void> loadStaffId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    staffId = prefs.getInt('staff_id') ?? 0;
  }

  // دریافت لیست دانشجویان از سرور
  Future<void> fetchStudentList() async {
    isLoadingStudents.value = true;
    try {
      var response = await http.get(
        Uri.parse("http://127.0.0.1/cafeteria_api/get_student_list.php"),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        studentList.value =
            List<Map<String, dynamic>>.from(data['students']).map((student) {
          return {
            "id": int.parse(student['id'].toString()), // تبدیل به int
            "name": student['name'],
          };
        }).toList();
      } else {
        Get.snackbar("خطا", "مشکلی در دریافت لیست دانشجویان وجود دارد");
      }
    } catch (e) {
      Get.snackbar("خطا", "اتصال به سرور برقرار نشد");
    } finally {
      isLoadingStudents.value = false;
    }
  }

  // ایجاد تیکت جدید
  Future<void> createTicket() async {
    if (selectedStudentId.value == 0) {
      Get.snackbar("خطا", "لطفاً یک دانشجو را انتخاب کنید");
      return;
    }
    if (subjectController.text.isEmpty || messageController.text.isEmpty) {
      Get.snackbar("خطا", "لطفاً موضوع و پیام را وارد کنید");
      return;
    }

    try {
      var response = await http.post(
        Uri.parse("http://127.0.0.1/cafeteria_api/create_staff_ticket.php"),
        body: json.encode({
          "staff_id": staffId,
          "student_id": selectedStudentId.value, // ارسال ID دانشجوی انتخاب‌شده
          "subject": subjectController.text,
          "message": messageController.text,
        }),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        Get.snackbar("موفقیت", "تیکت شما با موفقیت ثبت شد");
        routeMainPage(); // صفحه لیست تیکت‌ها
      } else {
        Get.snackbar("خطا", "مشکلی در ثبت تیکت وجود دارد: ${data['message']}");
      }
    } catch (e) {
      Get.snackbar("خطا", "اتصال به سرور برقرار نشد");
    }
  }
}
